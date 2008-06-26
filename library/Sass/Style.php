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
 * Sass_Arithmetic
 */
require_once 'Sass/Arithmetic.php';


/**
 * @category   Sass
 * @package    Sass
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Sass_Style implements Sass_Element {
  
  /**
   * Attribute portion of this style (i.e. font-family)
   * @var string
   */
  private $attribute;
  
  /**
   * Value portion of this style (i.e. 1px)
   * @var string
   */
  private $value;

  /**
   * Whether the value should be parsed
   * @var boolean
   */
  private $parse_value;

  /**
   * Class constructor
   *
   * Construct a new Sass_Style object
   *
   * @param string  CSS attribute
   * @param string  CSS value
   * @param boolean whether or not the value should be parsed (":x y" versus ":x = !y")
   */
  public function __construct($attribute, $value, $parse_value = false) {
    $this->attribute = $attribute;
    $this->value = $value;
    $this->parse_value = $parse_value;
  }
  
  /**
   * Declared by Sass_Element; returns the content for this style
   *
   * @return string
   */
  public function content() {
    return $this->attribute . ': ' . $this->value . ';';
  }
  
  /**
   * Parses the value for the list of constants given
   *
   * @param array constants
   */
  public function parse_value($constants) {
    if(!$this->parse_value) return;
    /*if(preg_match_all('/!\w+/', $this->value, $matches, PREG_OFFSET_CAPTURE)) {
      foreach($matches[0] as $match) {
        $this->value = substr_replace($this->value, $constants[substr($match[0], 1)], $match[1], strlen($match[0]));
      }
    }*/
    $arith = new Sass_Arithmetic($this->value, $constants);
    $this->value = $arith->parse();
  }
}