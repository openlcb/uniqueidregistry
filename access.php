<?php

// provide database access constants

// Keep session data long enough for "remember me" (366 days). Otherwise the server
// garbage-collects the session file after session.gc_maxlifetime (default ~24 min)
// and the user appears logged out even though the cookie is still valid.
define('SESSION_REMEMBER_LIFETIME', 60 * 60 * 24 * 366);
ini_set('session.gc_maxlifetime', (string) SESSION_REMEMBER_LIFETIME);
// Reject attacker-supplied session IDs that were never issued by this server.
ini_set('session.use_strict_mode', '1');

// Conservative security headers. No Content-Security-Policy: the pages rely on
// the Bootstrap CDN, Cloudflare Turnstile and a few inline handlers.
if (!headers_sent()) {
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: SAMEORIGIN');
  header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Session cookie: must run before any session_start() (e.g. in DAL).
// When behind Cloudflare over HTTPS, the origin often sees HTTP; trust X-Forwarded-Proto so the cookie gets Secure and is sent on subsequent requests.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
  || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
if (PHP_VERSION_ID >= 70300) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
} else {
  session_set_cookie_params(0, '/', '', $isHttps, true);
}

// Defaults; override in config.local.php (gitignored) to keep secrets out of Git
$opts = ['hn' => '', 'un' => '', 'pw' => '', 'db' => ''];
if (file_exists(__DIR__ . '/config.local.php')) {
  require_once __DIR__ . '/config.local.php';
}
$opts['hn'] = isset($opts['hn']) ? $opts['hn'] : '';
$opts['un'] = isset($opts['un']) ? $opts['un'] : '';
$opts['pw'] = isset($opts['pw']) ? $opts['pw'] : '';
$opts['db'] = isset($opts['db']) ? $opts['db'] : '';

// Cloudflare Turnstile – set in config.local.php to keep out of Git
if (!defined('TURNSTILE_SITE_KEY'))   define('TURNSTILE_SITE_KEY', '');
if (!defined('TURNSTILE_SECRET_KEY')) define('TURNSTILE_SECRET_KEY', '');

define('EMAIL_FROM', '"OpenLCB Registry" <registry@openlcb.org>');

// Optional: canonical hostname for links in email (e.g. openlcb.org). Override in config.local.php.
if (!defined('REGISTRY_CANONICAL_HOST')) {
  define('REGISTRY_CANONICAL_HOST', '');
}
