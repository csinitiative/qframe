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
class Test_Unit_ResponseModelTest extends QFrame_Test_Unit {
    
  // Initialization
  public function start() {
    $this->fixture(array(
      'PageModel',
      'SectionModel',
      'QuestionModel',
      'QuestionTypeModel',
      'QuestionPromptModel'
    ));
  }
    
  /*
   * test that not supplying a responseID produces a new response
   */
  public function testMissingResponseIDProducesNewResponse() {
    $response = new ResponseModel(array(
      'questionID'    => 1,
      'instanceID'    => 1,
      'pageID'         => 1,
      'sectionID'     => 1,
      'responseText'  => 'Sample Response'
    ));
    $this->assertNull($response->responseID);
  }

  /*
   * test that supplying a responseID produces the correct response
   * model
   */
  public function testSupplyingResponseIDProducesCorrectResponse() {
    $response = $this->response();
    $this->assertEquals($response->responseID, 1);
    $this->assertEquals(get_class($response), 'ResponseModel');
  }
  
  /*
   * test that changing the state of a response creates a new row
   */
  public function testStateChangeCreatesNewRow() {
    $response = $this->response();
    $old_id = $response->responseID;
    $response->state = 'Assigned';
    $response->save();
    $this->assertNotEquals($old_id, $response->responseID);
  }
  
  /*
   * test that not changing state does not create a new row
   */
  public function testNotChangingStatePreservesRow() {
    $response = $this->response();
    $old_id = $response->responseID;
    $response->responseText = 'Something Else';
    $response->save();
    $response = $this->response();
    $this->assertEquals($response->responseID, $old_id);
    $this->assertEquals($response->responseText, 'Something Else');
  }
  
  
  /*
   * test that getting or setting a non-existent property produces
   * an exception
   */
  public function testGettingOrSettingInvalidPropertyProducesError() {
    $response = $this->response();
    try {
      $tmp = $response->invalidElement;
      $this->fail('Getting invalid property did not result in an exception being thrown');
      return;
    } catch(Exception $e) {}
    try {
      $response->invalidElement = 'Something New';
      $this->fail('Setting invalid property did not result in an exception being thrown');
      return;
    } catch(Exception $e) {}
  }
  
  /*
   * test that deleting a response removes it from the database
   */
  public function testDeletingResponseProperlyRemovesObject() {
    $response = $this->response();
    $response->delete();
    try {
      $response = $this->response();
      $this->fail('Fetching a deleted response should produce an Exception');
    } catch(Exception $e) {}
  }
  
  /*
   * test that requiresAdditionalInfo() is functioning correctly
   */
  public function testrequiresAdditionalInfo() {
    $response = $this->response();
    $this->assertFalse($response->requiresAdditionalInfo());
    $response = $this->response(array('responseID' => 6));
    $this->assertTrue($response->requiresAdditionalInfo());
  }
  
  private function response($args = array()) {
    $args = array_merge(array(
      'responseID'   => 1
    ), $args);
    return new ResponseModel($args);
  }
}
