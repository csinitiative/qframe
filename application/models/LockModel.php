<?php
/**
 * This file is part of the CSI RegQ.
 *
 * The CSI RegQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI RegQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   Models
 * @package    Models
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   Models
 * @package    Models
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class LockModel extends RegQ_Db_SerializableTransaction {
  
  /**
   * Time that it will take (in seconds) for a lock to expire
   * @var integer
   */
  private static $expirationTime = 0;
  
  /**
   * Unique ID of this lock
   * @var integer
   */
  private $id = 0;
  
  /**
   * The user that obtained this lock
   * @var DbUserModel
   */
  private $user = null;
  
  /**
   * The object that the lock applies to
   * @var RegQ_Lockable
   */
  private $lockable = null;
  
  /**
   * When this lock was otained
   * @var Time
   */
  private $obtained = null;
  
  /**
   * Expiration date/time of the lock
   * @var string
   */
  private $expiration = null;
  
  /**
   * Fetch the current expiration time
   *
   * @return integer
   */
  public static function getExpiration() {
    return self::$expirationTime;
  }
  
  /**
   * Set the expiration time for all new locks
   *
   * @param integer time (in seconds) to set for expiration
   */
  public static function setExpiration($seconds) {
    self::$expirationTime = $seconds;
  }
  
  /**
   * Convert the current expiration time to a database compatible string
   *
   * @return string
   */
  public static function getExpirationString() {
    $expiration = self::$expirationTime;
    if(self::$expirationTime === 0) return '9999-12-31 23:59:59';
    else return strftime('%Y-%m-%d %T', strtotime('+' . self::$expirationTime . ' seconds'));
  }
  
  /**
   * Construct a new LockModel object.  Since the constructor is private
   * LockModel will not be directly instantiable.
   */
  private function __construct($id, $user, $lockable, $expiration, $obtained = null) {
    $this->id = $id;
    $this->user = $user;
    $this->lockable = $lockable;
    $this->expiration = $expiration;
    $this->obtained = ($obtained === null) ? time() : $obtained;
  }
  
  /**
   * Allows user to get any of the properties of a LockModel
   *
   * @param  string the property being requested
   * @return mixed
   */
  public function __get($key) {
    if(isset($this->$key)) return $this->$key;
    else throw new Exception("Invalid property '{$key}' requested");
  }
  
  /**
   * Obtain a lock on the "lockable" object passed in
   *
   * @param  RegQ_Lockable object to lock
   * @param  DbUserModel   user on whose behalf the lock is being requested
   * @return boolean
   */
  public static function obtain(RegQ_Lockable $lockable, DbUserModel $user) {
    // start a transaction and get the associated adapter object
    $transactionNumber = self::startSerializableTransaction();
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    
    // if the object is not currently locked, lock it
    if(!self::isLocked($lockable, false)) {
      $expiration = self::getExpirationString();
      $numRows = $adapter->insert('locks', array(
        'dbUserID'    => intval($user->dbUserID),
        'className'   => get_class($lockable),
        'objectID'    => $lockable->objectID(),
        'expiration'  => $expiration
      ));
      if($numRows < 1) throw new Exception('Unable to insert a row into the lock table');
      $lastID = intval($adapter->lastInsertId());
      self::dbCommit($transactionNumber);
      return new LockModel($lastID, $user, $lockable, $expiration);
    }
    else {
      self::dbCommit($transactionNumber);
      return self::fetchLock($user, $lockable);
    }
    
    // If we get here no rows were inserted so we can just commit the transaction
    // we started
    self::dbCommit($transactionNumber);
    return null;
  }
  
  /**
   * Determine of a lockable object is currently locked
   *
   * @param  RegQ_Lockable object to check
   * @param  boolean       (optional) manage the necessary transaction
   * @return boolean
   */
  public static function isLocked(RegQ_Lockable $lockable, $transaction = true) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    if ($transaction) {
      $transactionNumber = self::startSerializableTransaction();
    }
    
    // delete any expired rows from the lock table
    $maxExpiration = strftime('%Y-%m-%d %T');
    $adapter->delete('locks', "expiration <= '{$maxExpiration}'");
    
    // fetch a count of locks on the requested object
    $result = $adapter->select()
                      ->from('locks', 'dbUserID')
                      ->where('className = ?', get_class($lockable))
                      ->where('objectID = ?', $lockable->objectID())
                      ->query()
                      ->fetchAll();
    
    if($result) {
      // if the result returned indicates that there are no existing locks, insert a
      // row in the lock table, commit the transaction, and return true
      if(is_array($result) && is_array($result[0])) {
        if($transaction) self::dbCommit($transactionNumber);
        return intval($result[0]['dbUserID']);
      }
    }
    
    if($transaction) self::dbCommit($transactionNumber);
    return false;
  }
  
  /**
   * Forces all locks on a RegQ_Lockable object to be released regardless of owner
   *
   * @param RegQ_Lockable lockable object
   */
  public static function releaseAll(RegQ_Lockable $lockable) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $transactionNumber = self::startSerializableTransaction();
    $adapter->delete('locks', 'className = ? AND objectID = ?', array(
      get_class($lockable),
      $lockable->objectID()
    ));
    self::dbCommit($transactionNumber);
  }
  
  /**
   * Get all locks for the requested user
   *
   * @param  DbUserModel user to get locks for
   * @return Array
   */
  public static function getLocks(DbUserModel $user) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $transactionNumber = self::startSerializableTransaction();
    
    // delete any expired rows from the lock table
    $maxExpiration = strftime('%Y-%m-%d %T');
    $adapter->delete('locks', "expiration <= '{$maxExpiration}'");
    
    // fetch all locks belonging to the specified user
    $results = $adapter->select()
                       ->from('locks')
                       ->where('dbUserID = ?', intval($user->dbUserID))
                       ->query()
                       ->fetchAll();
    
    // create an array of LockModels
    $locks = array();
    if($results) {
      foreach($results as $result) {
        $locks[] = new LockModel($result['lockID'], null, null, $result['expiration'],
            strtotime($result['obtained']));
      }
    }

    self::dbCommit($transactionNumber);
    return $locks;
  }
  
  /**
   * Release this lock
   */
  public function release() {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $transactionNumber = self::startSerializableTransaction();
    $adapter->delete('locks', $adapter->quoteInto('lockID = ?', intval($this->id)));
    self::dbCommit($transactionNumber);
  }
  
  /**
   * Return true if modification of the argument is possible with this
   * lock
   *
   * @param  RegQ_Lockable the lockable object being inquired about
   * @return boolean
   */
  public function canModify(RegQ_Lockable $lockable) {
    return get_class($lockable) === get_class($this->lockable) &&
        $lockable->objectID() === $this->lockable->objectID() &&
        $this->expiration > strftime('%Y-%m-%d %T');
  }
  
  /**
   * Fetch the specified lock
   *
   * @param  DbUserModel   user who owns the lock
   * @param  RegQ_Lockable lockable object the locks should be for
   * @return LockModel
   */
  private static function fetchLock($user, $lockable) {
    // Fetch a reference to the database adapter
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    
    // fetch a count of locks on the requested object
    $result = $adapter->select()
                      ->from('locks', array('lockID', 'expiration', 'obtained'))
                      ->where('dbUserID = ?', intval($user->dbUserID))
                      ->where('className = ?', get_class($lockable))
                      ->where('objectID = ?', $lockable->objectID())
                      ->query()
                      ->fetchAll();
    
    // return a new lock populated from an existing lock in the database, if such a lock
    // exists
    if($result && is_array($result) && is_array($result[0])) {
      $expiration = self::getExpirationString();
      $adapter->update(
        'locks',
        array('expiration' => $expiration),
        "lockID = {$result[0]['lockID']}"
      );
      return new LockModel($result[0]['lockID'], $user, $lockable, $expiration,
          strtotime($result[0]['obtained']));
    }

    return null;
  }
}
