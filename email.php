<?php
require_once('access.php');

function mail_abstraction($recipients, $subject, $body, $cc = array()) {
  return mail(join(', ', $recipients), $subject, $body, 'From: ' . EMAIL_FROM . (empty($cc) ? '' : "\r\nCC: " . join(', ', $cc)));
}
