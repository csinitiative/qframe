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
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
interface Haml_Element {
  /**
   * Whether element is self-closing
   *
   * @return boolean
   */
  public function is_closed();
  
  /**
   * Start tag for element
   *
   * @return string
   */
  public function start();
  
  /**
   * End tag for element
   *
   * @return string
   */
  public function end();
  
  /**
   * Whether the content of this element should be hidden (not rendered)
   *
   * @return boolean
   */
  public function hide_content();
}