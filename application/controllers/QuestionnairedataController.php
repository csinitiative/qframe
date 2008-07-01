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
class QuestionnaireDataController extends RegQ_Controller_Admin {

  /**
   * Index action.  Presents the Questionnaire Management page to the user.
   */
  public function indexAction() {
    $session = new Zend_Session_Namespace('login');
    $questionnaireID = ($this->_hasParam('questionnaire')) ? $this->_getParam('questionnaire') : $session->dataQuestionnaireID;

    if (is_numeric($questionnaireID) && $questionnaireID > 0) {
      $this->view->dataQuestionnaire = new QuestionnaireModel(array('questionnaireID' => $questionnaireID,
                                                              'depth' => 'questionnaire'));
    }
    else {
      $questionnaireID = null;
    }

    $session->dataQuestionnaireID = $questionnaireID;
    $this->view->dataQuestionnaireID = $session->dataQuestionnaireID;

    $questionnaires = QuestionnaireModel::getAllQuestionnaires('tab');
    $allowedInstances = array();
    foreach($questionnaires as $questionnaire) {
      while($instance = $questionnaire->nextInstance()) {
        while($tab = $instance->nextTab()) {
          if($this->_user->hasAnyAccess($tab)) {
            $allowedInstances[] = $instance;
            break;
          }
        }
      }
    }
    $this->view->dataInstances = $allowedInstances;
    
    $this->view->cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    
  }
    
  /**
   * Action for deleting an instance
   */
  public function deleteQuestionnaireAction() {
    $session = new Zend_Session_Namespace('login');
    $questionnaireID = $session->dataQuestionnaireID;
    
    if(!isset($questionnaireID)) {
      $this->_redirector->gotoRouteAndExit(array('action' => 'index'));
    }
    
    $questionnaire = new QuestionnaireModel(array('questionnaireID' => $questionnaireID,
                                            'depth' => 'questionnaire'));
    
    $questionnaire->delete();
    unset($session->dataQuestionnaireID);
    $this->flash('notice', 'Deletion Complete');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
  
  /**
   * Action for importing an questionnaire
   */
  public function importQuestionnaireAction() {

    $uploadErrors = array(
      UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
      UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
      UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
      UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
      UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
      UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
      UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
    );
    
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;

    $errorCode = $_FILES['questionnaireFile']['error'];
    if ($errorCode !== UPLOAD_ERR_OK) {
      if (isset($uploadErrors[$errorCode]))
        throw new Exception($uploadErrors[$errorCode]);
      else
        throw new Exception("Unknown error uploading file.");
    }

    $file = $_FILES['questionnaireFile']['tmp_name'];
    $filename = $_FILES['questionnaireFile']['name'];
    
    if (preg_match('/\.enc$/i', $filename)) {
      if (!isset($cryptoID) || $cryptoID == 0) {
        throw new Exception('Key not specified for encrypted file');
      }
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      if (preg_match('/\.zip\.enc$/i', $filename)) {
        $decrypted = $crypto->decrypt(file_get_contents($file));
        $tempfile = tempnam(PROJECT_PATH . DIRECTORY_SEPARATOR . 'tmp', 'zip');
        unlink($tempfile);
        file_put_contents($tempfile, $decrypted);
        $import = new ZipArchiveModel(null, array('filename' => $tempfile));
      }
      elseif (preg_match('/\.xml\.enc$/i', $filename)) {
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
    
    QuestionnaireModel::importXML($import);
    
    $this->flash('notice', 'Import Complete');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
    
  /**
   * Export actions
   */
   
  public function QuestionnaireDefinitionXMLDownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $questionnaire = new QuestionnaireModel(array('questionnaireID' => $session->dataQuestionnaireID,
                                            'depth' => 'questionnaire'));
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->xml = $crypto->encrypt($questionnaire->fetchQuestionnaireDefinition());
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->xml = $questionnaire->fetchQuestionnaireDefinition();
    }
    $this->view->setRenderLayout(false);      
  }
   
  public function ResponsesXMLSchemaDownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $questionnaire = new QuestionnaireModel(array('questionnaireID' => $session->dataQuestionnaireID,
                                            'depth' => 'questionnaire'));
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->xml = $crypto->encrypt($questionnaire->fetchResponseSchema());
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->xml = $questionnaire->fetchResponseSchema();
    }
    $this->view->setRenderLayout(false);
  }
   
  public function CompletedResponsesXMLSchemaDownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $questionnaire = new QuestionnaireModel(array('questionnaireID' => $session->dataQuestionnaireID,
                                            'depth' => 'questionnaire'));
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->xml = $crypto->encrypt($questionnaire->fetchCompletedResponseSchema());
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->xml = $questionnaire->fetchCompletedResponseSchema();
    }
    $this->view->setRenderLayout(false);
  }

}
