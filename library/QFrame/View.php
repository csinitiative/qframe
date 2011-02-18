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
 * @category   QFrame
 * @package    QFrame
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */

/**
 * Haml_Engine
 */
require_once 'Haml/Engine.php';


/**
 * @category   QFrame
 * @package    QFrame
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class QFrame_View implements Zend_View_Interface {
  
  /**
   * Haml_Engine object used to do the actual work
   * @var Haml_Engine
   */
  private $_haml;

  /**
   * Current list of helper paths
   * @var array
   */
  private $_helpers = array();

  /**
   * Determines whether a layout will be rendered
   * @var bool
   */
  private $_renderLayout = true;

  /**
   * Class constructor
   *  
   * @param string template path
   */
  public function __construct($templatePath = '') {
    $this->_haml = new Haml_Engine($templatePath);
    $this->_haml->setUndefined($this);
  }
  
  /**
   * Return the template engine object
   *
   * @return Haml_Engine
   */
  public function getEngine() {
    return $this->_haml;
  }
  
  /**
   * Set the path to the templates
   *
   * @param string $path Directory to set as the path
   */
  public function setScriptPath($path) {
    if(is_readable($path)) $this->_haml->setBase($path);
    else throw new Exception('Invalid path provided');
    
    return;
  }
  
  /**
   * Retrieves the current template directory
   *
   * @return string
   */
  public function getScriptPaths() {
    return array($this->_haml->getBase());
  }
  
  /**
   * Alias for setScriptPath
   *
   * @param string $path
   * @param string $prefix Unused
   */
  public function setBasePath($path, $prefix = 'Zend_View') {
    return $this->setScriptPath($path);
  }
  
  /**
   * Alias for setScriptPath
   *
   * @param string $path
   * @param string $prefix Unused
   */
  public function addBasePath($path, $prefix = 'Zend_View') {
    return $this->setScriptPath($path);
  }
  
  /**
   * Sets the helper path to a single path or list of paths
   *
   * @param string|array path(s) to set
   * @param string       (optional) prefix to use for path(s)
   */
  public function setHelperPath($path, $prefix = 'QFrame_View_Helper') {
    array_splice($this->_helpers, 0);
    if(is_array($path)) {
      foreach($path as $p) $this->addHelperPath($p, $prefix);
    }
    else {
      $this->addHelperPath($path, $prefix);
    }
  }
  
  /**
   * Adds a single path to the list of helpers
   *
   * @param string path to add
   * @param string (optional) prefix to assign to this path
   */
  public function addHelperPath($path, $prefix = 'QFrame_View_Helper') {
    $this->_helpers[$path] = $prefix;
  }
  
  /**
   * Turn on/off view rendering
   *
   * @param bool should layout rendering be on
   */
  public function setRenderLayout($r) {
    $this->_renderLayout = $r;
  }
  
  /**
   * Sets an assigned variable
   *
   * @param string variable name
   * @param string variable value
   */
  public function __set($key, $val) {
    $this->_haml->assign($key, $val);
  }
  
  /**
   * Gets an assigned variable
   *
   * @param  string variable name
   * @return mixed
   */
  public function __get($key) {
    return $this->_haml->getAssign($key);
  }
  
  /**
   * Unsets an assigned variable
   *
   * @param string variable name
   */
  public function __unset($key) {
    $this->_haml->unassign($key);
  }
  
  /**
   * Checks whether or not an assigned variable has been set
   *
   * @param string variable name
   */
  public function __isset($key) {
    return $this->_haml->is_assigned($key);
  }
  
  /**
   * Assigns one or more variables to the template
   *
   * @param string|array either a variable name or array of variable => value pairs
   * @param mixed        if first param is a string, the value of this variable
   */
  public function assign($spec, $value = null) {
    if(is_array($spec)) {
      foreach($spec as $key => $val) $this->__set($key, $val);
    }
    elseif(!is_null($value)) {
      $this->__set($spec, $value);
    }
    else throw new Exception("You must specify either an array or a variable *and* value");
    
    return;
  }
  
  /**
   * Clear all assigned variables
   */
  public function clearVars() {
    $this->_haml->clear_assigns();
    return;
  }
  
  /**
   * Renders a specified template
   *
   * @param  string template name
   * @param  Array  (optional) array of local variables to assign before rendering
   * @return string
   */
  public function render($name, $locals = array()) {
    ob_start();
    $this->_haml->render($name, $this->_renderLayout, $locals);
    $html = ob_get_clean();
    /*if(function_exists('tidy_repair_string')) {
      $tidy_config = array(
        'indent'        => true,
        'wrap'          => 120,
        'hide-comments' => true
      );
      $html = tidy_repair_string($html, $tidy_config);
    }*/
    return $html;
  }
  
  /**
   * Renders a partial template
   *
   * @param  string      template name (will be prepended with '_' to make filename)
   * @param  mixed|Array object or array of objects to provide to the partial
   * @param  boolean     (optional) treat object argument as a collection
   * @param  boolean     (optional) render application wide partial
   * @param  Array       array of local variables to set before rendering
   * @return string 
   */
  public function renderPartial($name, $object, $arr = false, $appl = false, $locals = array()) {
    if($arr) {
      $content = '';
      foreach($object as $individual)
        $content .= $this->renderPartial($name, $individual, false, $appl, $locals);
      return $content;
    }
    
    // Figure out the relative filename of the partial template to render (based on
    // whether or not the user requested an application global)
    if($appl) {
      $filename = implode(DIRECTORY_SEPARATOR, array('scripts', 'application', "_{$name}.haml"));
    }
    else {
      $filename = implode(DIRECTORY_SEPARATOR, array(
        'scripts',
        Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
        "_{$name}.haml"
      ));
    }
    
    ob_start();
    if($this->_haml->is_assigned($name)) $originalValue = $this->_haml->getAssign($name);
    $this->_haml->assign($name, $object);
    $this->_haml->render($filename, false, $locals);
    $html = ob_get_clean();
    if(isset($originalValue)) $this->_haml->assign($name, $originalValue);
    return $html;
  }
  
  /**
   * Escapes special HTML characters in strings (required for some ZF helpers)
   *
   * @param  string string to escape
   * @return string
   */
  public function escape($str) {
    return htmlspecialchars($str);
  }
  
  /**
   * Handle calls to undefined methods
   *
   * @param string method name
   * @param array  arguments
   */
  public function __call($method, $args) {
    // First, check to see if there is a standard style Zend helper (class and method)
    // named the same as the helper
    foreach($this->_helpers as $path => $prefix) {
      if(substr($prefix, -1, 1) != '_') $prefix .= '_';
      $helperFile = $path . DIRECTORY_SEPARATOR . ucfirst($method) . '.php';
      if(file_exists($helperFile)) {
        require_once($helperFile);
        $class = new ReflectionClass($prefix . ucfirst($method));
        $object = $class->newInstance();
        if(method_exists($object, 'setView')) $object->setView($this);
        return call_user_func_array(array(&$object, $method), $args);
      }
    }
    
    // Second, check to see if there is a class named something of the form "ControllerHelpers"
    // that has a method with the same name as what has been called
    $controllerName = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
    foreach($this->_helpers as $path => $prefix) {
      if(substr($prefix, -1, 1) != '_') $prefix .= '_';
      $globalHelperFile = $path . DIRECTORY_SEPARATOR . ucfirst($controllerName) . 'Helpers.php';
      if(file_exists($globalHelperFile)) {
        require_once($globalHelperFile);
        $class = new ReflectionClass($prefix . ucfirst($controllerName) . 'Helpers');
        $object = $class->newInstance();
        if(method_exists($object, 'setView')) $object->setView($this);
        if(method_exists($object, $method))
          return call_user_func_array(array(&$object, $method), $args);
      }
    }
    
    // Lastly, check to see if there is a class named ApplicationHelpers with a method named the
    // same thing as the helper that was called.
    foreach($this->_helpers as $path => $prefix) {
      if(substr($prefix, -1, 1) != '_') $prefix .= '_';
      $applicationHelperFile = $path . DIRECTORY_SEPARATOR . 'ApplicationHelpers.php';
      if(file_exists($applicationHelperFile)) {
        require_once($applicationHelperFile);
        $class = new ReflectionClass($prefix . 'ApplicationHelpers');
        $object = $class->newInstance();
        if(method_exists($object, 'setView')) $object->setView($this);
        if(method_exists($object, $method))
          return call_user_func_array(array(&$object, $method), $args);
      }
    }
    
    throw new Exception('Helper method ' . $method . ' is not defined');
  }
}
