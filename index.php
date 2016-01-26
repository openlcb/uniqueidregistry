<?php
require_once('access.php');
require_once('dao.php');
require_once('utils.php');
require_once('email.php');

$dao = new DAO($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);

$user = $dao->selectUser();
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

    <title>OpenLCB ID Registry</title>

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
      <h2>OpenLCB ID Registry</h2>
      <p>
        This is the “web” directory of the OpenLCB&#8482; web site. It contains various resources for the OpenLCB web site.
      </p>
      <p>
        The main page of the OpenLCB web site is <a href="../../index.html">here</a>.
      </p>
      <p>
        Local pages:
        <ul>
          <li><a href="viewuid">Formatted tables</a> of assigned unique ID ranges
          <li><a href="viewuidall">Single table</a> of assigned unique ID ranges
          <li><a href="uidxml">XML listing</a> of assigned unique ID ranges
          <li><a href="uidjson">JSON listing</a> of assigned unique ID ranges
          <li><a href="requestuidrange">Request</a> to be assigned a range
        </ul>
      </p>
      <hr/>
      <p>
       Site hosted by <a href="http://sourceforge.net/projects/openlcb"><img src="http://sflogo.sourceforge.net/sflogo.php?group_id=286373&amp;type=13" width="120" height="30" border="2"/></a>
      </p>
      <p>
        This web site contains trademarks and copyrighted information. Please see the <a href="../../Licensing.html">Licensing</a> page.
      </p>
    </div>
  </body>
</html>
