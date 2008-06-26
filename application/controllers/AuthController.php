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
class AuthController extends RegQ_Controller_Action {
  
  /**
   * It is necessary to override the default preDispatch and postDispatch methods to prevent
   * an attempt to load tabs
   */
  public function preDispatch() {
    $this->sanityChecks();
  }
  public function postDispatch() {}
  
  /**
   * Log a user in to the system
   */
  public function loginAction() {
    $request = $this->getRequest();
    if($request->isPost()){
      $auth_adapter = new RegQ_Auth_Adapter(
        $this->_getParam('username'),
        $this->_getParam('password')
      );
      $auth = Zend_Auth::getInstance();
      if($auth->authenticate($auth_adapter)->isValid()) {
        $this->_redirector->gotoRoute(array('controller' => 'index', 'action' => 'index'));
      }
      else {
        $this->flashNow('error', 'Invalid username/password.  Please try again.');
      }
    }
  }
  
  /**
   * Log a user out of the system
   */
  public function logoutAction () {
    $this->logout('You have been successfully logged out');
  }
  
  /**
   * Change the current user's password
   */
  public function passwdAction() {
    $auth = Zend_Auth::getInstance();
    if(!$auth->hasIdentity()) {
      $this->flash('error', 'You must be logged in to change your password');
      $this->_redirector->gotoRouteAndExit(array('action' => 'login'));
    }

    $request = $this->getRequest();
    if($request->isPost()) {
      $auth_adapter = new RegQ_Auth_Adapter(
        $auth->getIdentity(),
        $this->_getParam('old')
      );
      if(!$auth->authenticate($auth_adapter)->isValid()) {
        $this->flashNow('error', 'Old password is invalid');
      }
      elseif($this->_getParam('new1') !== $this->_getParam('new2')) {
        $this->flashNow('error', 'New passwords do not match');
      }
      elseif($this->_getParam('old') === $this->_getParam('new1')) {
        $this->flashNow('error', 'New password is the same as old password');
      }
      else {
        $user = DbUserModel::findByUsername($auth->getIdentity());
        $user->dbUserPW = $this->_getParam('new1');
        $user->save();
        $this->logout('Password successfully changed');
      }
    }
  }
  
  /**
   * Perform a logout
   *
   * @param string flash notice to set
   */
  private function logout($message = null) {
    Zend_Auth::getInstance()->clearIdentity();
    $session = new Zend_Session_Namespace('login');
    $session->unsetAll();
    if($message !== null) $this->flash('notice', $message);
    $this->_redirector->gotoRoute(array('action' => 'login'));
  }
}