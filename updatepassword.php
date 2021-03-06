<?php
require_once('access.php');
require_once('dal.php');
require_once('utils.php');
require_once('email.php');

$error = null;
$message = null;
$user = null;
$person = null;

$dal = new DAL($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);
try {
  $dal->beginTransaction();
    
  if (isset($_GET['verify'])) {
    if (!$dal->loginWithEmailSharedSecret($_GET['person_id'], $_GET['person_email_shared_secret'])) throw new UserException('Login failed.');
  }

  $user = $dal->selectUser();

  if (isset($_GET['person_id'])) {
    if ($user === null) throw new UserException('Login required.');
    $person = $dal->selectPersonById($_GET['person_id']);
    if ($person === null) throw new UserException('Profile not found.');
    if ($user['person_id'] !== $person['person_id'] && $user['person_is_moderator'] !== 'y') throw new UserException('Moderator login required.');
  } else {
    $person = $user;
  }
  
  if (isset($_POST['send_verification_email'])) {
    $person = $dal->selectPersonByEmail($_POST['email']);
    if ($person === null) throw new UserException('Profile not found.');
    
    if (!$person['person_email_shared_secret']) {
      $person['person_email_shared_secret'] = randHex();
      $dal->updatePerson($person);
    }

    $url = "http://" . $_SERVER['HTTP_HOST'] . "/updatepassword?person_id=" . $person['person_id'] . "&person_email_shared_secret=" . $person['person_email_shared_secret'] . '&verify';
    $name = formatPersonName($person);
    $email = formatPersonEmail($person);
    $subject = "Update OpenLCB User Password";
    $body = "Hi $name,

A request to update your password has been received.
You can update your password with the link below.
$url

The OpenLCB Group";
    if (!mail_abstraction(array( $email ), $subject, $body)) throw new UserError('Failed to send email.');

    $message = 'Verification email sent.';
  } else if (isset($_POST['update_password'])) {
    if ($user === null) throw new UserException('Login required.');
  
    if (strlen($_POST['password']) < 8) throw new UserException('Password must be at least 8 characters long.');
    if ($_POST['password'] !== $_POST['repeat_password']) throw new UserException('The entered passwords do not match.');
    
    setPersonPassword($person, $_POST['password']);

    $dal->updatePerson($person);
    
    $message = 'Password updated.';
  } else if (isset($_GET['verify'])) {
    $user['person_email_verified'] = 'y';
  
    $dal->updatePerson($user);
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

    <title>Update OpenLCB User Password</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>

    <!-- Custom styles for this template -->
    <link href="theme.css" rel="stylesheet"/>
  </head>

  <body>
<?php
include('navbar.php');
?>
    <div class="container-fluid form-login">
      <h2 class="form-login-heading">Update password</h2>
<?php
if ($person !== null) {
?>
      <h3><?php echo htmlspecialchars(formatPersonName($person)); ?></h3>
<?php
}
?>
<?php
if ($error !== null) {
?>
      <div class="alert alert-danger">
        <a href="updatepassword<?php if ($user !== null && $user['person_id'] !== $person['person_id']) echo '?person_id=' . $person['person_id']; ?>" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($error); ?>
      </div>
<?php
} else if ($message !== null) {
?>
      <div class="alert alert-info">
        <a href="updatepassword<?php if ($user !== null && $user['person_id'] !== $person['person_id']) echo '?person_id=' . $person['person_id']; ?>" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($message); ?>
      </div>
<?php
} else if ($person !== null) {
?>
      <form method="POST">
        <div class="form-group">
          <label for="inputPassword" class="sr-only">Password</label>
          <input type="password" name="password" id="inputPassword" class="form-control input-sm" placeholder="Password" required autofocus/>
          <label for="inputRepearPassword" class="sr-only">Repeat password</label>
          <input type="password" name="repeat_password" id="inputRepearPassword" class="form-control input-sm" placeholder="Repeat password" required/>
        </div>
        <button type="submit" name="update_password" class="btn btn-sm btn-primary btn-block"><span class="glyphicon glyphicon-floppy-disk"></span> Update</button>
      </form>
<?php
} else {
?>
      <form method="POST">
        <div class="form-group">
          <label for="inputEmail" class="sr-only">Email address</label>
          <input type="email" name="email" id="inputEmail" class="form-control input-sm" placeholder="Email address" required autofocus/>
        </div>
        <button type="submit" name="send_verification_email" class="btn btn-sm btn-primary btn-block"><span class="glyphicon glyphicon-send"></span> Send verification email</button>
      </form>
<?php
}
?>
    </div>
  </body>
</html>
