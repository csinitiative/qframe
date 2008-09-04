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
class PageModel implements QFrame_Lockable, QFrame_Permissible {

  private $pageRow;
  private $sections;
  private $sectionsIndex;
  private $depth;
  private $parent;
  private $referenceDetailRows;
  static $pageTable;
  static $sectionTable;
  static $questionTable;
  static $ruleTable;
  static $questionTypeTable;
  static $pageReferenceTable;
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
    if (!isset($args['pageID'])) {
      throw new InvalidArgumentException('Missing pageID as argument to PageModel constructor');
    }

    if (!isset(self::$pageTable)) self::$pageTable = QFrame_Db_Table::getTable('page');
    if (!isset(self::$sectionTable)) self::$sectionTable = QFrame_Db_Table::getTable('section');
    if (!isset(self::$questionTable)) self::$questionTable = QFrame_Db_Table::getTable('question');
    if (!isset(self::$ruleTable)) self::$ruleTable = QFrame_Db_Table::getTable('rule');
    if (!isset(self::$questionTypeTable)) self::$questionTypeTable = QFrame_Db_Table::getTable('question_type');
    if (!isset(self::$pageReferenceTable)) self::$pageReferenceTable = QFrame_Db_Table::getTable('page_reference');
    if (!isset(self::$referenceDetailTable)) self::$referenceDetailTable = QFrame_Db_Table::getTable('reference_detail');
    
    $rows = self::$pageTable->fetchRows('pageID', $args['pageID']);
    $this->pageRow = $rows[0];
    
    // page row assertion
    if ($this->pageRow === NULL) {
      throw new Exception('Page not found [' . $args['pageID'] . ']');
    }
    
    // Load up table data in bulk to limit sql queries
    QFrame_Db_Table::preloadAll($this->pageRow->instanceID, $this->pageRow->pageID);

    $this->depth = $args['depth'];
    
    $ruleRows = self::$ruleTable->fetchRows('targetID', $this->pageRow->pageID, null, $this->pageRow->instanceID);
    $disableCount = 0;
    foreach ($ruleRows as $row) {
      if ($row->enabled === 'Y' && $row->type === 'disablePage') {
        $disableCount++;
      }
      elseif ($row->enabled === 'Y' && $row->type === 'enablePage') {
        $disableCount--;
      }
    }
    if ($this->pageRow->defaultPageHidden) $disableCount++;
    if ($disableCount != $this->pageRow->disableCount) {
      $this->pageRow->disableCount = $disableCount;
      $this->pageRow->save();
    }
    
    if ($this->depth !== 'page') $this->_loadSections();
    
    $pageReferenceRows = self::$pageReferenceTable->fetchRows('pageID', $this->pageRow->pageID, null, $this->pageRow->instanceID);
    foreach ($pageReferenceRows as $row) {
      $rows = self::$referenceDetailTable->fetchRows('referenceDetailID', $row->referenceDetailID, null, $this->pageRow->instanceID);
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
      $this->parent = new InstanceModel(array('instanceID' => $this->pageRow->instanceID,
                                              'depth' => 'instance'));
    }

    // If we have a valid key or a key that is a column in question or questionType, return a value
    if(array_key_exists($key, self::$validProperties)) return $this->{self::$validProperties[$key]};
    elseif(isset($this->pageRow->$key)) {
      return $this->pageRow->$key;
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
    return array_key_exists($key, self::$validProperties) || isset($this->pageRow->$key);
  }

  public function save() {
    if(count($this->sections)) {
      foreach ($this->sections as $section) {
        $section->save();
      }
    }
    $this->pageRow->numQuestions = $this->getNumQuestions();
    $this->pageRow->numComplete = $this->getNumQuestionsComplete();
    $this->pageRow->numApproved = $this->getNumQuestionsApproved();
    $this->pageRow->save();
    if ($this->depth !== 'page') $this->_loadSections();
  }

  public function nextSection() {
    if(!isset($this->sections)) return null;
    $nextSection = each($this->sections);
    return (is_array($nextSection)) ? $nextSection['value'] : null;
  }
  
  /**
   * Returns the number of sections that this page contains (or throws
   * an Exception if depth is set to 'page')
   *
   * @return integer
   */
  public function numSections() {
    if($this->depth == 'page') throw new Exception('Attempt to return section count when depth is page');
    return count($this->sections);
  }

