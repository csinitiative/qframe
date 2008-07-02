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
 * @category   QFrame_Test
 * @package    QFrame_Test_Unit
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * PHPUnit_Framework
 */
require_once 'PHPUnit/Framework.php';


/**
 * @category   QFrame_Test
 * @package    QFrame_Test_Unit
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Test_Unit_QuestionModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array(
      'TabModel',
      'SectionModel',
      'QuestionTypeModel',
      'QuestionPromptModel',
      'ResponseModel'
    ));
  }
  
  /*
   * test that not providing an ID to the QuestionModel controller
   * will produce the appropriate exception
   */
  public function testNotProvidingQuestionIDThrowsException() {
    try {
      $tmp = new QuestionModel;
    }
    catch(InvalidArgumentException $e) { return; }
    catch(Exception $e) {}
    $this->fail('Expected an InvalidArgumentException to be thrown');
  }
  
  /*
   * test that providing an invalid questionID produces an exception
   */
  public function testProvidingInvalidIDProducesError() {
    try {
      $tmp = $this->question(array('questionID' => 15000));
    } catch(Exception $e) { return; }
    $this->fail('Expected an Exception to be thrown for invalid questionID');
  }
  
  /*
   * test that trying to fetch a question with an invalid questionType
   * produces an error
   */
  public function testInvalidQuestionTypeProducesError() {
    $conn = Zend_Db_Table_Abstract::getDefaultAdapter()->getConnection();
    $conn->exec(
      "INSERT question(tabID,sectionID,questionID,questionGUID,seqNumber,questionTypeID)" .
      "VALUES(1,2,15000,15000,15000,15000)"
    );
    try {
      $tmp = $this->question(array('questionID' => 15000));
    } catch(Exception $e) { return; }
    $this->fail('Invalid questionTypeID should result in an error');
  }
  
  /*
   * test that the depth argument defaults to response
   */
  public function testDepthDefaultsToResponse() {
    $question = $this->question();
    $this->assertNotNull($question->nextResponse());
  }
  
  /*
   * test that the depth argument is respected
   */
  public function testDepthArgumentIsRespected() {
    $question = $this->question(array('depth' => 'question'));
    $this->assertNull($question->nextResponse());
  }
  
  /*
   * test that newResponse gives us a brand new response
   */
  public function testNewResponseProvidesABrandNewResponse() {
    $question = $this->question();
    $response = $question->newResponse();
    $this->assertNull($response->responseID);
  }
  
  /*
   * test that newResponse sets the new response's parent to the QuestionModel on which
   * newResponse is called
   */
  public function testNewResponseSetsParent() {
    $question = $this->question();
    $response = $question->newResponse();
    $this->assertEquals($response->parent->questionID, $question->questionID);
  }
  
  /*
   * test that save method saves changes to all associated responses
   */
  public function testSaveMethodSavesAllResponses() {
    $question = $this->question();
    while($response = $question->nextResponse())
      $response->responseText = 'TESTING';
    $question->save();

    $question = $this->question();
    while($response = $question->nextResponse())
      $this->assertEquals($response->responseText, 'TESTING');    
  }
  
  /*
   * test that nextReponse returns responses in the correct order
   */
  public function testNextResponseReturnsResponsesInOrder() {
    $question = $this->question();
    $lastID = 0;
    while($response = $question->nextResponse()) {
      $this->assertTrue($response->responseID > $lastID);
      $lastID = $response->responseID;
    }
  }
  
  /*
   * test that QuestionModel::prompts returns an array of prompts
   * for questions that have prompts and null otherwise
   */
  public function testFunctionOfPromptsProperty() {
    $question = $this->question();
    $this->assertEquals(count($question->prompts), 0);
    $question = $this->question(array('questionID' => 2));
    $this->assertEquals(count($question->prompts), 3);
  }
  
  /*
   * test that properties of question and questionType are available
   * as properties of question
   */
  public function testQuestionAndQuestionTypePropertiesAvailable() {
    $question = $this->question();
    $this->assertNotNull($question->qText);
    $this->assertNotNull($question->format);
    try {
      $tmp = $question->nonExistentProperty;
    } catch(Exception $e) { return; }
    $this->fail('Expected exception on reference of non-existent property');
  }

  /*
   * test that child has a sane parent object
   */
  public function testQuestionChildParent() {
    $question = $this->question(array('depth' => 'response'));
    $response = $question->nextResponse();
    $this->assertNotNull($response->parent->seqNumber);
  }
  
  
  /*
   * produce a generic QuestionModel object to use for testing
   */
  private function question($args = array()) {
    $args = array_merge(array(
      'questionID'    => 1
    ), $args);
    return new QuestionModel($args);
  }
}
