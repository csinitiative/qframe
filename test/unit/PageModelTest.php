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
 * @category   QFrame_Test
 * @package    QFrame_Test_Unit
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * PHPUnit_Framework
 */
require_once 'PHPUnit/Framework.php';


/**
 * @category   QFrame_Test
 * @package    QFrame_Test_Unit
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class Test_Unit_PageModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array('SectionModel', 'ResponseModel', 'QuestionModel', 'QuestionTypeModel'));
  }
  
  /*
   * test that not supplying a pageID argument to the PageModel
   * constructor produces an InvalidArgumentException
   */
  public function testMissingPageIDProducesException() {
    try {
      $page = new PageModel(array());
    }
    catch(InvalidArgumentException $e) { return; }
    
    $this->fail('Expected InvalidArgumentException');
  }
  
  /*
   * test that the 'depth' parameter to the PageModel constructor
   * defaults to 'response'
   */
  public function testDepthDefaultsToResponse() {
    $page = $this->page();
    $this->assertNotNull($page->nextSection()->nextQuestion()->nextResponse());
  }
  
  /*
   * test that the 'depth' parameter is obeyed in the PageModel
   * constructor
   */
  public function testDepthParameterIsObeyed() {
    $page = $this->page(array('depth' => 'question'));
    $question = $page->nextSection()->nextQuestion();
    $this->assertNull($question->nextResponse());
  }
  
  /*
   * test that providing a valid pageID returns the correct page
   * while providing an invalid one throws an Exception
   */
  public function testConstructorHandlesSectionIDArgumentProperly() {
    $page = $this->page();
    $this->assertEquals($page->pageID, 1);
    try {
      $page = $this->page(array('pageID' => -15000));
    } catch(Exception $e) { return; }
    $this->fail('Expected an Exception to be thrown for an invalid pageID');
  }
  
  /*
   * test that instantiated PageModel contains only those properties
   * that it ought to contain
   */
  public function testPageModelContainsCorrectProperties() {
    $page = $this->page();
    $this->assertNotNull($page->pageHeader);
    try {
      $tmp = $page->invalidProperty;
    } catch(Exception $e) { return; }
    $this->fail('Expected an Exception to be thrown for invalid property access');
  }
  
  /*
   * test that save method will save all subordinate objects
   */
  public function testSaveMethodSavesSubordinates() {
    $page = $this->page(array('pageID' => 2));
    while($section = $page->nextSection())  {    
      while($question = $section->nextQuestion()) {
        $response = $question->nextResponse();
        if(is_null($response)) $response = $question->newResponse();
        $response->responseText = 'TESTING';
      }
    }
    $page->save();
    
    $page = $this->page(array('pageID' => 2));
    while($section = $page->nextSection()) {    
      while($response = $section->nextQuestion()->nextResponse())
        $this->assertEquals($response->responseText, 'TESTING');
    }
  }
  
  /*
   * test that sections are returned by page in the correct order
   */
  public function testSectionsReturnInCorrectOrder() {
    $page = $this->page();
    $lastSeq = -15000;
    while($section = $page->nextSection()) {
      $this->assertTrue($section->seqNumber > $lastSeq);
      $lastSeq = $section->seqNumber;
    }
  }
  
  /*
   * test that numSections() provides the correct response
   */
  public function testNumSectionsIsCorrect() {
    $this->assertEquals($this->page()->numSections(), 3);
  }
  
  /*
   * test that trying to call numSections() when depth is set to 'page'
   * throws an Exception
   */
  public function testNumSectionsWithDepthPageProducesException() {
    try {
      $this->page(array('depth' => 'page'))->numSections();
    } catch(Exception $e) { return; }
    $this->fail('Attempt to call numSections() on a PageModel object with depth = page should throw Exception');
  }
  
  /*
   * test that child has a sane parent object
   */
  public function testPageChildParent() {
    $page = $this->page(array('depth' => 'section'));
    $section = $page->nextSection();
    $this->assertNotNull($section->parent->seqNumber);
  }
  
  /*
   * test that getQuestion() method returns questions that are on this page,
   * null if a question is not on this page, and throws an exception if depth is not
   * sufficient to return questions.
   */
  public function testGetQuestion() {
    $page = $this->page(array('depth' => 'question'));
    $this->assertNull($page->getQuestion(6));
    $this->assertTrue($page->getQuestion(1) instanceof QuestionModel);
    
    try {
      $page = $this->page(array('depth' => 'page'));
      $page->getQuestion(0);
    }
    catch(Exception $e) { return; }
    $this->fail('Trying to call getQuestion() with a depth < question should throw exception');
  }
  
  /*
   * test that PageModel objects properly respond to isset()
   */
  public function testPageModelIsset() {
    $section = $this->page();
    $this->assertTrue(isset($section->disableCount));
    $this->assertFalse(isset($section->foofoofoo));
  }

  
  private function page($args = array()) {
    $args = array_merge(array(
      'pageID'   => 1
    ), $args);
    return new PageModel($args);
  }
}
