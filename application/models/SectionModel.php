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
class SectionModel {

  /**
   * Stores the row object correlated to this section
   * @var QFrame_Db_Rowset
   */
  private $sectionRow;

  /**
   * Rows of reference detail associated with this section
   * @var Array
   */
  private $referenceDetailRows;

  /**
   * Questions that belong to this section
   * @var Array
   */
  public $questions;

  /**
   * The depth that was requested when this object was instantiated
   * @var string
   */
  private $depth;

  /**
   * Holds a reference to the parent object
   * @var PageModel
   */
  private $parent;
  
  /**
   * (static) list of valid properties objects of this class possess
   * @var Array
   */
  private static $validProperties = array(
    'parent'            => 'parent',
    'references'        => 'referenceDetailRows'
  );
  
  private static $sectionTable;
  private static $ruleTable;
  private static $referenceTable;
  private static $referenceDetailTable;
  private static $sectionReferenceTable;

  /**
   * Create a new SectionModel object.
   *
   * @param Array array of arguments
   */
  function __construct ($args = array()) {
    // merge default parameters with arguments
    $args = array_merge(array(
      'depth'   => 'response'
    ), $args);

    // argument assertions
    if (!isset($args['sectionID'])) {
      throw new InvalidArgumentException('Missing sectionID as argument to SectionModel()');
    }

    if (!isset(self::$sectionTable)) self::$sectionTable = QFrame_Db_Table::getTable('section');
    if (!isset(self::$ruleTable)) self::$ruleTable = QFrame_Db_Table::getTable('rule');
    if (!isset(self::$sectionReferenceTable)) self::$sectionReferenceTable = QFrame_Db_Table::getTable('sectionreference');
    if (!isset(self::$referenceDetailTable)) self::$referenceDetailTable = QFrame_Db_Table::getTable('referencedetail');
    
    $rows = self::$sectionTable->fetchRows('sectionID', $args['sectionID']);
    $this->sectionRow = $rows[0];

    // section row assertion
    if ($this->sectionRow === NULL) {
      throw new Exception('Section not found [' . $args['sectionID'] . ']');
    }

    if ($args['depth'] !== 'section') {
      $this->depth = $args['depth'];
      $this->_loadQuestions();
    }
    
    $ruleRows = self::$ruleTable->fetchRows('targetID', $this->sectionRow->sectionID, null, $this->sectionRow->instanceID);
    $disableCount = 0;
    foreach ($ruleRows as $row) {
      if ($row->enabled == 'Y' && $row->type == 'disableSection') {
        $disableCount++;
      }
      elseif ($row->enabled == 'Y' && $row->type == 'enableSection') {
        $disableCount--;
      }
    }
    if ($this->sectionRow->defaultSectionHidden) $disableCount++;
    $page = new PageModel(array('pageID' => $this->sectionRow->pageID,
                                'depth' => 'page'));
    $disableCount += $page->disableCount;
    if ($disableCount != $this->sectionRow->disableCount) {
      $this->sectionRow->disableCount = $disableCount;
      $this->sectionRow->save();
    }

    $sectionReferenceRows = self::$sectionReferenceTable->fetchRows('sectionID', $this->sectionRow->sectionID, null, $this->sectionRow->instanceID);
    foreach ($sectionReferenceRows as $row) {
      $rows = self::$referenceDetailTable->fetchRows('referenceDetailID', $row->referenceDetailID, null, $this->sectionRow->instanceID);
      $this->referenceDetailRows[] = $rows[0]->toArray();
    }

  }
  
  /**
   * Magic method that returns values of properties
   *
   * @param  string key that is being requested
   * @return mixed
   */
  public function __get($key) {
    if ($key === 'parent' && !isset($this->parent)) {
      $this->parent = new PageModel(array('pageID' => $this->sectionRow->pageID, 'depth' => 'page'));
    }
    
    // If we have a valid key or a key that is a column in question or questionType, return a value
    if(array_key_exists($key, self::$validProperties)) return $this->{self::$validProperties[$key]};
    elseif(isset($this->sectionRow->$key)) {
      return $this->sectionRow->$key;
    }

    // Otherwise, throw an exception
    throw new Exception("Attribute not found [$key]");
  }
  
  /**
   * Magic method that returns true if the requested property exists and false otherwise
   *
   * @param  string  key that is being requested
   * @return boolean
   */
  public function __isset($key) {
    return array_key_exists($key, self::$validProperties) || isset($this->sectionRow->$key);
  }

  public function save() {
    foreach ($this->questions as $question) {
      $question->save();
    }

    if ($this->depth !== 'section') $this->_loadQuestions();
  }

  public function nextQuestion() {
    $nextElement = each($this->questions);

    return (is_array($nextElement)) ? $nextElement['value'] : null;
  }
  
  /**
   * Return an ID that is guaranteed to be unique among objects of type SectionModel
   *
   * @return string
   */
  public function objectID() {
    return "{$this->sectionID}";
  }
  
  /**
   * Find a particular question from this section's collection by its ID
   *
   * @param  integer ID of the question to look for
   */
  public function _findQuestionById($id) {
    $found = null;
    foreach($this->questions as $question) {
      if($question->questionID == $id) {
        $found = $question;
        break;
      }
    }
    reset($this->questions);
    return $found;
  }

  private function _loadQuestions() {  
    $questions = QFrame_Db_Table::getTable('question')->fetchRows('sectionID', $this->sectionID, 'seqNumber');
    
    $this->questions = array();
    foreach ($questions as $question) {
      if ($question->parentID == 0) {
        $this->questions[] = new QuestionModel(array(
          'questionID'  => $question->questionID,
          'depth'       => $this->depth
        ));
      }
    }
    
    reset($this->questions);
    
    return 1;
  }
}
