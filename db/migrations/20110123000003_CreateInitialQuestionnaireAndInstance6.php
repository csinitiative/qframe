<?php
class CreateInitialQuestionnaireAndInstance6 extends Migration {

  public function up() {
    // reset db metadata cache
    QFrame_Db_Table::scanDb();
    QFrame_Db_Table::resetAll();

    // If the questionnaire already exists, don't run the migration
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $rows = $adapter->select()->from('questionnaire')->where('questionnaireName = ? AND questionnaireVersion = ?', 'CSI SIG', '6.0')->query()->fetchall();
    if (count($rows) > 0) return true;

    $this->auth();
    $xml = file_get_contents(_path(PROJECT_PATH, 'xml', 'sig-6-0-questionnaire-definition.xml'));
    QuestionnaireModel::importXML($xml);
    InstanceModel::importXML($xml, 'Sample SIG 6 Instance', array(), 1);
  }

  public function down() {
    $this->auth();
    try {
      $instance = new InstanceModel(array(
        'questionnaireName'    => 'CSI SIG',
        'questionnaireVersion' => '6.0',
        'revision'             => '0',
        'instanceName'         => 'Sample SIG 6 Instance'
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
    // create an auth adapter that will auto-grant admin rights
    $authAdapter = new QFrame_Auth_Adapter('', '', true);

    $auth = Zend_Auth::getInstance();
    if(!$auth->authenticate($authAdapter)->isValid()) {
      throw new Exception('Authentication failed');
    }
  }
}
