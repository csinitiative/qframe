<?php
/*
 * The following six tables define the hierarchy that describes a compliance
 * reporting questionnaire and response to the web application:
 *   questionnaire
 *      version
 *         page
 *           section
 *             questionGroup
 *               question
 *                 response
 * The contents of all except the response will be read from an XML file 
 * conforming to the csiq.xsd schema definition.

 * seqNumber is used in all screen-displayed elements to allow the preservation
 * of element ordering found in the paper, document, or spreadsheet reference
 * implementations of the questionnaires.

 * Questionnaire is the top of the content hierarchy, allows a single database to
 * be used for more than one repoting task. Initially the web application will
 * only need to provide support for one questionnaire at a time, but should allow
 * for expansion to multi-questionnaire support. The BITS Shared Assessment SIG is
 * one instance of questionnaire. 

 * The combination of questionnaireName and questionnaireVersion must be unique.
 */

class CreateTableQuestionnaire extends Migration {

  public function up() {
    $this->createTable('questionnaire', array('primary' => 'questionnaireID'), array(
      array('questionnaireID', 'integer'),
      array('questionnaireName', 'string', array('limit' => 100, 'null' => true)),
      array('questionnaireVersion', 'string', array('limit' => 20, 'null' => true)),
      array('revision', 'integer'),
      array('signature', 'string', array('limit' => 32, 'null' => true))
    ));
  }

  public function down() {
    $this->dropTable('questionnaire');
  }
}
