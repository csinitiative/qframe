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
class QFrame_View_Helper_StylesheetTag {
  
  /**
   * Generates a link to a stylesheet or stylesheets
   *
   * @param string|array css file(s) being processed
   */
  public function stylesheetTag($stylesheets, $options = array()) {
    $output = '';
    if(is_array($stylesheets)) {
      foreach($stylesheets as $stylesheet) $output .= $this->oneTag($stylesheet, $options);
    }
    else {
      $output = $this->oneTag($stylesheets, $options);
    }
    return $output;
  }
  
  /**
   * Generates a single stylesheet tag
   *
   * @param  string the basic name of the sheet (no .css and no path)
   * @return string
   */
  private function oneTag($stylesheet, $options) {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $options = array_merge(array(
      'rel'       => 'stylesheet',
      'href'      => $baseUrl . '/css/' . $stylesheet . '.css',
      'type'      => 'text/css',
      'media'     => 'screen',
      'charset'   => 'utf-8' 
    ), $options);
    $output = '<link';
    foreach($options as $key => $value) {
      $output .= ' ' . $key . '="' . $value . '"';
    }
    return $output .= " />\n";
  }
}
