<?php
require_once('access.php');
include('Mail.php');

// and send email notification
define('EMAIL_FROM', "openlcb@pacbell.net");

function sourceforge_email($recipients, $subject, $body) {
  global $opts;

  //error_log(join(', ', $recipients) . ' ' . $subject);
  //error_log($body);
  //return true;

  $headers = array (
    'From'    => EMAIL_FROM,
    'To'      => join(', ', $recipients),
    //'CC'      => EMAIL_FROM,
    'Subject' => $subject,
  );

  $mail_object = &Mail::factory(
    'smtp',
    array(
      'host'     => 'prwebmail',
      'auth'     => true,
      'username' => 'openlcb-demo',
      'password' => $opts['email'] //,
      //'debug'    => true, # uncomment to enable debugging
    )
  );

  return $mail_object->send($recipients, $headers, $body);
}
