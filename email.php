<?php
require_once('access.php');

function mail_abstraction($recipients, $subject, $body) {
  return mail(join(', ', $recipients), $subject, $body, 'From: ' . EMAIL_FROM);
}
