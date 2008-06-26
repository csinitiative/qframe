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
class InstrumentDataController extends RegQ_Controller_Admin {

  /**
   * Index action.  Presents the Instrument Management page to the user.
   */
  public function indexAction() {
    $session = new Zend_Session_Namespace('login');
    $instrumentID = ($this->_hasParam('instrument')) ? $this->_getParam('instrument') : $session->dataInstrumentID;

    if (is_numeric($instrumentID) && $instrumentID > 0) {
      $this->view->dataInstrument = new InstrumentModel(array('instrumentID' => $instrumentID,
                                                              'depth' => 'instrument'));
    }
    else {
      $instrumentID = null;
    }

    $session->dataInstrumentID = $instrumentID;
    $this->view->dataInstrumentID = $session->dataInstrumentID;

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
    
    $this->view->cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    
  }
    
  /**
   * Action for deleting an instance
   */
  public function deleteInstrumentAction() {
    $session = new Zend_Session_Namespace('login');
    $instrumentID = $session->dataInstrumentID;
    
    if(!isset($instrumentID)) {
      $this->_redirector->gotoRouteAndExit(array('action' => 'index'));
    }
    
    $instrument = new InstrumentModel(array('instrumentID' => $instrumentID,
                                            'depth' => 'instrument'));
    
    $instrument->delete();
    unset($session->dataInstrumentID);
    $this->flash('notice', 'Deletion Complete');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
  
  /**
   * Action for importing an instrument
   */
  public function importInstrumentAction() {

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

    $errorCode = $_FILES['instrumentFile']['error'];
    if ($errorCode !== UPLOAD_ERR_OK) {
      if (isset($uploadErrors[$errorCode]))
        throw new Exception($uploadErrors[$errorCode]);
      else
        throw new Exception("Unknown error uploading file.");
    }

    $file = $_FILES['instrumentFile']['tmp_name'];
    $filename = $_FILES['instrumentFile']['name'];
    
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
    
    InstrumentModel::importXML($import);
    
    $this->flash('notice', 'Import Complete');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
    
  /**
   * Export actions
   */
   
  public function InstrumentDefinitionXMLDownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $instrument = new InstrumentModel(array('instrumentID' => $session->dataInstrumentID,
                                            'depth' => 'instrument'));
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->xml = $crypto->encrypt($instrument->fetchInstrumentDefinition());
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->xml = $instrument->fetchInstrumentDefinition();
    }
    $this->view->setRenderLayout(false);      
  }
   
  public function ResponsesXMLSchemaDownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $instrument = new InstrumentModel(array('instrumentID' => $session->dataInstrumentID,
                                            'depth' => 'instrument'));
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->xml = $crypto->encrypt($instrument->fetchResponseSchema());
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->xml = $instrument->fetchResponseSchema();
    }
    $this->view->setRenderLayout(false);
  }
   
  public function CompletedResponsesXMLSchemaDownloadAction() {
    $session = new Zend_Session_Namespace('login');
    $instrument = new InstrumentModel(array('instrumentID' => $session->dataInstrumentID,
                                            'depth' => 'instrument'));
    $cryptoID = ($this->_hasParam('cryptoID')) ? $this->_getParam('cryptoID') : null;
    if (isset($cryptoID) && $cryptoID != 0) {
      $crypto = new CryptoModel(array('cryptoID' => $cryptoID));
      $this->view->xml = $crypto->encrypt($instrument->fetchCompletedResponseSchema());
      $this->view->cryptoID = $cryptoID;
    }
    else {
      $this->view->xml = $instrument->fetchCompletedResponseSchema();
    }
    $this->view->setRenderLayout(false);
  }

}
