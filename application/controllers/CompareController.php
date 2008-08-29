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
class CompareController extends QFrame_Controller_Action {

  /**
   * Override the default preDispatch to prevent loading of an instance which is
   * not necessary in the compare context
   */
  public function preDispatch() {}
  
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
      $this->view->models = ModelModel::findBy('questionnaireID', $this->view->selected);
    }
  }
  
  /**
   * Create action.  Simply creates a new model.
   */
  public function createAction() {
    $model = ModelModel::create($this->_getParam('model'));
    $model->save();
    $this->_redirector->gotoUrl("/compare?questionnaire={$this->_getParam('questionnaire')}");
  }
}