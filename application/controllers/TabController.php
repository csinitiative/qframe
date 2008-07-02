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
class TabController extends RegQ_Controller_Action {
  
  /**
   * Method to execute before dispatching takes place
   */
  public function preDispatch() {
    $this->view->currentTabID = $this->_getParam('id');
    parent::preDispatch();
  }
  
  /**
   * Action for editing a particular tab
   */
  public function editAction() {
    // get a TabModel object for the current tab
    $this->view->tab = new TabModel(array('tabID' => $this->view->currentTabID));
    
    if(!$this->_user->hasAccess('edit', $this->view->tab)) $this->denyAccess();
    
    // get a lock on this tab (if possible)
    $auth = Zend_Auth::getInstance();
    $user = DbUserModel::findByUsername($auth->getIdentity());
    $lock = LockModel::obtain($this->view->tab, $user);
    if(is_null($lock)) {
      $lockUser = new DbUserModel(array('dbUserID' => LockModel::isLocked($this->view->tab)));
      $this->flash(
        'error',
        'A lock could not be obtained because the page is currently lock by ' . $lockUser->dbUserFullName
      );
      // redirect to the view action
      $this->_redirector->gotoRoute(array('action' => 'view', 'id' => $this->view->currentTabID));
    }
  }
  
  /**
   * Action for viewing a particular tab
   */
  public function viewAction() {
    $tabId = $this->_getParam('id');
    $this->view->tab = new TabModel(array('tabID' => $tabId));
    if(!$this->_user->hasAccess('view', $this->view->tab)) $this->denyAccess();
    $this->view->currentTabID = $tabId;
  }
  
  /**
   * Show action.  Determines what a user is able to do with a tab and redirects to the
   * action that is allowed.
   */
  public function showAction() {
    $tab = new TabModel(array('tabID' => $this->_getParam('id')));
    foreach(array('view', 'edit', 'approve') as $a) {
      if($this->_user->hasAccess($a, $tab)) {
        $action = $a;
        break;
      }
    }
    if(!isset($action)) $this->denyAccess();
    
    $this->_redirector->gotoRoute(array('action' => $action));
  }
  
  /**
   * Saves the tab currently being edited
   */
  public function saveAction() {
    $tab = new TabModel(array('tabID' => $this->_getParam('id'), 'depth' => 'tab'));
    $lock = $this->lockTab($tab, 'edit');
    $attachments = array();
    
    $responses = array();
    $additionalInfo = '';
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
            $this->_redirector->gotoRouteAndExit(array('action' => 'view', 'id' => $tab->tabID));
          }
          if (strlen($value) > 0) {
            $responses[$questionID]['value'][] = $value;
            if(intval($this->_getParam("q{$q->questionID}_addl_mod")))
              $responses[$questionID]['addl'] = $this->_getParam("q{$q->questionID}_addl");
            else
              $responses[$questionID]['addl'] = null;        
          }
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
      $response->responseText = join(',', $data['value']);
      $response->additionalInfo = $data['addl'];
      $response->save();
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

    $tab = new TabModel(array('tabID' => $this->_getParam('id'),
                              'depth' => 'response'));
    $tab->save();
        
    $instance = new InstanceModel(array('instanceID' => $tab->instanceID,
                                        'depth' => 'tab'));
    $instance->save();
      
    $lock->release();
    
    // redirect to the view action
    $this->flash('notice', 'Edits successfully saved');
    $this->_redirector->gotoRoute(array('action' => 'edit', 'id' => $tab->tabID));
  }
  
  /**
   * Approve action.  Sets up a tab for approval and saves approval info (on post).
   */
  public function approveAction() {
    $tab = new TabModel(array('tabID' => $this->_getParam('id'), 'depth' => 'response'));
    $lock = $this->lockTab($tab);
    
    // if this request is a post, go ahead and do approvals
    if($this->getRequest()->isPost()) {
      $comments = $this->_getParam('comments');
      foreach($this->_getParam('approvals') as $questionID => $state) {
        $question = $tab->getQuestion(intval($questionID));
        if($question === null) {
          throw new Exception(
            'Invalid attempt to approve a non-existent question or question on another tab'
          );
        }
        $response = $question->getResponse();
        
        if($response->requiresAdditionalInfo() && !$response->hasAdditionalInfo()) {
          throw new Exception(
            'Invalid attempt to approve a response that requires additional with none provided'
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
      $tab->save();
      
      $instance = new InstanceModel(array('instanceID' => $tab->instanceID,
                                          'depth' => 'tab'));
      $instance->save();
      
      $lock->release();
      $this->flash('notice', 'Approvals successfully saved');
      $this->_redirector->gotoRouteAndExit(array('action' => 'approve', 'id' => $tab->tabID));
    }

    // set variables for the view
    $this->view->tab = $tab;
  }
  
  /**
   * Accepts a file upload and outputs only the temp filename
   */
  public function uploadAction() {    
    $this->view->setRenderLayout(false);
    if(count($_FILES) > 1) throw new Exception('Too many files posted at the same time');
    $file = current($_FILES);
    move_uploaded_file($file['tmp_name'], PROJECT_PATH . '/tmp/' . basename($file['tmp_name']));
    file_put_contents(PROJECT_PATH . '/tmp/.' . basename($file['tmp_name']), Spyc::YAMLDump(array(
      'filename'      => $file['name'],
      'mime'          => $file['type']
    )));
    $this->view->filename = basename($file['tmp_name']);
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
   * Unlock the requested tab provided that the current user has edit or approve access to this
   * tab
   */
  public function unlockAction() {
    $tab = new TabModel(array('tabID' => $this->_getParam('id'), 'depth' => 'tab'));
    if($this->_user->hasAccess('edit', $tab) || $this->_user->hasAccess('approve', $tab)) {
      LockModel::releaseAll($tab);
    }
    else {
      $this->flash('error', 'You do not have the access necessary to unlock a tab');
    }
    $this->_redirector->gotoRoute(array('action' => 'show', 'id' => $tab->tabID));
  }
  
  /**
   * Attempt to lock a tab, check for access and return the lock on success, redirect to the view
   * page with an error on failure
   *
   * @param  mixed  TabModel or id of tab being locked
   * @param  string the action we are checking for access to
   * @return mixed
   */
  private function lockTab($tab, $action = 'approve') {
    if(!($tab instanceof TabModel))
      $tab = new TabModel(array('tabID' => $this->_getParam('id'), 'depth' => 'response'));
    $lock = LockModel::obtain($tab, $this->_user);
    if($lock === null) {
      $this->flash('error', 'A lock could not be obtained for the requested tab. Please ' .
          'ensure you have access and try again later.');
      $this->_redirector->gotoRouteAndExit(array('action' => 'view', 'id' => $tab->tabID));
    }
    elseif(!$this->_user->hasAccess($action, $tab)) $this->denyAccess();
    return $lock;
  }
}
