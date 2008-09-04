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
class ModelResponseModel {
  
  /**
   * Stores the model table object used by this class
   * @var QFrame_Db_Table_Model
   */
  private static $modelTable;

  /**
   * Stores the model response table object used by this class
   * @var QFrame_Db_Table_ModelResponse
   */
  private static $modelResponseTable;
  
  /**
   * Stores the ModelResponse row object
   */
  private $modelResponseRow;

  /**
   * Determines depth of object hierarchy
   */
  private $depth;
   
  /**
   * Instantiate a new ModelResponseModel object
   *
   * @param array
   */
  public function __construct($args = array()) {

    $args = array_merge(array(
      'depth'   => 'response'
    ), $args);
    $this->depth = $args['depth'];
    
    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    if (!isset(self::$modelResponseTable)) self::$modelResponseTable = QFrame_Db_Table::getTable('model_response');
    
    if (isset($args['modelResponseID'])) {
      $where = self::$modelResponseTable->getAdapter()->quoteInto('modelResponseID = ?', $args['modelResponseID']);
      $this->modelResponseRow = self::$modelResponseTable->fetchRow($where);
    }
    else {
      throw new InvalidArgumentException('Missing arguments to ModelResponseModel constructor');
    }
   
  }
  
  /**
   * Return attributes of this ModelResponse object
   *
   * @param  string key
   * @return mixed
   */
  public function __get($key) {
    if(isset($this->modelResponseRow->$key)) return $this->modelResponseRow->$key;

    // Otherwise, throw an exception
    throw new Exception("Attribute not found [$key]");
  }

  /**
   * Set attributes of this ModelResponse object
   *
   * @param  string key
   * @param  string value
   */
   public function __set ($key, $value) {

    if (isset($this->modelResponseRow->$key)) {
      if ($this->modelResponseRow->$key !== $value) {
        $this->modelResponseRow->$key = $value;
        $this->dirty = 1;
      }
    }
    else {
      throw new Exception("Attribute not found [$key]");
    }

  }
  
  /**
   * Return true if an attribute exists, false otherwise
   *
   * @return boolean
   */
  public function __isset($key) {
    if (isset($this->modelResponseRow->$key)) return true;
    return false;
  }
  
  /**
   * Save this ModelResponseModel object
   *
   * @return boolean
   */
  public function save() {
    
    if (!$this->dirty) return;

    $this->modelResponseRow->save();

    $this->dirty = 0;
  
  }
  
  /**
   * Deletes this model response
   */
  public function delete() {
    $this->modelResponseRow->delete();
  }

}
