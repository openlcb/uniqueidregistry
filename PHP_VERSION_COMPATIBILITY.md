# PHP version compatibility

## Declared minimum

- **composer.json:** `"php": ">=7.3"`

## Application code (excluding vendor)

| Feature | Minimum PHP | Used in |
|--------|-------------|---------|
| Short array `[]` | 5.4 | access.php, dal.php, register.php, requestuniqueidrange.php |
| `__DIR__` | 5.3 | register.php, requestuniqueidrange.php |
| `session_set_cookie_params(array)` with `samesite` | 7.3 | access.php (if branch), dal.php (if branch) |
| `session_set_cookie_params(lifetime, path, domain, secure, httponly)` | 5.2 | access.php (else), dal.php (else) |
| `PHP_VERSION_ID` | 5.2.7 | access.php, dal.php |
| PDO | 5.1 | dal.php |
| `array()` | 4 | dal.php, utils.php, etc. |

## Current state (after alignment)

- **Session code** uses only the PHP 7.3+ API: `session_set_cookie_params(array)` with `samesite`, and `$p['samesite'] ?? 'Lax'` (null coalescing, PHP 7.0+).
- **composer.json** declares **PHP ≥ 7.3**.
- **Conclusion:** Declared minimum and code are aligned at **PHP 7.3**. The application is compatible with **PHP 7.3 through 8.x**.
