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
class QFrame_Controller_Admin extends QFrame_Controller_Action {
    
  /**
   * Execute stuff that needs to happen before dispatch (build menus, etc)
   */
  public function preDispatch() {
    $this->sanityChecks();
    if(!$this->_user->hasAccess('administer')) $this->denyAccess();
    $this->buildMenu();
    $this->loadInstance(false);
  }
  
  /**
   * Build the list of menu items that will be available on the admin tab
   */
  protected function buildMenu() {
    $controller = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
    $this->view->menuItems = array(
      array(
        'label'   => 'Domain Management',
        'url'     => $this->view->url(array('controller' => 'domain'), null, true),
        'current' => $controller == 'domain',
        'locked'  => false
      ),
      array(
        'label'   => 'Questionnaire Management',
        'url'     => $this->view->url(array('controller' => 'questionnairedata'), null, true),
        'current' => $controller == 'questionnairedata',
        'locked'  => false
      ),
      array(
        'label'   => 'Instance Management',
        'url'     => $this->view->url(array('controller' => 'instancedata'), null, true),
        'current' => $controller == 'instancedata',
        'locked'  => false
      ),
      array(
        'label'   => 'Encryption Management',
        'url'     => $this->view->url(array('controller' => 'crypto'), null, true),
        'current' => $controller == 'crypto',
        'locked'  => false
      ),
      array(
        'label'   => 'User Management',
        'url'     => $this->view->url(array('controller' => 'user'), null, true),
        'current' => $controller == 'user',
        'locked'  => false
      ),
      array(
        'label'   => 'Role Management',
        'url'     => $this->view->url(array('controller' => 'role'), null, true),
        'current' => $controller == 'role',
        'locked'  => false
      )
    );
    $this->view->menuTitle = 'options';
  }
}
