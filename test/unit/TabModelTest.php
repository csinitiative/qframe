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
class Test_Unit_TabModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array('SectionModel', 'ResponseModel', 'QuestionModel', 'QuestionTypeModel'));
  }
  
  /*
   * test that not supplying a tabID argument to the TabModel
   * constructor produces an InvalidArgumentException
   */
  public function testMissingTabIDProducesException() {
    try {
      $tab = new TabModel(array());
    }
    catch(InvalidArgumentException $e) { return; }
    
    $this->fail('Expected InvalidArgumentException');
  }
  
  /*
   * test that the 'depth' parameter to the TabModel constructor
   * defaults to 'response'
   */
  public function testDepthDefaultsToResponse() {
    $tab = $this->tab();
    $this->assertNotNull($tab->nextSection()->nextQuestion()->nextResponse());
  }
  
  /*
   * test that the 'depth' parameter is obeyed in the TabModel
   * constructor
   */
  public function testDepthParameterIsObeyed() {
    $tab = $this->tab(array('depth' => 'question'));
    $question = $tab->nextSection()->nextQuestion();
    $this->assertNull($question->nextResponse());
  }
  
  /*
   * test that providing a valid tabID returns the correct tab
   * while providing an invalid one throws an Exception
   */
  public function testConstructorHandlesSectionIDArgumentProperly() {
    $tab = $this->tab();
    $this->assertEquals($tab->tabID, 1);
    try {
      $tab = $this->tab(array('tabID' => -15000));
    } catch(Exception $e) { return; }
    $this->fail('Expected an Exception to be thrown for an invalid tabID');
  }
  
  /*
   * test that instantiated TabModel contains only those properties
   * that it ought to contain
   */
  public function testTabModelContainsCorrectProperties() {
    $tab = $this->tab();
    $this->assertNotNull($tab->tabHeader);
    try {
      $tmp = $tab->invalidProperty;
    } catch(Exception $e) { return; }
    $this->fail('Expected an Exception to be thrown for invalid property access');
  }
  
  /*
   * test that save method will save all subordinate objects
   */
  public function testSaveMethodSavesSubordinates() {
    $tab = $this->tab(array('tabID' => 2));
    while($section = $tab->nextSection())  {    
      while($question = $section->nextQuestion()) {
        $response = $question->nextResponse();
        if(is_null($response)) $response = $question->newResponse();
        $response->responseText = 'TESTING';
      }
    }
    $tab->save();
    
    $tab = $this->tab(array('tabID' => 2));
    while($section = $tab->nextSection()) {    
      while($response = $section->nextQuestion()->nextResponse())
        $this->assertEquals($response->responseText, 'TESTING');
    }
  }
  
  /*
   * test that sections are returned by tab in the correct order
   */
  public function testSectionsReturnInCorrectOrder() {
    $tab = $this->tab();
    $lastSeq = -15000;
    while($section = $tab->nextSection()) {
      $this->assertTrue($section->seqNumber > $lastSeq);
      $lastSeq = $section->seqNumber;
    }
  }
  
  /*
   * test that numSections() provides the correct response
   */
  public function testNumSectionsIsCorrect() {
    $this->assertEquals($this->tab()->numSections(), 3);
  }
  
  /*
   * test that trying to call numSections() when depth is set to 'tab'
   * throws an Exception
   */
  public function testNumSectionsWithDepthTabProducesException() {
    try {
      $this->tab(array('depth' => 'tab'))->numSections();
    } catch(Exception $e) { return; }
    $this->fail('Attempt to call numSections() on a TabModel object with depth = tab should throw Exception');
  }
  
  /*
   * test that child has a sane parent object
   */
  public function testTabChildParent() {
    $tab = $this->tab(array('depth' => 'section'));
    $section = $tab->nextSection();
    $this->assertNotNull($section->parent->seqNumber);
  }
  
  /*
   * test that getQuestion() method returns questions that are on this tab,
   * null if a question is not on this tab, and throws an exception if depth is not
   * sufficient to return questions.
   */
  public function testGetQuestion() {
    $tab = $this->tab(array('depth' => 'question'));
    $this->assertNull($tab->getQuestion(6));
    $this->assertTrue($tab->getQuestion(1) instanceof QuestionModel);
    
    try {
      $tab = $this->tab(array('depth' => 'tab'));
      $tab->getQuestion(0);
    }
    catch(Exception $e) { return; }
    $this->fail('Trying to call getQuestion() with a depth < question should throw exception');
  }
  
  /*
   * test that TabModel objects properly respond to isset()
   */
  public function testTabModelIsset() {
    $section = $this->tab();
    $this->assertTrue(isset($section->disableCount));
    $this->assertFalse(isset($section->foofoofoo));
  }

  
  private function tab($args = array()) {
    $args = array_merge(array(
      'tabID'   => 1
    ), $args);
    return new TabModel($args);
  }
}
