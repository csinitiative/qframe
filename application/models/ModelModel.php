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
 * @category   Application
 * @package    Models
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   Application
 * @package    Models
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class ModelModel {
  
  /**
   * Stores the table object used by this class
   * @var QFrame_Db_Table_Model
   */
  private static $table = null;
  
  /**
   * Stores the row object associated with this ModelModel
   * @var QFrame_Db_Table_Row
   */
  private $row;
  
  /**
   * Initialize or return (if already initialized) this class's table object
   *
   * @return QFrame_Db_Table_Model
   */
  public static function table() {
    if(self::$table === null) self::$table = new QFrame_Db_Table_Model;
    return self::$table;
  }
  
  /**
   * Process a conditions argument and return a where clause
   *
   * @param  string|array conditions being processed
   * @return string
   */
  private static function buildWhere($conditions) {
    $result = null;
    
    if(is_array($conditions)) {
      $pieces = explode('?', '#' . array_shift($conditions) . '#');
      $result = array_shift($pieces);
      if(count($pieces) !== count($conditions)) throw new Exception('Invalid conditions');
      foreach($conditions as $value) {
        $result .= self::table()->getAdapter()->quote($value) . array_shift($pieces);
      }
      $result = substr($result, 1, -1);
    }
    elseif($conditions !== null) $result = $conditions;
    
    return $result;
  }
  
  /**
   * Find a set of models that matches the given criteria
   *
   * TODO - This will need to be enriched to deal with $what = ID or array of IDs
   *
   * @param  string|array the string 'all', 'first', a single ID, or an array of IDs
   * @param  string|array (optional) a conditions string or an array of conditions
   * @param  string       (optional) order by clause
   * @return ModelModel|array 
   */
  public static function find($what, $conditions = null, $order = null) {
    $models = array();
    
    if($what === 'all') {
      foreach(self::table()->fetchAll(self::buildWhere($conditions), $order) as $row) {
        $models[] = new ModelModel($row);
      }
    }
    elseif($what === 'first') {
      $row = self::table()->fetchRow(self::buildWhere($conditions), $order);
      return ($row) ? new ModelModel($row) : null;
    }
    
    return $models;
  }
  
  /**
   * Find a set of models that matches the given criteria
   *
   * @param  string       the name of the property to limit the find
   * @param  mixed        value that property must have
   * @param  string|array (optional) a conditions string or an array of conditions
   * @param  string       (optional) order by clause
   * @return array 
   */
  public static function findBy($property, $value, $conditions = null, $order = null) {
    $property = self::table()->getAdapter()->quoteIdentifier($property);
    $addlConditions = self::buildWhere($conditions);
    $conditions = self::buildWhere(array("{$property} = ?", $value));
    if($addlConditions !== null && $addlConditions !== '') {
      $conditions .= " AND ({$addlConditions})";
    }
    
    return self::find('all', $conditions, $order);
  }

  /**
   * (PRIVATE) Construct a new ModelModel object
   *
   * @param QFrame_Db_Table_Row model row object (this is a new object if $row is not given)
   */
  private function __construct(QFrame_Db_Table_Row $row) {
    $this->row = $row;
  }
  
  /**
   * Return properties of this ModelModel object
   *
   * @param  string property name
   * @return mixed
   */
  public function __get($property) {
    if(isset($this->row->$property)) return $this->row->$property;
  
    throw new Exception("Property {$property} of ModelModel does not exist.");
  }
  
  /**
   * Set properties of this ModelModel object
   *
   * @param  string property name
   * @param  mixed  property value
   */
  public function __set($property, $value) {
    if(isset($this->row->$property)) {
      $this->row->$property = $value;
    }
    else {
      throw new Exception("Property {$property} of ModelModel does not exist.");
    }
  }
  
  /**
   * Return true if a property exists, false otherwise
   *
   * @return boolean
   */
  public function __isset($property) {
    return isset($this->row->$property);
  }
  
  /**
   * Save this ModelModel object, return true on success, false on failure
   *
   * @return boolean
   */
  public function save() {
    return $this->row->save();
  }
  
  /**
   * Create a new model
   *
   * @param  array (optional) parameters of this new model
   * @return ModelModel
   */
  public static function create(array $params = array()) {
    $row = self::table()->createRow($params);
    return new ModelModel($row);
  }
  
  /**
   * Update a bunch of attributes at once (rather than one at a time as properties)
   *
   * @param array attributes being updated
   */
  public function updateAttributes(array $attributes) {
    $this->row->setFromArray($attributes);
  }
  
  /**
   * Get a list of response for this model
   *
   * TODO - This will need to be implemented once relationships are set up...hopefully
   * this can be made dynamic by using __call() and inferring based on relationships
   * (call to a method findXxxxx would be look for a relationship named xxxxx, etc)
   *
   * @param  string|array (optional) a conditions string or an array of conditions
   * @param  string       (optional) order by clause
   * @return ModelModel|array 
   */
  public function findResponses($conditions = null, $order = null) {
    /* this will need to return results from a call to ModelResponseModel::find() */
    return array();
  }
}