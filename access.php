<?php

// provide database access constants

// Session cookie: must run before any session_start() (e.g. in DAL).
// When behind Cloudflare over HTTPS, the origin often sees HTTP; trust X-Forwarded-Proto so the cookie gets Secure and is sent on subsequent requests.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
  || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
session_set_cookie_params(0, '/', '', $isHttps, true);

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
