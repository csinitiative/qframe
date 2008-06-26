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
class QuestionModel implements RegQ_Storer {

  private $questionRow;
  private $questionTypeRow;
  private $questionPromptRows = array ();
  private $referenceDetailRows = array ();
  private $ruleRows;
  private $responsesIndex;
  private $depth;
  private $parent;
  private $virtualQuestion = 0;
  public $responses;
  static $questionTable;
  static $questionTypeTable;
  static $questionPromptTable;
  static $ruleTable;
  static $questionReferenceTable;
  static $referenceDetailTable;
  static $referenceTable;
  static $responseTable;

  /**
   * Collection of children that this question owns
   * @var Array
   */
  private $children = array ();

  /**
   * (static) list of valid properties objects of this class possess
   * @var Array
   */
  private static $validProperties = array (
    'virtualQuestion' => 'virtualQuestion',
    'children' => 'children',
    'responses' => 'responses',
    'prompts' => 'questionPromptRows',
    'references' => 'referenceDetailRows'
  );

  function __construct($args = array ()) {

    $args = array_merge(array (
      'depth' => 'response'
    ), $args);

    // argument assertions
    if (!isset ($args['questionID'])) {
      throw new InvalidArgumentException('Missing questionID as argument to QuestionModel constructor');
    }

    if (!isset (self::$questionTable)) self::$questionTable = RegQ_Db_Table::getTable('question');
    if (!isset (self::$questionReferenceTable)) self::$questionReferenceTable = RegQ_Db_Table::getTable('questionReference');
    if (!isset (self::$referenceTable)) self::$referenceTable = RegQ_Db_Table::getTable('reference');
    if (!isset (self::$referenceDetailTable)) self::$referenceDetailTable = RegQ_Db_Table::getTable('referenceDetail');
    if (!isset (self::$questionTypeTable)) self::$questionTypeTable = RegQ_Db_Table::getTable('questionType');
    if (!isset (self::$questionPromptTable)) self::$questionPromptTable = RegQ_Db_Table::getTable('questionPrompt');

    $questions = self::$questionTable->fetchRows('questionID', $args['questionID']);
    $this->questionRow = $questions[0];

    // question row assertion
    if ($this->questionRow === NULL) {
      throw new Exception('Question not found');
    }

    if (isset ($args['parent'])) {
      $this->parent = $args['parent'];
    }

    $questionTypes = self::$questionTypeTable->fetchRows('questionTypeID', $this->questionRow->questionTypeID);
    $this->questionTypeRow = $questionTypes[0];

    // question type row assertion
    if ($this->questionTypeRow === NULL) {
      throw new Exception('Question type not found');
    }

    // virtual question
    if ($this->questionTypeRow->format === 'V') {
      $this->virtualQuestion = 1;
      $questions = self::$questionTable->fetchRows('questionGUID', $this->questionRow->questionGUID);
      foreach ($questions as $question) {
        if ($question->instanceID != $this->questionRow->instanceID) continue;
        if ($question->questionTypeID != $this->questionTypeRow->questionTypeID) {
          $seqNumber = $this->questionRow->seqNumber;
          $this->questionRow = $question;
          $this->questionRow->seqNumber = $seqNumber;
          $questionTypes = self::$questionTypeTable->fetchRows('questionTypeID', $this->questionRow->questionTypeID);
          $this->questionTypeRow = $questionTypes[0];
          break;
        }
      }
    }

    $questionPromptRows = self::$questionPromptTable->fetchRows('questionTypeID', $this->questionRow->questionTypeID);

    if (!isset (self::$ruleTable)) self::$ruleTable = RegQ_Db_Table::getTable('rule');
    foreach ($questionPromptRows as $row) {
      $array = $row->toArray();
      $array['rules'] = array ();
      $ruleRows = self::$ruleTable->fetchRows('sourceID', $row->promptID, null, $this->questionRow->instanceID);
      foreach ($ruleRows as $ruleRow) {
        array_push($array['rules'], $ruleRow);
      }
      array_push($this->questionPromptRows, $array);
    }

    if ($this->questionRow->parentID != 0) {
      $parent = new QuestionModel(array('questionID' => $this->questionRow->parentID,
                                        'depth' => 'question',
                                        'noChildren' => true));
    }
    else {
      $parent = new SectionModel(array('sectionID' => $this->questionRow->sectionID,
                                       'depth' => 'section'));
    }

    $ruleRows = self::$ruleTable->fetchRows('targetID', $this->questionRow->questionID, null, $this->questionRow->instanceID);
    $disableCount = 0;
    foreach ($ruleRows as $row) {
      if ($row->enabled == 'Y' && $row->type == 'disableQuestion') {
        $disableCount++;
      }
      elseif ($row->enabled == 'Y' && $row->type == 'enableQuestion') {
        $disableCount--;
      }
    }
    if ($this->questionRow->defaultQuestionHidden) $disableCount++;
    $disableCount += $parent->disableCount;
    if ($disableCount != $this->questionRow->disableCount) {
      $this->questionRow->disableCount = $disableCount;
      $this->questionRow->save();
    }

    $questionReferenceRows = self::$questionReferenceTable->fetchRows('questionID', $this->questionRow->questionID, null, $this->questionRow->instanceID);
    foreach ($questionReferenceRows as $row) {
      $referenceDetailRows = self::$referenceDetailTable->fetchRows('referenceDetailID', $row->referenceDetailID, null, $this->questionRow->instanceID);
      foreach ($referenceDetailRows as $rd) {
        $array = $rd->toArray();
        $referenceRows = self::$referenceTable->fetchRows('shortName', $rd->shortName, null, $this->questionRow->instanceID);
        $array['referenceName'] = $referenceRows[0]->referenceName;
        array_push($this->referenceDetailRows, $array);
      }
    }
    
    if ($args['depth'] !== 'question') {
      $this->depth = $args['depth'];
      $this->_loadResponses();
    }
    
    if (!isset($args['noChildren']) && $this->questionTypeRow->format == '_questionGroup') {
      $this->_loadChildren();
    }

  }

