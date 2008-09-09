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
class ModelQuestionModel {
  
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
   * Stores the question object
   * @var QuestionModel
   */
  private $question;
  
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
   * ModelResponse objects associated with this model
   */
  private $modelResponses = array();
   
  /**
   * Store the modelID
   */
  private $modelID;
  
  /**
   * Array of child questions
   * @var array
   */
  private $name = null;
   
  /**
   * Instantiate a new ModelQuestionModel object
   *
   * @param array
   */
  public function __construct($args = array()) {

    $args = array_merge(array(
      'depth' => 'question',
      'instance' => null
    ), $args);
    $this->depth = $args['depth'];
    $this->compareInstance = $args['instance'];
    
    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    if (!isset(self::$modelResponseTable)) self::$modelResponseTable = QFrame_Db_Table::getTable('model_response');
    
    if (isset($args['modelID']) && isset($args['questionID'])) {
      $this->modelID = $args['modelID'];
      $this->question = new QuestionModel(array('questionID' => $args['questionID'],
                                                'depth' => $args['depth'])); 
    }
    else {
      throw new InvalidArgumentException('Missing arguments to ModelQuestionModel constructor');
    }
   
    if ($this->depth !== 'question') $this->_loadModelResponses();
    
    if (!isset($args['noChildren']) && $this->question->format == '_questionGroup') {
      $this->_loadChildren();
    }
     
  }
  
  /**
   * Return attributes of this ModelQuestion object
   *
   * @param  string key
   * @return mixed
   */
  public function __get($key) {
    if($key === 'children') return $this->children;
    return $this->question->$key;
  }
  
  /**
   * Return true if an attribute exists, false otherwise
   *
   * @return boolean
   */
  public function __isset($key) {
    if (isset($this->question->$key)) return true;
    return false;
  }
  
  /**
   * Pass any unimplemented method calls along to $this->question
   *
   * @param  string method being called
   * @param  array  argyments to pass to said method
   * @return mixed
   */
  public function __call($method, $arguments) {
    return call_user_func_array(array($this->question, $method), $arguments);
  }
  
  /**
   * Save this ModelQuestionModel object and its descendents
   *
   * @return boolean
   */
  public function save() {
    
    if (count($this->modelResponses)) {
      foreach ($this->modelResponses as $modelResponse) {
        $modelResponse->save();
      }
    }
  
  }
  
  /**
   * Creates a ModelResponse object for this ModelQuestionModel
   *
   * @param type One of 'no preference', 'match' (exact text match), 'selected', 'not selected'
   * @param target If for a question of type T, then exact text.  If type S or M (single or multi-select),
   * then the promptID 
   * @return ModelResponseModel
   */
  public function createModelResponse($type, $target) {
    $row = self::$modelResponseTable->createRow();
    if ($type !== 'no preference' && $type !== 'match' && $type !== 'selected' && $type !== 'not selected') {
      throw new Exception('Unknown model response type [${type}]');
    }
    $row->type = $type;
    $row->target = $target;
    $row->modelID = $this->modelID;
    $row->pageID = $this->question->pageID;
    $row->sectionID = $this->question->sectionID;
    $row->questionID = $this->question->questionID;
    $row->save();
    $modelResponse = new ModelResponseModel(array('modelResponseID' => $row->modelResponseID));
    return $modelResponse;
  }
  
  /**
   * Returns the next ModelResponseModel associated with this ModelQuestionModel
   *
   * @return ModelResponseModel Returns null if there are no further pages
   */
  public function nextModelResponse() {
    $nextModelResponse = each($this->modelResponses);
    if(!$nextModelResponse) return null;

    return $nextModelResponse['value'];
  }
  
  /**
   * Determine if this question has a particular model response
   *
   * @param  string (optional) target to check for
   * @return boolean
   */
  public function hasModelResponse($target = null) {
    if($target === null && count($this->modelResponses) > 0) return true;
    foreach($this->modelResponses as $response) {
      if($response->target == $target) return true;
    }
    return false;
  }
  
