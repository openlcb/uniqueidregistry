<?php
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/legacy_common.php';

global $opts;
$mysqli = legacy_db_connect($opts);
if ($mysqli === null) {
  header('Content-Type: application/xml; charset=utf-8');
  echo '<uidranges/>';
  exit;
}

function value_from_row($row, $index) {
  if (255 == (int) $row['uniqueid_byte' . $index . '_mask']) {
    return '*';
  }
  return $row['uniqueid_byte' . $index . '_value'];
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
$result = $mysqli->query($query);
if (!$result) {
  header('Content-Type: application/xml; charset=utf-8');
  echo '<uidranges/>';
  exit;
}

header('Content-Type: application/xml; charset=utf-8');

echo "<uidranges>\n";
echo "<!-- '*' means that any values are accepted in that byte, forming the range.-->\n";

while ($row = $result->fetch_assoc()) {
  echo '<uidrange>';
  echo '<byte0>' . htmlspecialchars((string) value_from_row($row, '0'), ENT_QUOTES, 'UTF-8') . '</byte0>';
  echo '<byte1>' . htmlspecialchars((string) value_from_row($row, '1'), ENT_QUOTES, 'UTF-8') . '</byte1>';
  echo '<byte2>' . htmlspecialchars((string) value_from_row($row, '2'), ENT_QUOTES, 'UTF-8') . '</byte2>';
  echo '<byte3>' . htmlspecialchars((string) value_from_row($row, '3'), ENT_QUOTES, 'UTF-8') . '</byte3>';
  echo '<byte4>' . htmlspecialchars((string) value_from_row($row, '4'), ENT_QUOTES, 'UTF-8') . '</byte4>';
  echo '<byte5>' . htmlspecialchars((string) value_from_row($row, '5'), ENT_QUOTES, 'UTF-8') . '</byte5>';
  if ($row['person_organization'] !== '' && $row['person_organization'] !== null) {
    echo '<organization>' . htmlspecialchars((string) $row['person_organization'], ENT_QUOTES, 'UTF-8') . '</organization>';
  } else {
    echo '<firstname>' . htmlspecialchars((string) $row['person_first_name'], ENT_QUOTES, 'UTF-8') . '</firstname>';
    echo '<lastname>' . htmlspecialchars((string) $row['person_last_name'], ENT_QUOTES, 'UTF-8') . '</lastname>';
  }
  echo '<url>' . htmlspecialchars((string) $row['uniqueid_url'], ENT_QUOTES, 'UTF-8') . '</url>';
  echo '<comment>' . htmlspecialchars((string) $row['uniqueid_user_comment'], ENT_QUOTES, 'UTF-8') . '</comment>';
  echo "</uidrange>\n";
}

echo "</uidranges>\n";
$mysqli->close();
