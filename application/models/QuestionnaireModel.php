<?php
/**
 * This file is part of the CSI QFrame.
 *
 * The CSI QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along wit. $this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */

/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class QuestionnaireModel extends QFrame_Db_SerializableTransaction implements QFrame_Storer {

  private $questionnaireRow;
  private $instances;
  private $Index;
  private $depth;
  static $questionnaireTable;
  static $instanceTable;

  function __construct ($args = array()) {

    $args = array_merge(array(
      'depth'   => 'questionnaire'
    ), $args);

    if (!isset(self::$questionnaireTable)) self::$questionnaireTable = QFrame_Db_Table::getTable('questionnaire');
    if (!isset(self::$instanceTable)) self::$instanceTable = QFrame_Db_Table::getTable('instance');

    if (isset($args['questionnaireID'])) {
      $where = self::$questionnaireTable->getAdapter()->quoteInto('questionnaireID = ?', intVal($args['questionnaireID']));
      $this->questionnaireRow = self::$questionnaireTable->fetchRow($where);
    }
    elseif (isset($args['questionnaireName']) && isset($args['questionnaireVersion']) && isset($args['revision'])) {
      $adapter = self::$questionnaireTable->getAdapter();
      $where = $adapter->quoteInto('questionnaireName = ?', $args['questionnaireName']) . ' AND ' .
          $adapter->quoteInto('questionnaireVersion = ?', $args['questionnaireVersion']) . ' AND ' .
          $adapter->quoteInto('revision = ?', $args['revision']);
      $this->questionnaireRow = self::$questionnaireTable->fetchRow($where);
    }
    else {
      throw new InvalidArgumentException('Missing arguments to QuestionnaireModel constructor');
    }

    // questionnaire row assertion
    if ($this->questionnaireRow === NULL) {
      throw new Exception('Questionnaire not found');
    }
    
    if ($args['depth'] !== 'questionnaire') {
      $this->depth = $args['depth'];
      $this->_loadInstances();
    }
    
  }

  public function __get($key) {

    if (isset($this->questionnaireRow->$key)) {
      return $this->questionnaireRow->$key;
    }
    else {
      throw new Exception("Attribute not found [$key]");
    }

  }

  /**
   * Saves instances and its descendants until 'depth' is reached
   */
  public function save() {

    if (count($this->instances)) {
      foreach ($this->instances as $instance) {
        $instance->save();
      }
    }
    
    if ($this->depth !== 'questionnaire') $this->_loadInstances();
  }

  /**
   * Returns the next instance
   * 
   * @return InstanceModel object
   */
  public function nextInstance() {
    $nextInstance = each($this->instances);
    if(!$nextInstance) return;

    return $nextInstance['value'];
  }

  /**
   * Returns a specific instance associated with this questionnaire
   * 
   * @param integer instanceID
   * @return InstanceModel object
   */
  public function getInstance($id) {
    foreach($this->instances as $instance) {
      if($instance->instanceID == $id) return $instance;
    }
    return;
  }

  /**
   * Return the last instance associated with this questionnaire
   *
   * @return InstanceModel object
   */
  public function getFirstInstance() {
    return current(array_slice($this->instances, 0, 1));
  }

  /**
   * Return the first instance associated with this questionnaire
   *
   * @return InstanceModel object
   */
  public function getLastInstance() {
    return current(array_slice($this->instances, -1, 1));
  }
  
  /**
   * Return an ID that is guaranteed to be unique among objects of type InstanceModel
   *
   * @return integer
   */
  public function getID() {
    return $this->questionnaireID;
  }

  /**
   * Return the content of the questionnaire definition associated with this questionnaire
   *
   * @return string XML document
   */
  public function fetchQuestionnaireDefinition() {
    $fileModel = new FileModel($this);
    $files = $fileModel->fetchAllProperties();
    foreach ($files as $id => $properties) {
      if ($properties['filename'] === 'questionnaire-definition.xml') {
        return $fileModel->fetch($id);
      }
    }
    throw new Exception('Questionnaire definition not found');
  }
  
  /**
   * Return the content of the Response XML Schema associated with this questionnaire
   *
   * @return string XML Schema document
   */
  public function fetchResponseSchema() {
    $fileModel = new FileModel($this);
    $files = $fileModel->fetchAllProperties();
    foreach ($files as $id => $properties) {
      if ($properties['filename'] === 'response-schema.xsd') {
        return $fileModel->fetch($id);
      }
    }
    throw new Exception('Response schema not found');
  }
  
  /**
   * Return the content of the Completed Response XML Schema associated with this questionnaire
   *
   * @return string XML Schema document
   */
  public function fetchCompletedResponseSchema() {
    $fileModel = new FileModel($this);
    $files = $fileModel->fetchAllProperties();
    foreach ($files as $id => $properties) {
      if ($properties['filename'] === 'completed-response-schema.xsd') {
        return $fileModel->fetch($id);
      }
    }
    throw new Exception('Completed response schema not found');
  }
  
  public static function importXML(&$import, $options = array()) {
    if (!isset(self::$questionnaireTable)) self::$questionnaireTable = QFrame_Db_Table::getTable('questionnaire');
    
    libxml_use_internal_errors(true);
    
    if (is_a($import, 'ZipArchiveModel')) {
      $zip = &$import;
      $xml = $import->getQuestionnaireDefinitionXMLDocument();
      if ($xml === NULL) $xml = $import->getInstanceFullResponsesXMLDocument();
      if ($xml === NULL) throw new Exception('Questionnaire definition not found in zip archive');
      $dom = new DOMDocument();
      $dom->loadXML($xml);
    }
    elseif (is_a($import, 'DOMDocument')) {
      $dom = &$import;
      $xml = $dom->saveXML();
    }
    else {
      $xml = &$import;
      $dom = new DOMDocument();
      $dom->loadXML($xml);
    }

    $errors = libxml_get_errors();
    try {
      $logger = Zend_Registry::get('logger');
    }
    catch (Zend_Exception $e) {}

    foreach ($errors as $error) {
      $message = rtrim("XML error on line {$error->line} of {$error->file}: {$error->message}");
      if(isset($logger) && $logger) $logger->log($message, Zend_Log::ERR);
      error_log($message);
    }
    if(count($errors) > 0) throw new Exception('XML Exception');

    self::validateQuestionnaireDefinitionXML($dom);
    
    $questionnaire = $dom->getElementsByTagName('questionnaire')->item(0);
    $questionnaireName = $questionnaire->getAttribute('questionnaireName');
    $questionnaireVersion = $questionnaire->getAttribute('questionnaireVersion');
    $revision = $questionnaire->getAttribute('revision');
    
    $transactionNumber = self::startSerializableTransaction();

    if (isset($options['SkipQuestionnaireExistCheck'])) {
      $signature = 'nosignature';
    }
    else {
      $questionnaireID = self::$questionnaireTable->getQuestionnaireID($questionnaireName, $questionnaireVersion, $revision);
      $signature = self::generateSignature($dom);
      if (isset($questionnaireID)) {
        $prevQuestionnaire = new QuestionnaireModel(array('questionnaireID' => $questionnaireID,
                                                    'depth' => 'questionnaire'));
    
        if ($signature === $prevQuestionnaire->signature) {
          $message = "Skipping questionnaire import since {$questionnaireName} {$questionnaireVersion} {$revision} already exists and has the same signature";
          if(isset($logger) && $logger) $logger->log($message, Zend_Log::ERR);
          self::dbCommit($transactionNumber);
          return $questionnaireID;
        }
        else {
          throw new Exception('An questionnaire with the same name, version, and revision already exists with different content');
        }
      }
    }
  
    $questionnaireID = self::$questionnaireTable->insert(array('questionnaireName' => $questionnaireName,
                                                         'questionnaireVersion' => $questionnaireVersion,
                                                         'revision' => $revision,
                                                         'signature' => $signature));

    $questionnaire = new QuestionnaireModel(array('questionnaireID' => $questionnaireID,
                                            'depth' => 'questionnaire'));                                      
    $questionnaire->validateQuestionnaireDefinitionXML($dom);
    
    // store files
    if (!isset($options['SkipFileAttachments'])) {
      $questionnaire->questionnaireDefinition2responseSchema($dom);
      $questionnaire->questionnaireDefinition2completedResponseSchema($dom);
      $files = new FileModel($questionnaire);
      $files->store($xml, array('filename' => 'questionnaire-definition.xml'));
    }
    
    self::dbCommit($transactionNumber);

    return $questionnaireID;
  }
  
  private function validateQuestionnaireDefinitionXML($dom) {
    
    if (!$dom->schemaValidate(PROJECT_PATH . '/xml/csi-qframe-v1_0.xsd')) {
      $errors = libxml_get_errors();
      try {
        $logger = Zend_Registry::get('logger');
      }
      catch (Zend_Exception $e) {}
      foreach ($errors as $error) {
        $message = rtrim("XML error on line {$error->line} of {$error->file}: {$error->message}");
        if(isset($logger) && $logger) $logger->log($message, Zend_Log::ERR);
        error_log($message);
      }
      if(count($errors) > 0) throw new Exception('XML Validation Exception');
    }
  }
  
  private function questionnaireDefinition2responseSchema($dom) {
    
    $xsl = new DOMDocument();
    if (!$xsl->load(PROJECT_PATH . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'csi-qframe-questionnaire-definition-to-response-schema-v1_0.xsl')) {
      $errors = libxml_get_errors();
      try {
        $logger = Zend_Registry::get('logger');
      }
      catch (Zend_Exception $e) {}
      foreach ($errors as $error) {
        $message = rtrim("XSL XML error on line {$error->line} of {$error->file}: {$error->message}");
        if(isset($logger) && $logger) $logger->log($message, Zend_Log::ERR);
        error_log($message);
      }
      if(count($errors) > 0) throw new Exception('XSL XML Validation Exception');
    }

    $proc = new XSLTProcessor();
    $proc->importStyleSheet($xsl);

    $result = $proc->transformToXML($dom);

    $files = new FileModel($this);
    $files->store($result, array('filename' => 'response-schema.xsd'));
    
  }
  
  private function questionnaireDefinition2completedResponseSchema($dom) {
    
    $xsl = new DOMDocument();
    if (!$xsl->load(PROJECT_PATH . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'csi-qframe-questionnaire-definition-to-completed-response-schema-v1_0.xsl')) {
      $errors = libxml_get_errors();
      try {
        $logger = Zend_Registry::get('logger');
      }
      catch (Zend_Exception $e) {}
      foreach ($errors as $error) {
        $message = rtrim("XSL XML error on line {$error->line} of {$error->file}: {$error->message}");
        if(isset($logger) && $logger) $logger->log($message, Zend_Log::ERR);
        error_log($message);
      }
      if(count($errors) > 0) throw new Exception('XSL XML Validation Exception');
    }

    $proc = new XSLTProcessor();
    $proc->importStyleSheet($xsl);

    $result = $proc->transformToXML($dom);

    $files = new FileModel($this);
    $files->store($result, array('filename' => 'completed-response-schema.xsd'));
    
  }
  
  /**
   * Return all available questionnaires
   *
   * @return array IntrumentModel
   */
  public static function getAllQuestionnaires($depth = 'questionnaire') {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $select = $adapter->select()
        ->from(array('questionnaire' => 'questionnaire'), array('questionnaire.questionnaireID'))
        ->order(array('questionnaireName ASC', 'questionnaireVersion ASC'));
    $stmt = $adapter->query($select);
    $result = $stmt->fetchAll();
    $questionnaires = array();
    while (list($key, $val) = each($result)) {
      array_push($questionnaires, new QuestionnaireModel(array('questionnaireID' => $val['questionnaireID'],
                                                         'depth' => $depth)));
    }
    return $questionnaires;
  }
  
  /**
   * Deletes this instance
   */
  public function delete() {
    $where = self::$questionnaireTable->getAdapter()->quoteInto('questionnaireID = ?', intVal($this->questionnaireID));
    $transactionNumber = self::startSerializableTransaction();
    $this->_loadInstances();
    if (is_a($this->getFirstInstance(), 'InstanceModel')) {
      throw new Exception('Cannot delete questionnaire while instances exist');
    }
    self::$questionnaireTable->delete($where);
    self::dbCommit($transactionNumber);
  
    // Delete files after transaction to ensure a healthy state such that the worst case
    // scenario is that there may be orphaned files left on disk if the file operation
    // is not successful.
    $files = new FileModel($this);
    $files->deleteAll();
  }

  /**
   * Load instances associated with this questionnaire instance
   */
  private function _loadInstances() {
    $auth = Zend_Auth::getInstance();
    if($auth->hasIdentity()) $user = DbUserModel::findByUsername($auth->getIdentity());
    else throw new Exception("Hey, no loading instances without being logged in");
    
    $where = self::$questionnaireTable->getAdapter()->quoteInto('questionnaireID = ?', intVal($this->questionnaireID));
    $instanceRowset = self::$instanceTable->fetchAll($where, 'instanceName ASC');

    $this->instances = array();
    foreach ($instanceRowset as $iRow) {
      $instance = new InstanceModel(array(
        'instanceID' => $iRow->instanceID,
        'depth' => $this->depth
      ));
      $this->instances[] = $instance;
    }
    
    $this->instancesIndex = 0;
  }
  
  /**
   * Generates a signature for an questionnaire definition
   * 
   * @param questionnaire definition xml dom object
   * @return string md5 hash
   */
  public static function generateSignature($dom) {
    $questionnaireID = QuestionnaireModel::importXML($dom, array('SkipQuestionnaireExistCheck' => 1,
                                                           'SkipFileAttachments' => 1));
    $instanceID = InstanceModel::importXML($dom, '_generateSignature', array('questionnaireID' => $questionnaireID));
    $instance = new InstanceModel(array('instanceID' => $instanceID,
                                        'depth' => 'instance'));
    $questionnaire = new QuestionnaireModel(array('questionnaireID' => $questionnaireID,
                                            'depth' => 'questionnaire'));
    $signature = md5($instance->toXML(1));
    $instance->delete();
    $questionnaire->delete();
    return $signature;
  }

}
