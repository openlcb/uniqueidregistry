<?php
require 'PasswordHash.php';

class UserException extends Exception { }

function formatPersonName($person) {
  return $person['person_organization'] != '' ? $person['person_organization'] : $person['person_first_name'] . ' ' . $person['person_last_name'];
}

function formatPersonEmail($person) {
  return formatPersonName($person) . ' <' . $person['person_email'] . '>';
}

function formatValueHex($value, $mask) {
  return $mask == 255 ? ' *' : substr('0' . strtoupper(dechex($value)), -2);
}

function formatUniqueIdHex($unique_id) {
  return
    formatValueHex($unique_id['uniqueid_byte0_value'], $unique_id['uniqueid_byte0_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte1_value'], $unique_id['uniqueid_byte1_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte2_value'], $unique_id['uniqueid_byte2_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte3_value'], $unique_id['uniqueid_byte3_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte4_value'], $unique_id['uniqueid_byte4_mask']) . ' ' .
    formatValueHex($unique_id['uniqueid_byte5_value'], $unique_id['uniqueid_byte5_mask']);
}

function randHex() {
  $count = 32;
  $chars = '0123456789abcdef';
  $max = strlen($chars) - 1;
  $result = "";
  for ($i = 0; $i < $count; $i++) $result .= $chars[rand(0, $max)];
  return $result;
}

function verifyPersonPassword($person, $password) {
  //return password_verify($password, $person['person_password_hash']);

  // Base-2 logarithm of the iteration count used for password stretching
  $hash_cost_log2 = 8;
  // Do we require the hashes to be portable to older systems (less secure)?
  $hash_portable = FALSE;
  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $result = $hasher->CheckPassword($password, $person['person_password_hash']);
  unset($hasher);
  return $result;
}

function setPersonPassword(&$person, $password) {
  //$person['person_password_hash'] = password_hash($password, PASSWORD_DEFAULT);

  // Base-2 logarithm of the iteration count used for password stretching
  $hash_cost_log2 = 8;
  // Do we require the hashes to be portable to older systems (less secure)?
  $hash_portable = FALSE;
  $hasher = new PasswordHash($hash_cost_log2, $hash_portable);
  $hash = $hasher->HashPassword($password);
  if (strlen($hash) < 20)
	fail('Failed to hash new password');
  unset($hasher);
  $person['person_password_hash'] = $hash;
}
