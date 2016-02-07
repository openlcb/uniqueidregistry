<?php
require_once('access.php');
require_once('dal.php');
require_once('utils.php');
require_once('email.php');

$error = null;
$user = null;
$people = null;

$dal = new DAL($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);
try {
  $dal->beginTransaction();
  
  $user = $dal->selectUser();
  if ($user === null) throw new UserException('Login required.');
  if ($user['person_is_moderator'] !== 'y') throw new UserException('Moderator login required.');
  
  $people = $dal->selectPeople();

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

    <title>View OpenLCB People</title>

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
      <h2>View OpenLCB People</h2>
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
      <div class="form-group">
        <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars('mailto:' . rawurlencode(implode(',', array_map('formatPersonEmail', array_filter($people, function ($person) { return $person['person_email'] && $person['person_is_moderator'] === 'y'; }))))); ?>">Email moderators</a>
        <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars('mailto:?bcc=' . rawurlencode(implode(',', array_map('formatPersonEmail', array_filter($people, function ($person) { return $person['person_email'] && $person['person_subscribe'] === 'y'; }))))); ?>">Email subscribers (BCC)</a>
        <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars('mailto:?bcc=' . rawurlencode(implode(',', array_map('formatPersonEmail', array_filter($people, function ($person) { return $person['person_email']; }))))); ?>">Email everyone (BCC)</a>
      </div>
      <table class="table table-condensed">
        <tbody>
          <tr>
            <th>Organization or person</th>
            <th>Email address</th>
            <th>Email address verified</th>
            <th>Subscribed</th>
            <th>Moderator</th>
            <th>Unique ID ranges</th>
          </tr>
<?php
  foreach ($people as $person) {
?>
          <tr>
            <td><a href="profile?person_id=<?php echo $person['person_id']; ?>"><?php echo htmlspecialchars(formatPersonName($person)); ?></a></td>
            <td><a href="<?php echo htmlspecialchars('mailto:' . rawurlencode(formatPersonEmail($person))); ?>"><?php echo htmlspecialchars($person['person_email']); ?></a></td>
            <td><?php echo htmlspecialchars($person['person_email_verified']); ?></td>
            <td><?php echo htmlspecialchars($person['person_subscribe']); ?></td>
            <td><?php echo htmlspecialchars($person['person_is_moderator']); ?></td>
            <td><?php echo htmlspecialchars($person['person_uniqueid_count']); ?></td>
          </tr>
<?php
  }
?>
        </tbody>
      </table>
<?php
}
?>
    </div>
  </body>
</html>
