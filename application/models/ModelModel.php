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
  private static $modelTable;
  private static $pageTable;
  
  /**
   * Stores the row object associated with this ModelModel
   * @var QFrame_Db_Table_Row
   */
  private $modelRow;
  
  /**
   * Stores the instance object associated with this ModelModel
   * @var QFrame_Db_Table_Row
   */
  private $instance;
  
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
      'depth'   => 'model'
    ), $args);
    $this->depth = $args['depth'];
    
    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    if (!isset(self::$pageTable)) self::$pageTable = QFrame_Db_Table::getTable('page');
    
    if (isset($args['modelID'])) {
      $where = self::$modelTable->getAdapter()->quoteInto('modelID = ?', $args['modelID']);
      $this->modelRow = self::$modelTable->fetchRow($where);
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
   * Loads Model Pages
   */
  private function _loadModelPages() {
    $auth = Zend_Auth::getInstance();
    if($auth->hasIdentity()) $user = DbUserModel::findByUsername($auth->getIdentity());
    else throw new Exception("Hey, no loading pages without being logged in");
    
    $where = self::$pageTable->getAdapter()->quoteInto('instanceID = ?', $this->instance->instanceID);
    $pageRowset = self::$pageTable->fetchAll($where);

    $this->modelPages = array();
    foreach ($pageRowset as $tRow) {
      $page = new PageModel(array(
        'pageID' => $tRow->pageID,
        'depth' => 'page'
      ));
      $modelPage = new ModelPageModel(array(
        'modelID' => $this->modelRow->modelID,
        'pageID' => $tRow->pageID,
        'depth' => $this->depth
      ));
      if($user->hasAnyAccess($page)) $this->modelPages[] = $modelPage;
    }
  }
  
}