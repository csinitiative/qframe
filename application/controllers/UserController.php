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
class UserController extends QFrame_Controller_Admin {
  
  /**
   * Index action...displays whatever search terms/pages are requested
   */
  public function indexAction() {
    $this->view->q = $this->_getParam('q');
    $page = ($this->_hasParam('page')) ? intval($this->_getParam('page')) : 1;
    $domain = $this->_user->isGlobalAdministrator() ? null : $this->_user->domain;
    $this->view->pager =
        new QFrame_Paginator('DbUserModel', 20, $page, 'dbUserFullName ASC', $this->view->q, $domain);
  }
  
  /**
   * Create action.  Creates a user and redirects back to the index action.
   */
  public function createAction() {
    $userParams = $this->_getParam('user');
    $pw = $this->_getParam('dbUserPW');
    $domainID = $this->_getParam('userDomain');
    $this->view->userDomain = $domainID;

    $user = new DbUserModel(array(
      'dbUserName'  => $userParams['dbUserName'],
      'dbUserPW'    => $pw
    ));
    $user->domainID = $domainID;

    if(!$this->_user->hasAccess('administer', $user->domain)) $this->denyAccess();

    foreach($userParams as $field => $value) $user->$field = $value;
    if($pw === '' || $pw !== $this->_getParam('dbUserPWConf')) {
      $this->flashNow('error', 'No passwords specified or specified passwords do not match');
      $this->view->user = $user;
      return;
    }
    $user->dbUserPW = $pw;
    $user->domainID = $domainID;
    $user->save();
    $this->flash('notice', 'User successfully created');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
  
  /**
   * Delete action.  Removes the specified user.
   */
  public function deleteAction() {
    $user = new DbUserModel(array('dbUserID' => $this->_getParam('id')));

    if(!$this->_user->hasAccess('administer', $user->domain)) $this->denyAccess();

    $user->delete();
    $this->flash('notice', 'User successfully deleted');
    $this->_redirector->gotoRoute(array('action' => 'index', 'id' => null));
  }
  
  /**
   * Edit action.  Sets a user up to be modified or, when posted to, edits a user
   * and redirects to the index page.
   */
  public function editAction() {
    $user = new DbUserModel(array('dbUserID' => $this->_getParam('id')));

    if(!$this->_user->hasAccess('administer', $user->domain)) $this->denyAccess();
    
    $this->view->userDomain = $user->domainID;
    if($this->getRequest()->isPost()) {
      foreach($this->_getParam('user') as $field => $value) $user->$field = $value;
      $user->domainID = $this->_getParam('userDomain');
      $pw = $this->_getParam('dbUserPW');
      $pwConf = $this->_getParam('dbUserPWConf');
      if($pw !== '' && $pw === $pwConf) $user->dbUserPW = $pw;
      
      if($pw !== $pwConf) {
        $this->flashNow('error', 'Passwords do not match');
        $this->view->user = $user;
      }
      else {
        $user->save();
        $this->flash('notice', 'User updated successfully');
        $this->_redirector->gotoRoute(array('action' => 'index', 'id' => null));
      }
    }
    else $this->view->user = $user;
  }
  
  /**
   * Roles action. Sets up the role administration page and, when posted to, modified
   * a user's assigned roles.
   */
  public function rolesAction() {
    $user = new DbUserModel(array('dbUserID' => $this->_getParam('id')));

    if(!$this->_user->hasAccess('administer', $user->domain)) $this->denyAccess();

    $this->view->user = $user;
    $allRoles = RoleModel::find('all');
    $roles = array();
    foreach($allRoles as $role) {
      if (!$this->_user->hasAccess('administer', $role->domain)) continue;
      $granted = false;
      foreach($this->view->user->roles as $userRole) {
        if($role->roleID === $userRole['roleID']) {
          $granted = true;
          break;
        }
      }
      if(!$granted) $roles[] = $role;
    }
    $this->view->roles = $roles;
  }
  
  /**
   * Add role action.  Adds the requested role to the current user.
   */
  public function addroleAction() {
    $user = new DbUserModel(array('dbUserID' => $this->_getParam('id')));

    if(!$this->_user->hasAccess('administer', $user->domain)) $this->denyAccess();

    $role = RoleModel::find($this->_getParam('role'));

    if(!$this->_user->hasAccess('administer', $role->domain)) $this->denyAccess();

    $user->addRole($role);
    $this->_redirector->gotoRoute(array('action' => 'roles', 'id' => $user->dbUserID));
  }
  
  /**
   * Remove role action.  Removes the requested role from the current user.
   */
  public function removeroleAction() {
    $user = new DbUserModel(array('dbUserID' => $this->_getParam('id')));

    if(!$this->_user->hasAccess('administer', $user->domain)) $this->denyAccess();
    
    $role = RoleModel::find($this->_getParam('role'));

    if(!$this->_user->hasAccess('administer', $role->domain)) $this->denyAccess();
    
    $user->removeRole($role);
    $this->_redirector->gotoRoute(array('action' => 'roles', 'id' => $user->dbUserID));
  }
}
