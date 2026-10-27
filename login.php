<?php
require_once('access.php');
require_once('dal.php');
require_once('utils.php');
require_once('email.php');

$error = null;
$user = null;

$dal = new DAL($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);
try {
  $dal->beginTransaction();
  ensure_csrf_token();
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    throw new UserException('Invalid session. Please try again.');
  }

  $user = $dal->selectUser();
  
  if (isset($_POST['login'])) {
    $submitted_email = $_POST['email'] ?? '';
    $login_email = is_string($submitted_email) ? $submitted_email : '';

    // Throttle brute-force attempts per client IP and per account. Successful
    // logins clear both counters.
    $ip_key = 'login-ip:' . registry_client_ip();
    $email_key = 'login-email:' . strtolower(trim($login_email));
    if (!registry_rate_limit($ip_key, 20, 900) || !registry_rate_limit($email_key, 10, 900)) {
      throw new UserException('Too many login attempts. Please wait 15 minutes and try again.');
    }

    if (!$dal->login($login_email, $_POST['password'] ?? '', isset($_POST['remember']))) throw new UserException("Login failed.");

    registry_rate_limit_reset($ip_key);
    registry_rate_limit_reset($email_key);

    header('Location: .');
    exit;    
  } else if ($user !== null) {
    header('Location: .');
    exit;
  }

  $dal->commit();
} catch (UserException $e) {
  registry_rollback_dal($dal);
  $error = $e->getMessage();
} catch (UserError $e) {
  registry_rollback_dal($dal);
  $error = $e->getMessage();
} catch (Throwable $e) {
  registry_rollback_dal($dal);
  $error = registry_user_error_from_exception($e);
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

    <title>Login as OpenLCB User</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>

    <!-- Custom styles for this template -->
    <link href="theme.css" rel="stylesheet">
  </head>

  <body>
<?php
include('navbar.php');
?>
    <div class="container-fluid form-login">
      <h2 class="form-login-heading">Login</h2>
<?php
if ($error !== null) {
?>
      <div class="alert alert-danger">
        <a href="" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($error); ?>
      </div>
<?php
} else {
?>
      <div class="alert alert-info" role="alert">
        Hello.
      </div>
<?php
}
?>
      <form method="POST">
        <?php echo csrf_field(); ?>
        <div class="form-group">
          <label for="inputEmail" class="sr-only">Email address</label>
          <input type="email" id="inputEmail" class="form-control input-sm" name="email" placeholder="Email address" required autofocus/>
          <label for="inputPassword" class="sr-only">Password</label>
          <input type="password" id="inputPassword" class="form-control input-sm" name="password" placeholder="Password" required/>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="remember"/> Remember me
            </label>
          </div>
        </div>
        <button type="submit" name="login" class="btn btn-sm btn-primary btn-block"><span class="glyphicon glyphicon-log-in"></span> Login</button>
      </form>
    </div>
  </body>
</html>
