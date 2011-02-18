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
class ModelSectionModel {
  
  /**
   * Stores the model table object used by this class
   * @var QFrame_Db_Table_Model
   */
  private static $modelTable;
  
  /**
   * Stores the model response table object used by this class
   * @var QFrame_Db_Table_ResponseModel
   */
  private static $modelResponseTable;
  
  /**
   * Stores the question table object used by this class
   * @var QFrame_Db_Table_Question
   */
  private static $questionTable;
  
  /**
   * Stores the section object
   * @var SectionModel
   */
  private $section;
  
  /**
   * Stores the instance that is being compared to the model (optional)
   * @var InstanceModel
   */
  private $compareInstance;
    
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
      'depth'   => 'section',
      'instance' => null
    ), $args);
    $this->depth = $args['depth'];
    $this->compareInstance = $args['instance'];

    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    if (!isset(self::$modelResponseTable)) self::$modelResponseTable = QFrame_Db_Table::getTable('model_response');
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
    $rows = self::$questionTable->fetchRows('sectionID', $this->section->sectionID, 'seqNumber', $this->section->pageID);
    foreach ($rows as $row) {
      if($row->parentID == 0) {
        $this->modelQuestions[] = new ModelQuestionModel(array(
          'modelID'    => $this->modelID,
          'questionID' => $row->questionID,
          'depth'      => $this->depth,
          'instance'   => $this->compareInstance
        ));
      }
    }
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
    
    // To populate cache
    self::$modelResponseTable->fetchRows('sectionID', $this->section->sectionID, 'modelResponseID', $this->modelID);
    
    $result = array();
    
    foreach ($args as $key => $value) {
      if ($args[$key] === TRUE) $result[$key] = array();
    }
      
    while ($modelQuestion = $this->nextModelQuestion()) {
      $q = $modelQuestion->compare($args);
      foreach ($q as $key => $value) {
        if ($args[$key] === TRUE) {
          $result[$key] = array_merge($result[$key], $q[$key]);
        }
      }
    }
    
    return $result;
  }
  
}
