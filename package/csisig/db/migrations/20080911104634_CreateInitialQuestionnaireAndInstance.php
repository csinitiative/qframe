<?php
class CreateInitialQuestionnaireAndInstance extends Migration {

  public function up() {
    $this->auth();
    $xml = file_get_contents(_path(PROJECT_PATH, 'xml', 'sig-3-1-questionnaire-definition.xml'));
    QuestionnaireModel::importXML($xml);
    InstanceModel::importXML($xml, 'Sample SIG Instance');
  }
  
  public function down() {
    $this->auth();
    try {
      $instance = new InstanceModel(array(
        'questionnaireName'    => 'CSI SIG',
        'questionnaireVersion' => '3.1',
        'revision'             => '1',
        'instanceName'         => 'Sample SIG Instance'
      ));
      $questionnaire = $instance->parent;
      $instance->delete();
      $questionnaire->delete();
    }
    catch(Exception $e) {}
  }
  
  /**
   * Authenticate
   */
  private function auth() {
    $authAdapter = new QFrame_Auth_Adapter('admin', 'admin');
    $auth = Zend_Auth::getInstance();
    if(!$auth->authenticate($authAdapter)->isValid()) {
      throw new Exception('Authentication failed');
    }
  }
}