  /**
   * Magic method that returns values of properties
   *
   * @param  string key that is being requested
   * @return mixed
   */
  public function __get($key) {
    // If we have a valid key or a key that is a column in question or questionType, return a value
    if (array_key_exists($key, self::$validProperties)) {
      return $this-> { self::$validProperties[$key] };
    }
    elseif (isset ($this->questionRow-> $key)) {
      return $this->questionRow-> $key;
    }
    elseif (isset ($this->questionTypeRow-> $key)) {
      return $this->questionTypeRow-> $key;
    }
    elseif ($key === 'parent') {
      if (!isset($this->parent)) {
	    if ($this->questionRow->parentID != 0) {
	      $this->parent = new QuestionModel(array('questionID' => $this->questionRow->parentID,
	                                              'depth' => 'question'));
	    }
	    else {
	      $this->parent = new SectionModel(array('sectionID' => $this->questionRow->sectionID,
	                                             'depth' => 'section'));
	    }
      }
      return $this->parent;
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
    return array_key_exists($key, self::$validProperties) || isset ($this->questionRow-> $key) || isset ($this->questionTypeRow-> $key);
  }

  public function newResponse($args = array ()) {
    $args = array_merge(array (
      'responseText' => null,
      'additionalInfo' => null,
      'externalReference' => null,
      'state' => null
    ), $args);

    return new ResponseModel(array (
      'instanceID' => $this->questionRow->instanceID,
      'questionID' => $this->questionRow->questionID,
      'tabID' => $this->questionRow->tabID,
      'sectionID' => $this->questionRow->sectionID,
      'responseText' => $args['responseText'],
      'additionalInfo' => $args['additionalInfo'],
      'externalReference' => $args['externalReference'],
      'state' => $args['state'],
      'parent' => $this
    ));
  }

  public function save() {

    foreach ($this->responses as $response) {
      $response->save();
    }

    foreach ($this->children as $child) {
      foreach ($child->responses as $response) {
        $response->save();
      }
    }

  }

  public function nextResponse() {

    if (count($this->responses) < ($this->responsesIndex + 1)) {
      return;
    }

    $response = $this->responses[$this->responsesIndex];
    $this->responsesIndex++;

    return $response;

  }

  /**
   * Fetches either the most recent response or a new one depending on whether one
   * yet exists
   *
   * @return ResponseModel
   */
  public function getResponse() {
    if (count($this->responses) <= 0) {
      $response = $this->newResponse(array (
        'responseID' => 'new',
        'state' => '1'
      ));
    }
    else {
      $response = $this->responses[count($this->responses) - 1];
    }
    return $response;
  }

  private function _loadResponses() {
    if (!isset (self::$responseTable)) self::$responseTable = RegQ_Db_Table::getTable('response');
    $responses = self::$responseTable->fetchRows('questionID', $this->questionRow->questionID, 'responseID', $this->questionRow->instanceID);
    $this->responses = array();
    foreach ($responses as $r) {
      if (!$r->responseEndDate) {
        array_push($this->responses, new ResponseModel(array (
          'responseID' => $r->responseID,
          'instanceID' => $r->instanceID,
          'depth' => $this->depth,
          'parent' => $this
        )));
      }
    }

    $this->responsesIndex = 0;

    return 1;

  }

  /**
   * Loads children questions
   */
  public function _loadChildren() {
    $this->children = array();
    $rows = RegQ_Db_Table::getTable('question')->fetchRows('parentID', $this->questionRow->questionID, 'seqNumber', $this->questionRow->instanceID);
    foreach ($rows as $row) {
      $this->children[] = new QuestionModel(array('questionID' => $row->questionID,
                                                  'depth' => $this->depth)); 
    }
  }

  /**
   * Return an ID that is guaranteed to be unique among objects of type QuestionModel
   *
   * @return string
   */
  public function objectID() {
    return "{$this->questionID}";
  }

  /**
   * Return an ID that is guaranteed to be unique among objects of this type to
   * satisfy RegQ_Storer interface
   *
   * @return mixed
   */
  public function getID() {
    return "{$this->questionID}";
  }

  /**
   * Return all attachments belonging to this question
   *
   * @return Array
   */
  public function getAttachments() {
    $fileModel = new FileModel($this);
    return $fileModel->fetchAllProperties();
  }

}