<?php
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/legacy_common.php';

global $opts;
$mysqli = legacy_db_connect($opts);
if ($mysqli === null) {
  header('Content-Type: application/json; charset=utf-8');
  echo '[]';
  exit;
}

function value_from_row($row, $index) {
  if (255 == (int) $row['uniqueid_byte' . $index . '_mask']) {
    return '*';
  }
  return (int) $row['uniqueid_byte' . $index . '_value'];
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
  header('Content-Type: application/json; charset=utf-8');
  echo '[]';
  exit;
}

header('Content-Type: application/json; charset=utf-8');

echo "[\n";

$rows = $result->fetch_all(MYSQLI_ASSOC);
$n = count($rows);

foreach ($rows as $j => $row) {
  echo "  {\n";
  echo '    "byte0": ' . json_encode(value_from_row($row, '0')) . ",\n";
  echo '    "byte1": ' . json_encode(value_from_row($row, '1')) . ",\n";
  echo '    "byte2": ' . json_encode(value_from_row($row, '2')) . ",\n";
  echo '    "byte3": ' . json_encode(value_from_row($row, '3')) . ",\n";
  echo '    "byte4": ' . json_encode(value_from_row($row, '4')) . ",\n";
  echo '    "byte5": ' . json_encode(value_from_row($row, '5')) . ",\n";
  if ($row['person_organization'] !== '' && $row['person_organization'] !== null) {
    echo '    "organization": ' . json_encode($row['person_organization']) . ",\n";
  } else {
    echo '    "firstname": ' . json_encode($row['person_first_name']) . ",\n";
    echo '    "lastname": ' . json_encode($row['person_last_name']) . ",\n";
  }
  echo '    "url": ' . json_encode($row['uniqueid_url']) . ",\n";
  echo '    "comment": ' . json_encode($row['uniqueid_user_comment']) . "\n";
  if ($j + 1 < $n) {
    echo "  },\n";
  } else {
    echo "  }\n";
  }
}

echo "]";
$mysqli->close();
