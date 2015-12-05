<?php
require_once('access.php');

// open DB
global $opts;
mysql_connect($opts['hn'],$opts['un'],$opts['pw']);
@mysql_select_db($opts['db']) or die( "Unable to select database. Error (" . mysql_errno() . ") " . mysql_error());

function value($result, $j, $index) {
    if (255 == mysql_result($result, $j, "uniqueid_byte" . $index . "_mask")) return "*";
    else return mysql_result($result, $j, "uniqueid_byte" . $index . "_value");
}

$query = "SELECT *
FROM
  UniqueIDs
  LEFT JOIN Person USING (person_id)
ORDER BY
  uniqueid_byte0_value,
  uniqueid_byte1_value,
  uniqueid_byte2_value,
  uniqueid_byte3_value,
  uniqueid_byte4_value,
  uniqueid_byte5_value;";
$result = mysql_query($query);

$json = array();

for ($j = 0; $j < mysql_numrows($result); $j++) {
  $entry = array();
  $entry['byte0'] = value($result, $j, "0");
  $entry['byte1'] = value($result, $j, "1");
  $entry['byte2'] = value($result, $j, "2");
  $entry['byte3'] = value($result, $j, "3");
  $entry['byte4'] = value($result, $j, "4");
  $entry['byte5'] = value($result, $j, "5");
  if (mysql_result($result, $j, "person_organization") != '') {
    $entry['organization'] = mysql_result($result, $j, "person_organization");
  } else {
    $entry['firstname'] = mysql_result($result, $j, "person_first_name");
    $entry['lastname'] = mysql_result($result, $j, "person_last_name");
  }
  $entry['url'] = mysql_result($result, $j, "uniqueid_url");
  $entry['comment'] = mysql_result($result, $j, "uniqueid_user_comment");
  $json[] = $entry;
}

header('Content-Type: application/json');

echo json_encode($json);
