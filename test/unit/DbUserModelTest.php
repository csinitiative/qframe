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
class Test_Unit_DbUserModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array('RoleModel', 'PageModel'));
  }
  
  /*
   * test that the findByUsername static method returns a DbUserModel
   * object or null if no user could be found
   */
  public function testFindByUsername() {
    $user = DbUserModel::findByUsername('sample1');
    $this->assertTrue($user instanceof DbUserModel);
    $user = DbUserModel::findByUsername('nonExistentUser');
    $this->assertNull($user);
  }
  
  /*
   * test that not providing a dbUserID argument works as expected
   * creates a new user or throws an InvalidArgumentException if
   * a username and password were not provided
   */
  public function testProperFunctionOfConstructor() {
    $user = $this->user();
    $this->assertNotNull($user);
    
    try {
      $user = new DbUserModel();
    }
    catch(InvalidArgumentException $e) {}
    catch(Exception $e) { $this->fail('Expecting InvalidArgumentException'); }
    
    try {
      $user = new DbUserModel(array('dbUserName' => 'test'));
    }
    catch(InvalidArgumentException $e) {}
    catch(Exception $e) { $this->fail('Expecting InvalidArgumentException'); }
    
    try {
      $user = new DbUserModel(array('dbUserPW' => 'test'));
    }
    catch(InvalidArgumentException $e) {}
    catch(Exception $e) { $this->fail('Expecting InvalidArgumentException'); }
  }
  
  /*
   * test that DbUserModel constructor throws an error when
   * an invalid dbUserID is passed
   */
  public function testInvalidIDCausesException() {
    try {
      $user = new DbUserModel(array('dbUserID' => -15000));
    } catch(Exception $e) { return; }
    $this->fail('Expected an Exception to be thrown');
  }
  
  /*
   * test that authentication works based on password that was
   * created using the constructor
   */
  public function testAuthenticationWorks() {
    $user = $this->user();
    $user->save();
    $this->assertTrue($user->authenticate('test'));
    $this->assertFalse($user->authenticate('badPassword'));
  }
  
  /*
   * test that a valid user object will not return a password
   */
  public function testValidUserDoesNotReturnPassword() {
    $user = $this->user();
    try {
      $pwd = $user->dbUserPW;
    } catch(Exception $e) { return; }
    $this->fail('Attempt to access dbUserPW should be met with an Exception');
  }
  
  /*
   * test that normal property access works while invalid property
   * access is met with an exception
   */
  public function testPropertyAccess() {
    $user = $this->user(array('dbUserID' => 1));
    $this->assertEquals($user->dbUserName, 'sample1');
    try {
      $tmp = $user->invalidProperty;
    } catch(Exception $e) { return; }
    $this->fail('Invalid property access should throw an exception');
  }
  
  /*
   * test that setting a password works as expected (hashes whatever is
   * passed)
   */
  public function testSettingPasswordWorksAsExpected() {
    $user = $this->user(array('dbUserID' => 1));
    $user->dbUserPW = 'TESTING';
    $this->assertTrue($user->authenticate('TESTING'));
  }
  
  /*
   * test that setting other properties works as it should (if the
   * property exists it gets set, otherwise, it an Exception is
   * thrown)
   */
  public function testSettingPropertiesWorksAsExpected() {
    $user = $this->user(array('dbUserID' => 1));
    $user->dbUserName = 'TESTUSER';
    $this->assertEquals($user->dbUserName, 'TESTUSER');
    try {
      $user->invalidProperty = 'TESTING';
    } catch(Exception $e) { return; }
    $this->fail('Setting invalid property should throw an Exception');
  }
  
  /*
   * test that trying to create two users with the same username
   * produces an Exception
   */
  public function testCreatingTwoUsersWithSameNameCausesException() {
    $user1 = $this->user();
    $user2 = $this->user();
    $user1->save();
    try {
      $user2->save();
    } catch(Exception $e) { return; }
    $this->fail('Attempt to create two users with the same username should produce Exception');
  }
  
  /*
   * test that saving a new user adds that user to the database
   */
  public function testAddingNewUser() {
    $user = $this->user();
    $user->save();
    $this->assertTrue(is_numeric($user->dbUserID));
    $user = $this->user(array('dbUserID' => $user->dbUserID));
    $this->assertNotNull($user);
  }
  
  /*
   * test that modifying an existing user and saving works property
   */
  public function testModifyingUser() {
    $user = $this->user(array('dbUserID' => 1));
    $user->dbUserName = 'TESTING';
    $user->save();
    $user = $this->user(array('dbUserID' => 1));
    $this->assertEquals($user->dbUserName, 'TESTING');
  }
    
  /*
   * test that deleting a user removes it from the database
   */
  public function testDeletingUserRemovesIt() {
    $user = new DbUserModel(array('dbUserID' => 1));
    $user->delete();
    try {
      $user = new DbUserModel(array('dbUserID' => 1));
    } catch(Exception $e) { return; }
    $this->fail('Expected an Exception to be thrown');
  }
  
  /*
   * test that adding a limit to a fetchAll call will limit the results
   * to the specified number
   */
  public function testLimitParameterToFetchAllWorks() {
    $this->assertEquals(count(DbUserModel::fetchAll(array('limit' => 3))), 3);
  }
  
  /*
   * test that the static method fetchAll() works as expected with no
   * parameters
   */
  public function testFetchAllWithoutParams() {
    $users = DbUserModel::fetchAll();
    $this->assertEquals(count($users), 5);
    $this->assertEquals($users[0]->dbUserName, 'sample1');
    $this->assertEquals($users[1]->dbUserName, 'sample2');
  }
   
  /*
   * test that the static method fetchAll() works as expected with
   * a limit parameter
   */
  public function testFetchAllWithLimit() {
    $users = DbUserModel::fetchAll(array('limit' => 3));
    $this->assertEquals(count($users), 3);
    $this->assertEquals($users[0]->dbUserName, 'sample1');
    $this->assertEquals($users[2]->dbUserName, 'sample3');
  }
  
  /*
   * test that the static method fetchAll() works as expected with
   * a limit and offset
   */
  public function testFetchAllWithLimitAndOffset() {
    $users = DbUserModel::fetchAll(array('limit' => 3, 'offset' => 1));
    $this->assertEquals(count($users), 3);
    $this->assertEquals($users[0]->dbUserName, 'sample2');
    $this->assertEquals($users[2]->dbUserName, 'sample4');
  }
  
  /*
   * test that the static method fetchAll() works as expected with
   * an order clause
   */
  public function testFetchAllWithOrderClause() {
    $users = DbUserModel::fetchAll(array(
      'limit'   => 3,
      'offset'  => 1,
      'order'   => 'dbUserName DESC'
    ));
    $this->assertEquals(count($users), 3);
    $this->assertEquals($users[0]->dbUserName, 'sample4');
    $this->assertEquals($users[2]->dbUserName, 'sample2');
  }
  
  /*
   * test that the static method fetchAll() works as expected with
   * a search string
   */
  public function testFetchAllWithSearchString() {
    $users = DbUserModel::fetchAll(array(
      'limit'   => 3,
      'offset'  => 0,
      'order'   => 'dbUserName DESC',
      'search'  => 'sample1'
    ));
    $this->assertEquals(count($users), 1);
    $this->assertEquals($users[0]->dbUserName, 'sample1');
  }
  
  /*
   * test that the count method returns the total number of rows in
   * the dbUser table
   */
  public function testCountMethod() {
    $this->assertEquals(DbUserModel::count(), 5);
    $this->assertEquals(DbUserModel::count('sample1'), 1);
  }
  
  /*
   * test that calling getPage() returns the correct set of objects
   */
  public function testGettingPages() {
    $users = DbUserModel::getPage(2, 2, 'dbUserName DESC', 'sample');
    $this->assertEquals(count($users), 2);
    $this->assertEquals($users[0]->dbUserID, 3);
    $this->assertEquals($users[1]->dbUserID, 2);
  }
  
  /*
   * test that adding a role to a user increases the count of roles assigned to
   * that user
   */
  public function testAddingRole() {
    $user = new DbUserModel(array('dbUserID' => 1));
    $someRole = RoleModel::find('first');
    $initialRoles = $user->roles;
    $user->addRole($someRole);
    $user = new DbUserModel(array('dbUserID' => 1));
    $this->assertEquals(count($user->roles), count($initialRoles) + 1);
  }
  
  /*
   * test that removing a role that has been added results in one fewer role
   * being assigned to the user
   */
  public function testRemovingRole() {
    $user = new DbUserModel(array('dbUserID' => 1));
    $someRole = RoleModel::find('first');
    $initialRoles = $user->roles;
    $user->addRole($someRole);
    $user = new DbUserModel(array('dbUserID' => 1));
    $this->assertEquals(count($user->roles), count($initialRoles) + 1);
    $user->removeRole($someRole);
    $this->assertEquals(count($user->roles), count($initialRoles));
  }
  
  /*
   * test hasAccess method with global permissions
   */
  public function testHasAccessWithGlobal() {
    $user = new DbUserModel(array('dbUserID' => 1));
    $page = new PageModel(array('pageID' => 1, 'depth' => 'page'));
    $this->assertFalse($user->hasAccess('view', $page));
    
    $role = RoleModel::find('first');
    $role->grant('view');
    $role->save();
    $user->addRole($role);
    $this->assertTrue($user->hasAccess('view'));
    $this->assertTrue($user->hasAccess('view', $page));
  }
  
  /*
   * test hasAccess method with page permissions
   */
  public function testHasAccessWithPage() {
    $user = new DbUserModel(array('dbUserID' => 1));
    $page = new PageModel(array('pageID' => 1, 'depth' => 'page'));
    $this->assertFalse($user->hasAccess('view', $page));
    
    $role = RoleModel::find('first');
    $role->grant('view', $page);
    $role->save();
    $user->addRole($role);
    $this->assertFalse($user->hasAccess('view'));
    $this->assertTrue($user->hasAccess('view', $page));
  }
  
  /*
   * test hasAnyAccess method (check to see if user has any rights on an object)
   */
  public function testAnyAccess() {
    $user = new DbUserModel(array('dbUserID' => 1));
    $page = new PageModel(array('pageID' => 1, 'depth' => 'page'));
    $this->assertFalse($user->hasAnyAccess($page));
    
    $role = RoleModel::find('first');
    $role->grant('view', $page);
    $role->save();
    $user->addRole($role);
    $this->assertTrue($user->hasAnyAccess($page));
  }
  
  
  /*
   * generate a new user
   */
  private function user($args = array()) {
    $args = array_merge(array(
      'dbUserName'      => 'test',
      'dbUserPW'        => 'test',
      'dbUserFullName'  => 'Test User'
    ), $args);
    return new DbUserModel($args);
  }
}