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
class RoleModel implements QFrame_Paginable {
  
  /**
   * Table object that members of this class will use to perform
   * database actions
   * @var QFrame_Db_Table_Role
   */
  private static $roleTable;
  
  /**
   * List of attributes that cannot be set
   * @var Array
   */
  private static $restrictedAttributes = array('roleID', 'ACLstring');
  
  /**
   * Zend_Db_Table_Row object representing this role in the database
   * @var Zend_Db_Table_Row
   */
  private $row;
  
  /**
   * Stores original value of each attribute (to decide whether a save is really necessary)
   * @var Array
   */
  private $originalAttributes;
  
  /**
   * Stores whether or not this object has yet been persisted
   * @var boolean
   */
  private $persisted = true;
  
  /**
   * Stores the actual ACL object associated with this role
   * @var Zend_Acl
   */
  private $acl;

  /**
   * (Private) constructor.  Allows the fetch methods that are part of this
   * class to construct new RoleModel objects without allowing new objects to
   * be constructed by a user.
   *
   * @param Zend_Db_Table_Row_Abstract database row that this object represents
   */
  private function __construct(Zend_Db_Table_Row_Abstract $row) {
    $this->row = $row;
    $this->acl = unserialize($row->ACLstring);
    if(!($this->acl instanceof Zend_Acl)) {
      $this->acl = new Zend_Acl;
    }
  }
  
  /**
   * Set a single property
   *
   * @param string property name
   * @param mixed  property value
   */
  public function __set($name, $value) {
    if($name === 'acl') $this->acl = $value;
    else{
      $this->row->$name = $value;
    }
  }
  
  /**
   * Get a single property
   *
   * @param  string property name
   * @return mixed
   */
  public function __get($name) {
    if($name === 'acl') return $this->acl;
    if($name === 'domain') return $this->domain();
    return $this->row->$name;
  }

  /**
   * Get the domain of the user
   *
   * @return DomainModel
   */
  public function domain() {
    $domain = DomainModel::find($this->row->domainID);
    return $domain;
  }
  
  /**
   * Return whether or not this role has access to a certain resource
   *
   * @param  string            the permission being asked about
   * @param  QFrame_Permissible  (optional) the permissible object in question
   * @return boolean
   */
  public function hasAccess($permission, QFrame_Permissible $permissible = null) {
    $resource = ($permissible === null) ? "GLOBAL" : $permissible->getPermissionID();
    if(!$this->acl->hasRole($permission) || !$this->acl->has($resource)) return false;
    return $this->acl->isAllowed($permission, $resource);
  }
  
  /**
   * Grant access to this role for a particular permissible object (or globally)
   *
   * @param  string           permission to grant
   * @param  QFrame_Permissible (optional) permissible object to grant access to
   */
  public function grant($permission, QFrame_Permissible $permissible = null) {
    $resource = ($permissible === null) ? "GLOBAL" : $permissible->getPermissionID();
    if(!$this->acl->hasRole($permission)) $this->acl->addRole(new Zend_Acl_Role($permission));
    if(!$this->acl->has($resource)) $this->acl->add(new Zend_Acl_Resource($resource));
    $this->acl->allow($permission, $resource);
  }
  
  /**
   * Deny access to this role for a particular permissible object (or globally)
   *
   * @param  string           permission to deny
   * @param  QFrame_Permissible (optional) permissible object to deny access to
   */
  public function deny($permission, QFrame_Permissible $permissible = null) {
    $resource = ($permissible === null) ? "GLOBAL" : $permissible->getPermissionID();
    if(!$this->acl->hasRole($permission)) $this->acl->addRole(new Zend_Acl_Role($permission));
    if(!$this->acl->has($resource)) $this->acl->add(new Zend_Acl_Resource($resource));
    $this->acl->deny($permission, $resource);
  }

  /**
   * Set a bunch of attributes at once
   *
   * @param  Array   hash of attributes and their values
   * @param  boolean (optional) whether to enforce restrictions on setting attributes
   * @return RoleModel
   */
  public function setAttributes($attributes, $restrict = true) {
    // make sure what was passed in was an array
    if(!is_array($attributes)) throw new exception('RoleModel::setAttributes() requires an array.');
  
    // check to make sure that an invalid or restricted property has not been referenced
    foreach($attributes as $attribute => $value) {
      if(!isset($this->row->$attribute))
        throw new Exception("Attempt to set property [{$attribute}] which does not exist.");
      if($restrict && in_array($attribute, self::$restrictedAttributes))
        throw new Exception("Attempt to set property [{$attribute}] which is restricted.");
    }
    $this->row->setFromArray($attributes);
    return $this;
  }
  
  /**
   * Persist a role to the database
   *
   * @return RoleModel
   */
  public function save() {
    $this->row->ACLstring = serialize($this->acl);
    $this->row->save();
    return $this;
  }
  
  /**
   * Delete a role from the database
   */
  public function delete() {
    $this->row->delete();
    return $this;
  }
  
