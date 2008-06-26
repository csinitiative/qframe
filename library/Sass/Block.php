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
 * @category   Sass
 * @package    Sass
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * Sass_Element
 */
require_once 'Sass/Element.php';


/**
 * @category   Sass
 * @package    Sass
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Sass_Block implements Sass_Element {
  
  /**
   * Base selector (selector that is actually on this line)
   * @var string
   */
  private $base_selector;
  
  /**
   * Parent selector (selector that prefixes this line's selector)
   * @var string
   */
  private $parent_selector = NULL;

  /**
   * Indent level of this block
   * @var integer
   */
  private $indent_level = 0;

  /**
   * Indent string to use for this block
   * @var string
   */
  private $indent_string = '  ';

  /**
   * List of styles for this block
   * @var array
   */
  private $styles = array();

  /**
   * Class constructor
   *
   * Create a new Sass_Block object
   *
   * @param string base selector of this new block
   */
  public function __construct($base_selector) {
    $this->base_selector = $base_selector;
  }
  
  /**
   * Set indent level and string for this block
   *
   * @param integer indent level
   * @param string  indent string
   */
  public function indent($level, $string) {
    $this->indent_level = $level;
    $this->indent_string = $string;
  }
  
  /**
   * Set the parent selector of this block
   *
   * @param string parent selector
   */
  public function parent_selector($selector) {
    $this->parent_selector = $selector;
  }
  
  /**
   * Gets the selector for this block\
   *
   * @return string
   */
  public function selector() {
    $split_selector = explode(',', $this->base_selector);
    
    if(is_null($this->parent_selector)) return $this->base_selector;
    elseif(substr($this->base_selector, 0, 1) == '&')
      return $this->parent_selector . substr($this->base_selector, 1);
    elseif(count($split_selector) > 1) {
      $final_selector = '';
      foreach($split_selector as $partial_selector) {
        if($final_selector !== '') $final_selector .= ', ';
        $final_selector .= $this->parent_selector . ' ' . trim($partial_selector);
      }
      return $final_selector;
    }
    else return $this->parent_selector . ' ' . $this->base_selector;
  }
  
  /**
   * Get the indent level of this block
   *
   * @return integer
   */
  public function indent_level() {
    return $this->indent_level;
  }
  
  /**
   * Declared by Sass_Element; returns the content for this selector
   *
   * @return string
   */
  public function content() {
    $indent = str_repeat($this->indent_string, $this->indent_level);
    $next_indent = $indent . $this->indent_string;
    return $indent . $this->selector() . " {\n" . $next_indent . implode($this->styles, "\n" . $next_indent) . " }";
  }
  
  /**
   * Add a style to this block
   *
   * @param string style to add
   */
  public function add_style($style) {
    array_push($this->styles, $style);
  }
}