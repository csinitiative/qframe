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
 * @category   QFrame
 * @package    QFrame
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   QFrame
 * @package    QFrame
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class QFrame_Db_SerializableTransaction {

   static $transactionNumber = 0;
   
  /**
   * Begins a serializable transaction and sets a savepoint
   *
   * @param string $savepoint name of the savepoint
   * @return void
   */
  public static function startSerializableTransaction() {
    if (self::$transactionNumber) {
      return;
    }
    self::$transactionNumber = rand(1, 32768);
    self::_setSerializable();
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $adapter->beginTransaction();
    return self::$transactionNumber;
  }

  /**
   * Rollback to a named savepoint
   *
   * @param string $savepoint name of the savepoint
   * @return void
   */
  public static function rollbackSavepoint($t) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $adapter->getConnection()->exec("ROLLBACK TO SAVEPOINT $t");
  }

  /**
   * Commits serializable transaction
   */
  public static function dbCommit($t) {
  	if (!$t) {
  	  return;
  	}
    if ($t !== self::$transactionNumber) {
      throw new Exception("Transaction number [{$t}] is not equal to [{self::$transactionNumber}]");
    }
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $adapter->commit();
    self::$transactionNumber = 0;
  }

  /**
   * Sets isolation level to SERIALIZABLE
   *
   * @return void
   */
  private static function _setSerializable() {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $adapter->getConnection()->exec('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');	
    $adapter->getConnection()->exec('SAVEPOINT sp' . self::$transactionNumber);
  }
  
}
