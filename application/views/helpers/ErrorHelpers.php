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
 * @category   QFrame_View
 * @package    QFrame_View_Helper
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * @category   QFrame_View
 * @package    QFrame_View_Helper
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class QFrame_View_Helper_ErrorHelpers {
  
  /**
   * Stores the associated view for persistence
   * @var Zend_View_Interface
   */
  private $view = null;
  
  /**
   * Sets the associated view (should be called automatically by the view)
   *
   * @param Zend_View_Interface
   */
  public function setView($view) {
    $this->view = $view;
  }
  
  /**
   * Makes a path relative to the root of the application (as opposed to an absolute path)
   *
   * @param  string absolute path
   * @return string
   */
  public function relativizePath($path) {
    if(substr($path, 0, strlen(PROJECT_PATH)) === PROJECT_PATH) {
      return substr($path, strlen(PROJECT_PATH) + 1);
    }
    return $path;
  }
  
  /**
   * Returns a readable version of a call in a stack trace (returned by Exception->getTrace())
   *
   * @param  Array single call in the call stack
   * @return string
   */
  public function stringifyCall($call) {
    $string = "{$this->relativizePath($call['file'])}({$call['line']}): ";
    if(isset($call['class'])) $string .= "{$call['class']}{$call['type']}";
    $string .= "{$call['function']}({$this->stringifyParameters($call)})";
    
    return $this->view->h($string);
  }
  
  /**
   * Returns a readable version of a parameter list from a stack trace call
   *
   * @param  Array single call in the call stack
   * @return string
   */
  public function stringifyParameters($call) {
    foreach($call['args'] as $arg) {
      if(is_object($arg)) $args[] = 'Object(' . get_class($arg) . ')';
      elseif(is_string($arg)) $args[] = "'{$arg}'";
      elseif(is_array($arg)) $args[] = 'Array(' . count($arg) . ')';
      else $args[] = $arg;
    }
    return (isset($args)) ? implode(', ', $args) : '';
  }
}
