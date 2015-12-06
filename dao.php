<?php
class DAO {

  private $db;
  public $user;

  function __construct($host, $dbname, $username, $password) {
    session_start();
    $this->db = new PDO('mysql:host=' . $host . ';dbname=' . $dbname, $username, $password);
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  function beginTransaction() {
    $this->db->beginTransaction();
  }

  function commit() {
    $this->db->commit();
  }

  function rollback() {
    $this->db->rollback();
  }
    
  function select($sql, $args = array( )) {
    $stmt = $this->db->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  function update($sql, $args = array( )) {
    $stmt = $this->db->prepare($sql);
    $stmt->execute($args);
  }

  function selectRow($sql, $args = array( )) {
    $rows = $this->select($sql, $args);
    return count($rows) != 1 ? null : $rows[0];
  }

  function selectCurrentTimestamp() {
    $row = $this->selectRow('SELECT CURRENT_TIMESTAMP');
    return $row['CURRENT_TIMESTAMP'];
  }

  function selectUser() {
    return !isset($_SESSION['person_id']) ? null : $this->selectPersonById($_SESSION['person_id']);
  }

  function login($email, $password, $remember = false) {
    $this->logout();
    
    $person = $this->selectPersonByEmail($email);
    if ($person === null) return false; //person not found
    if (!verifyPersonPassword($person, $password)) return false; //password wrong
    if ($remember) {
      session_set_cookie_params(60 * 60 * 24 * 366); //session will be garbage collected long before cookie expires
    }
    session_start();
    session_regenerate_id(); //create new session with possibly different expiry
    $_SESSION['person_id'] = $person['person_id'];
    $_SESSION['remember'] = $remember;

    return true;
  }

  function loginWithEmailSharedSecret($person_id, $person_email_shared_secret, $remember = false) {
    $this->logout();
    
    $person = $this->selectPersonById($person_id);
    if ($person === null) return false; //person not found
    if ($person['person_email_shared_secret'] !== $person_email_shared_secret) return false; //email shared secret wrong
    if ($remember) {
      session_set_cookie_params(60 * 60 * 24 * 366); //session will be garbage collected long before cookie expires
    }
    session_start();
    session_regenerate_id(); //create new session with possibly different expiry
    $_SESSION['person_id'] = $person['person_id'];
    $_SESSION['remember'] = $remember;

    return true;
  }

  function logout() {
    session_destroy();
    unset($_SESSION['person_id']);
    unset($_SESSION['remember']);
  }

  function insertPerson(&$person) {
    $person['person_created'] = $this->selectCurrentTimestamp();
    $this->update('INSERT INTO Person (
  person_created,
  person_first_name,
  person_last_name,
  person_organization,
  person_email,
  person_password_hash,
  person_subscribe,
  person_email_verified,
  person_email_shared_secret
)
VALUES (
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?
)', array(
      $person['person_created'],
      $person['person_first_name'],
      $person['person_last_name'],
      $person['person_organization'],
      $person['person_email'],
      $person['person_password_hash'],
      $person['person_subscribe'],
      $person['person_email_verified'],
      $person['person_email_shared_secret'],
    ));
    $person['person_id'] = $this->db->lastInsertId();
  }

  function selectPersonById($person_id) {
    return $this->selectRow('SELECT *, (SELECT COUNT(*) FROM UniqueIDs WHERE UniqueIDs.person_id = Person.person_id) person_uniqueid_count FROM Person WHERE person_id = ?', array( $person_id ));
  }
    
  function selectPersonByEmail($email) {
    return $this->selectRow('SELECT *, (SELECT COUNT(*) FROM UniqueIDs WHERE UniqueIDs.person_id = Person.person_id) person_uniqueid_count FROM Person WHERE person_email = ?', array( $email ));
  }

  function selectModerators() {
    return $this->select('SELECT * FROM Person WHERE person_is_moderator = \'y\'');
  }

  function selectPeople() {
    return $this->select('SELECT * FROM Person');
  }

