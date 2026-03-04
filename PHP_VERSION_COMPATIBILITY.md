# PHP version compatibility

## Declared minimum

- **composer.json:** `"php": ">=5.6"`

## Application code (excluding vendor)

| Feature | Minimum PHP | Used in |
|--------|-------------|---------|
| Short array `[]` | 5.4 | access.php, dal.php, register.php, requestuniqueidrange.php |
| `__DIR__` | 5.3 | register.php, requestuniqueidrange.php |
| `session_set_cookie_params(lifetime, path, domain, secure, httponly)` | 5.2 | access.php, dal.php |
| PDO | 5.1 | dal.php |
| `array()` | 4 | dal.php, utils.php, etc. |

## Current state

- **Session code** uses the 5-argument form of `session_set_cookie_params()` only (no array form, no `samesite`), compatible with PHP 5.2+.
- **No null coalescing (`??`)** – uses `isset() ? : default` for PHP 5.6.
- **Conclusion:** The application is compatible with **PHP 5.6 through 8.x**.

## Note on SameSite

PHP 5.6 cannot set the `SameSite` cookie attribute. Browsers will use their default (often `Lax`). For stricter control, use PHP 7.3+ or set the cookie via a custom `Set-Cookie` header.
