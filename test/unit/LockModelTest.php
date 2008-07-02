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
class Test_Unit_LockModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array('TabModel', 'DbUserModel'));
  }
    
  /*
   * test that two locks can not be obtained on the same object by two different users
   */
  public function testNoTwoLocks() {
    $tab = new TabModel(array('tabID' => 1, 'depth' => 'tab'));
    $user1 = new DbUserModel(array('dbUserID' => 1));
    $user2 = new DbUserModel(array('dbUserID' => 2));
    $this->assertTrue(LockModel::obtain($tab, $user1) instanceof LockModel);
    $this->assertNull(LockModel::obtain($tab, $user2));
  }
  
  /*
   * test that a user, trying to obtain a lock on the same lockable object will get an
   * updated (in terms of expiration) lock
   */
  public function testReobtainingLockUpdatesExpiration() {
    $tab = new TabModel(array('tabID' => 1, 'depth' => 'tab'));
    $user = new DbUserModel(array('dbUserID' => 1));
    LockModel::setExpiration(900);
    $lock1 = LockModel::obtain($tab, $user);
    $this->assertTrue($lock1 instanceof LockModel);
    usleep(1000001); // sleep for one micro-second more than one second...make sure new lock
                     // has later expiration
    $lock2 = LockModel::obtain($tab, $user);   
    $this->assertTrue($lock2 instanceof LockModel);
    $this->assertTrue($lock2->expiration > $lock1->expiration);
  }
  
  /*
   * test that expired rows in the lock table do not prevent a lock from being obtained
   */
  public function testLocksExpireCorrectly() {
    $expiration = LockModel::getExpiration() + 1;
    $tab = new TabModel(array('tabID' => 1, 'depth' => 'tab'));
    $user1 = new DbUserModel(array('dbUserID' => 1));
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $adapter->insert('locks', array(
      'dbUserID'    => 1,
      'className'   => 'TabModel',
      'objectID'    => '1',
      'expiration'  => strftime('%Y-%m-%d %T', (time() + $expiration))
    ));
    $this->assertTrue(LockModel::obtain($tab, $user1) instanceof LockModel);
  }
  
  /*
   * ensure that isLocked return false when not locked, true otherwise
   */
  public function testIsLocked() {
    $tab = new TabModel(array('tabID' => 1, 'depth' => 'tab'));
    $user = new DbUserModel(array('dbUserID' => 1));
    $this->assertFalse(LockModel::isLocked($tab));
    $this->assertTrue(LockModel::obtain($tab, $user) instanceof LockModel);
    $this->assertEquals(LockModel::isLocked($tab), 1);
  }
  
  /*
   * test that releasing a lock allows it to be obtained again
   */
  public function testReleasingLock() {
    $tab = new TabModel(array('tabID' => 1, 'depth' => 'tab'));
    $user1 = new DbUserModel(array('dbUserID' => 1));
    $user2 = new DbUserModel(array('dbUserID' => 2));
    $lock = LockModel::obtain($tab, $user1);
    $this->assertTrue($lock instanceof LockModel);
    $lock->release();
    $this->assertTrue(LockModel::obtain($tab, $user2) instanceof LockModel);
  }
  
  /*
   * test that lock will give user permission to modify the locked tab but
   * not any other tab
   */
  public function testCanModifyAllowsAppropriateModification() {
    $tab1 = new TabModel(array('tabID' => 1, 'depth' => 'tab'));
    $tab2 = new TabModel(array('tabID' => 2, 'depth' => 'tab'));
    $user = new DbUserModel(array('dbUserID' => 1));
    $lock = LockModel::obtain($tab1, $user);
    $this->assertTrue($lock instanceof LockModel);
    $this->assertTrue($lock->canModify($tab1));
    $this->assertFalse($lock->canModify($tab2));
  }
}
