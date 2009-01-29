<?php
class ExpandStringFieldsInDbUser extends Migration {

  public function up() {
    // alter columns
    $this->alterColumn('db_user', 'dbUserName', 'string', array('limit' => 255));
    $this->alterColumn('db_user', 'dbUserPW', 'string', array('limit' => 255));
    $this->alterColumn('db_user', 'dbUserFullName', 'string', array('limit' => 255));
  }
  
  public function down() {

  }
  
}
