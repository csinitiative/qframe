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
class RoleController extends QFrame_Controller_Admin {
  
  /**
   * Index action...displays whatever search terms/pages are requested
   */
  public function indexAction() {
    $this->view->q = $this->_getParam('q');
    $page = ($this->_hasParam('page')) ? intval($this->_getParam('page')) : 1;
    $this->view->pager =
        new QFrame_Paginator('RoleModel', 5, $page, 'roleDescription ASC', $this->view->q);
  }
  
  /**
   * Create action.  Creates a role and redirects back to the index action.
   */
  public function createAction() {
    RoleModel::create(array(
      'roleDescription' => $this->_getParam('roleDescription')
    ))->save();
    $this->flash('notice', 'Role successfully created');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
  
  /**
   * Delete action.  Removes the specified role.
   */
  public function deleteAction() {
    RoleModel::find($this->_getParam('id'))->delete();
    $this->flash('notice', 'Role successfully deleted');
    $this->_redirector->gotoRoute(array('action' => 'index', 'id' => null));
  }
  
  /**
   * Edit action.  Sets a role up to be modified.
   */
  public function editAction() {
    $this->view->role = RoleModel::find($this->_getParam('id'));
  }
  
  /**
   * Modify action. Completes the modification of a role
   */
  public function modifyAction() {
    $role = RoleModel::find($this->_getParam('id'));
    $role->setAttributes(array(
      'roleDescription'   => $this->_getParam('roleDescription')
    ));
    $role->save();
    $this->flash('notice', 'Role successfully modified');
    $this->_redirector->gotoRoute(array('action' => 'index', 'id' => null));
  }
  
  /**
   * Permissions action.  Presents a list of permissions that can be assigned
   * to a role.
   */
  public function permissionsAction() {
    // if this is a post request, just go ahead and update permissions
    if($this->getRequest()->isPost()) $this->updatePermissions();
    
    $session = new Zend_Session_Namespace('login');
    if(!isset($session->instanceID)) {
      $instanceID = ($this->_hasParam('instance')) ? $this->_getParam('instance') : null;
      $questionnaireID = ($this->_hasParam('questionnaire')) ? $this->_getParam('questionnaire') : null;

      if(is_numeric($instanceID) && $instanceID != 0) {
        $session->instanceID = intVal($instanceID);
        $this->_redirector->gotoRouteAndExit(array());
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

    $this->view->role = RoleModel::find($this->_getParam('id'));

    $this->view->changeInstancePath = $this->view->url(array(
      'action' => 'changeInstance',
      'id'     => $this->view->role->roleID
    ));
  }
  
  /**
   * Change the current instance
   */
  public function changeInstanceAction() {
    $session = new Zend_Session_Namespace('login');
    unset($session->instanceID);
    $this->_redirector->gotoRoute(array('action' => 'permissions', 'id' => $this->_getParam('id')));
  }
  
  /**
   * Processes an update to permissions
   */
  private function updatePermissions() {
    $globals = $this->_getParam('global');
    $pages = $this->_getParam('page');
    $role = RoleModel::find($this->_getParam('id'));
    
    foreach($globals as $permission => $value) {
      if($value) $role->grant($permission);
      else $role->deny($permission);
    }
    foreach($pages as $id => $permissions) {
      $page = $this->_instance->getPage($id);
      foreach($permissions as $permission => $value) {
        if($value) $role->grant($permission, $page);
        else $role->deny($permission, $page);
      }
    }
    $role->save();
    $this->flash('notice', 'Permissions updated successfully');
    $this->_redirector->gotoRouteAndExit(array('action' => 'index', 'id' => null));
    
  }
}
