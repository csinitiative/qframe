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
class Test_Unit_LockModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array('PageModel', 'DbUserModel'));
  }
    
  /*
   * test that two locks can not be obtained on the same object by two different users
   */
  public function testNoTwoLocks() {
    $page = new PageModel(array('pageID' => 1, 'depth' => 'page'));
    $user1 = new DbUserModel(array('dbUserID' => 1));
    $user2 = new DbUserModel(array('dbUserID' => 2));
    $this->assertTrue(LockModel::obtain($page, $user1) instanceof LockModel);
    $this->assertNull(LockModel::obtain($page, $user2));
  }
  
  /*
   * test that a user, trying to obtain a lock on the same lockable object will get an
   * updated (in terms of expiration) lock
   */
  public function testReobtainingLockUpdatesExpiration() {
    $page = new PageModel(array('pageID' => 1, 'depth' => 'page'));
    $user = new DbUserModel(array('dbUserID' => 1));
    LockModel::setExpiration(900);
    $lock1 = LockModel::obtain($page, $user);
    $this->assertTrue($lock1 instanceof LockModel);
    usleep(1000001); // sleep for one micro-second more than one second...make sure new lock
                     // has later expiration
    $lock2 = LockModel::obtain($page, $user);   
    $this->assertTrue($lock2 instanceof LockModel);
    $this->assertTrue($lock2->expiration > $lock1->expiration);
  }
  
  /*
   * test that expired rows in the lock table do not prevent a lock from being obtained
   */
  public function testLocksExpireCorrectly() {
    $expiration = LockModel::getExpiration() + 1;
    $page = new PageModel(array('pageID' => 1, 'depth' => 'page'));
    $user1 = new DbUserModel(array('dbUserID' => 1));
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $adapter->insert('locks', array(
      'dbUserID'    => 1,
      'className'   => 'PageModel',
      'objectID'    => '1',
      'expiration'  => strftime('%Y-%m-%d %T', (time() + $expiration))
    ));
    $this->assertTrue(LockModel::obtain($page, $user1) instanceof LockModel);
  }
  
  /*
   * ensure that isLocked return false when not locked, true otherwise
   */
  public function testIsLocked() {
    $page = new PageModel(array('pageID' => 1, 'depth' => 'page'));
    $user = new DbUserModel(array('dbUserID' => 1));
    $this->assertFalse(LockModel::isLocked($page));
    $this->assertTrue(LockModel::obtain($page, $user) instanceof LockModel);
    $this->assertEquals(LockModel::isLocked($page), 1);
  }
  
  /*
   * test that releasing a lock allows it to be obtained again
   */
  public function testReleasingLock() {
    $page = new PageModel(array('pageID' => 1, 'depth' => 'page'));
    $user1 = new DbUserModel(array('dbUserID' => 1));
    $user2 = new DbUserModel(array('dbUserID' => 2));
    $lock = LockModel::obtain($page, $user1);
    $this->assertTrue($lock instanceof LockModel);
    $lock->release();
    $this->assertTrue(LockModel::obtain($page, $user2) instanceof LockModel);
  }
  
  /*
   * test that lock will give user permission to modify the locked page but
   * not any other page
   */
  public function testCanModifyAllowsAppropriateModification() {
    $page1 = new PageModel(array('pageID' => 1, 'depth' => 'page'));
    $page2 = new PageModel(array('pageID' => 2, 'depth' => 'page'));
    $user = new DbUserModel(array('dbUserID' => 1));
    $lock = LockModel::obtain($page1, $user);
    $this->assertTrue($lock instanceof LockModel);
    $this->assertTrue($lock->canModify($page1));
    $this->assertFalse($lock->canModify($page2));
  }
}
