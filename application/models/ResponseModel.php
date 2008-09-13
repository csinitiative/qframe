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
class ResponseModel {

  private $responseRow;
  private $dirty;
  private $stateChange = 0;
  private $parent;
  static $responseTable;
  static $questionPromptTable;

  function __construct ($args = array()) {
    if (!isset(self::$responseTable)) self::$responseTable = QFrame_Db_Table::getTable('response');
    if (!isset(self::$questionPromptTable)) self::$questionPromptTable = QFrame_Db_Table::getTable('question_prompt');
    
    $args = array_merge(array(
      'questionID'          => null,
      'instanceID'          => null,
      'responseText'        => null,
      'additionalInfo'      => null,
      'externalReference'   => null,
      'state'               => null
    ), $args);


    if (isset($args['responseID'])) {
      $responseRows = self::$responseTable->fetchRows('responseID', intval($args['responseID']));
      foreach ($responseRows as $r) {
        if (!$r->responseEndDate) {
          $this->responseRow = $r;
        }
      }
      
      // response row assertion
      if ($this->responseRow === NULL) {
        throw new Exception('Response not found');
      }

      $this->dirty = 0;
    }
    else {
      $this->responseRow = self::$responseTable->createRow();
      $this->responseRow->questionID = intval($args['questionID']);
      $this->responseRow->instanceID = intval($args['instanceID']);
      $this->responseRow->pageID = intval($args['pageID']);
      $this->responseRow->sectionID = intval($args['sectionID']);
      $this->responseRow->responseText = $args['responseText'];
      $this->responseRow->additionalInfo = $args['additionalInfo'];
      $this->responseRow->externalReference = $args['externalReference'];
      $this->responseRow->state = $args['state'];
      $this->dirty = 1;
    }
    
  }
  
  public function save() {
    
    if (!$this->dirty) {
      return;
    }

    $this->responseRow->additionalInfo = trim($this->responseRow->additionalInfo);
    $this->responseRow->approverComments = trim($this->responseRow->approverComments);
    $this->responseRow->responseText = trim($this->responseRow->responseText);

    if ($this->responseRow->questionID && !$this->responseRow->responseID) {
      $this->responseRow->responseEndDate = new Zend_Db_Expr('NULL');
      $this->responseRow->save();

      $this->updateDisableRules();
    }
    elseif ($this->stateChange) {
      $this->responseRow->responseEndDate = new Zend_Db_Expr('now()');
      $this->responseRow->save();
      $rowArray = $this->responseRow->toArray();
      unset($rowArray["responseID"]);
      unset($rowArray["responseDate"]);
      unset($rowArray["responseEndDate"]);
      $newRow = self::$responseTable->createRow();
      $newRow->setFromArray($rowArray);
      $newRow->responseEndDate = new Zend_Db_Expr('NULL');
      $newRow->save();
      $this->responseRow = $newRow;

      $this->updateDisableRules();
    }
    else {
      $this->responseRow->responseEndDate = new Zend_Db_Expr('NULL');
      $this->responseRow->save();

      $this->updateDisableRules();
    }

    $this->stateChange = 0;
    
  }

  public function delete() {

    $this->responseRow->delete();

    return 1;

  }

  public function __get($key) {

    if ($key === 'parent') {
      if (!isset($this->parent)) {
        $this->parent = new QuestionModel(array('questionID' => $this->responseRow->questionID,
                                                'depth' => 'question'));
      }
      return $this->parent;
    }

    if (isset($this->responseRow->$key)) {
      return $this->responseRow->$key;
    }

    throw new Exception("Attribute not found [$key]");

  }

  public function __set ($key, $value) {

    if (isset($this->responseRow->$key)) {
      if ($this->responseRow->$key !== $value) {
        if ($key === 'state') {
          $this->stateChange = 1;
        }
        $this->responseRow->$key = $value;
        $this->dirty = 1;
      }
    }
    else {
      throw new Exception("Attribute not found [$key]");
    }

  }
  
  /**
   * Get prompt text for responseText as responseText is the promptID for S and M question types
   * @return string
   */
  public function promptText () {
    if($this->responseRow->responseText === null || $this->responseRow->responseText === '') {
      return '';
    }
    $questionPromptRows = self::$questionPromptTable->fetchRows('promptID', $this->responseRow->responseText, null, $this->responseRow->instanceID);
    $questionPromptRow = $questionPromptRows[0];
    if (isset($questionPromptRow->value)) return $questionPromptRow->value;
    throw new Exception('Question prompt row not found for promptID [' . $this->responseRow->responseText . ']');
  }
  
