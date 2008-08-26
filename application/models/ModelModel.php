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
   * (PRIVATE) Construct a new ModelModel object
   *
   * @param XXX model row object (this is a new object if $row is not given)
   */
  private function __construct(/* XXX */$row = null) {
    
  }
  
  /**
   * Return properties of this ModelModel object
   *
   * @param  string property name
   * @return mixed
   */
  public function __get($property) {
    if($property === 'name') return 'Dummy Model';
    if($property === 'instance') return new InstanceModel(array('instanceID' => 1));
  
    throw new Exception("Property {$property} of ModelModel does not exist.");
  }
  
  /**
   * Set properties of this ModelModel object
   *
   * @param  string property name
   * @param  mixed  property value
   */
  public function __set($property, $value) {
    if($property === 'name' || $property === 'instance') return;

    throw new Exception("Property {$property} of ModelModel does not exist.");
  }
  
  /**
   * Return true if a property exists, false otherwise
   *
   * @return boolean
   */
  public function __isset($property) {
    return ($property === 'name' || $property === 'instance');
  }
  
  /**
   * Save this ModelModel object, return true on success, false on failure
   *
   * @return boolean
   */
  public function save() {
    return true;
  }
  
  /**
   * Create a new model
   *
   * @param  array (optional) parameters of this new model
   * @return ModelModel
   */
  public static function create(array $params = array()) {
    return new ModelModel(null);
  }
  
  /**
   * Update a bunch of attributes at once (rather than one at a time as properties)
   *
   * @param array attributes being updated
   */
  public function updateAttributes(array $attributes) {
    /* this needs to be implemented (and __set() may end up using this under the covers) */
  }
  
  /**
   * Get a list of response for this model
   *
   * @param  string|array (optional) a conditions string or an array of conditions
   * @param  string       (optional) order by clause
   * @return ModelModel|array 
   */
  public function findResponses($conditions = null, $order = null) {
    /* this will need to return results from a call to ModelResponseModel::find() */
    return array();
  }
  
  
  /**
   * Find a set of models that matches the given criteria
   *
   * @param  string|array the string 'all', 'first', a single ID, or an array of IDs
   * @param  string|array (optional) a conditions string or an array of conditions
   * @param  string       (optional) order by clause
   * @return ModelModel|array 
   */
  public static function find($what, $conditions = null, $order = null) {
    return array(new ModelModel, new ModelModel);
  }  
}