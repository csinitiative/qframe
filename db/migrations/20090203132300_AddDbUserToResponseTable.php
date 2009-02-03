<?php
class AddDbUserToResponseTable extends Migration {

  public function up() {
    $this->addColumn('response', 'dbUserID', 'integer', array('default' => -1));
  }
  
  public function down() {
    $this->removeColumn('response', 'dbUserID');
  }
  
}
