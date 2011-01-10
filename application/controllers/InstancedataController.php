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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */

require_once(PROJECT_PATH . '/library/dompdf-0.5.1/dompdf_config.inc.php');

/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class InstancedataController extends QFrame_Controller_Admin {
    
  /**
   * Index action.  Presents the Instance Management page to the user.
   */
  public function indexAction() {
    $session = new Zend_Session_Namespace('login');
    $domainID = ($this->_hasParam('domain')) ? $this->_getParam('domain') : $session->dataDomainID;
    $questionnaireID = ($this->_hasParam('questionnaire')) ? $this->_getParam('questionnaire') : $session->dataQuestionnaireID;
    $instanceID = ($this->_hasParam('instance')) ? $this->_getParam('instance') : $session->dataInstanceID;


    if (is_numeric($domainID) && $domainID > 0) {
      $this->view->dataDomain = DomainModel::find($domainID);
    }
    else {
      $instanceID = null;
    }

    if (is_numeric($questionnaireID) && $questionnaireID > 0) {
      $this->view->dataQuestionnaire = new QuestionnaireModel(array('questionnaireID' => $questionnaireID,
                                                                    'depth' => 'questionnaire'));
    }
    else {
      $questionnaireID = null;
    }

    if (is_numeric($instanceID) && $instanceID > 0) {
      $this->view->dataInstance = new InstanceModel(array('instanceID' => $instanceID,
                                                          'depth' => 'page'));
    }
    else {
      $instanceID = null;
    }

    $session->dataDomainID = $domainID;
    $session->dataQuestionnaireID = $questionnaireID;
    $session->dataInstanceID = $instanceID;
    $this->view->dataDomainID = $session->dataDomainID;
    $this->view->dataQuestionnaireID = $session->dataQuestionnaireID;
    $this->view->dataInstanceID = $session->dataInstanceID;

    $questionnaires = QuestionnaireModel::getAllQuestionnaires('page');
    $allowedInstances = array();
    foreach($questionnaires as $questionnaire) {
      while($instance = $questionnaire->nextInstance()) {
        while($page = $instance->nextPage()) {
          if($this->_user->hasAnyAccess($page) ||
             $this->_user->hasAnyAccess($page->parent->domain)) {
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
    $domainID = ($this->_hasParam('importResponsesDomainSelect')) ? $this->_getParam('importResponsesDomainSelect') : $session->importResponsesDomainID;
    $instanceID = ($this->_hasParam('importResponsesInstanceSelect')) ? $this->_getParam('importResponsesInstanceSelect') : $session->importResponsesInstanceID;
    $questionnaireID = ($this->_hasParam('importResponsesQuestionnaireSelect')) ? $this->_getParam('importResponsesQuestionnaireSelect') : $session->importResponsesQuestionnaireID;
    $session->importResponsesDomainID = $domainID;
    $session->importResponsesQuestionnaireID = $questionnaireID;
    $session->importResponsesInstanceID = $instanceID;
    $this->view->importResponsesDomainID = $domainID;
    $this->view->importResponsesInstanceID = $instanceID;
    $this->view->importResponsesQuestionnaireID = $questionnaireID;
    
    // new instance import responses
    $radioButton = $this->_getParam('newInstanceImportResponsesRadioButton');
    $this->view->newInstanceImportResponsesRadioButton = $radioButton;
    $domainID = ($this->_hasParam('newInstanceResponsesDomainSelect')) ? $this->_getParam('newInstanceResponsesDomainSelect') : $session->newInstanceResponsesDomainID;
    $instanceID = ($this->_hasParam('newInstanceResponsesInstanceSelect')) ? $this->_getParam('newInstanceResponsesInstanceSelect') : $session->newInstanceResponsesInstanceID;
    $questionnaireID = ($this->_hasParam('newInstanceResponsesQuestionnaireSelect')) ? $this->_getParam('newInstanceResponsesQuestionnaireSelect') : $session->newInstanceResponsesQuestionnaireID;
    $session->newInstanceResponsesDomainID = $domainID;
    $session->newInstanceResponsesQuestionnaireID = $questionnaireID;
    $session->newInstanceResponsesInstanceID = $instanceID;
    $this->view->newInstanceResponsesDomainID = $domainID;
    $this->view->newInstanceResponsesInstanceID = $instanceID;
    $this->view->newInstanceResponsesQuestionnaireID = $questionnaireID;
    
    $this->view->cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    $this->view->decryptID = ($this->_hasParam('decryptID')) ? $this->_getParam('decryptID') : null;
    
  }

  /**
   * Action for copying an instance
   */
  public function copyinstanceAction() {
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
    InstanceModel::importXML($zip, $instanceName, array('pageResponses' => array('all' => 1)));
    $zip->deleteZipFile();
    
    $this->flash('notice', 'Copy Complete');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
  
  /**
   * Action for creating a new instance from an existing questionnaire
   */
  public function newinstanceAction() {
    $session = new Zend_Session_Namespace('login');
    $instanceID = $session->newInstanceResponsesInstanceID;
    
    $instanceName = $this->_getParam('instanceName');
    $importResponses = $this->_getParam('newInstanceImportResponsesRadioButton');
    $domainID = $session->dataDomainID;
    
    if(is_numeric($instanceID)) {
      $session->newInstanceResponsesInstanceID = intVal($instanceID);
    }
    elseif (isset($importResponses) && $importResponses === 'newInstanceImportInstanceResponses') {
      $this->_redirector->gotoRouteAndExit(array('action' => 'index'));
    }
    
    $questionnaire = new QuestionnaireModel(array('questionnaireID' => $session->dataQuestionnaireID,
                                            'depth' => 'questionnaire'));

    if ($importResponses === 'newInstanceImportInstanceResponses') {
      $importResponsesInstanceID = $this->_getParam('newInstanceResponsesInstanceSelect');
      InstanceModel::importXML($questionnaire->fetchQuestionnaireDefinition(), $instanceName, array('instanceID' => $importResponsesInstanceID), $domainID);
    }
    else {
      InstanceModel::importXML($questionnaire->fetchQuestionnaireDefinition(), $instanceName, array(), $domainID);
    }
    
    $this->flash('notice', 'New Instance Created');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
    
  /**
   * Action for deleting an instance
   */
  public function deleteinstanceAction() {
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
  public function importinstanceAction() {
    $session = new Zend_Session_Namespace('login');
    $instanceID = $session->importResponsesInstanceID;
    
    $instanceName = $this->_getParam('instanceName');
    $importResponses = $this->_getParam('importResponsesRadioButton');
    
    $decryptID = ($this->_hasParam('decryptID')) ? $this->_getParam('decryptID') : null;
    
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
    
    if (preg_match('/\.enc\.zip$/i', $filename)) { // Encrypted zip
      if (!isset($decryptID) || $decryptID == 0) {
        throw new Exception('Key not specified for encrypted file');
      }
      $crypto = new CryptoModel(array('cryptoID' => $decryptID));
      if (preg_match('/\.xml.enc.zip$/i', $filename)) { // Encrypted zip consisting of just the xml
        $decrypted = $crypto->decrypt(file_get_contents($file), 'instance-responses.xml');
        $import = $decrypted;
      }
      elseif (preg_match('/\.enc\.zip$/i', $filename)) { // Encrypted zip consisting of xml and attachments
        $decrypted = $crypto->decrypt(file_get_contents($file), 'instance-responses.zip');
        $tempfile = tempnam(PROJECT_PATH . DIRECTORY_SEPARATOR . 'tmp', 'zip');
        unlink($tempfile);
        file_put_contents($tempfile, $decrypted);
        $import = new ZipArchiveModel(null, array('filename' => $tempfile));
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
  
    // Import the questionnaire definition if it doesn't already exist    
    QuestionnaireModel::importXML($import);

    if ($importResponses === 'importInstanceResponses') { 
      $importResponsesInstanceID = $this->_getParam('importResponsesInstanceSelect');
      InstanceModel::importXML($import, $instanceName, array('instanceID' => $importResponsesInstanceID));
    }
    elseif ($importResponses === 'importXMLResponses') {
      InstanceModel::importXML($import, $instanceName, array('pageResponses' => array('all' => 1)));
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
   
  public function responsesonlyxmldownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                        'depth' => 'instance'));
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->xml = $crypto->encrypt($instance->toXML(), 'instance-responses.xml');
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->xml = $instance->toXML();
    }
    $this->view->setRenderLayout(false);
  }
  
  public function responsesonlyxmlarchiveAction() {
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
      $this->view->archive = $crypto->encrypt($zip->getZipFileContents(), 'instance-responses.zip');
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->archive = $zip->getZipFileContents();
    }
    $zip->deleteZipFile();
    $this->view->setRenderLayout(false);
  }
   
  public function responsesfullxmldownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if ($this->_hasParam('download') && isset($session->tempFile)) {
      if (isset($cryptoID) && $cryptoID != 0) {
        $this->view->cryptoID = $cryptoID;
      }
      $this->view->xml = file_get_contents($session->tempFile);
      unlink($session->tempFile);
      unset($session->tempFile);
    }
    else {
      $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                          'depth' => 'instance'));
      $tempFile = tempnam(PROJECT_PATH . DIRECTORY_SEPARATOR . 'tmp', 'exp');
      $xml = '';
      if (isset($cryptoID) && $cryptoID != 0) {
        $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
        $xml = $crypto->encrypt($instance->toXML(1), 'instance-responses.xml');
        $this->view->cryptoID = $cryptoID;
      }
      else {
        $xml = $instance->toXML(1);
      }
      file_put_contents($tempFile, $xml);
      $session->tempFile = $tempFile;
    }
    $this->view->setRenderLayout(false);
  }
  
  public function responsesfullxmlarchiveAction() {
    $session = new Zend_Session_Namespace('login');
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if ($this->_hasParam('download') && isset($session->tempFile)) {
      if (isset($cryptoID) && $cryptoID != 0) {
        $this->view->cryptoID = $cryptoID;
      }
      $this->view->archive = file_get_contents($session->tempFile);
      unlink($session->tempFile);
      unset($session->tempFile);
    }
    else {
      $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                          'depth' => 'instance'));
      $zip = new ZipArchiveModel($instance, array('new' => 1));
      $zip->addInstanceFullResponsesXMLDocument();
      $zip->addAttachments();
      $zip->close();
      $tempFile = tempnam(PROJECT_PATH . DIRECTORY_SEPARATOR . 'tmp', 'exp');
      $archive = '';
      if (isset($cryptoID) && $cryptoID != 0) {
        $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
        $archive = $crypto->encrypt($zip->getZipFileContents(), 'instance-responses.zip');
        $this->view->cryptoID = $cryptoID;
      }
      else {
        $archive = $zip->getZipFileContents();
      }
      $zip->deleteZipFile();
      file_put_contents($tempFile, $archive);
      $session->tempFile = $tempFile;
    }
    $this->view->setRenderLayout(false);
  }

  public function pdfexportAction() {
    $session = new Zend_Session_Namespace('login');
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    $pageHeadersAll = ($this->_hasParam('pageHeader')) ? $this->_getParam('pageHeader') : array();
    $pageHeaders = array();
    while (list($key, $val) = each($pageHeadersAll)) {
      if (isset($val['pdf']) && $val['pdf'] == 1) { 
        $pageHeaders[] = $key;
      }
    }
    if ($this->_hasParam('download') && isset($session->tempFile)) {
      if (isset($cryptoID) && $cryptoID != 0) {
        $this->view->cryptoID = $cryptoID;
      }
      $this->view->pdf = file_get_contents($session->tempFile);
      unlink($session->tempFile);
      unset($session->tempFile);
    }
    else {
      $instance = new InstanceModel(array('instanceID' => $session->dataInstanceID,
                                          'depth' => 'instance'));

     
      $footer1 = $footer2 = $coverText = $coverImage = null;

      $uploadErrors = array(
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
      );

      if ($this->_hasParam('footer1')) {
        $footer1 = $this->_getParam('footer1');
      }
      if ($this->_hasParam('footer2')) {
        $footer2 = $this->_getParam('footer2');
      }
      if ($this->_hasParam('coverText')) {
        $coverText = $this->_getParam('coverText');
      }
      if (isset($_FILES['pdfCoverImage'])) {
        $errorCode = $_FILES['pdfCoverImage']['error'];
        if ($errorCode !== UPLOAD_ERR_NO_FILE && $errorCode !== UPLOAD_ERR_OK) {
          if (isset($uploadErrors[$errorCode]))
            throw new Exception($uploadErrors[$errorCode]);
          else
            throw new Exception("Error uploading image file");
        }
        if ($errorCode == UPLOAD_ERR_OK) {
          $imgFile = $_FILES['pdfCoverImage']['tmp_name'];
          $imgFilename = $_FILES['pdfCoverImage']['name'];
          $imgExtension = preg_replace('/.+\./', '', $imgFilename);
          $coverImage = "{$imgFile}.{$imgExtension}";
          move_uploaded_file($imgFile, $coverImage); 
        }
      }

      $pdf = $instance->toPDF($pageHeaders, $footer1, $footer2, $coverText, $coverImage);
      if (isset($cryptoID) && $cryptoID != 0) {
        $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
        $pdf = $crypto->encrypt($pdf, 'instance-responses.pdf');
        $this->view->cryptoID = $cryptoID;
      }
      $tempFile = tempnam(PROJECT_PATH . DIRECTORY_SEPARATOR . 'tmp', 'exp');
      file_put_contents($tempFile, $pdf);
      $session->tempFile = $tempFile;
    }
    $this->view->setRenderLayout(false);
  }
    
}
