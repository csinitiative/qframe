<?php
/**
 * This file is part of QFrame.
 *
 * QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class CompareController extends QFrame_Controller_Action {
  
  /**
   * Store the model we are current working on
   * @var ModelModel
   */
  private $model;

  /**
   * Override the default preDispatch to prevent loading of an instance which is
   * not necessary in the compare context
   */
  public function preDispatch() {
    $id = $this->_getParam('id');
    if($id) {
      $this->model = new ModelModel(array('modelID' => $id));
      $questionnaire = new QuestionnaireModel(array(
        'questionnaireID' => $this->model->questionnaireID,
        'depth'           => 'page'
      ));
      $this->_instance = $questionnaire->getDefaultInstance();
      if($this->_hasParam('page')) {
        $this->view->currentPageID = $this->_getParam('page');
      }
      else {
        $this->view->currentPageID = $this->_instance->getFirstPage()->pageID;
      }
      
      $this->view->menuItems = $this->buildMenu("/compare/edit/{$id}?page=");
    }
  }
  
  /**
   * Index action, either allows you to pick a model, or, if a model is selected already,
   * redirects to the edit action
   */
  public function indexAction() {
    $session = new Zend_Session_Namespace('login');
    if(isset($session->compareModelID)) {
      
    }
    $this->view->questionnaires = QuestionnaireModel::getAllQuestionnaires();
    $this->view->selected = $this->_getParam('questionnaire');
    if($this->view->selected === null) $this->view->models = null;
    else {
      $questionnaire = new QuestionnaireModel(array(
        'questionnaireID' => $this->view->selected,
        'depth'           => 'page'
      ));
      $this->view->models = ModelModel::getAllModels($questionnaire);
      while($instance = $questionnaire->nextInstance()) {
        while($page = $instance->nextPage()) {
          if($this->_user->hasAnyAccess($page) ||
             $this->_user->hasAnyAccess($page->parent->domain)) {
            $allowedInstances[] = $instance;
            break;
          }
        }
      }
      if(isset($allowedInstances)) $this->view->instances = $allowedInstances;
      
    }
  }
  
  /**
   * Create action.  Simply creates a new model.
   */
  public function createAction() {
    // fetch model parameters
    $modelParams = $this->_getParam('model');
    
    // make sure that another model with this name does not already exist
    if(count(ModelModel::findModelsByName($modelParams['name'])) > 0) {
      $this->flash('error', 'A model with the specified name already exists');
    }
    else {
      ModelModel::create($modelParams['name'], $modelParams['questionnaireID']);
    }
    
    // redirect back to the model selection screen
    $this->_redirector->gotoUrl("/compare?questionnaire={$this->_getParam('questionnaire')}");
  }
  
  /**
   * Edit action. Edit a model.
   */
  public function editAction() {
    $this->view->page = new ModelPageModel(array(
      'modelID' => $this->model->modelID,
      'pageID'  => $this->view->currentPageID,
      'depth'   => 'response'
    ));
    $this->view->model = $this->model;
  }
  
  /**
   * Delete action. Delete a model.
   */
  public function deleteAction() {
    $this->model->delete();
    $this->_redirector->gotoUrl("/compare?questionnaire={$this->_getParam('questionnaire')}");
  }
  
  /**
   * Save action.  Save a model.
   */
  public function saveAction() {
    // don't really need this but it should help with query caching efficiency
    $page = new ModelPageModel(array(
      'modelID' => $this->model->modelID,
      'pageID'  => $this->view->currentPageID,
      'depth'   => 'response'
    ));
    $responses = $this->_getParam('response');
    foreach($responses as $questionID => $response) {
      $question = new ModelQuestionModel(array(
        'modelID'    => $this->model->modelID,
        'questionID' => $questionID,
        'depth'      => 'response'
      ));
      if($question->nextModelResponse() !== null) $question->delete();
      if(!$this->isBlank($response)) {
        if($response['noinclude']) $question->createModelResponse('no preference', '-');
        else {
          if($response['target']) $this->setModelResponse($question, $response['target']);
          if($response['remediationInfoMod'] == 1) {
            $question->createModelResponse('remediation info', -1, $response['remediationInfo']);
          }
          if($response['requireAttachment'] == 1) $question->createModelResponse('require attachment', -1);
        }
      }
    }
    $this->flash('notice', 'Model saved successfully');
    $this->_redirector->gotoUrl("/compare/edit/{$this->model->modelID}?page={$page->pageID}");
  }
  
  /**
   * Perform an actual comparison
   */
  public function doAction() {
    $this->runReport();
  }

  /**
   * Perform an actual comparison
   */
  public function csvAction() {
    $this->runReport();
    $this->view->setRenderLayout(false);
  }
  
  /**
   * Do the work of running a report and assigning the results to the view
   */
  private function runReport() {
    unset($this->view->menuItems);
    $instance = new InstanceModel(array(
      'instanceID' => $this->_getParam('instance'),
      'depth'      => 'response'
    ));
    $model = new ModelModel(array(
      'modelID'  => $this->_getParam('id'),
      'depth'    => 'response',
      'instance' => $instance
    ));
    $this->view->failures = $model->compare(array(
      'additional_information' => (($this->_getParam('addlInfo')) ? true : false),
      'model_pass' => (($this->_getParam('passing')) ? true : false)
    ));
  }
  
  /**
   * Set the question response correctly depending on the type of question
   *
   * @param Object       question
   * @param string|array target
   */
  private function setModelResponse($question, $target) {
    switch(substr($question->format, 0, 1)) {
      case 'T':
      case 'D':
        $question->createModelResponse('match', $target);
        break;
      case 'S':
      case 'M':
        foreach($target as $promptID => $value) {
          if($value) $question->createModelResponse('selected', $promptID);
        }
        break;
    }
  }
  
  /**
   * Determine whether a response is blank or not
   *
   * @param  array response data
   * @return boolean
   */
  private function isBlank($response) {
    if(isset($response['noinclude']) && $response['noinclude']) return false;
    if(!is_array($response['target']) && $response['target'] !== null) {
      if($response['target'] !== '' && $response['target']) return false;
    }
    elseif (isset($response['requireAttachment']) && $response['requireAttachment'] !== '') {
      return false;
    }
    else {
      foreach($response['target'] as $target) {
        if(is_array($target) && $this->isBlank($target)) return false;
        elseif($target !== null && $target !== '' && $target != 0) return false;
      }
    }
    
    return true;
  }
}
