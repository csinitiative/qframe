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
class DashboardController extends QFrame_Controller_Action {
  
  /**
   * Override the default preDispatch() method to avoid trying to load instance
   */
  public function preDispatch() {
    $session = new Zend_Session_Namespace('login');
    unset($session->instanceID);
  }

  /**
   * Index action.  Presents the dashboard to the user.
   */
  public function indexAction() {
    $instanceID = ($this->_hasParam('instance')) ? $this->_getParam('instance') : null;
    $questionnaireID = ($this->_hasParam('questionnaire')) ? $this->_getParam('questionnaire') : null;

    if(is_numeric($instanceID) && $instanceID != 0) {
      $session = new Zend_Session_Namespace('login');
      $session->instanceID = intVal($instanceID);
      $this->_redirector->gotoRouteAndExit(array('controller' => 'index'), null, true);
    }
    
    $questionnaires = QuestionnaireModel::getAllQuestionnaires('page');
    $allowedInstances = array();
    foreach($questionnaires as $questionnaire) {
      while($instance = $questionnaire->nextInstance()) {
        while($page = $instance->nextPage()) {
          if($this->_user->hasAnyAccess($page)) {
            $allowedInstances[] = $instance;
            break;
          }
        }
      }
    }
    $this->view->instances = $allowedInstances;
    $this->view->questionnaire = $questionnaireID;
  }
}
