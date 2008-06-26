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
class Sass_Constant implements Sass_Element {
  /**
   * Value of this constant
   * @var string
   */
  private $value;
  
  /**
   * Name of this constant
   * @var string
   */
  private $name;

  /**
   * Class constructor
   *
   * Create a new constant
   *
   * @param string constant name
   * @param string constant value
   */
  public function __construct($name, $value) {
    $this->name = $name;
    $this->value = $value;
  }
  
  /**
   * Declared by Sass_Element; returns the value of this constant
   *
   * @return string
   */
  public function content() {
    return $this->value;
  }
  
  /**
   * Returns the name of this constant
   *
   * @return string
   */
  public function name() {
    return $this->name;
  }
}