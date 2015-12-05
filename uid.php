<?php
require_once('access.php');
require_once('dao.php');
require_once('utils.php');
require_once('email.php');

$error = null;
$message = null;
$user = null;
$unique_id = null;

$dao = new DAO($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);
try {
  $dao->beginTransaction();
  
  $user = $dao->selectUser();
  if ($user === null) throw new UserException('Login required.');
  if ($user['person_is_moderator'] !== 'y') throw new UserException('Moderator login required.');
  
  $unique_id = $dao->selectUniqueId($_GET['uniqueid_id']);
  if ($unique_id === null) throw new UserException('Range not found.');

  if (isset($_POST['save'])) {
    $unique_id['uniqueid_url']          = $_POST['uniqueid_url'];
    $unique_id['uniqueid_user_comment'] = $_POST['uniqueid_user_comment'];

    $dao->updateUniqueId($unique_id);

    $message = 'Saved.';
  } else if (isset($_POST['approve'])) {
    $unique_id['uniqueid_approved']    = $dao->selectCurrentTimestamp();
    $unique_id['uniqueid_approved_by'] = $user['person_id'];

    $dao->updateUniqueId($unique_id);

    $message = 'Approved.';
  } else if (isset($_POST['delete'])) {
    $dao->deleteUniqueId($unique_id['uniqueid_id']);

    header('Location: viewuidall.php');
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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="OpenLCB ID Registry">
    <link rel="icon" href="../../favicon.ico">

    <title>View OpenLCB Unique ID Range</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>

    <!-- Custom styles for this template -->
    <link href="theme.css" rel="stylesheet">
  </head>

  <body>
<?php
include('navbar.php');
?>
    <div class="container-fluid">
      <h2>View OpenLCB Unique ID Range</h2>
<?php
if ($error !== null) {
?>
      <div class="alert alert-danger">
        <a href="<?php echo htmlspecialchars("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']); ?>" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></a>
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
        <h3 style="font-family: monospace; white-space: pre;"><?php echo htmlspecialchars(formatUniqueIdHex($unique_id)); ?></h3>
        <table class="table table-condensed">
          <tbody>
            <tr>
              <th>URL</th>
<?php
  if (isset($_POST['edit'])) {
?>
              <td><input name="uniqueid_url" class="form-control input-sm" value="<?php echo htmlspecialchars($unique_id['uniqueid_url']) ?>"/></td>
<?php
  } else {
?>
              <td><?php echo htmlspecialchars($unique_id['uniqueid_url']) ?></td>
<?php
  }
?>

            </tr>
            <tr>
              <th>Comment</th>
<?php
  if (isset($_POST['edit'])) {
?>
              <td><textarea name="uniqueid_user_comment" class="form-control input-sm" oninput="this.rows = 2; this.style.height = '0'; this.style.height = this.scrollHeight + 2 + 18 + 'px';"><?php echo htmlspecialchars($unique_id['uniqueid_user_comment']) ?></textarea></td>
<?php
  } else {
?>
              <td><?php echo nl2br(htmlspecialchars($unique_id['uniqueid_user_comment'])) ?></td>
<?php
  }
?>
            </tr>
            <tr>
              <th>Delegating organization or person</th>
              <td><a href="profile.php?person_id=<?php echo $unique_id['person_id']; ?>"><?php echo htmlspecialchars(formatPersonName($unique_id)); ?></a></td>
            </tr>
            <tr>
              <th>Created</th>
              <td><?php echo htmlspecialchars($unique_id['uniqueid_created']) ?></td>
            </tr>
            <tr>
              <th>Updated</th>
              <td><?php echo htmlspecialchars($unique_id['last_updated']) ?></td>
            </tr>
            <tr>
              <th>Approved</th>
              <td><?php echo htmlspecialchars($unique_id['uniqueid_approved']) ?></td>
            </tr>
            <tr>
              <th>Approved by</th>
              <td><a href="profile.php?person_id=<?php echo $unique_id['uniqueid_approved_by']; ?>"><?php echo htmlspecialchars($unique_id['approved_by_organization'] != '' ? $unique_id['approved_by_organization'] : $unique_id['approved_by_first_name'] . ' ' . $unique_id['approved_by_last_name']) ?></a></td>
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
        <button type="submit" name="edit" class="btn btn-sm btn-warning"><span class="glyphicon glyphicon-edit"></span> Edit</button>
<?php
    if ($unique_id['uniqueid_approved'] === null) {
?>
        <button type="submit" name="approve" class="btn btn-sm btn-success"><span class="glyphicon glyphicon-check"></span> Approve</button>
<?php
    }
?>
        <button type="submit" name="delete" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this range?');"><span class="glyphicon glyphicon-trash"></span> Delete</button>
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
