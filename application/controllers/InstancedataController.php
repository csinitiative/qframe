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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class InstancedataController extends RegQ_Controller_Admin {
    
  /**
   * Index action.  Presents the Instance Management page to the user.
   */
  public function indexAction() {
    $session = new Zend_Session_Namespace('login');
    $instrumentID = ($this->_hasParam('instrument')) ? $this->_getParam('instrument') : $session->dataInstrumentID;
    $instanceID = ($this->_hasParam('instance')) ? $this->_getParam('instance') : $session->dataInstanceID;

    if (is_numeric($instrumentID) && $instrumentID > 0) {
      $this->view->dataInstrument = new InstrumentModel(array('instrumentID' => $instrumentID,
                                                              'depth' => 'instrument'));
    }
    else {
      $instrumentID = null;
    }

    if (is_numeric($instanceID) && $instanceID > 0) {
      $this->view->dataInstance = new InstanceModel(array('instanceID' => $instanceID,
                                                          'depth' => 'instance'));
    }
    else {
      $instanceID = null;
    }

    $session->dataInstrumentID = $instrumentID;
    $session->dataInstanceID = $instanceID;
    $this->view->dataInstrumentID = $session->dataInstrumentID;
    $this->view->dataInstanceID = $session->dataInstanceID;

    $instruments = InstrumentModel::getAllInstruments('tab');
    $allowedInstances = array();
    foreach($instruments as $instrument) {
      while($instance = $instrument->nextInstance()) {
        while($tab = $instance->nextTab()) {
          if($this->_user->hasAnyAccess($tab)) {
            $allowedInstances[] = $instance;
            break;
          }
        }
      }
    }
    $this->view->dataInstances = $allowedInstances;

    // import xml import responses
    $radioButton = $this->_getParam('importResponsesRadioButton');
    $this->view->importResponsesRadioButton = $radioButton;
    $instanceID = ($this->_hasParam('importResponsesInstanceSelect')) ? $this->_getParam('importResponsesInstanceSelect') : $session->importResponsesInstanceID;
    $instrumentID = ($this->_hasParam('importResponsesInstrumentSelect')) ? $this->_getParam('importResponsesInstrumentSelect') : $session->importResponsesInstrumentID;
    $session->importResponsesInstrumentID = $instrumentID;
    $session->importResponsesInstanceID = $instanceID;
    $this->view->importResponsesInstanceID = $instanceID;
    $this->view->importResponsesInstrumentID = $instrumentID;
    
    // new instance import responses
    $radioButton = $this->_getParam('newInstanceImportResponsesRadioButton');
    $this->view->newInstanceImportResponsesRadioButton = $radioButton;
    $instanceID = ($this->_hasParam('newInstanceResponsesInstanceSelect')) ? $this->_getParam('newInstanceResponsesInstanceSelect') : $session->newInstanceResponsesInstanceID;
    $instrumentID = ($this->_hasParam('newInstanceResponsesInstrumentSelect')) ? $this->_getParam('newInstanceResponsesInstrumentSelect') : $session->newInstanceResponsesInstrumentID;
    $session->newInstanceResponsesInstrumentID = $instrumentID;
    $session->newInstanceResponsesInstanceID = $instanceID;
    $this->view->newInstanceResponsesInstanceID = $instanceID;
    $this->view->newInstanceResponsesInstrumentID = $instrumentID;
    
    $this->view->cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    
  }

  /**
   * Action for copying an instance
   */
  public function copyInstanceAction() {
    $session = new Zend_Session_Namespace('login');
    $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                        'depth' => 'instance'));
    $instanceName = $this->_getParam('instanceName');
    
    $zip = new ZipArchiveModel($instance, array('new' => '1'));
    $zip->addInstanceFullResponsesXMLDocument();
    $zip->addAttachments();
    $zip->close();
    $filename = $zip->getZipFileName();
    $zip = new ZipArchiveModel(null, array('filename' => $filename));
    InstanceModel::importXML($zip, $instanceName, array('tabResponses' => array('all' => 1)));
    $zip->deleteZipFile();
    
    $this->flash('notice', 'Copy Complete');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
  
  /**
   * Action for creating a new instance from an existing instrument
   */
  public function newInstanceAction() {
    $session = new Zend_Session_Namespace('login');
    $instanceID = $session->newInstanceResponsesInstanceID;
    
    $instanceName = $this->_getParam('instanceName');
    $importResponses = $this->_getParam('newInstanceImportResponsesRadioButton');
    
    if(is_numeric($instanceID)) {
      $session->newInstanceResponsesInstanceID = intVal($instanceID);
    }
    elseif (isset($importResponses) && $importResponses === 'newInstanceImportInstanceResponses') {
      $this->_redirector->gotoRouteAndExit(array('action' => 'index'));
    }
    
    $instrument = new InstrumentModel(array('instrumentID' => $session->dataInstrumentID,
                                            'depth' => 'instrument'));
    
    if ($importResponses === 'newInstanceImportInstanceResponses') { 
      $importResponsesInstanceID = $this->_getParam('newInstanceResponsesInstanceSelect');
      InstanceModel::importXML($instrument->fetchInstrumentDefinition(), $instanceName, array('instanceID' => $importResponsesInstanceID));
    }
    else {
      InstanceModel::importXML($instrument->fetchInstrumentDefinition(), $instanceName);
    }
    
    $this->flash('notice', 'New Instance Created');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
    
  /**
   * Action for deleting an instance
   */
  public function deleteInstanceAction() {
    $session = new Zend_Session_Namespace('login');
    $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                        'depth' => 'instance'));
    $instance->delete();
    if ($session->instanceID == $session->dataInstanceID) {
      unset($session->instanceID);
    }
    unset($session->dataInstanceID);
    $this->flash('notice', 'Deletion Complete');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }

  /**
   * Action for importing an instance
   */
  public function importInstanceAction() {
    $session = new Zend_Session_Namespace('login');
    $instanceID = $session->importResponsesInstanceID;
    
    $instanceName = $this->_getParam('instanceName');
    $importResponses = $this->_getParam('importResponsesRadioButton');
    
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    
    if(is_numeric($instanceID)) {
      $session->importResponsesInstanceID = intVal($instanceID);
    }
    elseif (isset($importResponses) && $importResponses === 'importInstanceResponses') {
      $this->_redirector->gotoRouteAndExit(array('action' => 'index'));
    }

    $uploadErrors = array(
      UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
      UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
      UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
      UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
      UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
      UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
      UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
    );
    $errorCode = $_FILES['instanceFile']['error'];
    if ($errorCode !== UPLOAD_ERR_OK) {
      if (isset($uploadErrors[$errorCode]))
        throw new Exception($uploadErrors[$errorCode]);
      else
        throw new Exception("Unknown error uploading file.");
    }

    $file = $_FILES['instanceFile']['tmp_name'];
    $filename = $_FILES['instanceFile']['name'];
    
    if (preg_match('/\.enc$/i', $filename)) {
      if (!isset($cryptoID) || $cryptoID == 0) {
        throw new Exception('Key not specified for encrypted file');
      }
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      if (preg_match('/\.zip\.enc$/i', $filename)) {
        $decrypted = $crypto->decrypt(file_get_contents($file), false);
        $tempfile = tempnam(PROJECT_PATH . DIRECTORY_SEPARATOR . 'tmp', 'zip');
        unlink($tempfile);
        file_put_contents($tempfile, $decrypted);
        $import = new ZipArchiveModel(null, array('filename' => $tempfile));
      }
      elseif (preg_match('/\.xml.enc$/i', $filename)) {
        $decrypted = $crypto->decrypt(file_get_contents($file));
        $import = $decrypted;
      }
      else {
        throw new Exception('Unrecognized file extension [' . $filename . ']');
      }
    }
    elseif (preg_match('/\.zip$/i', $filename)) {
      $import = new ZipArchiveModel(null, array('filename' => $file));
    }
    elseif (preg_match('/\.xml$/i', $filename)) {
      $import = file_get_contents($file);
    }
    else {
      throw new Exception('Unrecognized file extension [' . $filename . ']');
    }
  
    // Import the instrument definition if it doesn't already exist    
    InstrumentModel::importXML($import);

    if ($importResponses === 'importInstanceResponses') { 
      $importResponsesInstanceID = $this->_getParam('importResponsesInstanceSelect');
      InstanceModel::importXML($import, $instanceName, array('instanceID' => $importResponsesInstanceID));
    }
    elseif ($importResponses === 'importXMLResponses') {
      InstanceModel::importXML($import, $instanceName, array('tabResponses' => array('all' => 1)));
    }
    else {
      InstanceModel::importXML($import, $instanceName);
    }
    $this->flash('notice', 'Import Complete');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
    
  /**
   * Export actions
   */
   
  public function ResponsesOnlyXMLDownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                        'depth' => 'instance'));
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->xml = $crypto->encrypt($instance->toXML());
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->xml = $instance->toXML();
    }
    $this->view->setRenderLayout(false);
  }
  
  public function ResponsesOnlyXMLArchiveAction() {
    $session = new Zend_Session_Namespace('login');
    $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                        'depth' => 'instance'));
    $zip = new ZipArchiveModel($instance, array('new' => 1));
    $zip->addInstanceResponsesXMLDocument();
    $zip->addAttachments();
    $zip->close();
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->archive = $crypto->encrypt($zip->getZipFileContents());
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->archive = $zip->getZipFileContents();
    }
    $zip->deleteZipFile();
    $this->view->setRenderLayout(false);
  }
   
  public function ResponsesFullXMLDownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                        'depth' => 'instance'));
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->xml = $crypto->encrypt($instance->toXML(1));
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->xml = $instance->toXML(1);
    }
    $this->view->setRenderLayout(false);      
  }
  
  public function ResponsesFullXMLArchiveAction() {
    $session = new Zend_Session_Namespace('login');
    $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                        'depth' => 'instance'));
    $zip = new ZipArchiveModel($instance, array('new' => 1));
    $zip->addInstanceFullResponsesXMLDocument();
    $zip->addAttachments();
    $zip->close();
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->archive = $crypto->encrypt($zip->getZipFileContents());
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->archive = $zip->getZipFileContents();
    }
    $zip->deleteZipFile();
    $this->view->setRenderLayout(false);
  }
    
}
