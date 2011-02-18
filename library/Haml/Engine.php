<?php
/**
 * This file is part of the CSI SIG.
 *
 * The CSI SIG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI SIG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */

/**
 * Haml_Parser
 */
require_once 'Haml/Parser.php';


/**
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class Haml_Engine {
  
  /**
   * Base path (where scripts are located)
   * @var string
   */
  private $_base;

  /**
   * Contents of the rendered action view script
   * @var string
   */
  private $_yield = null;

  /**
   * Assigned variables
   * @var array
   */
  private $_assigns = array();

  /**
   * Stores a reference to the object that undefined method callbacks will cascade to
   * @var mixed
   */
  private $_undef = null;

  /**
   * Class constructor
   *
   * Constructs a new Haml_Engine object
   *
   * @param string base path (where scripts are located)
   */
  public function __construct($base) {
    $this->_base = $base;
  }
  
  /**
   * Returns the current base path for this engine object
   *
   * @return string
   */
  public function getBase() {
    return $this->_base;
  }
  
  /**
   * Sets a new base path for this engine
   *
   * @param string new base path
   */
  public function setBase($base) {
    $this->_base = $base;
  }
  
  /**
   * Assigns a variable to templates rendered by this engine
   *
   * @param string variable name
   * @param mixed  variable value
   */
  public function assign($key, $val) {
    $this->_assigns[$key] = true;
    $this->$key = $val;
    return;
  }
  
  /**
   * Fetches and returns the value of an assigned variable
   *
   * @param string variable name
   * @return mixed
   */
  public function getAssign($key) {
    if(isset($this->_assigns[$key]) && $this->_assigns[$key]) return $this->$key;
    return null;
  }
  
  /**
   * Is the specified variable assigned?
   *
   * @param  string variable name
   * @return boolean
   */
  public function is_assigned($name) {
    return isset($this->_assigns[$name]);
  }
    
  /**
   * Clears an assigned variable for templates rendered by this engine
   *
   * @param string variable name
   */
  public function unassign($name) {
    if(isset($this->_assigns[$name])) {
      unset($this->_assigns[$name]);
      unset($this->$name);
    }
    return;
  }
  
  /**
   * Clears all assigned variables for templates rendered by this engine
   */
  public function clear_assigns() {
    foreach(array_keys($this->_assigns) as $name) {
      unset($this->_assigns[$name]);
      unset($this->$name);
    }
    return;
  }
  
  /**
   * Renders the template with the specified name, surrounded by appropriate template
   *
   * @param  string template name
   * @param  bool   (optional) should a layout be rendered
   * @param  Array  local variables to set for the render
   * @return string
   */
  public function render($name, $render_layout = true, $locals = array()) {    
    $layout_path = $this->_base . DIRECTORY_SEPARATOR . 'layouts';
    
    if($render_layout) {
      $request = Zend_Controller_Front::getInstance()->getRequest();
      $controller = $request->getControllerName();
      $action = $request->getActionName();
      $actnTmpl = implode(DIRECTORY_SEPARATOR, array($layout_path, $controller, $action . '.haml'));
      $ctrlTmpl = implode(DIRECTORY_SEPARATOR, array($layout_path, $controller . '.haml'));
      $applTmpl = $layout_path . DIRECTORY_SEPARATOR . 'application.haml';
      if(file_exists($actnTmpl)) $template = $actnTmpl;
      elseif(file_exists($ctrlTmpl)) $template = $ctrlTmpl;
      else $template = $applTmpl;
    }
    
    $localString = '';
    foreach($locals as $localName => $localValue) {
      $localString .= "\${$localName} = \"{$localValue}\";";
    }

    if(isset($template)) {
      $rendered = $this->renderTemplate($name);
      ob_start();
      eval("{$localString}?>" . $rendered);
      $this->_yield = ob_get_clean();
      $rendered = $this->renderTemplate($template, false);
    }
    else $rendered = $this->renderTemplate($name);
        
    return eval("{$localString}?>" . $rendered);
  }
  
  /**
   * Returns the rendered action view (for situations where a layout is present)
   *
   * @return string
   */
  public function yield() {
    return (null === $this->_yield) ? '' : $this->_yield;
  }
  
  /**
   * Renders a template or returns the rendered template from cache (if appropriate)
   *
   * @param  string name of the template to render
   * @param  bool   whether or not the template name is relative
   * @return string
   */
  private function renderTemplate($name, $relative = true) {
    $name = ($relative) ? $name : substr($name, strlen($this->_base) + 1);
    
    if(QFrame_Config::instance()->cache_templates) {
      $cache_path = $this->_base . DIRECTORY_SEPARATOR . 'cache';
      $cache_file = $cache_path . DIRECTORY_SEPARATOR . preg_replace('/\.haml$/', '.php', $name);
      $template_file = $this->_base . DIRECTORY_SEPARATOR . $name;
      if(file_exists($cache_file) && filemtime($cache_file) > filemtime($template_file)) {
        $rendered = file_get_contents($cache_file);
      }
      else {
        $rendered = $this->renderHamlTemplate($name);
        if(!file_exists(dirname($cache_file))) mkdir(dirname($cache_file), 0775, true);
        file_put_contents($cache_file, $rendered);
      }
    }
    
    return (isset($rendered)) ? $rendered : $this->renderHamlTemplate($name);
  }
  
  /**
   * Performs a HAML render of the requested template
   *
   * @param  string name of the template to render
   * @return string
   */
  private function renderHamlTemplate($name) {
    $parser = new Haml_Parser(file_get_contents($this->_base . DIRECTORY_SEPARATOR . $name));
    return $parser->render();
  }
  
  /**
   * Sets the object that will be called in the case of an undefined method call
   *
   * @param mixed object who undefined method calls will cascade upon
   */
  public function setUndefined($obj) {
    $this->_undef = $obj;
  }
  
  /**
   * Handle undefined method calls
   *
   * @param string method being called
   * @param array  list of arguments
   */
  public function __call($method, $args) {
    if($this->_undef === null)
      throw new Exception('Method ' . $method . ' could not be cascaded because no undef object is defined');
    else
      return call_user_func_array(array(&$this->_undef, $method), $args);
  }
}
