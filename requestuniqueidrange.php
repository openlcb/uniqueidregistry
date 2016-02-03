<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once('access.php');
require_once('dal.php');
require_once('utils.php');
require_once('email.php');

$error = null;
$message = null;
$user = null;

$dal = new DAL($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);
try {
  $dal->beginTransaction();
  
  $user = $dal->selectUser();

  if (isset($_POST['send_request'])) {
    if ($user === null) {
      if (!isset($_POST['g-recaptcha-response']) || !$_POST['g-recaptcha-response']) throw new UserException('Robots not allowed.');
      $recaptcha = new \ReCaptcha\ReCaptcha(RECAPTCHA_SECRET);
      $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
      if (!$resp->isSuccess()) throw new UserException('reCAPTCHA error');

      if (!$_POST['email']) throw new UserException('Email address not entered.');

      if ($_POST['email'] !== $_POST['repeat_email']) throw new UserException('The entered email addresses do not match.');
      if ($dal->selectPersonByEmail($_POST['email']) !== null) throw new UserException('The entered email address is already in use. Please login before requesting an unique id range.');
    
      $person = array(
        'person_first_name' => $_POST['first_name'],
        'person_last_name' => $_POST['last_name'],
        'person_organization' => $_POST['organization'],
        'person_email' => $_POST['email'],
        'person_subscribe' => isset($_POST['subscribe']) ? 'y' : 'n',
        'person_email_verified' => 'n',
        'person_email_shared_secret' => null,
        'person_password_hash' => null
      );
    
      $dal->insertPerson($person);
    } else {
      if ($user['person_unapproved_uniqueid_count'] > 0) throw new UserException('A previous unique id range request is still pending approval.');

      $person = $user;
    }

    $unique_id = array(
      'person_id' => $person['person_id'],
      'uniqueid_url' => $_POST['url'],
      'uniqueid_user_comment' => $_POST['comment']
    );

    $dal->insertUniqueId($unique_id);
    
    $subject = "OpenLCB Unique ID Range Requested";
    $body = "Hi " . formatPersonName($person) . ",

You were assigned an OpenLCB unique ID range of:
" . formatUniqueIdHex($unique_id) . "

Delegating organization or person: " . formatPersonName($person) . "
URL: " . $unique_id['uniqueid_url'] . "
Comment: " . $unique_id['uniqueid_user_comment'] . "

The OpenLCB Group";
    if (!mail_abstraction(array( formatPersonEmail($person) ), $subject, $body)) throw new UserError('Failed to send email.');

    $body = "A new OpenLCB unique ID range has been assigned.
You have been notified as you are a moderator.

" . formatUniqueIdHex($unique_id) . "

Delegating organization or person: " . formatPersonName($person) . "
URL: " . $unique_id['uniqueid_url'] . "
Comment: " . $unique_id['uniqueid_user_comment'] . "

UID: " . 'http://' . $_SERVER['HTTP_HOST'] . '/uniqueidrange?uniqueid_id=' . $unique_id['uniqueid_id'] . "
All pending UIDs: " . "http://" . $_SERVER['HTTP_HOST'] . '/uniqueidranges?pending';
    if (!mail_abstraction(array_map('formatPersonEmail', $dal->selectModerators()), $subject, $body, array( EMAIL_FROM ))) throw new UserError('Failed to send email.');    
    
    $message = 'Your assigned range is: ' . formatUniqueIdHex($unique_id);
  } else {
    if ($user !== null && $user['person_unapproved_uniqueid_count'] > 0) $message = 'A previous unique id range request is still pending approval.';
  }

  $dal->commit();
} catch (UserException $e) {
  $dal->rollback();
  
  $error = $e->getMessage();
} catch (Exception $e) {
  $dal->rollback();

  throw $e;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="OpenLCB ID Registry"/>
    <link rel="icon" href="../../favicon.ico"/>

    <title>Request OpenLCB Unique ID Range</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>

    <!-- Custom styles for this template -->
    <link href="theme.css" rel="stylesheet"/>

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  </head>

  <body>
<?php
include('navbar.php');
?>
    <div class="container-fluid form-request">
      <h2 class="form-login-heading">Request OpenLCB Unique ID Range</h2>
      <div class="alert alert-info" role="alert">
        This page allows you to request a range of 256 OpenLCB Unique IDs for your own use.
        <p>
          For more information on OpenLCB, please see the <a href="http://openlcb.sourceforge.net/trunk/documents/index.html">documentation page</a>.
          For more information on OpenLCB unique ID assignment, please see the current
          <a href="http://openlcb.sourceforge.net/trunk/specs/UniqueIdentifiersS.pdf">specification</a> and 
          <a href="http://openlcb.sourceforge.net/trunk/specs/UniqueIdentifiersTN.pdf">technical note</a>.
        <p>
        <ul>
          <li>
            You must provide a personal name. You may also provide a company name.
            In our <a href="uniqueidranges">listing of assigned ranges</a>, we will publish the company name, if provided.
            If there is no company name, we will publish the personal name.
          </li>
          <li>
            We will not publish your email address.
          </li>
          <li>
            You may provide a URL, which we will publish if provided.
          </li>
          <li>
            You may provide a comment, which we will publish if provided.
            You can use this for company contact information, for example, including an email address.
          </li>
          <li>
            If you check the &quot;Add to OpenLCB email list&quot; box, we will add your email address to a
            <a href="https://sourceforge.net/p/openlcb/mailman/openlcb-announcements/">mailing list</a>
            for occasional updates regarding OpenLCB standards &amp; documentation, policy changes, etc.
            We strongly recommend that you subscribe so that you'll hear about these things in a timely manner.  
            The traffic on that list will be low, generally less than one email a month.
          </li>
        </ul>
        Unique ID Range requests require moderator approval.
      </div>
<?php
if ($error !== null) {
?>
      <div class="alert alert-danger">
        <a href="" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($error); ?>
      </div>
<?php
} else if ($message !== null) {
?>
      <div class="alert alert-info">
        <a href="" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($message); ?>
      </div>
<?php
} else {
?>
      <form method="POST">
        <div class="form-group">
<?php
  if ($user === null) {
?>
          <label for="inputFirstName" class="sr-only">First name</label>
          <input type="text" name="first_name" id="inputFirstName" class="form-control input-sm" placeholder="First name" required autofocus/>
          <label for="inputLastName" class="sr-only">Last name</label>
          <input type="text" name="last_name" id="inputLastName" class="form-control input-sm" placeholder="Last name" required/>
          <label for="inputOrganization" class="sr-only">Organization</label>
          <input type="text" name="organization" id="inputOrganization" class="form-control input-sm" placeholder="Organization"/>
          <label for="inputEmail" class="sr-only">Email address</label>
          <input type="email" name="email" id="inputEmail" class="form-control input-sm" placeholder="Email address" required/>
          <label for="inputRepeatEmail" class="sr-only">Repeat email address</label>
          <input type="email" name="repeat_email" id="inputRepeatEmail" class="form-control input-sm" placeholder="Repeat email address" required/>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="subscribe"> Add to OpenLCB email list
            </label>
          </div>
          <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>"></div>
<?php
  }
?>
        </div>
        <div class="form-group">
          <label for="inputUrl" class="sr-only">URL</label>
          <input type="text" id="inputUrl" name="url" class="form-control input-sm" placeholder="URL"/>
        </div>
        <div class="form-group">
          <label for="imputComment" class="sr-only">Comments</label>
          <textarea name="comment" id="imputComment" class="form-control input-sm" placeholder="Comments" oninput="this.rows = 2; this.style.height = '0'; this.style.height = this.scrollHeight + 2 + 18 + 'px';"></textarea>
        </div>
        <button type="submit" name="send_request" class="btn btn-sm btn-primary btn-block"><span class="glyphicon glyphicon-send"></span> Send request</button>
      </form>
<?php
}
?>
    </div>
  </body>
</html>
