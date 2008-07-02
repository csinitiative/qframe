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
class Test_Unit_SectionModelTest extends QFrame_Test_Unit {
  
  /*
   * set up for each test
   */
  public function start() {
    $this->fixture(array('TabModel', 'QuestionModel', 'QuestionTypeModel', 'ResponseModel'));
  }
    
  /*
   * test that the SectionModel controller throws an InvalidArgumentException
   * when no sectionID argument is provided
   */
  public function testConstructorThrowsExceptionWithoutID() {
    try {
      $section = new SectionModel;
    } catch(InvalidArgumentException $e) { return; }
    $this->fail('InvalidArgumentException not thrown with missing sectionID argument');
  }
  
  /*
   * test that providing a sectionID returns the correct SectionModel object
   */
  public function testProvidingSectionIDWorksCorrectly() {
    $section = $this->section();
    $this->assertEquals($section->sectionID, 2);
  }
  
  /*
   * test that providing an invalid sectionID throws an Exception
   */
  public function testProvidingInvalidSectionIDProducesException() {
    try {
      $section = $this->section(array('sectionID' => -15000));
    } catch(Exception $e) { return; }
    $this->fail('Invalid sectionID did not produce an Exception');
  }
  
  /*
   * test saving section saves all subordinate questions
   */
  public function testSavingSectionSavesSubordinates() {
    $section = $this->section();
    while($question = $section->nextQuestion()) {
      $response = $question->nextResponse();
      if(is_null($response)) $response = $question->newResponse();
      $response->responseText = 'TESTING';
    }
    $section->save();
    
    $section = $this->section();
    while($response = $section->nextQuestion()->nextResponse())
      $this->assertEquals($response->responseText, 'TESTING');
  }
  
  /*
   * test that questions are returned in the correct order
   */
  public function testQuestionsReturnedInCorrectOrder() {
    $section = $this->section();
    $lastSeq = -1000;
    while($question = $section->nextQuestion()) {
      $this->assertTrue($question->seqNumber > $lastSeq);
      $lastSeq = $question->seqNumber;
    }
  }
  
  /*
   * test that depth defaults to response
   */
  public function testDepthDefaultsToResponse() {
    $section = $this->section();
    $this->assertNotNull($section->nextQuestion()->nextResponse());
  }
  
  /*
   * test that depth can be configured
   */
  public function testDepthCanBeConfigured() {
    $section = $this->section(array('depth' => 'question'));
    $this->assertNull($section->nextQuestion()->nextResponse());
  }
  
  /*
   * test that SectionModel objects contain valid properties
   * but throw Exceptions for invalid properties
   */
  public function testPropertiesWorkProperly() {
    $section = $this->section(array('depth' => 'section'));
    $this->assertNotNull($section->sectionHeader);
    try {
      $tmp = $section->invalidProperty;
    } catch(Exception $e) { return; }
    $this->fail('Exception not thrown for invalid property access');
  }
  
  /*
   * ensure that getting a collection of questions does not return
   * any questions that have parentIDs
   */
  public function testQuestionCollectionLacksSubordinates() {
    $section = $this->section();
    while($question = $section->nextQuestion())
      $this->assertEquals($question->parentID, 0);
  }
  
  /*
   * test that questions that have subordinate questions (questionGroups)
   * have a property called "children"
   */
  public function testQuestionGroupFunctions() {
    $section = $this->section();
    while($question = $section->nextQuestion()) {
      if($question->questionID == 4)
        $this->assertNotNull($question->children);
      else
        $this->assertEquals(count($question->children), 0);
    }
  }

  /*
   * test that child has a sane parent object
   */
  public function testSectionChildParent() {
    $section = $this->section(array('depth' => 'question'));
    $question = $section->nextQuestion();
    $this->assertNotNull($question->parent->seqNumber);
  }
  
  /*
   * test that SectionModel objects properly respond to isset()
   */
  public function testSectionModelIsset() {
    $section = $this->section();
    $this->assertTrue(isset($section->references));
    $this->assertFalse(isset($section->foofoofoo));
  }

  
  /*
   * return a generic SectionModel object
   */
  private function section($args = array()) {
    $args = array_merge(array(
      'sectionID'   => 2
    ), $args);
    return new SectionModel($args);
  }

}
