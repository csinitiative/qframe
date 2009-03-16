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
class ErrorController extends QFrame_Controller_Action {
  
  /*
   * We need blank init and pre/post dispatch routines to prevent redirecting back to
   * the login action if the user is not already logged in.
   */
  public function preDispatch() {}
  public function postDispatch() {
    // This line is basically copied from the postDispatch() in QFrame_Controller_Action
    $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
  }
  public function init() {}
  
  /**
   * Error action.  Used to output exceptions and errors.
   */
  public function errorAction() {
    $this->view->exceptions = $this->_response->getException();
    $logger = Zend_Registry::get('logger');
    foreach($this->view->exceptions as $e) {
      $message = $e->getMessage();
      if(isset($logger) && $logger) $logger->log($message, Zend_Log::ERR);
    }
  }
  
  /**
   * Index action.  Used to output "unknown" error message
   */
  public function indexAction() {}

  /**
   * Access action.  Used to output "access denied" error message
   */
  public function accessAction() {}
}
