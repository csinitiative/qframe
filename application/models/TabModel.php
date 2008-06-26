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
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */

/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class TabModel implements RegQ_Lockable, RegQ_Permissible {

  private $tabRow;
  private $sections;
  private $sectionsIndex;
  private $depth;
  private $parent;
  private $referenceDetailRows;
  static $tabTable;
  static $sectionTable;
  static $questionTable;
  static $ruleTable;
  static $questionTypeTable;
  static $tabReferenceTable;
  static $referenceDetailTable;
  
  /**
   * (static) list of valid properties objects of this class possess
   * @var Array
   */
  private static $validProperties = array(
    'parent'            => 'parent',
    'references'        => 'referenceDetailRows'
  );

  function __construct ($args) {
    $args = array_merge(array(
      'depth' => 'response'
    ), $args);

    // argument assertions
    if (!isset($args['tabID'])) {
      throw new InvalidArgumentException('Missing tabID as argument to TabModel constructor');
    }

    if (!isset(self::$tabTable)) self::$tabTable = RegQ_Db_Table::getTable('tab');
    if (!isset(self::$sectionTable)) self::$sectionTable = RegQ_Db_Table::getTable('section');
    if (!isset(self::$questionTable)) self::$questionTable = RegQ_Db_Table::getTable('question');
    if (!isset(self::$ruleTable)) self::$ruleTable = RegQ_Db_Table::getTable('rule');
    if (!isset(self::$questionTypeTable)) self::$questionTypeTable = RegQ_Db_Table::getTable('questionType');
    if (!isset(self::$tabReferenceTable)) self::$tabReferenceTable = RegQ_Db_Table::getTable('tabReference');
    if (!isset(self::$referenceDetailTable)) self::$referenceDetailTable = RegQ_Db_Table::getTable('referenceDetail');
    
    $rows = self::$tabTable->fetchRows('tabID', $args['tabID']);
    $this->tabRow = $rows[0];
    
    // Load up table data in bulk to limit sql queries
    RegQ_Db_Table::preloadAll($this->tabRow->instanceID, $this->tabRow->tabID);

    // tab row assertion
    if ($this->tabRow === NULL) {
      throw new Exception('Tab not found [' . $args['tabID'] . ']');
    }

    $this->depth = $args['depth'];
    
    $ruleRows = self::$ruleTable->fetchRows('targetID', $this->tabRow->tabID, null, $this->tabRow->instanceID);
    $disableCount = 0;
    foreach ($ruleRows as $row) {
      if ($row->enabled === 'Y' && $row->type === 'disableTab') {
        $disableCount++;
      }
      elseif ($row->enabled === 'Y' && $row->type === 'enableTab') {
        $disableCount--;
      }
    }
    if ($this->tabRow->defaultTabHidden) $disableCount++;
    if ($disableCount != $this->tabRow->disableCount) {
      $this->tabRow->disableCount = $disableCount;
      $this->tabRow->save();
    }
    
    if ($this->depth !== 'tab') $this->_loadSections();
    
    $tabReferenceRows = self::$tabReferenceTable->fetchRows('tabID', $this->tabRow->tabID, null, $this->tabRow->instanceID);
    foreach ($tabReferenceRows as $row) {
      $rows = self::$referenceDetailTable->fetchRows('referenceDetailID', $row->referenceDetailID, null, $this->tabRow->instanceID);
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
    if($key === 'parent' && !isset($this->parent)) {
      $this->parent = new InstanceModel(array('instanceID' => $this->tabRow->instanceID,
                                              'depth' => 'instance'));
    }

    // If we have a valid key or a key that is a column in question or questionType, return a value
    if(array_key_exists($key, self::$validProperties)) return $this->{self::$validProperties[$key]};
    elseif(isset($this->tabRow->$key)) {
      return $this->tabRow->$key;
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
    return array_key_exists($key, self::$validProperties) || isset($this->tabRow->$key);
  }

  public function save() {
    if(count($this->sections)) {
      foreach ($this->sections as $section) {
        $section->save();
      }
    }
    $this->tabRow->numQuestions = $this->getNumQuestions();
    $this->tabRow->numComplete = $this->getNumQuestionsComplete();
    $this->tabRow->numApproved = $this->getNumQuestionsApproved();
    $this->tabRow->save();
    if ($this->depth !== 'tab') $this->_loadSections();
  }

  public function nextSection() {
    if(!isset($this->sections)) return null;
    $nextSection = each($this->sections);
    return (is_array($nextSection)) ? $nextSection['value'] : null;
  }
  
  /**
   * Returns the number of sections that this tab contains (or throws
   * an Exception if depth is set to 'tab')
   *
   * @return integer
   */
  public function numSections() {
    if($this->depth == 'tab') throw new Exception('Attempt to return section count when depth is tab');
    return count($this->sections);
  }

  private function _loadSections() {
    
    $where = self::$sectionTable->getAdapter()->quoteInto('tabID = ?', $this->tabID);
    
    $sections = self::$sectionTable->fetchAll($where, 'seqNumber ASC');
    $this->sections = array();
    foreach ($sections as $section) {
      $this->sections[] = new SectionModel(array('sectionID' => $section->sectionID,
                                                 'depth' => $this->depth
      ));
    }

    return 1;
  }
  
  /**
   * Return the number of questions for this tab
   *
   * @return integer
   */
  public function getNumQuestions() {
    $questionGroupTypeID = self::$questionTypeTable->getQuestionTypeID($this->tabRow->instanceID, '_questionGroup');
    $virtualQuestionTypeID = self::$questionTypeTable->getQuestionTypeID($this->tabRow->instanceID, 'V');
    if (!isset($virtualQuestionTypeID)) $virtualQuestionTypeID = -1;
    if (!isset($questionGroupTypeID)) $questionGroupTypeID = -1;
    $select = self::$tabTable->getAdapter()->select()
            ->from(array('q' => 'question'), array('COUNT(*) as tally'))
            ->where('q.tabID = ?', $this->tabRow->tabID)
            ->where('q.questionTypeID != ?', $questionGroupTypeID)
            ->where('q.questionTypeID != ?', $virtualQuestionTypeID);
    $stmt = self::$tabTable->getAdapter()->query($select);
    $result = $stmt->fetchAll();
    return $result[0]['tally'];
  }

  /**
   * Return the number of approved questions for this tab
   *
   * @return integer
   */
  public function getNumQuestionsApproved() {
    $questionGroupTypeID = self::$questionTypeTable->getQuestionTypeID($this->tabRow->instanceID, '_questionGroup');
    $virtualQuestionTypeID = self::$questionTypeTable->getQuestionTypeID($this->tabRow->instanceID, 'V');
    if (!isset($virtualQuestionTypeID)) $virtualQuestionTypeID = -1;
    if (!isset($questionGroupTypeID)) $questionGroupTypeID = -1;
    $select = 'SELECT COUNT(q.questionID) AS tally FROM question AS q INNER JOIN ' .
        '(SELECT DISTINCT questionID FROM response WHERE state = 2 AND ISNULL(responseEndDate)) ' .
        'AS r WHERE q.tabID = ? AND q.questionTypeID != ? AND q.questionTypeID != ? AND ' .
        'q.questionID = r.questionID AND q.questionTypeID != ? AND q.questionTypeID != ?';
    $bindVars = array(
      $this->tabRow->tabID,
      $questionGroupTypeID,
      $virtualQuestionTypeID,
      $questionGroupTypeID,
      $virtualQuestionTypeID
    );
    $stmt = self::$tabTable->getAdapter()->query($select, $bindVars);
    $result = $stmt->fetchAll();
    return $result[0]['tally'];
  }
  
  /**
   * Return the number of questions for this tab that are complete (answered)
   *
   * @return integer
   */
  public function getNumQuestionsComplete() {
    $questionGroupTypeID = self::$questionTypeTable->getQuestionTypeID($this->tabRow->instanceID, '_questionGroup');
    $virtualQuestionTypeID = self::$questionTypeTable->getQuestionTypeID($this->tabRow->instanceID, 'V');
    if (!isset($virtualQuestionTypeID)) $virtualQuestionTypeID = -1;
    if (!isset($questionGroupTypeID)) $questionGroupTypeID = -1;
    $count = 0;    
    
    $stmt = self::$tabTable->getAdapter()->query('SELECT COUNT(*) as tally FROM question as q ' .
        'WHERE q.disableCount > 0 AND q.tabID = ? AND q.questionTypeID != ? AND ' . 
        'q.questionTypeID != ?',
      array($this->tabRow->tabID, $questionGroupTypeID, $virtualQuestionTypeID)
    );
    $result = $stmt->fetchAll();
    $count += $result[0]['tally'];

    $stmt = self::$tabTable->getAdapter()->query('SELECT q.questionID FROM question AS q, ' .
        'questionType AS qt, questionPrompt as qp, response as r WHERE ' .
        'q.questionTypeID = qt.questionTypeID AND qt.questionTypeID = qp.questionTypeID AND ' .
        'q.questionID = r.questionID AND requireAddlInfo = 1 AND ISNULL(r.additionalInfo) AND ' .
        'ISNULL(r.responseEndDate) AND r.responseText = qp.promptID AND q.tabID = ?',
      array($this->tabRow->tabID)
    );
    $missingAddlInfoRows = $stmt->fetchAll();

    $select = 'SELECT COUNT(q.questionID) as tally FROM question AS q INNER JOIN ' .
        "(SELECT DISTINCT questionID FROM response WHERE responseText != '' AND " .
        'ISNULL(responseEndDate)) AS r WHERE q.tabID = ? AND q.questionTypeID != ? AND ' . 
        'q.questionTypeID != ? AND q.questionID = r.questionID AND q.disableCount = 0';
    $bindVars = array(
      $this->tabRow->tabID,
      $questionGroupTypeID,
      $virtualQuestionTypeID
    );
    foreach ($missingAddlInfoRows as $r) {
      $select .= ' AND q.questionID != ?';
      $bindVars[] = $r['questionID'];
    }
    $stmt = self::$tabTable->getAdapter()->query($select, $bindVars);
    $result = $stmt->fetchAll();
    $count += $result[0]['tally'];

    return $count;
  }
  
  /**
   * Return an ID that is guaranteed to be unique among objects of type TabModel
   *
   * @return string
   */
  public function objectID() {
    return "{$this->tabID}";
  }
  
  /**
   * Return an ID that is unique to this tab but common to all instances of this
   * tab on different instruments.
   * 
   * @return string
   */
  public function getPermissionID() {
    $id = get_class($this) . '_';
    if($this->tabGUID) $id .= "GUID{$this->tabGUID}";
    else $id .= "ID{$this->tabID}";
    
    return $id;
  }
  
  /**
   * Returns a QuestionModel object if that question is on this tab, null if it is not, and
   * throws an exception if depth is not sufficient.
   *
   * @param  integer QuestionModel id
   * @return QuestionModel
   */
  public function getQuestion($id) {
    // sanity check, make sure depth is enough to support a query for a question
    if($this->depth === 'tab' || $this->depth === 'section')
      throw new Exception('Cannot fetch questions with a depth < question');
      
    $question = null;
    foreach($this->sections as $section) {
      $question = $section->_findQuestionById($id);
      if($question !== null) break;
    }
    reset($this->sections);
    return $question;
  }
  
  /**
   * Returns a string representing the percentage of questions that have been completed
   *
   * @return string
   */
  public function pctComplete() {
    $pct = round(($this->numComplete / $this->numQuestions) * 100, 2);
    return "{$pct}";
  }
}
