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

header('Content-Type: application/json');

echo "[\n";

for ($j = 0; $j < mysql_numrows($result); $j++) {
  echo "  {\n";
  echo '    "byte0": ' . json_encode(value($result, $j, "0")) . ",\n";
  echo '    "byte1": ' . json_encode(value($result, $j, "1")) . ",\n";
  echo '    "byte2": ' . json_encode(value($result, $j, "2")) . ",\n";
  echo '    "byte3": ' . json_encode(value($result, $j, "3")) . ",\n";
  echo '    "byte4": ' . json_encode(value($result, $j, "4")) . ",\n";
  echo '    "byte5": ' . json_encode(value($result, $j, "5")) . ",\n";
  if (mysql_result($result, $j, "person_organization") != '') {
    echo '    "organization": ' . json_encode(mysql_result($result, $j, "person_organization")) . ",\n";
  } else {
    echo '    "firstname": ' . json_encode(mysql_result($result, $j, "person_first_name")) . ",\n";
    echo '    "lastname": ' . json_encode(mysql_result($result, $j, "person_last_name")) . ",\n";
  }
  echo '    "url": ' . json_encode(mysql_result($result, $j, "uniqueid_url")) . ",\n";
  echo '    "comment": ' . json_encode(mysql_result($result, $j, "uniqueid_user_comment")) . "\n";
  if ($j + 1 < mysql_numrows($result)) {
    echo "  },\n";
  } else {
    echo "  }\n";
  }
}


echo "]";
