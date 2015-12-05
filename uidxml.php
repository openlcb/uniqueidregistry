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

header('Content-Type: application/xml');

echo "<uidranges>\n";
echo "<!-- '*' means that any values are accepted in that byte, forming the range.-->\n";

for ($j = 0; $j < mysql_numrows($result); $j++) {
  echo '<uidrange>';
  echo '<byte0>' . htmlspecialchars(value($result, $j, "0"), ENT_XML1) . '</byte0>';
  echo '<byte1>' . htmlspecialchars(value($result, $j, "1"), ENT_XML1) . '</byte1>';
  echo '<byte2>' . htmlspecialchars(value($result, $j, "2"), ENT_XML1) . '</byte2>';
  echo '<byte3>' . htmlspecialchars(value($result, $j, "3"), ENT_XML1) . '</byte3>';
  echo '<byte4>' . htmlspecialchars(value($result, $j, "4"), ENT_XML1) . '</byte4>';
  echo '<byte5>' . htmlspecialchars(value($result, $j, "5"), ENT_XML1) . '</byte5>';
  if (mysql_result($result, $j, "person_organization") != '') {
    echo '<organization>' . htmlspecialchars(mysql_result($result, $j, "person_organization"), ENT_XML1) . '</organization>';
  } else {
    echo '<firstname>' . htmlspecialchars(mysql_result($result, $j, "person_first_name"), ENT_XML1) . '</firstname>';
    echo '<lastname>' . htmlspecialchars(mysql_result($result, $j, "person_last_name"), ENT_XML1) . '</lastname>';
  }
  echo '<url>' . htmlspecialchars(mysql_result($result, $j, "uniqueid_url"), ENT_XML1).'</url>';
  echo '<comment>' . htmlspecialchars(mysql_result($result, $j, "uniqueid_user_comment"), ENT_XML1).'</comment>';
  echo "</uidrange>\n";
}

echo "</uidranges>\n";