  function updatePerson($person) {
    $this->update('UPDATE
  Person
SET
  person_first_name = ?,
  person_last_name = ?,
  person_organization = ?,
  person_subscribe = ?,
  person_is_moderator = ?,
  person_email_shared_secret = ?,
  person_email = ?,
  person_email_verified = ?,
  person_password_hash = ?
WHERE
  person_id = ?', array(
      $person['person_first_name'],
      $person['person_last_name'],
      $person['person_organization'],
      $person['person_subscribe'],
      $person['person_is_moderator'],
      $person['person_email_shared_secret'],
      $person['person_email'],
      $person['person_email_verified'],
      $person['person_password_hash'],
      $person['person_id']
    ));
  }

  function deletePerson($person_id) {
    $this->update('DELETE FROM Person WHERE person_id = ?', array( $person_id ));
  }

  function insertUniqueId(&$unique_id) {
    $unique_id['uniqueid_created'] = $this->selectCurrentTimestamp();
    
    $b0 = 5;
    $b1 = 1;
    $b2 = 1;
    $b3 = 1;

    $b4 = $this->selectRow('SELECT (COALESCE(MAX(uniqueid_byte4_value), 0) + 1) uniqueid_byte5_value
FROM UniqueIDs
WHERE
  uniqueid_byte0_value = ?
  AND uniqueid_byte1_value = ?
  AND uniqueid_byte2_value = ?
  AND uniqueid_byte3_value = ?', array( $b0, $b1, $b2, $b3 ));
    $b4 = $b4['uniqueid_byte5_value'];

    $unique_id['uniqueid_byte0_value'] = $b0;
    $unique_id['uniqueid_byte1_value'] = $b1;
    $unique_id['uniqueid_byte2_value'] = $b2;
    $unique_id['uniqueid_byte3_value'] = $b3;
    $unique_id['uniqueid_byte4_value'] = $b4;
    $unique_id['uniqueid_byte5_value'] = 0;
    $unique_id['uniqueid_byte0_mask'] = 0;
    $unique_id['uniqueid_byte1_mask'] = 0;
    $unique_id['uniqueid_byte2_mask'] = 0;
    $unique_id['uniqueid_byte3_mask'] = 0;
    $unique_id['uniqueid_byte4_mask'] = 0;
    $unique_id['uniqueid_byte5_mask'] = 255;
    
    $this->update('INSERT INTO UniqueIDs (
  uniqueid_created,
  person_id,
  uniqueid_byte0_value,
  uniqueid_byte1_value,
  uniqueid_byte2_value,
  uniqueid_byte3_value,
  uniqueid_byte4_value,
  uniqueid_byte5_value,
  uniqueid_byte0_mask,
  uniqueid_byte1_mask,
  uniqueid_byte2_mask,
  uniqueid_byte3_mask,
  uniqueid_byte4_mask,
  uniqueid_byte5_mask,
  uniqueid_url,
  uniqueid_user_comment
) VALUES (
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?,
  ?
)', array(
      $unique_id['uniqueid_created'],
      $unique_id['person_id'],
      $unique_id['uniqueid_byte0_value'],
      $unique_id['uniqueid_byte1_value'],
      $unique_id['uniqueid_byte2_value'],
      $unique_id['uniqueid_byte3_value'],
      $unique_id['uniqueid_byte4_value'],
      $unique_id['uniqueid_byte5_value'],
      $unique_id['uniqueid_byte0_mask'],
      $unique_id['uniqueid_byte1_mask'],
      $unique_id['uniqueid_byte2_mask'],
      $unique_id['uniqueid_byte3_mask'],
      $unique_id['uniqueid_byte4_mask'],
      $unique_id['uniqueid_byte5_mask'],
      $unique_id['uniqueid_url'],
      $unique_id['uniqueid_user_comment']
    ));
    $unique_id['uniqueid_id'] = $this->db->lastInsertId();
  }

  function selectUniqueId($uniqueid_id) {
    return $this->selectRow('SELECT
  UniqueIDs.*,
  Person.person_organization,
  Person.person_first_name,
  Person.person_last_name,
  approved_by.person_organization approved_by_organization,
  approved_by.person_first_name approved_by_first_name,
  approved_by.person_last_name approved_by_last_name
FROM
  UniqueIDs
  LEFT JOIN Person USING (person_id)
  LEFT JOIN Person approved_by ON (approved_by.person_id = UniqueIDs.uniqueid_approved_by)
WHERE
  uniqueid_id = ?
ORDER BY
  uniqueid_byte0_value, uniqueid_byte0_mask DESC,
  uniqueid_byte1_value, uniqueid_byte1_mask DESC,
  uniqueid_byte2_value, uniqueid_byte2_mask DESC,
  uniqueid_byte3_value, uniqueid_byte3_mask DESC,
  uniqueid_byte4_value, uniqueid_byte4_mask DESC,
  uniqueid_byte5_value, uniqueid_byte5_mask DESC', array( $uniqueid_id ));
  }

  function selectUniqueIds() {
    return $this->select('SELECT
  UniqueIDs.*,
  Person.person_organization,
  Person.person_first_name,
  Person.person_last_name
FROM
  UniqueIDs
  LEFT JOIN Person USING (person_id)
ORDER BY
  uniqueid_byte0_value, uniqueid_byte0_mask DESC,
  uniqueid_byte1_value, uniqueid_byte1_mask DESC,
  uniqueid_byte2_value, uniqueid_byte2_mask DESC,
  uniqueid_byte3_value, uniqueid_byte3_mask DESC,
  uniqueid_byte4_value, uniqueid_byte4_mask DESC,
  uniqueid_byte5_value, uniqueid_byte5_mask DESC');
  }

  function selectTopUniqueIds() {
    return $this->select('SELECT
  UniqueIDs.*,
  Person.person_organization,
  Person.person_first_name,
  Person.person_last_name
FROM
  UniqueIDs
  LEFT JOIN Person USING (person_id)
WHERE
  uniqueid_byte1_mask = 255
  AND uniqueid_byte2_mask = 255
  AND uniqueid_byte3_mask = 255
  AND uniqueid_byte4_mask = 255
  AND uniqueid_byte5_mask = 255  
ORDER BY
  uniqueid_byte0_value, uniqueid_byte0_mask DESC,
  uniqueid_byte1_value, uniqueid_byte1_mask DESC,
  uniqueid_byte2_value, uniqueid_byte2_mask DESC,
  uniqueid_byte3_value, uniqueid_byte3_mask DESC,
  uniqueid_byte4_value, uniqueid_byte4_mask DESC,
  uniqueid_byte5_value, uniqueid_byte5_mask DESC');
  }

  function selectSubUniqueIds($uniqueid_byte0_value) {
    return $this->select('SELECT
  UniqueIDs.*,
  Person.person_organization,
  Person.person_first_name,
  Person.person_last_name
FROM
  UniqueIDs
  LEFT JOIN Person USING (person_id)
WHERE
  uniqueid_byte0_value = ?
  AND NOT (
        uniqueid_byte1_mask = 255
    AND uniqueid_byte2_mask = 255
    AND uniqueid_byte3_mask = 255 
    AND uniqueid_byte4_mask = 255
    AND uniqueid_byte5_mask = 255
  )
ORDER BY
  uniqueid_byte1_value, uniqueid_byte1_mask,
  uniqueid_byte2_value, uniqueid_byte2_mask,
  uniqueid_byte3_value, uniqueid_byte3_mask,
  uniqueid_byte4_value, uniqueid_byte4_mask,
  uniqueid_byte5_value, uniqueid_byte5_mask', array( $uniqueid_byte0_value ));
  }

  function selectUniqueIdsByPersonId($person_id) {
    return $this->select('SELECT
  UniqueIDs.*,
  Person.person_organization,
  Person.person_first_name,
  Person.person_last_name
FROM
  UniqueIDs
  LEFT JOIN Person USING (person_id)
WHERE
  UniqueIDs.person_id = ?
ORDER BY
  uniqueid_byte0_value, uniqueid_byte0_mask DESC,
  uniqueid_byte1_value, uniqueid_byte1_mask DESC,
  uniqueid_byte2_value, uniqueid_byte2_mask DESC,
  uniqueid_byte3_value, uniqueid_byte3_mask DESC,
  uniqueid_byte4_value, uniqueid_byte4_mask DESC,
  uniqueid_byte5_value, uniqueid_byte5_mask DESC', array( $person_id ));
  }

  function updateUniqueId($unique_id) {
    $this->update('UPDATE
  UniqueIDs
SET
  uniqueid_url = ?,
  uniqueid_user_comment = ?
WHERE
  person_id = ?', array(
      $unique_id['uniqueid_url'],
      $unique_id['uniqueid_user_comment'],
      $unique_id['uniqueid_id']
    ));
  }

  function deleteUniqueId($unique_id_id) {
    $this->update('DELETE FROM UniqueIDs WHERE uniqueid_id = ?', array( $unique_id_id ));
  }

}
