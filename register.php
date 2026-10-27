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
  ensure_csrf_token();
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    throw new UserException('Invalid session. Please try again.');
  }

  if (isset($_GET['verify'])) {
    if (!$dal->loginWithEmailSharedSecret($_GET['person_id'] ?? null, $_GET['person_email_shared_secret'] ?? null)) throw new UserException('Login failed.');
  }

  $user = $dal->selectUser();
  
  if (isset($_POST['send_verification_email'])) {
    if (!isset($_POST['cf-turnstile-response']) || !$_POST['cf-turnstile-response']) throw new UserException('Robots not allowed.');

	function validateTurnstile($token, $secret, $remoteip = null) {
		$url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
	
		$data = [
			'secret' => $secret,
			'response' => $token
		];
	
		if ($remoteip) {
			$data['remoteip'] = $remoteip;
		}
	
		$options = [
			'http' => [
				'header' => "Content-type: application/x-www-form-urlencoded\r\n",
				'method' => 'POST',
				'content' => http_build_query($data),
				// Never let a slow/unreachable Cloudflare endpoint stall the request.
				'timeout' => 10
			]
		];
	
		$context = stream_context_create($options);
		// Suppress the E_WARNING on network failure; we handle the false return below.
		$response = @file_get_contents($url, false, $context);
	
		if ($response === FALSE) {
			return ['success' => false, 'error-codes' => ['internal-error']];
		}
	
		return json_decode($response, true);
	
	}
	
	// Usage
	$secret_key = TURNSTILE_SECRET_KEY;
	$token = $_POST['cf-turnstile-response'] ?? '';
	
	$validation = validateTurnstile($token, $secret_key);
	
	if (!is_array($validation) || empty($validation['success'])) {
		$codes = (is_array($validation) && isset($validation['error-codes']) && is_array($validation['error-codes']))
			? implode(', ', $validation['error-codes'])
			: 'unknown';
		error_log('Turnstile validation failed: ' . $codes);
		throw new UserException("I do not think you are a Human.");
	}

    $email = normalizeEmail($_POST['email'] ?? '');
    if ($email === null) throw new UserException('Please enter a valid email address.');
    if (strlen($_POST['password'] ?? '') < 8) throw new UserException('Password must be at least 8 characters long.');

    if ($email !== normalizeEmail($_POST['repeat_email'] ?? '')) throw new UserException('The entered email addresses do not match.');
    if (($_POST['password'] ?? '') !== ($_POST['repeat_password'] ?? '')) throw new UserException('The entered passwords do not match.');
    if ($dal->selectPersonByEmail($email) !== null) throw new UserException('The entered email address is already in use.');

    $person = array(
      'person_first_name' => $_POST['first_name'] ?? null,
      'person_last_name' => $_POST['last_name'] ?? null,
      'person_organization' => $_POST['organization'] ?? null,
      'person_email' => $email,
      'person_subscribe' => isset($_POST['subscribe']) ? 'y' : 'n',
      'person_email_verified' => 'n',
      'person_email_shared_secret' => randHex()
    );
    
    setPersonPassword($person, (string) ($_POST['password'] ?? ''));

    $dal->insertPerson($person);
    
    $url = registry_public_base_url() . "/register?person_id=" . $person['person_id'] . "&person_email_shared_secret=" . $person['person_email_shared_secret'] . '&verify';
    $name = formatPersonName($person);
    $email = formatPersonEmail($person);
    $subject = "Register as OpenLCB User";
    $body = "Hi $name,

You can verify your email address with the link below.
$url

The OpenLCB Group";
    if (!mail_abstraction(array( $email ), $subject, $body)) throw new UserError('Failed to send email.');
    
    $message = 'Registered and verification email sent.';
  } else if (isset($_GET['verify'])) { 
    $user['person_email_verified'] = 'y';
    
    $dal->updatePerson($user);
  
    $message = 'Email address verified.';
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

    <title>Register as OpenLCB User</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>

    <!-- Custom styles for this template -->
    <link href="theme.css" rel="stylesheet"/>
	<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
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
        <a href="register" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($error); ?>
      </div>
<?php
} else if ($message !== null) {
?>
      <div class="alert alert-info">
        <a href="register" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($message); ?>
      </div>
<?php
} else {
?>
      <form method="POST">
        <?php echo csrf_field(); ?>
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
          <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars(TURNSTILE_SITE_KEY); ?>"></div>
        </div>
        <button type="submit" name="send_verification_email" class="btn btn-sm btn-primary btn-block"><span class="glyphicon glyphicon-send"></span> Register and send verification email</button>
      </form>
<?php
}
?>
    </div>
  </body>
</html>
