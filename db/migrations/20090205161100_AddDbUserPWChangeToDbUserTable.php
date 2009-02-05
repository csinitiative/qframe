<?php
class AddDbUserPWChangeToDbUserTable extends Migration {

  public function up() {
    $this->addColumn('db_user', 'dbUserPWChange', 'string', array('limit' => 1, 'default' => 'N'));
  }
  
  public function down() {
    $this->removeColumn('db_user', 'dbUserPWChange');
  }
  
}
