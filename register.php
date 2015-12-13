<?php
require_once __DIR__ . '/vendor/autoload.php';
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
  
  if (isset($_POST['send_verification_email'])) {
    if (!isset($_POST['g-recaptcha-response']) || !$_POST['g-recaptcha-response']) throw new UserException('Robots not allowed.');
    $recaptcha = new \ReCaptcha\ReCaptcha(RECAPTCHA_SECRET);
    $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
    if (!$resp->isSuccess()) throw new UserException('reCAPTCHA error');

    if (!$_POST['email']) throw new UserException('Email address not entered.');
    if (strlen($_POST['password']) < 8) throw new UserException('Password must be at least 8 characters long.');

    if ($_POST['email'] !== $_POST['repeat_email']) throw new UserException('The entered email addresses do not match.');
    if ($_POST['password'] !== $_POST['repeat_password']) throw new UserException('The entered passwords do not match.');
    if ($dao->selectPersonByEmail($_POST['email']) !== null) throw new UserException('The entered email address is already in use.');

    $person = array(
      'person_first_name' => $_POST['first_name'],
      'person_last_name' => $_POST['last_name'],
      'person_organization' => $_POST['organization'],
      'person_email' => $_POST['email'],
      'person_subscribe' => isset($_POST['subscribe']) ? 'y' : 'n',
      'person_email_verified' => 'n',
      'person_email_shared_secret' => randHex()
    );
    
    setPersonPassword($person, $_POST['password']);

    $dao->insertPerson($person);
    
    $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?person_id=" . $person['person_id'] . "&person_email_shared_secret=" . $person['person_email_shared_secret'] . '&verify';
    $name = formatPersonName($person);
    $email = formatPersonEmail($person);
    $subject = "Register as OpenLCB User";
    $body = "Hi $name,

You can verify your email address with the link below.
$url

The OpenLCB Group";
    if (!sourceforge_email(array( $email ), $subject, $body)) throw new UserError('Failed to send email.');
    
    $message = 'Registered and verification email sent.';
  } else if (isset($_GET['verify'])) { 
    $user['person_email_verified'] = 'y';
    
    $dao->updatePerson($user);
  
    $message = 'Email address verified.';
  } else if ($user !== null) {
    header('Location: .');
    exit;
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

    <title>Register as OpenLCB User</title>

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
    <div class="container-fluid form-login">
      <h2 class="form-login-heading">Register</h2>
<?php
if ($error !== null) {
?>
      <div class="alert alert-danger">
        <a href="register.php" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($error); ?>
      </div>
<?php
} else if ($message !== null) {
?>
      <div class="alert alert-info">
        <a href="register.php" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($message); ?>
      </div>
<?php
} else {
?>
      <form method="POST">
        <div class="form-group">
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
          <label for="inputPassword" class="sr-only">Password</label>
          <input type="password" name="password" id="inputPassword" class="form-control input-sm" placeholder="Password" required/>
          <label for="inputRepearPassword" class="sr-only">Repeat password</label>
          <input type="password" name="repeat_password" id="inputRepearPassword" class="form-control input-sm" placeholder="Repeat password" required/>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="subscribe"> Add to OpenLCB email list
            </label>
          </div>
          <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>"></div>
        </div>
        <button type="submit" name="send_verification_email" class="btn btn-sm btn-primary btn-block"><span class="glyphicon glyphicon-send"></span> Register and send verification email</button>
      </form>
<?php
}
?>
    </div>
  </body>
</html>