  /**
   * Return an ID that is guaranteed to be unique among objects of type ResponseModel
   *
   * @return string
   */
  public function objectID() {
    return "{$this->responseID}";
  }
  
  /**
   * Return whether or not this response has associated additional information
   *
   * @return boolean
   */
  public function hasAdditionalInfo() {
    return ($this->additionalInfo !== null && $this->additionalInfo !== '');
  }
  
  /**
   * Return whether or not this response requires that additional info be provided
   *
   * @return boolean
   */
  public function requiresAdditionalInfo() {
    if (!isset($this->parent)) {
      $this->parent = new QuestionModel(array('questionID' => $this->responseRow->questionID,
                                              'depth' => 'question'));
    }
    foreach($this->parent->prompts as $prompt) {
      if($prompt['promptID'] == $this->responseText && $prompt['requireAddlInfo']) return true;
    }
    
    return false;
  }

  /**
   * Update any disable rules associated with this response.
   */
  private function updateDisableRules() {
    if (!isset($this->parent)) {
      $this->parent = new QuestionModel(array('questionID' => $this->responseRow->questionID,
                                              'depth' => 'question'));
    }
    $question = $this->parent;
    foreach($question->prompts as $prompt) {
      foreach($prompt['rules'] as $rule) {
        if($prompt['promptID'] == $this->responseText) {
          if ($rule->enabled != 'Y') {
            $rule->enabled = 'Y';
            $rule->save();
            if (preg_match('/.+Page$/', $rule->type)) {
              $page = new PageModel(array('pageID' => $rule->targetID,
                                          'depth' => 'question'));
              $page->save();
            }
            elseif (preg_match('/.+Section$/', $rule->type)) {
              $section = new SectionModel(array('sectionID' => $rule->targetID,
                                                'depth' => 'question'));
              $section->save();
            }
            elseif (preg_match('/.+Question$/', $rule->type)) {
              $question = new QuestionModel(array('questionID' => $rule->targetID,
                                                  'depth' => 'question'));
              $question->save();
            }
          }
        }
        else {
          if ($rule->enabled != 'N') {
            $rule->enabled = 'N';
            $rule->save();
            if (preg_match('/.+Page$/', $rule->type)) {
              $page = new PageModel(array('pageID' => $rule->targetID,
                                        'depth' => 'question'));
              $page->save();
            }
            elseif (preg_match('/.+Section$/', $rule->type)) {
              $section = new SectionModel(array('sectionID' => $rule->targetID,
                                                'depth' => 'question'));
              $section->save();
            }
            elseif (preg_match('/.+Question$/', $rule->type)) {
              $question = new QuestionModel(array('questionID' => $rule->targetID,
                                                  'depth' => 'question'));
              $question->save();
            }
          }
        }
      }
    }
  }

  /**
   * Returns true if this response is approvable, false otherwise
   *
   * @return boolean
   */
  public function hasApprovableResponse() {
    // if we require additional info and none is provided, FALSE
    if($this->requiresAdditionalInfo() && !$this->hasAdditionalInfo()) return false;
    
    // check children if we are a parent...if any is not approvable, FALSE
    if (!isset($this->parent)) {
      $this->parent = new QuestionModel(array('questionID' => $this->responseRow->questionID,
                                              'depth' => 'question'));
    }
    $question = $this->parent;
    if($question->parent instanceof SectionModel) {
      $question = $this->parent;
      foreach($question->children as $child) {
        $response = $child->getResponse();
        if(!$response->hasApprovableResponse()) return false;
      }
    }
    
    // if this is the parent of a question group and we got here, all children are approvable
    if($question->format === '_questionGroup') return true;
    
    // check approvability of this specific response
    if($this->responseID !== null && $this->responseText !== null && $this->responseText !== '') {
      return true;
    }
    
    // if we get here, FALSE
    return false;
  }
  
  /**
   * Checks whether or not additional information is required based on the current responseText
   * value
   *
   * @return boolean
   */
  public function addlInfoRequired() {
    return false;
  }
}
