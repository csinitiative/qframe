<?php
/**
 * This file is part of QFrame.
 *
 * QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class CryptoController extends QFrame_Controller_Admin {
  
  /**
   * Index action...displays whatever search terms/pages are requested
   */
  public function indexAction() {
    $this->view->q = $this->_getParam('q');
    $page = ($this->_hasParam('page')) ? intval($this->_getParam('page')) : 1;
    $this->view->pager =
        new QFrame_Paginator('CryptoModel', 5, $page, 'name ASC', $this->view->q);
  }
  
  /**
   * Create action.  Creates a new key and redirects back to the index action.
   */
  public function createAction() {
    $name = $this->_getParam('name');
    $secret = $this->_getParam('secret');
    if ($secret === '') $secret = null;
    CryptoModel::generateNewRijndael256Key($name, $secret);
    $this->flash('notice', 'New key successfully created');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
  
  /**
   * Import action.  Imports a key and redirects back to the index action.
   */
  public function importAction() {
    $name = $this->_getParam('name');
    $key = $this->_getParam('key');
    CryptoModel::importRijndael256Key($name, $key);
    $this->flash('notice', 'New key successfully created');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
  
  /**
   * Delete action.  Removes the specified key.
   */
  public function deleteAction() {
    $crypto = new CryptoModel(array('cryptoID' => $this->_getParam('id')));
    $crypto->delete();
    $this->flash('notice', 'Key successfully deleted');
    $this->_redirector->gotoRoute(array('action' => 'index', 'id' => null));
  }
  
  /**
   * Edit action.  Sets a key profile up to be modified.
   */
  public function editAction() {
    $this->view->crypto = new CryptoModel(array('cryptoID' => $this->_getParam('id')));
  }
  
  /**
   * Modify action. Completes the modification of a key profile
   */
  public function modifyAction() {
    $crypto = new CryptoModel(array('cryptoID' => $this->_getParam('id')));
    $crypto->name = $this->_getParam('name');
    $crypto->save();
    $this->flash('notice', 'Key profile successfully modified');
    $this->_redirector->gotoRoute(array('action' => 'index', 'id' => null));
  }
  
}
