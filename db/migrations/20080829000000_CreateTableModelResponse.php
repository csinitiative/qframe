<?php
class CreateTableModelResponse extends Migration {

  public function up() {
    $this->createTable('model_response', array('primary' => 'modelResponseID'), array(
      array('modelResponseID', 'integer'),
      array('modelID', 'integer', array('null' => false)),
      array('pageID', 'integer', array('null' => false)),
      array('sectionID', 'integer', array('null' => false)),
      array('questionID', 'integer', array('null' => false)),
      array('type', 'string', array('limit' => 50, 'null' => false)), // 'no preference', 'match', 'selected', 'not selected', 'remediation info'
      array('target', 'string', array('limit' => 255, 'null' => false)) // if match (questionType T), then text to match. If single or multi select, the promptID.
    ));
  }

  public function down() {
    $this->dropTable('model_response');
  }
}