  /**
   * Determine if this question has a single response indicating no preference
   *
   * @return boolean
   */
  public function hasNoPreference() {
    return count($this->modelResponses) === 1 && $this->modelResponses[0]->type === 'no preference';
  }
  
  /**
   * Deletes all responses for this Model Question
   */
  public function delete() {
    $adapter = self::$modelResponseTable->getAdapter();
    $where = $adapter->quoteInto('modelID = ?', $this->modelID);
    $where .= $adapter->quoteInto(' AND questionID = ?', $this->questionID);
    self::$modelResponseTable->delete($where);
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
    
    $response = $this->question->getResponse();
    
    $result = array();
    
    if ($args['model_fail'] || $args['model_pass']) {
      $messages = array();
      $pass = null;
      while ($modelResponse = $this->nextModelResponse()) {
        switch ($modelResponse->type) {
          case "no preference":
            break;
          case "match":
            if ($modelResponse->target == $response->responseText) {
              $messages['pass'][] = "Matches " . $modelResponse->target;
              $pass = true;
            }
            else {
              $messages['fail'][] = "Does not match " . $modelResponse->target;
              $pass = false;
              break;
            }
            break;
          case "selected":
            if ($modelResponse->target == $response->responseText) {
              $messages['pass'][] = "Prompt selected: " . $modelResponse->promptText();
              $pass = true;
            }
            else {
              $messages['fail'][] = "Prompt not selected: " . $modelResponse->promptText();
              $pass = false;
              break;
            }
            break;
          case "not selected":
            if ($modelResponse->target == $response->responseText) {
              $messages['fail'][] = "Prompt selected: " . $modelResponse->promptText();
              $pass = false;
              break;
            }
            else {
              $messages['pass'][] = "Prompt not selected: " . $modelResponse->promptText();
              $pass = true;
            }
            break;
          case "or selected":
            if ($modelResponse->target == $response->responseText) {
              $messages['pass'][] = "Or prompt selected: " . $modelResponse->promptText();
              $pass = true;
            }
            else {
              if ($pass !== TRUE) {
                $messages['fail'][] = "And prompt not selected: " . $modelResponse->promptText();
                $pass = false;
              }
            }
            break;
          default:
            throw new Exception('Unknown model response type');
        }
      }
      
      if ($pass === TRUE && $args['model_pass']) {
        $result['model_pass'][] = array('question' => $this->question,
                                        'messages' => $messages['pass']
        );
      }
      
      if ($pass === FALSE && $args['model_fail']) {
        $result['model_fail'][] = array('question' => $this->question,
                                        'messages' => $messages['fail']
        );
      }
    }
    
    if ($args['additional_information'] && count($result) === 0 && strlen($response->additionalInfo) > 0) {
      $result['additional_information'][] = array('question' => $this->question);
    }
    
    return $result;
  }

  /**
   * Loads Model Responses
   */
  private function _loadModelResponses() {
    $where = self::$modelResponseTable->getAdapter()->quoteInto('modelID = ?', $this->modelID) .
             self::$modelResponseTable->getAdapter()->quoteInto(' AND questionID = ?', $this->question->questionID);

    $rows = self::$modelResponseTable->fetchAll($where);
    foreach ($rows as $row) {
      $this->modelResponses[] = new ModelResponseModel(array('modelResponseID' => $row->modelResponseID,
                                                             'instance' => $this->compareInstance));
    }
  }
  
  /**
   * Loads children questions
   */
  public function _loadChildren() {
    $this->children = array();
    $rows = QFrame_Db_Table::getTable('question')->fetchRows('parentID', $this->questionID,
        'seqNumber', $this->instanceID);
    foreach ($rows as $row) {
      $this->children[] = new ModelQuestionModel(array(
        'modelID'    => $this->modelID,
        'questionID' => $row->questionID,
        'depth'      => $this->depth
      )); 
    }
  }
  
}
