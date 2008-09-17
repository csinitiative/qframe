<?php
class AddHiddenColumnToInstance extends Migration {

  public function up() {
    // add the column
    $this->addColumn('instance', 'hidden', 'boolean', array('default' => 0));
    
    // reset db metadata cache
    QFrame_Db_Table::scanDb();
    QFrame_Db_Table::resetAll();
    
    // make sure that existing questionnaires have a default instance (and create one if not)
    $this->auth();
    foreach(QuestionnaireModel::getAllQuestionnaires('instance') as $questionnaire) {
      $default = $questionnaire->getDefaultInstance();
      if($default === null) {
        $xml = $questionnaire->fetchQuestionnaireDefinition();
        InstanceModel::importXML($xml, '_default_', array('hidden' => 1));
      }
    }
  }
  
  public function down() {
    // remove any default instances that exist
    $this->auth();
    foreach(QuestionnaireModel::getAllQuestionnaires('instance') as $questionnaire) {
      $default = $questionnaire->getDefaultInstance();
      if($default !== null) {
        $default->delete();
      }
    }

    // now actually remove the column
    $this->removeColumn('instance', 'hidden');
  }
  
  /**
   * Perform mock authentication
   */
  private function auth() {
    // create an auth adapter that will auto-grant admin rights
    $authAdapter = new QFrame_Auth_Adapter('', '', true);
    
    $auth = Zend_Auth::getInstance();
    if(!$auth->authenticate($authAdapter)->isValid()) {
      throw new Exception('Authentication failed');
    }
  }
}
