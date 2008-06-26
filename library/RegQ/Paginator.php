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
class RegQ_Paginator {
  
  /**
   * The name of the class that this paginator will return objects of
   * @var string
   */
  private $className;
  
  /**
   * Page size for this paginator
   * @var integer
   */
  private $pageSize;
  
  /**
   * Current page number
   * @var integer
   */
  private $current;
  
  /**
   * Order by clause to use when fetching objects
   * @var string
   */
  private $order;
  
  /**
   * Search term to use when fetching objects
   * @var string
   */
  private $search;
  
  /**
   * Cached total number of objects
   * @var integer
   */
  private $total = null;

  /**
   * Construct a new paginator
   *
   * @param  string  name of the class that this paginator will paginate
   * @param  integer page size
   * @param  integer current page
   * @param  string  (optional) order by clause to use when fetching objects
   * @param  string  (optional) search term to apply
   */
  function __construct($className, $pageSize, $current, $order = null, $search = null) {
    if(class_exists($className)) {
      $class = new ReflectionClass($className);
      if(!$class->implementsInterface('RegQ_Paginable'))
        throw new Exception('Class must implement the RegQ_Paginable interface');
    }
    else throw new Exception('Invalid class name requested');
        
    $this->className = $className;
    $this->pageSize = $pageSize;
    $this->current = $current;
    $this->order = $order;
    $this->search = $search;
  }
  
  /**
   * Return an array of objects representing the requested page
   *
   * @param  integer page being requested
   * @return Array
   */
  public function page($num) {
    $offset = (($num - 1) * $this->pageSize);
    return call_user_func(
      array($this->className, 'getPage'),
      $this->pageSize,
      $offset,
      $this->order,
      $this->search
    );
  }
  
  /**
   * Returns the current page
   *
   * @return Array
   */
  public function current() {
    return $this->page($this->current);
  }
  
  /**
   * Returns the current page number
   *
   * @return integer
   */
  public function currentNumber() {
    return $this->current;
  }
  
  /**
   * Returns the last valid page number
   *
   * @return integer
   */
  public function lastPageNumber() {
    return intval(ceil($this->total() / $this->pageSize));
  }
  
  /**
   * Returns the first element represented by the current page
   *
   * @return integer
   */
  public function first() {
    return (($this->current - 1) * $this->pageSize) + 1;
  }
  
  /**
   * Returns the last element represented by the current page
   *
   * @return integer
   */
  public function last() {
    $last = $this->first() + $this->pageSize - 1;
    return ($this->total() < $last) ? $this->total() : $last;
  }
  
  /**
   * Returns an array of all available page numbers
   *
   * @return Array or null if no items
   */
  public function pageNumbers() {
    if ($this->lastPageNumber() === 0) return null;

    return range(1, $this->lastPageNumber());
  }
  
  /**
   * Returns the total number of objects
   *
   * @return integer
   */
  public function total() {
    if($this->total === null)
      $this->total = call_user_func(array($this->className, 'count'), $this->search);
    return $this->total;
  }
}
