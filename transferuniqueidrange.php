<?php
require_once('access.php');
require_once('dal.php');
require_once('utils.php');
require_once('email.php');

$error = null;
$message = null;
$user = null;
$unique_id = null;

$dal = new DAL($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);
try {
  $dal->beginTransaction();
  
  $user = $dal->selectUser();
  if ($user === null) throw new UserException('Login required.');
  
  $unique_id = $dal->selectUniqueId($_GET['uniqueid_id']);
  if ($unique_id === null) throw new UserException('Range not found.');

  if ($user['person_id'] !== $unique_id['person_id'] && $user['person_is_moderator'] !== 'y') throw new UserException('Moderator login required.');  

  if (isset($_POST['transfer'])) {
    $person = $dal->selectPersonByEmail($_POST['email']);
    if ($person === null) throw new UserException('Profile not found.');
    
    $unique_id['person_id'] = $person['person_id'];

    $dal->updateUniqueId($unique_id);

    $subject = "OpenLCB Unique ID Range Transferred";
    $body = "Hi " . formatPersonName($unique_id) . ",

The following OpenLCB Unique ID Range has been Transferred.

" . formatUniqueIdHex($unique_id) . "

From delegating organization or person: " . formatPersonName($unique_id) . "
To delegating organization or person: " . formatPersonName($person) . "
URL: " . $unique_id['uniqueid_url'] . "
Comment: " . $unique_id['uniqueid_user_comment'] . "

The OpenLCB Group";
    if (!mail_abstraction(array( formatPersonEmail($unique_id) ), $subject, $body)) throw new UserError('Failed to send email.');

    $subject = "OpenLCB Unique ID Range Transferred";
    $body = "Hi " . formatPersonName($person) . ",

The following OpenLCB Unique ID Range has been Transferred.

" . formatUniqueIdHex($unique_id) . "

From delegating organization or person: " . formatPersonName($unique_id) . "
To delegating organization or person: " . formatPersonName($person) . "
URL: " . $unique_id['uniqueid_url'] . "
Comment: " . $unique_id['uniqueid_user_comment'] . "

The OpenLCB Group";
    if (!mail_abstraction(array( formatPersonEmail($person) ), $subject, $body)) throw new UserError('Failed to send email.');

    $body = "The following OpenLCB Unique ID Range has been Transferred.
You have been notified as you are a moderator.

" . formatUniqueIdHex($unique_id) . "

From delegating organization or person: " . formatPersonName($unique_id) . "
To delegating organization or person: " . formatPersonName($person) . "
URL: " . $unique_id['uniqueid_url'] . "
Comment: " . $unique_id['uniqueid_user_comment'] . "

UID: " . 'https://' . $_SERVER['HTTP_HOST'] . '/uniqueidrange?uniqueid_id=' . $unique_id['uniqueid_id'] . "
All pending UIDs: " . "https://" . $_SERVER['HTTP_HOST'] . '/uniqueidranges?pending';
    if (!mail_abstraction(array_map('formatPersonEmail', $dal->selectModerators()), $subject, $body, array( EMAIL_FROM ))) throw new UserError('Failed to send email.');
    
    $message = 'Transferred.';
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

    <title>Transfer OpenLCB Unique ID Range</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>

    <!-- Custom styles for this template -->
    <link href="theme.css" rel="stylesheet"/>
  </head>

  <body>
<?php
include('navbar.php');
?>
    <div class="container-fluid form-request">
      <h2 class="form-login-heading">Transfer OpenLCB Unique ID Range</h2>
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
        <h3 style="font-family: monospace; white-space: pre;"><?php echo htmlspecialchars(formatUniqueIdHex($unique_id)); ?></h3>
        <div class="form-group">
          <label for="inputEmail" class="sr-only">Email address</label>
          <input type="email" name="email" id="inputEmail" class="form-control input-sm" placeholder="Email address" required/>
        </div>
        <button type="submit" name="transfer" class="btn btn-sm btn-primary btn-block"><span class="glyphicon glyphicon-send"></span> Transfer</button>
      </form>
<?php
}
?>
    </div>
  </body>
</html>
