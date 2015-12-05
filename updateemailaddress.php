<?php
require_once('access.php');
require_once('dao.php');
require_once('utils.php');
require_once('email.php');

$error = null;
$message = null;
$user = null;

$dao = new DAO($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);
try {
  $dao->beginTransaction();
    
  if (isset($_GET['verify'])) {
    if (!$dao->loginWithEmailSharedSecret($_GET['person_id'], $_GET['person_email_shared_secret'])) throw new UserException('Login failed.');
  }

  $user = $dao->selectUser();
  if ($user === null) throw new UserException('Login required.');
  
  if (isset($_POST['send_verification_email'])) {
    if ($_POST['new_email'] !== $_POST['repeat_new_email']) throw new UserException('The entered email addresses do not match.');
    if ($user['person_email'] === $_POST['new_email']) throw new UserException('The entered email addresses is the same as your current email address.');
    if ($dao->selectPersonByEmail($_POST['new_email']) !== null) throw new UserException('The entered email address is already in use.');

    $user['person_email_shared_secret'] = randHex();
    $user['person_email'] = $_POST['new_email'];
    $user['person_email_verified'] = 'n';
    
    $dao->updatePerson($user);
    
    $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?person_id=" . $user['person_id'] . "&person_email_shared_secret=" . $user['person_email_shared_secret'] . '&verify';
    $name = formatPersonName($user);
    $email = formatPersonEmail($user);
    $subject = "Update OpenLCB User Email Address";
    $body = "Hi $name,

You can verify your email address with the link below.
$url

The OpenLCB Group";
    if (!sourceforge_email(array( $email ), $subject, $body)) throw new UserError('Failed to send email.');
    
    $message = 'Email address updated and verification email sent.';
  } else if (isset($_GET['verify'])) {
    $user['person_email_verified'] = 'y';
  
    $dao->updatePerson($user);

    $message = 'Email address verified.';
  }

  $dao->commit();
} catch (UserException $e) {
  $dao->rollback();
  
  $error = $e->getMessage();
} catch (Exception $e) {
  $dao->rollback();

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

    <title>Update OpenLCB User Email Address</title>

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
      <h2 class="form-login-heading">Update email address</h2>
<?php
if ($user !== null) {
?>
      <h3><?php echo htmlspecialchars(formatPersonName($user)); ?></h3>
<?php
}
?>
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
        <a href="<?php echo htmlspecialchars("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']); ?>" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($message); ?>
      </div>
<?php
} else {
?>
      <form method="POST">
        <div class="form-group">
          <label for="inputEmail" class="sr-only">New email address</label>
          <input type="email" name="new_email" id="inputEmail" class="form-control input-sm" placeholder="New email address" required autofocus/>
          <label for="inputEmail" class="sr-only">Repeat new email address</label>
          <input type="email" name="repeat_new_email" id="inputEmail" class="form-control input-sm" placeholder="Repeat new email address" required/>
        </div>
        <button type="submit" name="send_verification_email" class="btn btn-sm btn-primary btn-block"><span class="glyphicon glyphicon-send"></span> Update and send verification email</button>
      </form>
<?php
}
?>
    </div>
  </body>
</html>
