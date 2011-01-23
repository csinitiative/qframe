<?php
/**
 * This file is part of the CSI SIG.
 *
 * The CSI SIG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI SIG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   QFrame_Controller
 * @package    QFrame_Controller
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */

/**
 * Zend_Controller_Action
 */
require_once 'Zend/Controller/Action.php';


/**
 * @category   QFrame_Controller
 * @package    QFrame_Controller
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class QFrame_Controller_Action extends Zend_Controller_Action {
  
  /**
   * Redirector object providing fine-grained control over redirection
   * @var Zend_Controller_Action_Helper_Redirector
   */
  protected $_redirector = null;
  
  /**
   * Object providing access to the root level questionnaire object
   * @var QuestionnaireModel
   */
  protected $_instance = null;

  /**
   * User object for the currently logged in user
   * @var DbUserModel
   */
  protected $_user = null;

  /**
   * storage of flash messages
   * @var array
   */
  protected $_flash = array();

  /**
   * Initialized helpers needed by all controllers
   */
  public function init() {
    $this->_redirector = $this->_helper->getHelper('Redirector');
    
    $session = new Zend_Session_Namespace;
    $this->_flash = $session->flash;
    $session->flash = array();
    $this->initView();
    $this->view->flash = $this->_flash;
    
    $auth = Zend_Auth::getInstance();
    if($auth->hasIdentity()) {
      $this->_user = DbUserModel::findByUsername($auth->getIdentity());
      $this->view->loggedInUser = $this->_user;
      foreach(LockModel::getLocks($this->_user) as $lock) {
        $lock->release();
      }
      if ($this->_user->mustChangePassword() &&
          ($this->getRequest()->getControllerName() !== 'auth' || $this->getRequest()->getActionName() !== 'passwd')) {
         $this->_redirector->gotoRouteAndExit(
           array('controller' => 'auth', 'action' => 'passwd'),
           null,
           true
         );
      }
    }
    elseif($this->getRequest()->getControllerName() !== 'auth') {
      $this->_redirector->gotoRouteAndExit(
        array('controller' => 'auth', 'action' => 'login'),
        null,
        true
      );
    }
    
    $this->view->_user = $this->_user;

    $this->view->headerPages = $this->buildPages();
  }
  
  /**
   * Add a message to the flash for this request
   *
   * @param string key under which the message will be stored
   * @param string message
   */
  public function flashNow($key, $message) {
    $this->_flash[$key] = $message;
    $this->view->flash = $this->_flash;
  }
  
  /**
   * Add a message to the flash for the next request
   *
   * @param string key under which the message will be stored
   * @param string message
   */
  public function flash($key, $message) {
    $session = new Zend_Session_Namespace();
    $session->flash[$key] = $message;
  }
  
  /**
   * Called before dispatch to a specific action controller method takes place
   * (unless child class overrides this method)
   */
  public function preDispatch() {
    if($this->_user !== null && $this->getRequest()->getControllerName() !== 'error') {
      $this->loadInstance();
    }
  }
  
  /**
   * Called after dispatch to an action controller.  Used primarily for default tasks
   * (like building the menu) giving the specific action controller a chance to perform
   * the task in a specialized way.
   */
  public function postDispatch() {
    $this->sanityChecks();
    if($this->_user !== null && !isset($this->view->menuItems)) {
      $this->view->menuItems = $this->buildMenu();
    }
    $this->view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
  }
  
  /**
   * Loads the current questionnaire model if said model exists, otherwise redirects to the
   * dashboard
   *
   * @param  boolean (optional) redirect to the dashboard if no instance is set
   * @return InstanceModel
   */
  protected function loadInstance($redirect = true) {
    $session = new Zend_Session_Namespace('login');
    
    // if we are in redirect mode && no instanceID is set
    if(!isset($session->instanceID)) {
      if($redirect) {
        $this->_redirector->gotoRouteAndExit(array('controller' => 'dashboard'), null, true);
      }
    }
    else {
      // set up current questionnaire/instance stuff for the view/controller
      $this->_instance = new InstanceModel(array('instanceID' => $session->instanceID,
                                                 'depth' => 'page'));
      $this->view->currentInstance = $this->_instance;
      $this->view->numQuestionsDisabled = $this->_instance->getNumQuestionsDisabled();
      $this->view->instanceInfo = array(
        'questionnaire'        => $this->_instance->questionnaireName,
        'questionnaireVersion' => $this->_instance->questionnaireVersion,
        'instance'          => $this->_instance->instanceName
      );
      $this->view->changeInstancePath = $this->view->url(array('controller' => 'dashboard'), null, true);
    }
  }
    
  /**
   * Build the normal menu (list of pages) and return an array of menu elements
   *
   * @param  string (optional) prefix for menu links
   * @return string
   */
  protected function buildMenu($prefix = '/page/show/') {
    $menus = array();
    if($this->_instance !== null) {
      while($page = $this->_instance->nextPage()) {
        $menus[] = array(
          'label'   => $page->pageHeader,
          'url'     => Zend_Controller_Front::getInstance()->getBaseUrl() . "{$prefix}{$page->pageID}",
          'current' => (isset($this->view->currentPageID) && $this->view->currentPageID == $page->pageID),
          'locked'  => LockModel::isLocked($page),
          'page'     => $page
        );
      }
    }  
    return $menus;
  }

  /**
   * Build the list of tabs (at the top of the window)
   *
   * @return string
   */
  protected function buildPages() {
    $controller = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
    $pages[] = array(
      'label'    => 'Questions',
      'url'      => $this->view->url(array('controller' => 'index'), null, true),
      'current'  => !($this instanceof QFrame_Controller_Admin ||
                      $this instanceof CompareController),
      'external' => false,
    );
    if($this->_user !== null && ($this->_user->hasAccess('administer') || $this->_user->isDomainAdministrator())) $pages[] = array(
      'label'    => 'Administration',
      'url'      => $this->view->url(array('controller' => 'admin'), null, true),
      'current'  => ($this instanceof QFrame_Controller_Admin),
      'external' => false,
    );
    if($this->_user !== null && $this->_user->hasAccess('compare')) $pages[] = array(
      'label'    => 'Compare',
      'url'      => $this->view->url(array('controller' => 'compare'), null, true),
      'current'  => ($this instanceof CompareController),
      'external' => false,
    );
    if(isset(QFrame_Config::instance()->help_url)) {
      $pages[] = array(
        'label'    => 'Online Help',
        'url'      => QFrame_Config::instance()->help_url,
        'current'  => false,
        'external' => true,
      );
    }
    return $pages;
  }
  
  /**
   * Render the lock icon next to a menu item where appropriate
   *
   * @param  PageModel the page for which we are rendering a lock icon
   * @return string
   */
  protected function renderMenuLockIcon(PageModel $page) {
    $builder = new Tag_Builder;
    if(LockModel::isLocked($page)) {
      return '&nbsp;' . $builder->image('lock.png', array('class' => 'inline'));
    }
    return '';
  }
  
  /**
   * Fetch all user parameters that begin with a certain prefix
   *
   * @param  string prefix
   * @return array
   */
  public function getPrefixedParams($prefix) {
    if(substr($prefix, -1, 1) != '_') $prefix .= '_';
    $params = $this->getRequest()->getUserParams();
    foreach($params as $key => $value)
      if(strpos($key, $prefix) != 0) unset($params[$key]);
    return $params;
  }
  
  /**
   * Denies access to a particular resource
   */
  protected function denyAccess() {
    $this->_redirector->gotoRouteAndExit(
      array('controller' => 'error', 'action' => 'access'),
      null,
      true
    );
  }
  
  /**
   * Perform sanity checks located in scripts/checks
   */
  protected function sanityChecks() {
    // build path to the checks directory
    $checks_path = implode(DIRECTORY_SEPARATOR, array(PROJECT_PATH, 'scripts', 'checks'));
    
    // only procede if the checks path exists
    if(file_exists($checks_path)) {
      foreach(scandir($checks_path) as $file) {
        if(substr($file, -4) === '.php') require($checks_path . DIRECTORY_SEPARATOR . $file);
      }
    }
  }
}
