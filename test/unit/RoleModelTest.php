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
class Test_Unit_RoleModelTest extends QFrame_Test_Unit {
  
  /*
   * test that calling RoleModel::new() returns a brand new
   * RoleModel object
   */
  public function testNewProducesNewRoleModel() {
    $role = RoleModel::create(array('roleDescription' => 'I am a brand new role!'));
    $this->assertTrue($role instanceof RoleModel);
  }
  
  /*
   * test that passing anything except an array to setAttributes will throw an
   * exception
   */
  public function testSetAttributesTakesOnlyAnArray() {
    $role = RoleModel::create(array('roleDescription' => 'I am a brand new role!'));
    try {
      $role->setAttributes('what about a string?');
    }
    catch(Exception $e) { return; }
    $role->setAttributes(array('roleDescription' => 'I am another new role description.'));
    $this->fail('Passing anything except an array to setAttributes() should throw an exception');
  }
  
  /*
   * test that trying to set a non-existent attribute using setAttributes() will
   * throw an exception
   */
  public function testSetAttributeRejectsInvalidAttributes() {
    $role = RoleModel::create(array('roleDescription' => 'I am a brand new role!'));
    try {
      $role->setAttributes(array('INVALIDATTRIBUTE' => 'X'));
    }
    catch(Exception $e) { return; }
    $this->fail('Passing invalid attribute(s) to setAttributes() should throw an exception');
  }
  
  /*
   * test that trying to set a restricted attribute using setAttributes() will
   * throw an exception
   */
  public function testSetAttributeRejectsRestrictedAttributes() {
    $role = RoleModel::create(array('roleDescription' => 'I am a brand new role!'));
    try {
      $role->setAttributes(array('roleID' => '5'));
    }
    catch(Exception $e) { return; }
    $this->fail('Passing restricted attribute(s) to setAttributes() should throw an exception');
  }
  
  /*
   * test that trying to get a property that does not exists produces an exception
   */
  public function testNonExistentPropertyThrowsException() {
    $role = RoleModel::create(array('roleDescription' => 'I am a brand new role!'));
    try {
      $invalid = $role->INVALIDPROPERTY;
    }
    catch(Exception $e) { return; }
    $this->fail('Fetching invalid attribute should throw an exception');
  }
  
  /*
   * test that the find() method will return a RoleModel object with the correct
   * attributes given an ID
   */
  public function testFindMethod() {
    $role = RoleModel::find(1);
    $this->assertEquals($role->roleDescription, 'sample1');
  }
  
  /*
   * test that find() method will return an array of all rows if given no parameters
   */
  public function testFindAll() {
    $roles = RoleModel::find('all');
    $this->assertEquals(count($roles), 4);
    foreach($roles as $role) $this->assertTrue($role instanceof RoleModel);
  }
  
  /*
   * test that find() method will return the correct subset of rows if given a where clause
   */
  public function testFindWithWhere() {
    $roles = RoleModel::find('all', array('where' => 'roleID = 1'));
    $this->assertEquals(count($roles), 1);
    $this->assertEquals($roles[0]->roleID, 1);
  }
  
  /*
   * test that findBy() correctly restricts results
   */
  public function testFindBy() {
    $roles = RoleModel::findBy('all', 'roleDescription', 'sample2or3');
    $this->assertEquals(count($roles), 2);
  }
  
  /*
   * test that calling save() on a brand new role persists that role to the database
   */
  public function testSavePersistsNewRoles() {
    $role = RoleModel::create(array('roleDescription' => 'SAMPLE'))->save();
    $this->assertFalse(is_null($role->roleID));
    $role = RoleModel::find($role->roleID);
    $this->assertEquals($role->roleDescription, 'SAMPLE');
  }
  
  /*
   * test that calling save() on a row that has been persisted already saves any changes
   */
  public function testSavePersistsChanges() {
    $role = RoleModel::find(1);
    $role->roleDescription = 'CHANGED';
    $role->save();
    $this->assertEquals(RoleModel::find(1)->roleDescription, 'CHANGED');
  }
  
  /*
   * test that getPage is working properly
   */
  public function testGetPage() {
    $roles = RoleModel::getPage(1, 1, 'roleID ASC', 'sample');
    $this->assertEquals(count($roles), 1);
    $this->assertEquals($roles[0]->roleID, 2);
  }
  
  /*
   * test that count() returns the correct count
   */
  public function testCountIsCorrect() {
    $this->assertEquals(RoleModel::count('2or3'), 2);
  }
  
  /*
   * test that deleting a role gets rid of it for good
   */
  public function testDelete() {
    $role = RoleModel::find(1);
    $role->delete();
    try {
      $role = RoleModel::find(1);
    }
    catch(Exception $e) { return; }
    $this->fail('Fetching a deleted object should throw an exception.');
  }
}