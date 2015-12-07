<?php
require_once('access.php');
require_once('dao.php');
require_once('utils.php');
require_once('email.php');

$dao = new DAO($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);
  
$user = $dao->selectUser();

$unique_ids = $dao->selectUniqueIds();
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

    <title>View All OpenLCB Unique ID Ranges</title>

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
      <h2>View All OpenLCB Unique ID Ranges</h2>
      <div class="alert alert-info" role="alert">
        This page shows the ranges of OpenLCB Unique ID's that have been assigned to date.
        <P>
        For more information on OpenLCB, please see the <a href="../documents/index.html">documentation page</a>.
        For more information on OpenLCB unique ID assignment, please see the current draft
        <a href="../specs/drafts/GenUniqueIdS.pdf">specification</a> and 
        <a href="../specs/drafts/GenUniqueIdTN.pdf">technical note</a>.<br/>
        This data is also available in <a href="uidxml.php">XML</a>, and <a href="uidjson.php">JSON</a>.<br/>
        '*' means that any values are accepted in that byte.
      </div>
      <table class="table table-condensed">
        <tbody>
          <tr>
            <th>Range</th>
            <th>Delegating organization or person</th>
            <th>URL</th>
            <th>Comment</th>
          </tr>
<?php
foreach ($unique_ids as $unique_id) {
?>
          <tr>
            <td style="font-family: monospace; white-space: pre;"><a href="uid.php?uniqueid_id=<?php echo $unique_id['uniqueid_id']; ?>"><?php echo htmlspecialchars(formatUniqueIdHex($unique_id)); ?></a></td>            
            <td><?php echo htmlspecialchars(formatPersonName($unique_id)); ?></td>
            <td><?php echo htmlspecialchars($unique_id['uniqueid_url']); ?></td>
            <td><?php echo htmlspecialchars($unique_id['uniqueid_user_comment']); ?></td>
          </tr>
<?php
}
?>
        </tbody>
      </table>
    </div>
  </body>
</html>
