<?php
require 'PasswordHash.php';

class UserException extends Exception { }

class UserError extends Exception { }

/** Roll back an open DAL transaction; log and ignore if rollback fails. */
function registry_rollback_dal($dal) {
  try {
    $dal->rollback();
  } catch (Throwable $e) {
    error_log('Registry rollback failed: ' . $e->getMessage());
  }
}

/** Log an unexpected error and return a message safe to show in the UI. */
function registry_user_error_from_exception(Throwable $e) {
  error_log('Registry error [' . get_class($e) . ']: ' . $e->getMessage());
  if (defined('DEPLOYMENT') && DEPLOYMENT === 'test') {
    return 'An error occurred: ' . $e->getMessage();
  }
  if ($e instanceof PDOException) {
    return 'A database error occurred. Your changes could not be saved. Please try again later or contact registry@openlcb.org for help.';
  }
  return 'An unexpected error occurred. Please try again later or contact registry@openlcb.org for help.';
}

/**
 * Compare two database IDs by value.
 *
 * PDO may return integer columns as int or string depending on the driver and
 * emulation settings, so identity checks must not rely on `===`.
 */
function registry_same_id($a, $b) {
  return $a !== null && $b !== null && (string) $a === (string) $b;
}

/**
 * File-based rate limiter.
 *
 * Returns true when the request is allowed, false when the limit is exceeded.
 * Fails open when the counter cannot be written, so a hosting problem can never
 * lock users out of the registry.
 */
function registry_rate_limit($key, $max_attempts = 20, $window_seconds = 900) {
  $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'openlcb-registry-rate';
  if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
    error_log('Registry rate limiter: cannot create ' . $dir);
    return true;
  }
  if (is_link($dir)) {
    error_log('Registry rate limiter: refusing symlinked storage ' . $dir);
    return true;
  }

  $file = $dir . DIRECTORY_SEPARATOR . hash('sha256', (string) $key) . '.json';
  $now = time();

  $fp = @fopen($file, 'c+');
  if ($fp === false) {
    error_log('Registry rate limiter: cannot open ' . $file);
    return true;
  }
  if (!flock($fp, LOCK_EX)) {
    fclose($fp);
    return true;
  }

  $data = json_decode((string) stream_get_contents($fp), true);
  if (!is_array($data) || !isset($data['count'], $data['reset']) || (int) $data['reset'] <= $now) {
    $data = array('count' => 0, 'reset' => $now + max(1, (int) $window_seconds));
  }

  $allowed = (int) $data['count'] < max(1, (int) $max_attempts);
  $data['count'] = (int) $data['count'] + 1;

  ftruncate($fp, 0);
  rewind($fp);
  fwrite($fp, json_encode($data));
  fflush($fp);
  flock($fp, LOCK_UN);
  fclose($fp);

  // Occasionally prune expired counters so the temp directory cannot grow
  // without bound under a distributed attack.
  if (mt_rand(1, 50) === 1) {
    registry_rate_limit_gc($dir, $window_seconds);
  }

  return $allowed;
}

/** Remove counters that are older than any active window. */
function registry_rate_limit_gc($dir, $window_seconds) {
  $cutoff = time() - max(3600, 2 * (int) $window_seconds);
  foreach ((array) @glob($dir . DIRECTORY_SEPARATOR . '*.json') as $file) {
    if (@filemtime($file) < $cutoff) @unlink($file);
  }
}

/** Clear a rate-limit counter (e.g. after a successful login). */
function registry_rate_limit_reset($key) {
  $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'openlcb-registry-rate';
  $file = $dir . DIRECTORY_SEPARATOR . hash('sha256', (string) $key) . '.json';
  if (is_file($file)) @unlink($file);
}

/** Best-effort client IP for rate limiting; validates before trusting a header. */
function registry_client_ip() {
  foreach (array('HTTP_CF_CONNECTING_IP', 'REMOTE_ADDR') as $name) {
    if (isset($_SERVER[$name]) && is_string($_SERVER[$name]) && filter_var($_SERVER[$name], FILTER_VALIDATE_IP) !== false) {
      return $_SERVER[$name];
    }
  }
  return 'unknown';
}

