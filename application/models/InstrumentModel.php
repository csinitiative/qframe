<?php
/**
 * This file is part of the CSI RegQ.
 *
 * The CSI RegQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI RegQ is distributed in the hope that it will be useful,
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
class InstrumentModel extends RegQ_Db_SerializableTransaction implements RegQ_Storer {

  private $instrumentRow;
  private $instances;
  private $Index;
  private $depth;
  static $instrumentTable;
  static $instanceTable;

  function __construct ($args = array()) {

    $args = array_merge(array(
      'depth'   => 'instrument'
    ), $args);

    if (!isset(self::$instrumentTable)) self::$instrumentTable = RegQ_Db_Table::getTable('instrument');
    if (!isset(self::$instanceTable)) self::$instanceTable = RegQ_Db_Table::getTable('instance');

    if (isset($args['instrumentID'])) {
      $where = self::$instrumentTable->getAdapter()->quoteInto('instrumentID = ?', intVal($args['instrumentID']));
      $this->instrumentRow = self::$instrumentTable->fetchRow($where);
    }
    elseif (isset($args['instrumentName']) && isset($args['instrumentVersion']) && isset($args['revision'])) {
      $adapter = self::$instrumentTable->getAdapter();
      $where = $adapter->quoteInto('instrumentName = ?', $args['instrumentName']) . ' AND ' .
          $adapter->quoteInto('instrumentVersion = ?', $args['instrumentVersion']) . ' AND ' .
          $adapter->quoteInto('revision = ?', $args['revision']);
      $this->instrumentRow = self::$instrumentTable->fetchRow($where);
    }
    else {
      throw new InvalidArgumentException('Missing arguments to InstrumentModel constructor');
    }

    // instrument row assertion
    if ($this->instrumentRow === NULL) {
      throw new Exception('Instrument not found');
    }
    
    if ($args['depth'] !== 'instrument') {
      $this->depth = $args['depth'];
      $this->_loadInstances();
    }
    
  }

  public function __get($key) {

    if (isset($this->instrumentRow->$key)) {
      return $this->instrumentRow->$key;
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
    
    if ($this->depth !== 'instrument') $this->_loadInstances();
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
   * Returns a specific instance associated with this instrument
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
   * Return the last instance associated with this instrument
   *
   * @return InstanceModel object
   */
  public function getFirstInstance() {
    return current(array_slice($this->instances, 0, 1));
  }

  /**
   * Return the first instance associated with this instrument
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
    return $this->instrumentID;
  }

  /**
   * Return the content of the instrument definition associated with this instrument
   *
   * @return string XML document
   */
  public function fetchInstrumentDefinition() {
    $fileModel = new FileModel($this);
    $files = $fileModel->fetchAllProperties();
    foreach ($files as $id => $properties) {
      if ($properties['filename'] === 'instrument-definition.xml') {
        return $fileModel->fetch($id);
      }
    }
    throw new Exception('Instrument definition not found');
  }
  
  /**
   * Return the content of the Response XML Schema associated with this instrument
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
   * Return the content of the Completed Response XML Schema associated with this instrument
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
    if (!isset(self::$instrumentTable)) self::$instrumentTable = RegQ_Db_Table::getTable('instrument');
    
    libxml_use_internal_errors(true);
    
    if (is_a($import, 'ZipArchiveModel')) {
      $zip = &$import;
      $xml = $import->getInstrumentDefinitionXMLDocument();
      if ($xml === NULL) $xml = $import->getInstanceFullResponsesXMLDocument();
      if ($xml === NULL) throw new Exception('Instrument definition not found in zip archive');
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

    self::validateInstrumentDefinitionXML($dom);
    
    $instrument = $dom->getElementsByTagName('instrument')->item(0);
    $instrumentName = $instrument->getAttribute('instrumentName');
    $instrumentVersion = $instrument->getAttribute('instrumentVersion');
    $revision = $instrument->getAttribute('revision');
    
    $transactionNumber = self::startSerializableTransaction();

    if (isset($options['SkipInstrumentExistCheck'])) {
      $signature = 'nosignature';
    }
    else {
      $instrumentID = self::$instrumentTable->getInstrumentID($instrumentName, $instrumentVersion, $revision);
      $signature = self::generateSignature($dom);
      if (isset($instrumentID)) {
        $prevInstrument = new InstrumentModel(array('instrumentID' => $instrumentID,
                                                    'depth' => 'instrument'));
    
        if ($signature === $prevInstrument->signature) {
          $message = "Skipping instrument import since {$instrumentName} {$instrumentVersion} {$revision} already exists and has the same signature";
          if(isset($logger) && $logger) $logger->log($message, Zend_Log::ERR);
          self::dbCommit($transactionNumber);
          return $instrumentID;
        }
        else {
          throw new Exception('An instrument with the same name, version, and revision already exists with different content');
        }
      }
    }
  
    $instrumentID = self::$instrumentTable->insert(array('instrumentName' => $instrumentName,
                                                         'instrumentVersion' => $instrumentVersion,
                                                         'revision' => $revision,
                                                         'signature' => $signature));

    $instrument = new InstrumentModel(array('instrumentID' => $instrumentID,
                                            'depth' => 'instrument'));                                      
    $instrument->validateInstrumentDefinitionXML($dom);
    
    // store files
    if (!isset($options['SkipFileAttachments'])) {
      $instrument->instrumentDefinition2responseSchema($dom);
      $instrument->instrumentDefinition2completedResponseSchema($dom);
      $files = new FileModel($instrument);
      $files->store($xml, array('filename' => 'instrument-definition.xml'));
    }
    
    self::dbCommit($transactionNumber);

    return $instrumentID;
  }
  
  private function validateInstrumentDefinitionXML($dom) {
    
    if (!$dom->schemaValidate(PROJECT_PATH . '/xml/csi-regq-v1_0.xsd')) {
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
  
  private function instrumentDefinition2responseSchema($dom) {
    
    $xsl = new DOMDocument();
    if (!$xsl->load(PROJECT_PATH . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'csi-regq-questionnaire-definition-to-response-schema-v1_0.xsl')) {
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
  
  private function instrumentDefinition2completedResponseSchema($dom) {
    
    $xsl = new DOMDocument();
    if (!$xsl->load(PROJECT_PATH . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'csi-regq-questionnaire-definition-to-completed-response-schema-v1_0.xsl')) {
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
   * Return all available instruments
   *
   * @return array IntrumentModel
   */
  public static function getAllInstruments($depth = 'instrument') {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $select = $adapter->select()
        ->from(array('instrument' => 'instrument'), array('instrument.instrumentID'))
        ->order(array('instrumentName ASC', 'instrumentVersion ASC'));
    $stmt = $adapter->query($select);
    $result = $stmt->fetchAll();
    $instruments = array();
    while (list($key, $val) = each($result)) {
      array_push($instruments, new InstrumentModel(array('instrumentID' => $val['instrumentID'],
                                                         'depth' => $depth)));
    }
    return $instruments;
  }
  
  /**
   * Deletes this instance
   */
  public function delete() {
    $where = self::$instrumentTable->getAdapter()->quoteInto('instrumentID = ?', intVal($this->instrumentID));
    $transactionNumber = self::startSerializableTransaction();
    $this->_loadInstances();
    if (is_a($this->getFirstInstance(), 'InstanceModel')) {
      throw new Exception('Cannot delete instrument while instances exist');
    }
    self::$instrumentTable->delete($where);
    self::dbCommit($transactionNumber);
  
    // Delete files after transaction to ensure a healthy state such that the worst case
    // scenario is that there may be orphaned files left on disk if the file operation
    // is not successful.
    $files = new FileModel($this);
    $files->deleteAll();
  }

  /**
   * Load instances associated with this instrument instance
   */
  private function _loadInstances() {
    $auth = Zend_Auth::getInstance();
    if($auth->hasIdentity()) $user = DbUserModel::findByUsername($auth->getIdentity());
    else throw new Exception("Hey, no loading instances without being logged in");
    
    $where = self::$instrumentTable->getAdapter()->quoteInto('instrumentID = ?', intVal($this->instrumentID));
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
   * Generates a signature for an instrument definition
   * 
   * @param instrument definition xml dom object
   * @return string md5 hash
   */
  public static function generateSignature($dom) {
    $instrumentID = InstrumentModel::importXML($dom, array('SkipInstrumentExistCheck' => 1,
                                                           'SkipFileAttachments' => 1));
    $instanceID = InstanceModel::importXML($dom, '_generateSignature', array('instrumentID' => $instrumentID));
    $instance = new InstanceModel(array('instanceID' => $instanceID,
                                        'depth' => 'instance'));
    $instrument = new InstrumentModel(array('instrumentID' => $instrumentID,
                                            'depth' => 'instrument'));
    $signature = md5($instance->toXML(1));
    $instance->delete();
    $instrument->delete();
    return $signature;
  }

}
