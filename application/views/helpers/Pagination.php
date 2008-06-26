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
class RegQ_View_Helper_Pagination {
  
  /**
   * Stores the associated view for persistence
   * @var Zend_View_Interface
   */
  private $_view = null;
  
  /**
   * Sets the associated view (should be called automatically by the view)
   *
   * @param Zend_View_Interface
   */
  public function setView($view) {
    $this->_view = $view;
  }
  
  /**
   * Generates a list of pagination links
   *
   * @param  RegQ_Paginator paginator object
   * @param  string         (optional) action to use for page link
   * @param  string         (optional) variable to use for page number
   * @param  Array          (optional) list of additional parameters to add to each link
   * @return string
   */
  public function pagination($pager, $action = 'page', $pageNum = 'id', $params = array()) {
    if(count($pager->pageNumbers()) <= 1) return '';
    
    $controller = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
    $b = new Tag_Builder;
    $content = '';
    
    // set up an array of arguments that are common to all links
    $linkArgs = array_merge($params, array(
      'controller'  => $controller,
      'action'      => $action
    ));
    
    // print out links to each page number (unless it is the current page number
    // in which case print out just the number, no link)
    foreach($pager->pageNumbers() as $page) {      
      if($page == $pager->currentNumber()) $pageContent = "{$page} ";
      else {
        $linkArgs[$pageNum] = $page;
        $pageContent = $this->_view->linkTo($linkArgs,"{$page}") . ' ';
      } 
      $content .= $b->li($pageContent);
    }
    
    // prepend a previous page link if there is/are previous page(s), otherwise
    // just the equivalent text
    if($pager->currentNumber() > 1) {
      $linkArgs[$pageNum] = $pager->currentNumber() - 1;
      $content = $b->li(
        $this->_view->linkTo($linkArgs, '< prev'),
        ' ',
        $content
      );
    }
    
    // append a next page link if there is/are next page(s), otherwise just
    // the equivalent text
    if($pager->currentNumber() < $pager->lastPageNumber()) {
      $linkArgs[$pageNum] = $pager->currentNumber() + 1;
      $content .= $b->li(
        $this->_view->linkTo($linkArgs,'next >'),
        ' '
      );
    }
    
    return $b->ol(
      array('class' => 'pagination'),
      $content
    );
  }
}
