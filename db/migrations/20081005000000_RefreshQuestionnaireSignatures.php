<?php
class RefreshQuestionnaireSignatures extends Migration {

  public function up() {
    $this->refreshSignatures();
  }
  
  public function down() {
    $this->refreshSignatures();
  }
  
  /**
   * Refresh all questionnaire signatures
   */
  private function refreshSignatures() {
    $this->auth();
    $questionnaires = QuestionnaireModel::getAllQuestionnaires();
    foreach ($questionnaires as $questionnaire) {
      $questionnaire->refreshSignature();
    }
    QFrame_Db_Table::reset('questionnaire');
  }

  /**
   * Authenticate
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
