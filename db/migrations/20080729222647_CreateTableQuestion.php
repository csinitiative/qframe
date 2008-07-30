<?php
/*
 * A question is the basic element definition for an atomic response. It 
 * acorresponds to one row in the SIG spreadsheet implementation. 
 * Note that when a spreadsheet row contains multiple response cells, they are
 * treated as separate questions, grouped for formatting by the web 
 * application. 
 * Which of the resonse elements are required is controlled by 
 * the questionType foreign key.
 * the questionGUID field is a unique global identifier for questions that
 * should hold across intruments and versions, allowing for persistence of
 * information in mapping responses across compliance questionnaires.
 *
 * The questionNumber is used to contain whatever kind of alphanumeric
 * numbering scheme is used in the display and export of the questionnaire.
 *
 * parentID is used to link questions into question groups by identifying the
 * parent question.
 */

class CreateTableQuestion extends Migration {

  public function up() {
    $this->createTable('question', array('primary' => 'questionID'), array(
      array('questionID', 'integer'),
      array('questionnaireID', 'integer'),
      array('instanceID', 'integer'),
      array('pageID', 'integer'),
      array('sectionID', 'integer'),
      array('questionGUID', 'integer'),
      array('questionNumber', 'string', array('limit' => 50, 'null' => true)),
      array('seqNumber', 'integer'),
      array('questionTypeID', 'integer'),
      array('qText', 'text', array('null' => true)),
      array('required', 'boolean', array('default' => 1)),
      array('parentID', 'integer', array('default' => 0)),
      array('cloneable', 'boolean', array('default' => 0)),
      array('defaultQuestionHidden', 'boolean', array('default' => 0)),
      array('disableCount', 'integer', array('default' => 0))
    ));
    $this->createIndex('question', array('sectionID'));
    $this->createIndex('question', array('pageID'));
    $this->createIndex('question', array('instanceID'));
  }

  public function down() {
    $this->dropTable('question');
  }
}
