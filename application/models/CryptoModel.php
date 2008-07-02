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
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */

/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class CryptoModel extends QFrame_Db_SerializableTransaction implements QFrame_Paginable {

  private $cryptoRow = null;
  private static $cryptoTable;
  
  /**
   * Create a new CryptoModel
   *
   * @param string Name of the encryption profile
   */
  function __construct ($args = array()) {
    if (!isset(self::$cryptoTable)) self::$cryptoTable = QFrame_Db_Table::getTable('crypto');
    
    if (isset($args['name'])) {
      $where = self::$cryptoTable->getAdapter()->quoteInto('name = ?', $args['name']);
      $this->cryptoRow = self::$cryptoTable->fetchRow($where);
    }
    elseif (isset($args['cryptoID'])) {
      $where = self::$cryptoTable->getAdapter()->quoteInto('cryptoID = ?', $args['cryptoID']);
      $this->cryptoRow = self::$cryptoTable->fetchRow($where);
    }
    else {
      throw new InvalidArgumentException('Missing arguments to CryptoModel constructor');
    }
    
    // crypto row assertion
    if ($this->cryptoRow === NULL) {
      throw new Exception('Crypto profile not found');
    }
    
  }
  
  /**
   * Allows user to get any of the properties of a CryptoModel
   *
   * @param  string the property being requested
   * @return mixed
   */
  public function __get($key) {
    if (isset($this->cryptoRow->$key)) {
      return $this->cryptoRow->$key;
    }
 
    // Otherwise, throw an exception
    throw new Exception("Attribute not found [$key]");
  }
  
  /**
   * Allows user to set any of the properties of a CryptoModel
   *
   * @param  string the property being requested
   * @param  mixed the value of the property
   */
  public function __set($key, $value) {
    if (isset($this->cryptoRow->$key)) {
      $this->cryptoRow->$key = $value;
    }
    else { // Otherwise, throw an exception
      throw new Exception("Attribute not found [$key]");
    }
  }
    
  /**
   * Encrypt a string
   * 
   * @param string Content to be decrypted
   * @return string Encrypted content
   */
  public function encrypt($string) {
    $key = base64_decode($this->cryptoRow->cryptoKey);
    $cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', 'cfb', '');
    $iv = substr(md5($key), 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB));
    mcrypt_generic_init($cipher, $key, $iv);
    $encrypted = mcrypt_generic($cipher, $string);
    mcrypt_generic_deinit($cipher);
    return $encrypted;
  }
  
  /**
   * Decrypt a string
   * 
   * @param string Content to be decrypted
   * @return string Decrypted content
   */
  public function decrypt($string) {
    $key = base64_decode($this->cryptoRow->cryptoKey);
    $cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', 'cfb', '');
    $iv = substr(md5($key), 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB));
    mcrypt_generic_init($cipher, $key, $iv);
    $decrypted = mdecrypt_generic($cipher, rtrim($string));
    mcrypt_generic_deinit($cipher);
    return $decrypted;
  }
  
  /**
   * Deletes this profile
   */
  public function delete() {
    $transactionNumber = self::startSerializableTransaction();
    $this->cryptoRow->delete();
    self::dbCommit($transactionNumber);
  }
  
  /**
   * Saves the profile
   */
  public function save() {
    $transactionNumber = self::startSerializableTransaction();
    $this->cryptoRow->save();
    self::dbCommit($transactionNumber);
  }

  /**
   * Returns an array of CryptoModel objects matching the given criteria
   *
   * @param  Array (optional) various parameters to use when querying (recognized keys are
   * where, order, limit, and offset)
   * @return Array
   */
  public static function _find($args = array()) {
    if (!isset(self::$cryptoTable)) self::$cryptoTable = QFrame_Db_Table::getTable('crypto');

    // set up default values for all of the allowed arguments
    $args = array_merge(array(
      'where'   => null,
      'order'   => null,
      'limit'   => null,
      'offset'  => null
    ), $args);

    $cryptos = array();
    $rows = self::$cryptoTable->fetchAll($args['where'], $args['order'], $args['limit'], $args['offset']);
    foreach($rows as $row) $cryptos[] = new CryptoModel(array('cryptoID' => $row->cryptoID));
    return $cryptos;
  }

  /**
   * Get one page worth of results
   *
   * @param  integer number of objects to return
   * @param  integer offset from the beginning of the result set
   * @param  string  (optional) order clause to apply to the result set
   * @param  string  (optional) search term to apply to the result set
   * @return Array
   */
  public static function getPage($num, $offset, $order = null, $search = null) {
    $where = ($search === null) ? null : self::searchWhere($search);
    return self::_find(array(
      'where'   => $where,
      'order'   => $order,
      'limit'   => $num,
      'offset'  => $offset
    ));
  }

  /**
   * Returns the count of key profiles that match a given search criteria
   *
   * @param  string (optional) search term to apply
   * @return integer
   */
  public static function count($search = null) {
    if($search !== null) $search = self::searchWhere($search);
    else $search = '1';

    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    return(intval($adapter->fetchOne("SELECT COUNT(*) FROM `crypto` WHERE {$search}")));
  }

  /**
   * Produces a where clause for the given search term
   *
   * @param  string search term
   * @return string
   */
  private static function searchWhere($search) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $whereParts = array();
    foreach(array('name') as $column) {
      $whereParts[] = $adapter->quoteInto("{$column} LIKE ?", "%{$search}%");
    } 
    return implode(' OR ', $whereParts);
  } 
  
  /**
   * Generates a new RIJNDAEL_256 symmetric key
   * 
   * @param string Profile name of the new key
   * @param string Optional secret
   * @return CryptoModel Object of new key
   */
  public static function generateNewRijndael256Key($name, $secret = null) {
    if (!isset(self::$cryptoTable)) self::$cryptoTable = QFrame_Db_Table::getTable('crypto');
    
    $trimmedSecret = trim($secret);
    
    $transactionNumber = self::startSerializableTransaction();
    
    if (self::$cryptoTable->getCryptoID($name) !== NULL) {
      throw new Exception('Profile name already exists [' . $name . ']');
    }
    
    if ($secret === NULL) {
      $random = microtime() . memory_get_usage();
      for ($i = 0; $i < 256; $i++) {
        $random .= rand();
      }
      $key = hash('sha256', $random, true);
    }
    elseif (strlen($trimmedSecret) < 8) {
      throw new Exception('The secret must be at least 8 characters or symbols');
    }
    else {
      $key = hash('sha256', $trimmedSecret, true);
    }
    $row = self::$cryptoTable->createRow();
    $row->name = $name;
    $row->cryptoKey = base64_encode($key);
    if (isset($secret)) $row->secret = $trimmedSecret;
    $row->type = 'RIJNDAEL_256';
    $row->save();
    
    self::dbCommit($transactionNumber);
  
    return new CryptoModel(array('name' => $name));
  }
  
  /**
   * Returns all available encryption profiles
   * 
   * @return array CryptoModel objects
   */
  public static function getAllProfiles() {
    if (!isset(self::$cryptoTable)) self::$cryptoTable = QFrame_Db_Table::getTable('crypto');
    
    $profiles = array();
    $rows = self::$cryptoTable->fetchAll()->toArray();
    foreach ($rows as $row) {
      $profiles[] = new CryptoModel(array('cryptoID' => $row['cryptoID']));
    }
    return $profiles;
  }
  
  /**
   * Imports a Rijndael 256 key
   * 
   * @param string Name of the encryption profile
   * @param string Key encoded in base64
   * @return CryptoModel
   */
  public static function importRijnDael256Key($name, $key) {
    if (!isset(self::$cryptoTable)) self::$cryptoTable = QFrame_Db_Table::getTable('crypto');
    
    $transactionNumber = self::startSerializableTransaction();
    
    if (self::$cryptoTable->getCryptoID($name) !== NULL) {
      throw new Exception('Profile name already exists [' . $name . ']');
    }
    
    if (strlen($key) !== 44) {
      throw new Exception('Key length must be 44 characters.');
    }
    
    $row = self::$cryptoTable->createRow();
    $row->name = $name;
    $row->cryptoKey = $key;
    $row->type = 'RIJNDAEL_256';
    $row->save();
    
    self::dbCommit($transactionNumber);
    return new CryptoModel(array('name' => $name));
  }
  
}
