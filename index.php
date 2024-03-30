<?php
require_once('access.php');
require_once('dal.php');
require_once('utils.php');
require_once('email.php');

$dal = new DAL($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);

$user = $dal->selectUser();
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
        This is the “web” directory of the OpenLCB&#8482; website. It contains various resources for the OpenLCB website.
      </p>
      <p>
        The main page of the OpenLCB website is <a href="https://openlcb.org">here</a>.
      </p>
      <p>
        Local pages:
        <ul>
          <li><a href="uniqueidranges">Listing</a> of assigned unique ID ranges
          <li><a href="uniqueidrangesxml">XML listing</a> of assigned unique ID ranges
          <li><a href="uniqueidrangesjson">JSON listing</a> of assigned unique ID ranges
          <li><a href="requestuniqueidrange">Request</a> to be assigned a range
        </ul>
      </p>
      <hr/>
      <p>
        This website contains trademarks and copyrighted information. Please see the <a href="https://openlcb.org/about-openlcb/licensing/">Licensing</a> page.
      </p>
    </div>
  </body>
</html>
