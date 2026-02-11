<?php

// provide database access constants

// Session cookie: must run before any session_start() (e.g. in DAL).
// When behind Cloudflare over HTTPS, the origin often sees HTTP; trust X-Forwarded-Proto so the cookie gets Secure and is sent on subsequent requests.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
  || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
if (PHP_VERSION_ID >= 70300) {
  $sessionCookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
  ];
  session_set_cookie_params($sessionCookieParams);
} else {
  session_set_cookie_params(0, '/', '', $isHttps, true);
}

$opts['hn'] = '';
$opts['un'] = '';
$opts['pw'] = '';  // filled in on host so not present in SVN
$opts['db'] = '';

define('RECAPTCHA_SITE_KEY', '');
define('RECAPTCHA_SECRET', '');

define('EMAIL_FROM', '"OpenLCB Registry" <registry@openlcb.org>');
