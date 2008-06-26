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
 * Sass_Style
 */
require_once 'Sass/Style.php';


/**
 * Sass_Blank
 */
require_once 'Sass/Blank.php';


/**
 * Sass_Block
 */
require_once 'Sass/Block.php';


/**
 * Sass_Constant
 */
require_once 'Sass/Constant.php';


/**
 * @category   Sass
 * @package    Sass
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Sass_Line {
  
  /**
   * Parse a single line of SASS code
   *
   * @param  string       line to parse
   * @return Sass_Element parsed line
   */
  public static function parse($str) {
    if($str == '') return new Sass_Blank();
    elseif(preg_match('/^!(\w+?)\s*=\s*(.+?)\s*$/', $str, $con_matches)) {
      return new Sass_Constant($con_matches[1], $con_matches[2]);
    }
    elseif(substr($str, 0, 1) == ':' || preg_match('/^([a-z\-]+?):(\s*=\s*|\s+)(.+)$/', $str, $sty_matches)) {
      if(isset($sty_matches) || preg_match('/^:([a-z\-]+?)(\s*=\s*|\s+)(.+)$/', $str, $sty_matches)) {
        return new Sass_Style($sty_matches[1], $sty_matches[3], strstr($sty_matches[2], '=') != false);
      }
    }
    else {
      return new Sass_Block($str);
    }
    
    return new Sass_Blank();
  }
}