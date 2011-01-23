<?php
class RenameAdministratorsToGlobalAdministrators extends Migration {

  public function up() {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $newValues = array('roleDescription' => 'Global Administrators');
    $where = "roleDescription = 'Administrators'";
    $adapter->update('role', $newValues, $where);
  }
  
  public function down() {

  }
  
}
