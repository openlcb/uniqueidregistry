# PHP version compatibility

## Declared minimum

- **composer.json:** `"php": ">=8.0"`
- Tested on **PHP 8.5.11**; the code targets **PHP 8.0 – 8.4**.

## Language / library features used

| Feature | Minimum PHP | Used in |
|---|---|---|
| `Throwable`, `intdiv()`, `random_bytes()` | 7.0 | `utils.php`, `PasswordHash.php`, `dal.php` |
| Null coalescing `??` | 7.0 | page request handlers |
| `session_set_cookie_params(array)` + `samesite` | 7.3 | `access.php`, `dal.php` (positional fallback for older versions) |
| `hash_equals()` | 5.6 | `dal.php`, `utils.php` |
| `filter_var(FILTER_VALIDATE_EMAIL)` | 5.2 | `utils.php` |
| PDO MySQL (`charset=utf8mb4`) | 5.1 | `dal.php` |
| mysqli | 5.0 | `legacy_common.php` (XML/JSON feeds) |

## PHP 8 compatibility notes

- `PasswordHash` uses `__construct()`. PHP 8 no longer calls PHP 4-style constructors
  (`function PasswordHash(...)`), which previously left the hasher uninitialised.
- `PasswordHash` uses `intdiv()` for the salt prefix, avoiding the PHP 8.1+
  "implicit conversion from float to int" deprecation.
- Nullable database columns are cast to `(string)` before `htmlspecialchars()`,
  avoiding the PHP 8.1+ "passing null to parameter" deprecations. This matters for
  the XML/JSON feeds, where a notice would corrupt the response.
- `legacy_common.php` calls `mysqli_report(MYSQLI_REPORT_OFF)`. PHP 8.1+ makes
  mysqli throw exceptions by default, which would bypass the `if (!$result)`
  handling in the XML/JSON feeds.
- Turnstile verification uses a 10 second timeout and suppresses network warnings.

## Session cookies

`access.php` sets `Secure` from `HTTPS`, `X-Forwarded-Proto` or `X-Forwarded-Ssl`
(so it still works behind Cloudflare), plus `HttpOnly` and `SameSite=Lax`.
`session.use_strict_mode` is enabled and login regenerates the session ID.

## Security hardening

- **Login throttling.** `login.php` limits failed logins to 20 per client IP and
  10 per email address in a 15-minute window. Counters are stored as small JSON
  files under `sys_get_temp_dir()/openlcb-registry-rate/` (mode 0700), use
  `flock()` for concurrency, and are cleared after a successful login. If the
  storage directory is not writable the limiter fails open, so a hosting problem
  cannot lock users out.
- **Logout is POST + CSRF.** `logout.php` only ends the session for a `POST`
  carrying a valid CSRF token; the navbar renders the logout control as a form.
  It also expires the session cookie and no longer touches the database.
- **Password hashing cost.** phpass now uses cost 10 (the PHP `password_hash()`
  default). Hashes embed their own cost, so existing `$2a$08$` hashes still verify.
- **Type-safe ID checks.** `registry_same_id()` compares database IDs by value,
  so ownership/moderator checks do not depend on PDO returning int vs string.
- **`.htaccess`.** Blocks direct HTTP access to `.git/`, `.idea/`, `composer.*`,
  `config.local.php`, `*.md`, and `*.sql` using `mod_rewrite` only (no
  `AllowOverride` dependency).

