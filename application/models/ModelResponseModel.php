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
 * @category   Application
 * @package    Models
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * @category   Application
 * @package    Models
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
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
   * Stores the question prompt table object used by this class
   * @var QFrame_Db_Table_QuestionPrompt
   */
  private static $questionPromptTable;
  
  /**
   * Stores the ModelResponse row object
   * @var Zend_Db_Table_Row
   */
  private $modelResponseRow;
  
  /**
   * Stores the instance that is being compared to the model (optional)
   * @var InstanceModel
   */
  private $compareInstance;

  /**
   * Flag for whether the contents of modelResponseRow has changed and should therefore be saved
   */
  private $dirty = 0;

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
      'depth' => 'response',
      'instance' => null 
    ), $args);
    $this->depth = $args['depth'];
    $this->compareInstance = $args['instance'];
    
    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    if (!isset(self::$modelResponseTable)) self::$modelResponseTable = QFrame_Db_Table::getTable('model_response');
    if (!isset(self::$questionPromptTable)) self::$questionPromptTable = QFrame_Db_Table::getTable('question_prompt');
    
    if (isset($args['modelResponseID'])) {
      $rows = self::$modelResponseTable->fetchRows('modelResponseID', $args['modelResponseID']);
      $this->modelResponseRow = $rows[0];
    }
    else {
      throw new InvalidArgumentException('Missing arguments to ModelResponseModel constructor');
    }

    // model response row assertion
    if ($this->modelResponseRow === NULL) {
      throw new Exception('Model response not found');
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
  
  /**
   * Get prompt text for target (when it is a promptID) for S and M question types
   * @return string
   */
  public function promptText () {
    $questionPromptRows = self::$questionPromptTable->fetchRows('promptID', $this->modelResponseRow->target);
    $questionPromptRow = $questionPromptRows[0];
    if (isset($questionPromptRow->value)) return $questionPromptRow->value;
    throw new Exception('Question prompt row not found for target promptID [' . $this->modelResponseRow->target . ']');
  }

}
