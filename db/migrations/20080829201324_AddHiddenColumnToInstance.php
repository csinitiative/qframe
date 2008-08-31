<?php
class AddHiddenColumnToInstance extends Migration {

  public function up() {
    $this->addColumn('instance', 'hidden', 'boolean', array('default' => 0));
  }
  
  public function down() {
    $this->removeColumn('instance', 'hidden');
  }
}
