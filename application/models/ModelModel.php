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
  private static $modelTable;
  
  /**
   * Stores the model response table object used by this class
   * @var QFrame_Db_Table_ResponseModel
   */
  private static $modelResponseTable;
  
  /**
   * Stores the page table object used by this class
   * @var QFrame_Db_Table_Page
   */
  private static $pageTable;
  
  /**
   * Stores the row object associated with this ModelModel
   * @var QFrame_Db_Table_Row
   */
  private $modelRow;
  
  /**
   * Stores the _default_ instance object associated with this ModelModel
   * @var InstanceModel
   */
  private $instance;
  
  /**
   * Stores the instance that is being compared to the model (optional)
   * @var InstanceModel
   */
  private $compareInstance;
   
  /**
   * Flag for whether the contents of modelRow has changed and should therefore be saved
   */
  private $dirty = 0;
  
  /**
   * Determines depth of object hierarchy
   */
  private $depth;
  
  /**
   * ModelPage objects associated with this model
   */
  private $modelPages = array();
   
  /**
   * Create a new ModelModel object
   *
   * @param QFrame_Db_Table_Row model row object (this is a new object if $row is not given)
   */
  public function __construct($args = array()) {
    
    $args = array_merge(array(
      'depth' => 'model',
      'instance' => null
    ), $args);
    $this->depth = $args['depth'];
    $this->compareInstance = $args['instance'];
    
    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    if (!isset(self::$modelResponseTable)) self::$modelResponseTable = QFrame_Db_Table::getTable('model_response');
    if (!isset(self::$pageTable)) self::$pageTable = QFrame_Db_Table::getTable('page');
    
    if (isset($args['modelID'])) {
      $rows = self::$modelTable->fetchRows('modelID', $args['modelID']);
      $this->modelRow = $rows[0];

      // model row assertion
      if (!isset($this->modelRow)) {
        throw new Exception('Model not found: modelID[' . $args['modelID'] . ']');
      }
    }
    elseif (isset($args['questionnaireID']) && isset($args['name'])) { 
      $where = self::$modelTable->getAdapter()->quoteInto('questionnaireID = ?', $args['questionnaireID']) .
               self::$modelTable->getAdapter()->quoteInto('AND name = ?', $args['name']);
      $this->modelRow = self::$modelTable->fetchRow($where);

      // model row assertion
      if (!isset($this->modelRow)) {
        throw new Exception('Model not found: questionnaireID[' . $args['questionnaireID'] . '], name[' . $args['name'] . ']');
      }
    }
    else {
      throw new InvalidArgumentException('Missing arguments to ModelModel constructor');
    }
   
    $this->instance = new InstanceModel(array('questionnaireID' => $this->modelRow->questionnaireID,
                                              'instanceName' => '_default_'));

    if ($this->depth !== 'model') $this->_loadModelPages(); 
  }
  
  /**
   * Return attributes of this ModelModel object
   *
   * @param  string key
   * @return mixed
   */
  public function __get($key) {
    if(isset($this->modelRow->$key)) return $this->modelRow->$key;
    if(isset($this->instance->$key)) return $this->instance->$key;
    
    // Otherwise, throw an exception
    throw new Exception("Attribute not found [$key]");
  }
  
  /**
   * Set attributes of this ModelModel object
   *
   * @param  string key
   * @param  mixed value
   */
  public function __set($key, $value) {
    if (isset($this->modelRow->$key)) {
      if ($this->modelRow->$key !== $value) {
        $this->modelRow->$key = $value;
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
    if (isset($this->modelRow->$key)) return true;
    if(isset($this->instance->$key)) return true;
    return false;
  }
  
  /**
   * Save this ModelModel object, return true on success, false on failure
   *
   * @return boolean
   */
  public function save() {
    
    if ($this->dirty) {
      $this->modelRow->save();
      $this->dirty = 0;
    }
    
    if (count($this->modelPages)) {
      foreach ($this->modelPages as $modelPage) {
        $modelPage->save();
      }
    }
    
  }
  
  /**
   * Returns the next ModelPageModel associated with this ModelModel
   *
   * @return ModelPageModel Returns null if there are no further pages
   */
  public function nextModelPage() {
    $nextModelPage = each($this->modelPages);
    if(!$nextModelPage) return;

    return $nextModelPage['value'];
  }
  
  /**
   * Deletes this ModelModel object
   */
  public function delete() {
    $this->modelRow->delete();
  }
  
  /**
   * Create a new ModelModel
   *
   * @param  string name The human-readable name of the model
   * @param  integer questionnaireID The numeric identifier of the questionnaire
   * @return ModelModel
   */
  public static function create($name, $questionnaireID) {
    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    if (strlen($name) < 3) { 
      throw new Exception('Model name must be at least 3 characters');
    }
    $questionnaire = new QuestionnaireModel(array('questionnaireID' => $questionnaireID,
                                                  'depth' => 'questionnaire'));
    $row = self::$modelTable->createRow();
    $row->name = $name;
    $row->questionnaireID = $questionnaire->questionnaireID;
    $row->save();
    $model = new ModelModel(array('modelID' => $row->modelID));
    return $model;
  }
  
  /**
   * Get all models associated with a questionnaire
   *
   * @param QuestionnaireModel
   * @return array ModelModel
   */
  public static function getAllModels(QuestionnaireModel $questionnaire) {
    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    $where = self::$modelTable->getAdapter()->quoteInto('questionnaireID = ?', $questionnaire->questionnaireID);
    $rows = self::$modelTable->fetchAll($where);
    $models = array();
    foreach ($rows as $row) {
      $models[] = new ModelModel(array('modelID' => $row->modelID));
    }
    return $models;
  }
  
  /**
   * Returns comparison information based on criteria arguments
   * 
   * @param array See argument array below
   * @return array Following this structure:
   *               array($criteria_term => array(array('question' => QuestionModel,
   *                                                   'messages' => array(string)
   *                                                  )
   *                                            )
   *               )
   */
  public function compare ($args = array()) {
    // Comparison criteria
    $args = array_merge(array('model_fail' => true,  
                              'model_pass' => false,
                              'additional_information' => false
    ), $args);
    
    if ($this->compareInstance->depth !== 'response') throw new Exception('Comparison not possible since compare instance depth not set to response');
    if ($this->depth !== 'response') throw new Exception('Comparison not possible since depth not set to response');
    
    $result = array();
    
    foreach ($args as $key => $value) {
      if ($args[$key] === TRUE) $result[$key] = array();
    }
    
    while ($modelPage = $this->nextModelPage()) {
      while ($modelSection = $modelPage->nextModelSection()) {
        while ($modelQuestion = $modelSection->nextModelQuestion()) {
          foreach ($modelQuestion->compare($args) as $key => $value) {
            if ($args[$key] === TRUE) {
              $result[$key] = array_merge($result[$key], $value);
            }
          }
          foreach($modelQuestion->children as $child) {
            foreach($child->compare($args) as $key => $value) {
              if ($args[$key] === TRUE) {
                $result[$key] = array_merge($result[$key], $value);
              }
            }
          }
        }
      }
    }
    
    return $result;
  }
  
  /**
   * Loads Model Pages
   */
  private function _loadModelPages() {
    $auth = Zend_Auth::getInstance();
    if($auth->hasIdentity()) $user = DbUserModel::findByUsername($auth->getIdentity());
    else throw new Exception("Hey, no loading pages without being logged in");
    
    $rows = self::$pageTable->fetchRows('instanceID', $this->instance->instanceID, 'seqNumber', $this->instance->instanceID);

    $this->modelPages = array();
    foreach ($rows as $row) {
      $page = new PageModel(array(
        'pageID' => $row->pageID,
        'depth' => 'page'
      ));
      $modelPage = new ModelPageModel(array('modelID' => $this->modelRow->modelID,
                                            'pageID' => $row->pageID,
                                            'depth' => $this->depth,
                                            'instance' => $this->compareInstance
      ));
      if($user->hasAnyAccess($page)) $this->modelPages[] = $modelPage;
    }
  }
  
}
