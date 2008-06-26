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
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * Haml_Element
 */
require_once 'Haml/Element.php';


/**
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Haml_Comment implements Haml_Element {
  
  /**
   * Whether or not this is a silent comment
   * @var boolean
   */
  private $silent = false;
  
  /**
   * Construct a new comment (or possibly a silent comment)
   *
   * @param boolean (optional) is this a silent comment?
   */
  public function __construct($silent = false) {
    $this->silent = $silent;
  }
  
  /**
   * Defined by Haml_Element; whether element is self-closing
   *
   * Since this class only handles multi-line comments it is never going to
   * be self closed so simply return false.
   *
   * @return boolean
   */
  public function is_closed() { return false; }

  /**
   * Defined by Haml_Element; returns the opening tag
   *
   * In the case of multi-line comments the opening tag is always <!-- so simply
   * returns that string.
   *
   * @return string
   */
  public function start() { return ($this->silent) ? '' : '<!--'; }

  /**
   * Defined by Haml_Element; returns the closing tag
   *
   * In the case of multi-line comments the closing tag is always --> so
   * simply returns that string.
   *
   * @return string
   */
  public function end() { return ($this->silent) ? '' : '-->'; }
  
  /**
   * Whether or not to hide content (just don't normally)
   *
   * @return boolean
   */
  public function hide_content() {
    return $this->silent;
  }
}