  /**
   * Create a new RoleModel object
   *
   * @param  Array     list of attributes for this RoleModel object
   * @return RoleModel
   */
  public static function create($attributes) {
    if (!isset(self::$roleTable)) self::$roleTable = QFrame_Db_Table::getTable('role');
    $role = new RoleModel(self::$roleTable->createRow());
    return $role->setAttributes($attributes);
  }
  
  /**
   * Returns a RoleModel or RoleModels that match the given criteria
   *
   * @param  integer the ID of the role being looked for (or one of the strings 'first' or 'all')
   * for the more involved find() syntax)
   * @param  Array   (optional) in the more advanced form of find() this is where additional
   * options are specified.
   * @return RoleModel
   */
  public static function find($id, $args = array()) {
    if (!isset(self::$roleTable)) self::$roleTable = QFrame_Db_Table::getTable('role');
 
    // if the first argument is numeric, treat it as an ID
    if(is_numeric($id)) 
      return new RoleModel(self::$roleTable->find(intval($id))->current());
    
    // if we have been asked to retrieve just the first matching element
    if($id === 'first') {
      $args['limit'] = 1;
      $roles = self::_find($args);
      return $roles[0];
    }
    elseif($id === 'all') {
      return self::_find($args);
    }
    else
      throw new Exception('First argument must be an integer or the string \'first\' or \'all\'.');
  }
  
  /**
   * Returns a RoleModel or RoleModels that match the given criteria (specifically that have
   * a certain value for a certain attribute)
   *
   * @param  string either 'first' or 'all' controlling whether everything is returned or
   * just the first match
   * @param  string the attribute name we are finding by
   * @param  string the value that attribute should have
   * @param  Array  (optional) 
   * @return RoleModel
   */
  public static function findBy($type, $attribute, $value, $args = array()) {
     if (!isset(self::$roleTable)) self::$roleTable = QFrame_Db_Table::getTable('role');

    // build the where clause that will confine results to the given attribute/value
    // and add it to any existing where clause
    $where = self::$roleTable->getAdapter()->quoteInto("`{$attribute}` = ?", $value);
    $args['where'] = (isset($args['where'])) ? "{$where} AND {$args['where']}" : $where;
    
    return self::find($type, $args);
  }
  
  /**
   * Returns an array of RoleModel objects matching the given criteria
   *
   * @param  Array (optional) various parameters to use when querying (recognized keys are
   * where, order, limit, and offset)
   * @return Array
   */
  public static function _find($args = array()) {
    if (!isset(self::$roleTable)) self::$roleTable = QFrame_Db_Table::getTable('role');

    // set up default values for all of the allowed arguments
    $args = array_merge(array(
      'where'   => null,
      'order'   => null,
      'limit'   => null,
      'offset'  => null
    ), $args);
    
    $roles = array();
    $roleRows =
        self::$roleTable->fetchAll($args['where'], $args['order'], $args['limit'], $args['offset']);
    foreach($roleRows as $row) $roles[] = new RoleModel($row);
    return $roles;
  }
  
  /**
   * Reload this object's data based on a given key
   *
   * @param integer primary key
   */
  public function _load($key) {
    if (!isset(self::$roleTable)) self::$roleTable = QFrame_Db_Table::getTable('role');
    $data = self::$roleTable->find($key)->current()->toArray();
    $this->setAttributes($data, false);
    $this->acl = unserialize($data['ACLstring']);
  }
  
  /**
   * Get one page worth of results
   *
   * @param  integer number of objects to return
   * @param  integer offset from the beginning of the result set
   * @param  string  (optional) order clause to apply to the result set
   * @param  string  (optional) search term to apply to the result set
   * @param  DomainModel (optional) limit the search to this domain
   * @return Array
   */
  public static function getPage($num, $offset, $order = null, $search = null, $domain = null) {
    $where = ($search === null) ? null : self::searchWhere($search);
    if ($domain) {
      $where .= $where ? ' AND ' : '';
      $where .= "domainID = {$domain->domainID}";
    }
    return self::_find(array(
      'where'   => $where,
      'order'   => $order,
      'limit'   => $num,
      'offset'  => $offset
    ));
  }
  
  /**
   * Returns the count of roles that match a given search criteria
   *
   * @param  string (optional) search term to apply
   * @param  DomainModel (optional) limit the search to this domain
   * @return integer
   */
  public static function count($search = null, $domain = null) {
    if($search !== null) $where = self::searchWhere($search);
    else $where = '1';

    if ($domain) {
      $where .= " AND domainID = {$domain->domainID}";
    }
    
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    return(intval($adapter->fetchOne("SELECT COUNT(*) FROM `role` WHERE {$where}")));
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
    foreach(array('roleDescription') as $column) {
      $whereParts[] = $adapter->quoteInto("{$column} LIKE ?", "%{$search}%");
    }
    return "(" . implode(' OR ', $whereParts) . ")";
  }
}