  private function _loadSections() {
    
    $where = self::$sectionTable->getAdapter()->quoteInto('pageID = ?', $this->pageID);
    
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
   * Return the number of questions for this page
   *
   * @return integer
   */
  public function getNumQuestions() {
    $questionGroupTypeID = self::$questionTypeTable->getQuestionTypeID($this->pageRow->instanceID, '_questionGroup');
    $virtualQuestionTypeID = self::$questionTypeTable->getQuestionTypeID($this->pageRow->instanceID, 'V');
    if (!isset($virtualQuestionTypeID)) $virtualQuestionTypeID = -1;
    if (!isset($questionGroupTypeID)) $questionGroupTypeID = -1;
    $select = self::$pageTable->getAdapter()->select()
            ->from(array('q' => 'question'), array('COUNT(*) as tally'))
            ->where('q.pageID = ?', $this->pageRow->pageID)
            ->where('q.questionTypeID != ?', $questionGroupTypeID)
            ->where('q.questionTypeID != ?', $virtualQuestionTypeID);
    $stmt = self::$pageTable->getAdapter()->query($select);
    $result = $stmt->fetchAll();
    return $result[0]['tally'];
  }

  /**
   * Return the number of approved questions for this page
   *
   * @return integer
   */
  public function getNumQuestionsApproved() {
    $questionGroupTypeID = self::$questionTypeTable->getQuestionTypeID($this->pageRow->instanceID, '_questionGroup');
    $virtualQuestionTypeID = self::$questionTypeTable->getQuestionTypeID($this->pageRow->instanceID, 'V');
    if (!isset($virtualQuestionTypeID)) $virtualQuestionTypeID = -1;
    if (!isset($questionGroupTypeID)) $questionGroupTypeID = -1;
    $select = 'SELECT COUNT(q.questionID) AS tally FROM question AS q INNER JOIN ' .
        '(SELECT DISTINCT questionID FROM response WHERE state = 2 AND ISNULL(responseEndDate)) ' .
        'AS r WHERE q.pageID = ? AND q.questionTypeID != ? AND q.questionTypeID != ? AND ' .
        'q.questionID = r.questionID AND q.questionTypeID != ? AND q.questionTypeID != ?';
    $bindVars = array(
      $this->pageRow->pageID,
      $questionGroupTypeID,
      $virtualQuestionTypeID,
      $questionGroupTypeID,
      $virtualQuestionTypeID
    );
    $stmt = self::$pageTable->getAdapter()->query($select, $bindVars);
    $result = $stmt->fetchAll();
    return $result[0]['tally'];
  }
  
  /**
   * Return the number of questions for this page that are complete (answered)
   *
   * @return integer
   */
  public function getNumQuestionsComplete() {
    $questionGroupTypeID = self::$questionTypeTable->getQuestionTypeID($this->pageRow->instanceID, '_questionGroup');
    $virtualQuestionTypeID = self::$questionTypeTable->getQuestionTypeID($this->pageRow->instanceID, 'V');
    if (!isset($virtualQuestionTypeID)) $virtualQuestionTypeID = -1;
    if (!isset($questionGroupTypeID)) $questionGroupTypeID = -1;
    $count = 0;    
    
    $stmt = self::$pageTable->getAdapter()->query('SELECT COUNT(*) as tally FROM question as q ' .
        'WHERE q.disableCount > 0 AND q.pageID = ? AND q.questionTypeID != ? AND ' . 
        'q.questionTypeID != ?',
      array($this->pageRow->pageID, $questionGroupTypeID, $virtualQuestionTypeID)
    );
    $result = $stmt->fetchAll();
    $count += $result[0]['tally'];

    $stmt = self::$pageTable->getAdapter()->query('SELECT q.questionID FROM question AS q, ' .
        'question_type AS qt, question_prompt AS qp, response as r WHERE ' .
        'q.questionTypeID = qt.questionTypeID AND qt.questionTypeID = qp.questionTypeID AND ' .
        'q.questionID = r.questionID AND requireAddlInfo = 1 AND ISNULL(r.additionalInfo) AND ' .
        'ISNULL(r.responseEndDate) AND r.responseText = qp.promptID AND q.pageID = ?',
      array($this->pageRow->pageID)
    );
    $missingAddlInfoRows = $stmt->fetchAll();

    $select = 'SELECT COUNT(q.questionID) as tally FROM question AS q INNER JOIN ' .
        "(SELECT DISTINCT questionID FROM response WHERE responseText != '' AND " .
        'ISNULL(responseEndDate)) AS r WHERE q.pageID = ? AND q.questionTypeID != ? AND ' . 
        'q.questionTypeID != ? AND q.questionID = r.questionID AND q.disableCount = 0';
    $bindVars = array(
      $this->pageRow->pageID,
      $questionGroupTypeID,
      $virtualQuestionTypeID
    );
    foreach ($missingAddlInfoRows as $r) {
      $select .= ' AND q.questionID != ?';
      $bindVars[] = $r['questionID'];
    }
    $stmt = self::$pageTable->getAdapter()->query($select, $bindVars);
    $result = $stmt->fetchAll();
    $count += $result[0]['tally'];

    return $count;
  }
  
  /**
   * Return an ID that is guaranteed to be unique among objects of type PageModel
   *
   * @return string
   */
  public function objectID() {
    return "{$this->pageID}";
  }
  
  /**
   * Return an ID that is unique to this page but common to all instances of this
   * page on different questionnaires.
   * 
   * @return string
   */
  public function getPermissionID() {
    $id = get_class($this) . '_';
    if($this->pageGUID) $id .= "GUID{$this->pageGUID}";
    else $id .= "ID{$this->pageID}";
    
    return $id;
  }
  
  /**
   * Returns a QuestionModel object if that question is on this page, null if it is not, and
   * throws an exception if depth is not sufficient.
   *
   * @param  integer QuestionModel id
   * @return QuestionModel
   */
  public function getQuestion($id) {
    // sanity check, make sure depth is enough to support a query for a question
    if($this->depth === 'page' || $this->depth === 'section')
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
