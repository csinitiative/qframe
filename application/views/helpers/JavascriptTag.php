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
class RegQ_View_Helper_JavascriptTag {
  
  /**
   * Generates a javascript link tag or tags
   *
   * @param string|array script or scripts being loaded
   */
  public function javascriptTag($scripts) {
    $output = '';
    if(is_array($scripts)) {
      foreach($scripts as $script) $output .= $this->oneTag($script);
    }
    else {
      $output = $this->oneTag($scripts);
    }
    return $output;
  }
  
  /**
   * Generates a single javascript tag
   *
   * @param  string the basic name of the script (no .js and no path)
   * @return string
   */
  private function oneTag($script) {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    $options = array(
      'src'       => $baseUrl . '/js/' . $script . '.js',
      'type'      => 'text/javascript',
      'charset'   => 'utf-8' 
    );
    $output = '<script';
    foreach($options as $key => $value) {
      $output .= ' ' . $key . '="' . $value . '"';
    }
    return $output .= "></script>\n";
  }
}
