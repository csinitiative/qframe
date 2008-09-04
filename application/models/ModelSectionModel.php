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
class ModelSectionModel {
  
  /**
   * Stores the model table object used by this class
   * @var QFrame_Db_Table_Model
   */
  private static $modelTable;
  
  /**
   * Stores the question table object used by this class
   * @var QFrame_Db_Table_Question
   */
  private static $questionTable;
  
  /**
   * Stores the section object
   */
  private $section;
    
  /**
   * Determines depth of object hierarchy
   */
  private $depth;
  
  /**
   * ModelQuestion objects associated with this model
   */
  private $modelQuestions = array();
   
  /**
   * Store the modelID
   */
  private $modelID;
   
  /**
   * Instantiate a new ModelSectionModel object
   *
   * @param array
   */
  public function __construct($args = array()) {
    $args = array_merge(array(
      'depth'   => 'section'
    ), $args);
    $this->depth = $args['depth'];

    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    if (!isset(self::$questionTable)) self::$questionTable = QFrame_Db_Table::getTable('question');
    
    if (isset($args['modelID']) && isset($args['sectionID'])) {
      $this->modelID = $args['modelID'];
      $this->section = new SectionModel(array('sectionID' => $args['sectionID'],
                                              'depth' => $args['depth'])); 
    }
    else {
      throw new InvalidArgumentException('Missing arguments to ModelSectionModel constructor');
    }
   
    if ($this->depth !== 'section') $this->_loadModelQuestions();
     
  }
  
  /**
   * Return attributes of this ModelSection object
   *
   * @param  string key
   * @return mixed
   */
  public function __get($key) {
    return $this->section->$key;
  }
  
  /**
   * Return true if an attribute exists, false otherwise
   *
   * @return boolean
   */
  public function __isset($key) {
    if (isset($this->section->$key)) return true;
    return false;
  }
  
  /**
   * Save this ModelSectionModel object and its descendents
   *
   * @return boolean
   */
  public function save() {
    
    if (count($this->modelQuestions)) {
      foreach ($this->modelQuestions as $modelQuestion) {
        $modelQuestion->save();
      }
    }
  
  }
  
  /**
   * Returns the next ModelQuestionModel associated with this ModelSectionModel
   *
   * @return ModelQuestionModel Returns null if there are no further pages
   */
  public function nextModelQuestion() {
    $nextModelQuestion = each($this->modelQuestions);
    if(!$nextModelQuestion) return;

    return $nextModelQuestion['value'];
  }
  
  /**
   * Deletes all responses for this Model Section
   */
  public function delete() {
    $where = self::$modelTable->getAdapter()->quoteInto('modelID = ?', $this->modelID) .
             self::$modelTable->getAdapter()->quoteInto(' AND sectionID = ?', $this->section->sectionID);
    $this->modelTable->delete($where);
  }
  

  /**
   * Loads Model Questions
   */
  private function _loadModelQuestions() {
    $where = self::$questionTable->getAdapter()->quoteInto('instanceID = ?', $this->section->instanceID) .
             self::$questionTable->getAdapter()->quoteInto(' AND sectionID = ?', $this->section->sectionID);

    $rows = self::$questionTable->fetchAll($where, 'seqNumber ASC');
    foreach ($rows as $row) {
      $this->modelQuestions[] = new ModelQuestionModel(array('modelID' => $this->modelID,
                                                             'questionID' => $row->questionID,
                                                             'depth' => $this->depth
      ));
    }
  }
  
}
