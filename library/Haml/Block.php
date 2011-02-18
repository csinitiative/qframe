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
 * Haml_Element 
 */
require_once 'Haml/Element.php';


/**
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class Haml_Block implements Haml_Element {
  
  /**
   * Stores the code that prefixes this block (i.e. if(), while(), etc.)
   * @var string
   */
  private $code;
  
  /**
   * Class constructor
   *
   * Simple constructor that sets the internal value of $code to the value
   * passed in (for later usage).
   *
   * @param string $code
   */
  public function __construct($code) {
    $this->code = $code;
  }
  
  /**
   * Defined by Haml_Element; whether element is self-closing
   *
   * Code blocks are never self-closing (need {} on either end) hence
   * simply return false.
   *
   * @return boolean
   */
  public function is_closed() { return false; }
  
  /**
   * Defined by Haml_Element; returns the opening tag
   *
   * In the case of a code block, the opening tag is simply the code
   * prefixing the block (and if, while, etc.) followed by a { wrapped
   * in <?php ?>.
   *
   * @return string
   */
  public function start() {
    return '<?php ' . $this->code . ' { ?>';
  }
  
  /**
   * Defined by Haml_Element; returns the closing tag
   *
   * In the case of a code block the closing tag is always simply the
   * string '<?php } ?>'.
   *
   * @return string
   */
  public function end() {
    return '<?php } ?>';
  }
  
  /**
   * Whether or not to hide content (just don't normally)
   *
   * @return boolean
   */
  public function hide_content() {
    return false;
  }
}