<?php
require_once('access.php');
require_once('utils.php');

// Logout changes state, so it must be a POST carrying a valid CSRF token.
// A plain link (or a cross-site request) must not be able to end the session.
ensure_csrf_token();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
  $_SESSION = array();

  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }

  session_destroy();
}

header('Location: .');
exit;
