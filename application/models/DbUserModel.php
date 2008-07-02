<?php
/**
 * This file is part of the CSI QFrame.
 *
 * The CSI QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI QFrame is distributed in the hope that it will be useful,
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
class DbUserModel implements QFrame_Paginable {

  private $dbUserRow;
  private $dirty;
  static $dbUserTable;
  static $assignmentTable;
  
  /**
   * List of roles that this user has been assigned
   * @var Array
   */
  private $roles = null;
  
  /**
   * Find and return a user by username
   *
   * @param  string username being requested
   * @return DbUserModel
   */
  public static function findByUsername($username) {
    if (!isset(self::$dbUserTable)) self::$dbUserTable = QFrame_Db_Table::getTable('dbUser');
    $where = self::$dbUserTable->getAdapter()->quoteInto('dbUserName = ?', $username);
    $user = self::$dbUserTable->fetchRow($where);
    if($user) return new DbUserModel(array('dbUserID' => $user->dbUserID));
    
    return null;
  }
  
  /**
   * Produces a where clause for the given search term
   *
   * @param  string search term
   * @return string
   */
  private static function searchWhere($search) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $whereParts = array();
    foreach(array('dbUserName', 'dbUserFullName') as $column) {
      $whereParts[] = $adapter->quoteInto("{$column} LIKE ?", "%{$search}%");
    }
    return implode(' OR ', $whereParts);
  }
  
  /**
   * Return all users in the system (or limit results to specified numbers)
   *
   * @param  Array arguments to this method (such as limit, offset, etc)
   * @return Array
   */
  public static function fetchAll($args = array()) {
    $args = array_merge(array(
      'limit'   => null,
      'offset'  => 0,
      'order'   => 'dbUserFullName ASC',
      'search'  => null
    ), $args);
    
    // process search terms into a where clause if any exist
    if($args['search'] !== null) $args['search'] = self::searchWhere($args['search']);
    
    if (!isset(self::$dbUserTable)) self::$dbUserTable = QFrame_Db_Table::getTable('dbUser');
    $users = array();
    $dbUsers = self::$dbUserTable->fetchAll(
      $args['search'],
      $args['order'],
      $args['limit'],
      $args['offset']
    );    
    foreach($dbUsers as $user) {
      $users[] = new DbUserModel(array('dbRow' => $user));
    }
    return $users;
  }
  
  /**
   * Returns the total number of rows in the dbUser table
   *
   * @param  string  (optional) search string to apply to this count
   * @return integer
   */
  public static function count($search = null) {
    if($search !== null) $search = self::searchWhere($search);
    else $search = '1';
    
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    return(intval($adapter->fetchOne("SELECT COUNT(*) FROM `dbUser` WHERE {$search}")));
  }
  
  /**
   * Returns a page of users that match the specified criteria
   *
   * @param  integer number of records that will be returned
   * @param  integer offset (starting record)
   * @param  string  (optional) order clause to apply to results
   * @param  string  (optional) search term
   * @return Array
   */
  public static function getPage($num, $offset, $order = null, $search = null) {
    return self::fetchAll(array(
      'limit'   => $num,
      'offset'  => $offset,
      'order'   => $order,
      'search'  => $search
    ));
  }
  
  private static function hashPassword($password) {
    $saltChars = array_merge(range('a', 'z'), range('0', '9'));
    $salt = '';
    for($i = 0; $i < 10; $i++) {
      $salt .= $saltChars[rand(0, count($saltChars) - 1)];
    }
    return $salt . self::hash($salt . $password);
  }
  
  private static function hash($string) {
    return sha1($string);
  }

  public function __construct ($args = array()) {
    if (!isset(self::$dbUserTable)) self::$dbUserTable = QFrame_Db_Table::getTable('dbUser');
      
    $args = array_merge(array(
      'dbUserFullName'  => null,
      'dbUserActive'    => 'Y'
    ), $args);

    if(isset($args['dbRow'])) $this->dbUserRow = $args['dbRow'];
    elseif (!isset($args['dbUserID'])) {
      if(!isset($args['dbUserName']) || !isset($args['dbUserPW']))
        throw new InvalidArgumentException('New users must be assigned a username and password');
      
      $this->dbUserRow = self::$dbUserTable->createRow();

      $this->dbUserRow->dbUserName = $args['dbUserName'];
      $this->dbUserRow->dbUserPW = self::hashPassword($args['dbUserPW']);
      $this->dbUserRow->dbUserFullName = $args['dbUserFullName'];
      $this->dbUserRow->dbUserActive = $args['dbUserActive'];

      $this->dirty = 1;
    }
    else {
      $where = self::$dbUserTable->getAdapter()->quoteInto('dbUserID = ?', $args['dbUserID']);
      $this->dbUserRow = self::$dbUserTable->fetchRow($where);
    }
    
    // user row assertion
    if ($this->dbUserRow === NULL) throw new Exception('User not found');
  }
  
  public function authenticate($password) {
    if($this->dbUserActive == 'N') return false;
    $salt = substr($this->dbUserRow->dbUserPW, 0, 10);
    $testPassword = self::hash($salt . $password);
    return ($salt . $testPassword) == $this->dbUserRow->dbUserPW;
  }

  public function __get($key) {
    if($key == 'dbUserPW') throw new Exception('Access to dbUserPW is not allowed');
    elseif($key === 'roles') {
      if($this->roles === null) $this->loadRoles();
      return $this->roles;
    }
    elseif(isset($this->dbUserRow->$key)) {
      return $this->dbUserRow->$key;
    }
    else {
      throw new Exception("Attribute not found [$key]");
    }
  }

  public function __set ($key, $value) {
    if($key === 'dbUserPW') {
      $this->dbUserRow->dbUserPW = self::hashPassword($value);
      $this->dirty = 1;
    }
    elseif(isset($this->dbUserRow->$key)) {
      if ($this->dbUserRow->$key !== $value) {
        $this->dbUserRow->$key = $value;
        $this->dirty = 1;
      }
    }
    else {
      throw new Exception("Attribute not found [$key]");
    }
  }

  public function save() {
    if (!$this->dirty) {
      return 1;
    }
    
    if (!isset(self::$dbUserTable)) self::$dbUserTable = QFrame_Db_Table::getTable('dbUser');
    self::$dbUserTable->lock();
    
    if (!$this->dbUserRow->dbUserID && !is_null(self::findByUsername($this->dbUserRow->dbUserName))) {
      self::$dbUserTable->unlock();
      throw new Exception('Attempt to create a user with a duplicate username');  
    }
    
    $this->dbUserRow->save();      
    self::$dbUserTable->unlock();
  }
  
  /**
   * Delete a user from the database
   */
  public function delete() {
    if (!isset(self::$dbUserTable)) self::$dbUserTable = QFrame_Db_Table::getTable('dbUser');
    self::$dbUserTable->lock();
    $this->dbUserRow->delete();      
    self::$dbUserTable->unlock();
  }
  
  /**
   * Load all of the roles associated with this user
   */
  public function loadRoles() {
    $this->roles = array();
    $rolesRowset =
        $this->dbUserRow->findManyToManyRowset('QFrame_Db_Table_Role', 'QFrame_Db_Table_Assignment');
    foreach($rolesRowset as $rolesRow) {
      $this->roles[] = $rolesRow->toArray();
    }
  }
  
  /**
   * Add a role to this user
   *
   * @param RoleModel role to add to this user
   */
  public function addRole(RoleModel $role) {
    if (!isset(self::$assignmentTable)) self::$assignmentTable = QFrame_Db_Table::getTable('assignment');
    self::$assignmentTable->insert(array(
      'dbUserID'  => $this->dbUserID,
      'roleID'    => $role->roleID
    ));
    $this->loadRoles();
  }
  
  /**
   * Remove a role from a user
   *
   * @param RoleModel role to remove
   */
  public function removeRole(RoleModel $role) {
    if (!isset(self::$assignmentTable)) self::$assignmentTable = QFrame_Db_Table::getTable('assignment');
    $adapter = self::$assignmentTable->getAdapter();
    $where = $adapter->quoteInto('dbUserID = ?', intVal($this->dbUserID)) . ' AND ';
    $where .= $adapter->quoteInto('roleID = ?', intVal($role->roleID));
    
    self::$assignmentTable->delete($where);
    $this->loadRoles();
  }
  
  /**
   * Determine whether or not this user has access to a permissible object (or has a global)
   * permission
   *
   * @param  string           permission to check
   * @param  QFrame_Permissible (optional) permissible object to check
   * @return boolean
   */
  public function hasAccess($permission, QFrame_Permissible $permissible = null) {
    if($this->roles === null) $this->loadRoles();
    foreach($this->roles as $role) {
      $role = RoleModel::find($role['roleID']);
      if($role->hasAccess($permission)) return true;
      elseif($permissible !== null && $role->hasAccess($permission, $permissible)) return true;
    }
    return false;
  }
  
  /**
   * Determine whether or not this user has *any* access to this permissible object
   *
   * @param  QFrame_Permissible object being checked
   * @return boolean
   */
  public function hasAnyAccess(QFrame_Permissible $permissible) {
    foreach(array('view', 'edit', 'approve') as $permission) {
      if($this->hasAccess($permission, $permissible)) return true;
    }
    return false;
  }
  
}
