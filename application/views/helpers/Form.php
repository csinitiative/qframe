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
class QFrame_View_Helper_Form {
  
  /**
   * Stores the associated view for persistence
   * @var Zend_View_Interface
   */
  private $_view = null;
  
  /**
   * Sets the associated view (should be called automatically by the view)
   *
   * @param Zend_View_Interface
   */
  public function setView($view) {
    $this->_view = $view;
  }
  
  /**
   * Generates a form element (and corresponding close tag)
   *
   * @param  string|array either a url or set of url parameters to use as the form's action
   * @param  boolean      (optional) whether this is an close tag
   * @param  string       (optional) method to use for this form
   * @param  array        (optional) collection of attributes to apply to this tag
   * @return string
   */
  public function form($url, $close = false, $method = 'post', $attribs = null) {
    if($close) return '</form>';
    if(is_array($url)) $url = $this->_view->url($url);
    if($attribs !== null) { 
      $attribs = array_map(
        array(get_class($this), 'produce_combined'),
        array_keys($attribs),
        array_values($attribs));
      $attribs = ' ' . implode($attribs, ' ');
    }
    else $attribs = '';
    return "<form action=\"{$url}\" method=\"{$method}\"{$attribs}>";
  }
  
  /**
   * (PRIVATE) Converts attributes in array form to an attribute string
   *
   * @param  string attribute name
   * @param  string attribute value
   * @return string
   */
  private static function produce_combined($key, $value) {
    return "{$key}=\"{$value}\"";
  }
}
