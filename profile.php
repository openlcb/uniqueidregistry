<?php
require_once('access.php');
require_once('dao.php');
require_once('utils.php');
require_once('email.php');

$error = null;
$message = null;
$user = null;
$person = null;

$dao = new DAO($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);
try {
  $dao->beginTransaction();
  
  if (isset($_GET['verify'])) {
    if (!$dao->loginWithEmailSharedSecret($_GET['person_id'], $_GET['person_email_shared_secret'])) throw new UserException('Login failed.');
  }
  
  $user = $dao->selectUser();
  if ($user === null) throw new UserException('Login required.');
  
  if (isset($_GET['person_id'])) {
    $person = $dao->selectPersonById($_GET['person_id']);
    if ($person === null) throw new UserException('Profile not found.');
  } else {
    $person = $user;
  }
  
  if ($user['person_id'] !== $person['person_id'] && $user['person_is_moderator'] !== 'y') throw new UserException('Moderator login required.');

  if (isset($_POST['save'])) {
    $person['person_first_name']   = $_POST['person_first_name'];
    $person['person_last_name']    = $_POST['person_last_name'];
    $person['person_organization'] = $_POST['person_organization'];
    if ($user['person_is_moderator'] === 'y') {
      $person['person_is_moderator'] = $_POST['person_is_moderator'];
    }

    $dao->updatePerson($person);

    $message = 'Saved.';
  } else if (isset($_POST['subscribe'])) {
    $person['person_subscribe'] = 'y';

    $dao->updatePerson($person);

    $message = 'Subscribed.';
  } else if (isset($_POST['unsubscribe'])) {
    $person['person_subscribe'] = 'n';

    $dao->updatePerson($person);

    $message = 'Unsubscribed.';
  } else if (isset($_POST['delete'])) {
    if (count($dao->selectUniqueIdsByPersonId($person['person_id'])) > 0) throw new UserException('Delete unique id ranges first.');

    $dao->deletePerson($person['person_id']);

    if ($user['person_is_moderator'] === 'y') {
      header('Location: people.php');
    } else {
      header('Location: .');
    }
    exit;
  } else if (isset($_POST['send_verification_email'])) {
    $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?person_id=" . $person['person_id'] . "&person_email_shared_secret=" . $person['person_email_shared_secret'] . '&verify';
    $name = formatPersonName($person);
    $email = formatPersonEmail($person);
    $subject = "Register as OpenLCB User";
    $body = "Hi $name,

You can verify your email address with the link below.
$url

The OpenLCB Group";
    sourceforge_email(array( $email ), $subject, $body);
 
    $message = 'Verification email sent.';
  } else if (isset($_GET['verify'])) {
    $person['person_email_verified'] = 'y';
    
    $dao->updatePerson($person);
  
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

    <title>View OpenLCB Profile</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>

    <!-- Custom styles for this template -->
    <link href="theme.css" rel="stylesheet"/>
  </head>

  <body>
<?php
include('navbar.php');
?>
    <div class="container-fluid">
      <h2>View OpenLCB Profile</h2>
<?php
if ($error !== null) {
?>
      <div class="alert alert-danger">
        <a href="profile.php<?php if ($user['person_id'] !== $person['person_id']) echo '?person_id=' . $person['person_id']; ?>" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($error); ?>
      </div>
<?php
} else if ($message !== null) {
?>
      <div class="alert alert-info">
        <a href="profile.php<?php if ($user['person_id'] !== $person['person_id']) echo '?person_id=' . $person['person_id']; ?>" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
        <?php echo htmlspecialchars($message); ?>
      </div>
<?php
} else {
?>
      <form method="POST">
        <h3><?php echo htmlspecialchars(formatPersonName($person)); ?></h3>
        <table class="table table-condensed">
          <tbody>
            <tr>
              <th>First name</th>
<?php
  if (isset($_POST['edit'])) {
?>
              <td><input name="person_first_name" class="form-control input-sm" value="<?php echo htmlspecialchars($person['person_first_name']); ?>"/></td>
<?php
  } else {
?>
              <td><?php echo htmlspecialchars($person['person_first_name']); ?></td>
<?php
  }
?>
            </tr>
            <tr>
              <th>Last name</th>
<?php
  if (isset($_POST['edit'])) {
?>
              <td><input name="person_last_name" class="form-control input-sm" value="<?php echo htmlspecialchars($person['person_last_name']); ?>"/></td>
<?php
  } else {
?>
              <td><?php echo htmlspecialchars($person['person_last_name']); ?></td>
<?php
  }
?>
            </tr>
            <tr>
              <th>Organization</th>
<?php
  if (isset($_POST['edit'])) {
?>
              <td><input name="person_organization" class="form-control input-sm" value="<?php echo htmlspecialchars($person['person_organization']); ?>"/></td>
<?php
  } else {
?>
              <td><?php echo htmlspecialchars($person['person_organization']); ?></td>
<?php
  }
?>
            </tr>
            <tr>
              <th>Email address</th>
              <td><a href="<?php echo htmlspecialchars('mailto:' . rawurlencode(formatPersonEmail($person))); ?>"><?php echo htmlspecialchars($person['person_email']); ?></a></td>
            </tr>
            <tr>
              <th>Email address verified</th>
              <td><?php echo htmlspecialchars($person['person_email_verified']); ?></td>
            </tr>
            <tr>
              <th>Subscribed</th>
              <td><?php echo htmlspecialchars($person['person_subscribe']); ?></td>
            </tr>
            <tr>
              <th>Moderator</th>
<?php
  if (isset($_POST['edit']) && $user['person_is_moderator'] === 'y') {
?>
              <td>
                <label><input type="radio" name="person_is_moderator" value="y"<?php if ($person['person_is_moderator'] === 'y') echo " checked" ?>/> y</label><br/>
                <label><input type="radio" name="person_is_moderator" value="n"<?php if ($person['person_is_moderator'] === 'n') echo " checked" ?>/> n</label>
              </td>
<?php
  } else {
?>
              <td><?php echo htmlspecialchars($person['person_is_moderator']); ?></td>
<?php
  }
?>
            </tr>
            <tr>
              <th>Created</th>
              <td><?php echo htmlspecialchars($person['person_created']); ?></td>
            </tr>
            <tr>
              <th>Updated</th>
              <td><?php echo htmlspecialchars($person['last_updated']); ?></td>
            </tr>
            <tr>
              <th>Unique ID ranges</th>
              <td><?php echo htmlspecialchars($person['person_uniqueid_count']); ?></td>
            </tr>
          </tbody>
        </table>
<?php
  if (isset($_POST['edit'])) {
?>
        <button type="submit" name="save" class="btn btn-sm btn-success"><span class="glyphicon glyphicon-floppy-saved"></span> Save</button>
        <button type="submit" name="cancel" class="btn btn-sm btn-danger"><span class="glyphicon glyphicon-floppy-remove"></span> Cancel</button>
<?php
  } else {
?>
        <a href="viewuidall.php<?php echo '?person_id=' . $person['person_id']; ?>" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-erase"></span> Show unique ID ranges</a>
        <button type="submit" name="edit" class="btn btn-sm btn-warning"><span class="glyphicon glyphicon-edit"></span> Edit</button>
<?php
    if ($user['person_id'] === $person['person_id'] || $user['person_is_moderator'] === 'y') {
?>
        <a href="updateemailaddress.php<?php if ($user['person_id'] !== $person['person_id']) echo '?person_id=' . $person['person_id']; ?>" class="btn btn-sm btn-warning"><span class="glyphicon glyphicon-erase"></span> Update email address</a>
        <a href="updatepassword.php<?php if ($user['person_id'] !== $person['person_id']) echo '?person_id=' . $person['person_id']; ?>" class="btn btn-sm btn-warning"><span class="glyphicon glyphicon-erase"></span> Update password</a>
<?php
    }
    if ($person['person_email_verified'] !== 'y') {
?>
        <button type="submit" name="send_verification_email" class="btn btn-sm btn-warning"><span class="glyphicon glyphicon-send"></span> Send verification email</button>
<?php
    }
    if ($person['person_subscribe'] !== 'y') {
?>
        <button type="submit" name="subscribe" class="btn btn-sm btn-success"><span class="glyphicon glyphicon-envelope"></span> Subscribe</button>
<?php
    } else {
?>
        <button type="submit" name="unsubscribe" class="btn btn-sm btn-danger"><span class="glyphicon glyphicon-envelope"></span> Unsubscribe</button>
<?php
    }
?>
        <button type="submit" name="delete" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this profile?');"><span class="glyphicon glyphicon-trash"></span> Delete</button>
<?php
  }
?>
      </form>
<?php
}
?>
    </div>
  </body>
</html>
