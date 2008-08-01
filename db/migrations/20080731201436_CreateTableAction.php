<?php
/*
 * An action is the fulfillment or completion of a requirement 
 * it too links a user and role tuple (assignment) to a question.
 */

class CreateTableAction extends Migration {

  public function up() {
    $this->createTable('action', array('primary' => array('assignmentID', 'questionID')), array(
      array('assignmentID', 'integer'),
      array('questionID', 'integer', array('limit' => 20)),
      array('complete', 'boolean'),
      array('dueDate', 'date', array('null' => true))
    ));
  }

  public function down() {
    $this->dropTable('action');
  }
}
