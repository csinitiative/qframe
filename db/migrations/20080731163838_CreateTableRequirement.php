<?php
// A requirement links a user and role tuple (assignment) to a question.

class CreateTableRequirement extends Migration {

  public function up() {
    $this->createTable('requirement',
      array('primary' => array('assignmentID', 'questionID')),
      array(
        array('assignmentID', 'integer'),
        array('questionID', 'integer'),
        array('complete', 'boolean'),
        array('dueDate', 'date', array('null' => true))
      )
    );
  }

  public function down() {
    $this->dropTable('requirement');
  }
}
