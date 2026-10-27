<?php
/**
 * Shared helpers for the XML/JSON range feeds.
 *
 * Kept inside the website directory so the deployed site is self-contained
 * (no includes from outside the document root).
 */

function h($s) {
  return htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Connect to the registry database.
 *
 * Returns null on failure so callers can emit a valid empty XML/JSON document
 * instead of a fatal error.
 *
 * @param array{hn:string,un:string,pw:string,db:string} $opts
 * @return mysqli|null
 */
function legacy_db_connect($opts) {
  // PHP 8.1+ makes mysqli throw exceptions by default. These feeds rely on the
  // older behaviour (return false / connect_error), so switch it back off.
  mysqli_report(MYSQLI_REPORT_OFF);

  try {
    $mysqli = @new mysqli($opts['hn'], $opts['un'], $opts['pw'], $opts['db']);
  } catch (Throwable $e) {
    error_log('Registry DB connection failed: ' . $e->getMessage());
    return null;
  }

  if ($mysqli->connect_error) {
    error_log('Registry DB connection failed: ' . $mysqli->connect_error);
    return null;
  }
  $mysqli->set_charset('utf8mb4');
  return $mysqli;
}