function formatPersonName($person) {
  return $person['person_organization'] != '' ? $person['person_organization'] : $person['person_first_name'] . ' ' . $person['person_last_name'];
}

function formatPersonEmail($person) {
  return formatPersonName($person) . ' <' . $person['person_email'] . '>';
}

/** Trim and validate an email address; returns null when it is not a usable address. */
function normalizeEmail($email) {
  $email = trim((string) $email);
  if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) return null;
  return $email;
}

function formatValueHex($value, $mask) {
  // (int) cast avoids "Passing null to parameter" deprecation on PHP 8.1+.
  return $mask == 255 ? ' *' : substr('0' . strtoupper(dechex((int) $value)), -2);
}

function formatUniqueIdHex($unique_id) {
  return
    formatValueHex($unique_id['uniqueid_byte0_value'], $unique_id['uniqueid_byte0_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte1_value'], $unique_id['uniqueid_byte1_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte2_value'], $unique_id['uniqueid_byte2_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte3_value'], $unique_id['uniqueid_byte3_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte4_value'], $unique_id['uniqueid_byte4_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte5_value'], $unique_id['uniqueid_byte5_mask']);
}

function randHex() {
  return bin2hex(random_bytes(16));
}

function ensure_csrf_token() {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
}

function csrf_verify() {
  if (session_status() === PHP_SESSION_NONE) {
    return false;
  }
  if (!isset($_POST['csrf_token'], $_SESSION['csrf_token'])) {
    return false;
  }
  return hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
}

function csrf_field() {
  ensure_csrf_token();
  return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"/>';
}

/** Base URL for links in outbound email (set REGISTRY_CANONICAL_HOST in config.local.php to avoid Host-header issues). */
function registry_public_base_url() {
  $host = defined('REGISTRY_CANONICAL_HOST') ? (string) REGISTRY_CANONICAL_HOST : '';
  if ($host === '' && isset($_SERVER['HTTP_HOST'])) {
    $candidate = (string) $_SERVER['HTTP_HOST'];
    // Only accept a plain hostname with an optional port. This keeps links working
    // behind Cloudflare while rejecting a crafted Host header (e.g. "evil.example/x").
    if (preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9.\-]*[A-Za-z0-9])?(?::[0-9]{1,5})?$/', $candidate)) {
      $host = $candidate;
    }
  }
  if ($host === '') $host = 'localhost';
  return 'https://' . $host;
}

function verifyPersonPassword($person, $password) {
  // No password set (e.g. accounts created via the range-request form) never matches.
  $stored_hash = isset($person['person_password_hash']) ? (string) $person['person_password_hash'] : '';
  if ($stored_hash === '') return false;

  // Base-2 logarithm of the iteration count used for password stretching.
  // 10 is the PHP password_hash() default; phpass hashes embed their own cost,
  // so existing cost-8 hashes still verify.
  $hash_cost_log2 = 10;
  // Do we require the hashes to be portable to older systems (less secure)?
  $hash_portable = FALSE;
  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $result = $hasher->CheckPassword((string) $password, $stored_hash);
  unset($hasher);
  return $result;
}

function setPersonPassword(&$person, $password) {
  //$person['person_password_hash'] = password_hash($password, PASSWORD_DEFAULT);

  // Base-2 logarithm of the iteration count used for password stretching.
  // 10 is the PHP password_hash() default; phpass hashes embed their own cost,
  // so existing cost-8 hashes still verify.
  $hash_cost_log2 = 10;
  // Do we require the hashes to be portable to older systems (less secure)?
  $hash_portable = FALSE;
  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $hash = $hasher->HashPassword($password);
  if (strlen($hash) < 20)
    throw new UserError('Failed to hash new password.');
  unset($hasher);
  $person['person_password_hash'] = $hash;
}
