<?php
/*
 * The questionprompt table provides the allowed values for a multiple
 * question.
 */

class CreateTableQuestionPrompt extends Migration {

  public function up() {
    $this->createTable('question_prompt', array('primary' => 'promptID'), array(
      array('promptID', 'integer'),
      array('instanceID', 'integer'),
      array('questionTypeID', 'integer'),
      array('value', 'string', array('limit' => 25)),
      array('requireAddlInfo', 'boolean', array('default' => 0))
    ));
    $this->createIndex('question_prompt', array('questionTypeID'));
    $this->createIndex('question_prompt', array('instanceID'));
  }

  public function down() {
    $this->dropTable('question_prompt');
  }
}
