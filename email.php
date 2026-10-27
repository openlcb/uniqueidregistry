<?php
require_once('access.php');

/**
 * Strip CR/LF/NUL from a value that is placed into a mail header.
 * Without this, a user-controlled display name or email address could inject
 * extra headers (e.g. Bcc:) into the message.
 */
function mail_sanitize_header($value) {
  return trim(str_replace(array("\r", "\n", "\0"), '', (string) $value));
}

function mail_abstraction($recipients, $subject, $body, $cc = array()) {
  $recipients = array_map('mail_sanitize_header', (array) $recipients);
  $cc = array_map('mail_sanitize_header', (array) $cc);
  $header = 'From: ' . mail_sanitize_header(EMAIL_FROM)
    . (empty($cc) ? '' : "\r\nCC: " . join(', ', $cc));
  return mail(join(', ', $recipients), mail_sanitize_header($subject), $body, $header);
}
