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
 * @category   RegQ
 * @package    RegQ
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   RegQ
 * @package    RegQ
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
interface RegQ_Paginable {
  
  /**
   * Returns a collection of a certain number of objects with
   * a certain offset that optionally match a certain search term
   *
   * @param  integer number of objects to return
   * @param  integer offset at which to begin
   * @param  string  (optional) order clause that results will be ordered by
   * @param  string  (optional) search term
   * @return Array
   */
  public static function getPage($num, $offset, $order = null, $search = null);
  
  /**
   * Returns the count of objects that match a given search criteria
   *
   * @param  string (optional) search term to apply
   */
  public static function count($search = null);
}