<?php
/*
 * The questiontype table is used to provide generic instructions to both users
 * and the questionnaire form generator on how a question is to be answered.
 */

class CreateTableQuestionType extends Migration {

  public function up() {
    $this->createTable('question_type', array('primary' => 'questionTypeID'), array(
      array('questionTypeID', 'integer', array('limit' => 20)),
      array('instanceID', 'integer'),
      array('format', 'string', array('limit' => 20, 'default' => 'T:A-Z0-9')),
      array('maxLength', 'integer', array('null' => true))
    ));
    $this->createIndex('question_type', array('instanceID'));
  }

  public function down() {
    $this->dropTable('question_type');
  }
}
