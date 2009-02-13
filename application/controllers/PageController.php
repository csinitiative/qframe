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


/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class PageController extends QFrame_Controller_Action {
  
  /**
   * Method to execute before dispatching takes place
   */
  public function preDispatch() {
    parent::preDispatch();
  }
  
  /**
   * Action for editing a particular page
   */
  public function editAction() {
    $pageID = $this->_getParam('id');
    $subPageNum = ($this->_hasParam('sp')) ? $this->_getParam('sp') : 1;
    $this->view->spNum = $subPageNum;
    $this->view->spNumSkip = ($subPageNum - 1) * 100;
    $this->view->spNumLeft = 100;
    $this->view->currentPageID = $pageID;

    if ($pageID > 0) {
      $page = new PageModel(array('pageID' => $pageID,
                                  'depth' => 'page'));
      $this->view->attachmentQuestions = FileModel::fetchObjectIdsByInstance($page->instanceID);
    }

    // get a PageModel object for the current page
    $this->view->page = new PageModel(array('pageID' => $this->view->currentPageID));
    
    if(!$this->_user->hasAccess('edit', $this->view->page)) $this->denyAccess();
    
    // get a lock on this page (if possible)
    $auth = Zend_Auth::getInstance();
    $user = DbUserModel::findByUsername($auth->getIdentity());
    $lock = LockModel::obtain($this->view->page, $user);
    if(is_null($lock)) {
      $lockUser = new DbUserModel(array('dbUserID' => LockModel::isLocked($this->view->page)));
      $this->flash(
        'error',
        'A lock could not be obtained because the page is currently lock by ' . $lockUser->dbUserFullName
      );
      // redirect to the view action
      $this->_redirector->gotoRoute(array('action' => 'view', 'id' => $this->view->currentPageID));
    }
  }
  
  /**
   * Action for viewing a particular page
   */
  public function viewAction() {
    $pageID = $this->_getParam('id');
    $subPageNum = ($this->_hasParam('sp')) ? $this->_getParam('sp') : 1;
    $this->view->spNum = $subPageNum;
    $this->view->spNumSkip = ($subPageNum - 1) * 100;
    $this->view->spNumLeft = 100;

    if ($pageID > 0) {
      $page = new PageModel(array('pageID' => $pageID,
                                  'depth' => 'page'));
      $this->view->attachmentQuestions = FileModel::fetchObjectIdsByInstance($page->instanceID);
    }

    $this->view->page = new PageModel(array('pageID' => $pageID));
    if(!$this->_user->hasAccess('view', $this->view->page)) $this->denyAccess();
    $this->view->currentPageID = $pageID;
  }
  
  /**
   * Show action.  Determines what a user is able to do with a page and redirects to the
   * action that is allowed.
   */
  public function showAction() {
    $page = new PageModel(array('pageID' => $this->_getParam('id')));
    foreach(array('view', 'edit', 'approve') as $a) {
      if($this->_user->hasAccess($a, $page)) {
        $action = $a;
        break;
      }
    }
    if(!isset($action)) $this->denyAccess();
    
    $this->_redirector->gotoRoute(array('action' => $action));
  }
  
  /**
   * Saves the page currently being edited
   */
  public function saveAction() {
    try {
      $page = new PageModel(array('pageID' => $this->_getParam('id'), 'depth' => 'page'));
      $lock = $this->lockPage($page, 'edit');
      $attachments = array();

      $auth = Zend_Auth::getInstance();
      $user = DbUserModel::findByUsername($auth->getIdentity());
    
      $responses = array();
      foreach($this->_getAllParams() as $key => $value) {
        // if the element's name begins 'qXXX' where X is a digit
        if(preg_match('/^q(\d+)(.*)$/', $key, $matches)) {
          $questionID = intval($matches[1]);
          $remainder = $matches[2];
        
          // if the element name consists of *only* 'qXXX' or qXXX_mXXX for multiple select question types
          if($remainder == '' || preg_match('/^_m(\d+)$/', $remainder)) {
            $q = new QuestionModel(array('questionID' => $questionID));
            $response = $q->getResponse();
            if($response->state == 2) {
              $this->flash('error', 'You cannot modify a response that has been approved');
              $this->_redirector->gotoRouteAndExit(array('action' => 'view', 'id' => $page->pageID));
            }
            if (strlen($value) > 0) {
              $responses[$questionID]['value'][] = $value;
            }
          }
          elseif($remainder == "_addl_mod" && intval($this->_getParam("q{$questionID}_addl_mod"))) {
            $responses[$questionID]['addl'] = $this->_getParam("q{$questionID}_addl");
          }
          elseif($remainder == "_privateNote_mod" && intval($this->_getParam("q{$questionID}_privateNote_mod"))) {
            $responses[$questionID]['pNote'] = $this->_getParam("q{$questionID}_privateNote");
          }
        
          // if the remainder indicates an array of attachments
          elseif($remainder == '_attachments') {
            $question = new QuestionModel(array('questionID' => $questionID));
            foreach($value as $file) {
              $fileModel = new FileModel($question);
              $properties = Spyc::YAMLLoad(PROJECT_PATH . '/tmp/.' . $file);
              $fileModel->storeFilename(PROJECT_PATH . '/tmp/' . $file, $properties);
            }
          }   
        
          // if the element name ends in '_fileXXX_delete'
          elseif(preg_match('/^_file(\d+)_delete$/', $remainder, $matches) && $value === 'true') {
            $question = new QuestionModel(array('questionID' => $questionID));
            $fileModel = new FileModel($question);
            $fileModel->delete(intval($matches[1]));
          }
        }
      }
    
      foreach ($responses as $questionID => $data) {
        $q = new QuestionModel(array('questionID' => $questionID));
        $response = $q->getResponse();
        if (isset($data['value'])) $response->responseText = join(',', $data['value']);
        if (isset($data['addl'])) $response->additionalInfo = $data['addl'];
        if (isset($data['pNote'])) $response->privateNote = $data['pNote'];
        $response->save($user);
      }
    
      /* If there are any file uploads that didn't auto-upload before the user saved */
      foreach($_FILES as $name => $file) {
        if($file['size'] > 0) {
          $question = new QuestionModel(array('questionID' => intVal($name)));
          $fileModel = new FileModel($question);
          $properties = array(
            'filename'  => $file['name'],
            'mime'      => $file['type']
          );
          $fileModel->storeFilename($file['tmp_name'], $properties);
        }
      }

      $page = new PageModel(array('pageID' => $this->_getParam('id'),
                                  'depth' => 'response'));
      $page->save();
        
      $instance = new InstanceModel(array('instanceID' => $page->instanceID,
                                          'depth' => 'page'));
      $instance->save();
    }
    catch (Exception $e) {
      $this->view->error = $e->getMessage();
    }
    $this->view->setRenderLayout(false);
  }
  
  /**
   * Approve action.  Sets up a page for approval and saves approval info (on post).
   */
  public function approveAction() {
    $page = new PageModel(array('pageID' => $this->_getParam('id'), 'depth' => 'response'));
    $subPageNum = ($this->_hasParam('sp')) ? $this->_getParam('sp') : 1;
    $this->view->spNum = $subPageNum;
    $this->view->spNumSkip = ($subPageNum - 1) * 100;
    $this->view->spNumLeft = 100;
    $lock = $this->lockPage($page);
    
    // if this request is a post, go ahead and do approvals
    if($this->getRequest()->isPost()) {
      $comments = $this->_getParam('comments');
      foreach($this->_getParam('approvals') as $questionID => $state) {
        $question = $page->getQuestion(intval($questionID));
        if($question === null) {
          throw new Exception(
            'Invalid attempt to approve a non-existent question or question on another page'
          );
        }
        $response = $question->getResponse();
        
        if($response->requiresAdditionalInfo() && !$response->hasAdditionalInfo()) {
          throw new Exception(
            'Invalid attempt to approve a response that requires additional information with none provided'
          );
        }
        
        $response->state = intval($state);
        if(array_key_exists($questionID, $comments)) {
          if($comments[$questionID] === '') $response->approverComments = null;
          else $response->approverComments = $comments[$questionID];
        }
        $response->save();
        foreach($question->children as $child) {
          $response = $child->getResponse();
          $response->state = intval($state);
          $response->save();
        }
      }
      $page->save();
      
      $instance = new InstanceModel(array('instanceID' => $page->instanceID,
                                          'depth' => 'page'));
      $instance->save();
      
      $this->_redirector->gotoRouteAndExit(array('action' => 'save', 'id' => $page->pageID));
    }

    // set variables for the view
    $this->view->page = $page;
  }
  
  /**
   * Accepts a file upload and outputs only the temp filename
   */
  public function uploadAction() {    
    try {
      if(count($_FILES) > 1) throw new Exception('Too many files posted at the same time');
      $file = current($_FILES);
      if (!file_exists($file['tmp_name'])) throw new Exception('Could not find temporary file: ' . $file['tmp_name']);
      move_uploaded_file($file['tmp_name'], PROJECT_PATH . '/tmp/' . basename($file['tmp_name']));
      if (!file_exists(PROJECT_PATH . '/tmp/' . basename($file['tmp_name']))) throw new Exception('Could not find temporary file: ' . PROJECT_PATH . '/tmp/' . basename($file['tmp_name']));
      file_put_contents(PROJECT_PATH . '/tmp/.' . basename($file['tmp_name']), Spyc::YAMLDump(array(
        'filename'      => $file['name'],
        'mime'          => $file['type']
      )));
      $this->view->filename = basename($file['tmp_name']);
    }
    catch (Exception $e) {
      $this->view->error = $e->getMessage();
    }
    $this->view->setRenderLayout(false);
  }
  
  /**
   * Streams the requested attachment file to the user
   */
  public function downloadAction() {    
    $this->view->setRenderLayout(false);
    $params = $this->getRequest()->getParams();
    $fileModel = new FileModel(new QuestionModel(array('questionID' => $params['id'])));
    $file = $fileModel->fetchWithProperties($params['fileID']);
    $this->view->file = $file;
    $this->view->setRenderLayout(false);
  }

  /**
   * Unlock the requested page provided that the current user has edit or approve access to this
   * page
   */
  public function unlockAction() {
    $page = new PageModel(array('pageID' => $this->_getParam('id'), 'depth' => 'page'));
    if($this->_user->hasAccess('edit', $page) || $this->_user->hasAccess('approve', $page)) {
      LockModel::releaseAll($page);
    }
    else {
      $this->flash('error', 'You do not have the access necessary to unlock a page');
    }
    $this->_redirector->gotoRoute(array('action' => 'show', 'id' => $page->pageID));
  }
  
  /**
   * Attempt to lock a page, check for access and return the lock on success, redirect to the view
   * page with an error on failure
   *
   * @param  mixed  PageModel or id of page being locked
   * @param  string the action we are checking for access to
   * @return mixed
   */
  private function lockPage($page, $action = 'approve') {
    if(!($page instanceof PageModel))
      $page = new PageModel(array('pageID' => $this->_getParam('id'), 'depth' => 'response'));
    $lock = LockModel::obtain($page, $this->_user);
    if($lock === null) {
      $this->flash('error', 'A lock could not be obtained for the requested page. Please ' .
          'ensure you have access and try again later.');
      $this->_redirector->gotoRouteAndExit(array('action' => 'view', 'id' => $page->pageID));
    }
    elseif(!$this->_user->hasAccess($action, $page)) $this->denyAccess();
    return $lock;
  }
}
