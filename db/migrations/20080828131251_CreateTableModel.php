<?php
class CreateTableModel extends Migration {

  public function up() {
    $this->createTable('model', array('primary' => 'modelID'), array(
      array('modelID', 'integer'),
      array('questionnaireID', 'integer', array('null' => true)),
      array('name', 'string')
    ));
  }

  public function down() {
    $this->dropTable('model');
  }
}
