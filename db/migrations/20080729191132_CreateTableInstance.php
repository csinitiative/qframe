<?php
/*
 * An questionnaire may have multiple versions over time. The SIG is currently at
 * version 2 and will rev by the of the year to version 3. Scripts may be 
 * needed to migrate data from version to version  * feasibility will depend
 * on the amount of structural change to an questionnaire from version-to-version.
 * Need to make the combination of questionnaireID and instanceName unique.
 */

class CreateTableInstance extends Migration {

  public function up() {
    $this->createTable('instance', array('primary' => 'instanceID'), array(
      array('questionnaireID', 'integer'),
      array('instanceID', 'integer'),
      array('instanceName', 'string', array('limit' => 100, 'null' => true)),
      array('instanceCurrent', 'string', array('limit' => 1, 'null' => true)),
      array('instanceDate', 'datetime', array('null' => 'true')),
      array('numQuestions', 'integer', array('default' => 0)),
      array('numComplete', 'integer', array('default' => 0)),
      array('numApproved', 'integer', array('default' => 0))
    ));
    $this->createIndex('instance', array('questionnaireID'));
  }

  public function down() {
    $this->dropTable('instance');
  }
}
