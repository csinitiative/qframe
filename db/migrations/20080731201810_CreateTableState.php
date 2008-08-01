<?php
/*
 * The state table is used to define the possible states (recorded in the
 * state integer field) of each response.
 */

class CreateTableState extends Migration {

  public function up() {
    $this->createTable('state', array('primary' => 'state'), array(
      array('state', 'integer'),
      array('stateDescription', 'string', array('limit' => 10, 'null' => true))
    ));
  }

  public function down() {
    $this->dropTable('state');
  }
}
