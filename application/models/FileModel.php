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
class FileModel {
  
  /**
   * Object that actions on this file model will use as the
   * owner of various files
   * @var QFrame_Storer
   */
  private $obj = null;
  
  /**
   * The path to the data directory
   * @var string
   */
  private static $dataDir = DATA_PATH;
  
  /*
   * Attachment table object
   */
  static $attachmentTable;

  /**
   * Create a new FileModel with a certain object
   *
   * @param QFrame_Storer object to use with this file model object
   */
  public function __construct(QFrame_Storer $obj) {
    $this->obj = $obj;
  }
  
  /**
   * Sets the data directory that is used to store files
   *
   * @param string path to use
   */
  public static function setDataPath($path) {
    self::$dataDir = $path;
  }
  
  /* Gets the data directory that is used to store files
   *
   * @return string path that is currently set
   */
  public static function getDataPath() {
    return self::$dataDir;
  }
  
  /**
   * Stores a new file
   *
   * @param  string data that is to be stored
   * @param  Array (optional) properties for this file
   * @return string id of the newly created file 
   */
  public function store(&$contents, $properties = array()) {
    if($properties === null) $properties = array();
    $properties = array_merge(array(
      'filename'    => 'plain.txt',
      'mime'        => 'text/plain',
      'objectType'  => get_class($this->obj),
      'objectID'    => $this->obj->getID(),
      'instanceID'  => (isset($this->obj->instanceID)) ? $this->obj->instanceID : null,
      'content'     => $contents
    ), $properties);

    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    return self::$attachmentTable->insert($properties);
  }
  
  /**
   * Fetches the file with the given identifier
   *
   * @param  string id of the file to be fetched
   * @return string
   */
  public function fetch($id) {
    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    
    $rows = self::$attachmentTable->fetchRows('attachmentID', intVal($id));
    
    if(count($rows)) {
      $row = $rows[0];
      return $row->content; 
    }
    
    return;
  }
  
  /**
   * Fetches the file with the given identifier in an array with
   * the file's properties
   *
   * @param  integer id of the file to fetch
   * @return Array
   */
  public function fetchWithProperties($id) {
    $properties = $this->fetchProperties($id);
    $properties['contents'] = $this->fetch($id);
    
    return $properties;
  }
  
  /**
   * Fetches the properties for the file with the given id
   *
   * @param  integer id of the file to fetch
   * @return Array
   */
  public function fetchProperties($id) {
    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    
    $rows = self::$attachmentTable->fetchRows('attachmentID', intVal($id));
    
    if(count($rows)) {
      $row = $rows[0];
      return array(
        'filename'    => $row->filename,
        'mime'        => $row->mime,
        'instanceID'  => $row->instanceID
      );
    }
    else throw new Exception('Requested attachment does not exist');
    
    return;
  }
  
  /**
   * Deletes a file with a particular ID
   *
   * @param  string  id of the file
   * @return boolean
   */
  public function delete($id) {
    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    $where = self::$attachmentTable->getAdapter()->quoteInto('attachmentID = ?', intVal($id));
    self::$attachmentTable->delete($where);
    return true;
  }
    
  /**
   * Deletes all files that belong to this object
   */
  public function deleteAll() {
    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    $adapter = self::$attachmentTable->getAdapter();
    $where = $adapter->quoteInto("objectType = ?", get_class($this->obj)) . ' AND ' .
        $adapter->quoteInto("objectID = ?", $this->obj->getID());

    self::$attachmentTable->delete($where);
    return true;
  }
  
  /**
   * Deletes all files that exist in the file repository
   */
  public static function clear($path = null) {
    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    self::$attachmentTable->delete("1");
  }
  
  /**
   * Fetches an array of IDs for files
   *
   * @return Array list of IDs owned by this object
   */
  public function fetchAll() {
    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    $adapter = self::$attachmentTable->getAdapter();
    $rows = self::$attachmentTable->fetchRows('objectID', $this->obj->getID());
    $class = get_class($this->obj);
    foreach ($rows as $row) {
      if ($row->objectType === $class) {
        $ids[] = $row->attachmentID;
      }
    }

    if(isset($ids)) {
      sort($ids);
      return $ids;
    }
    
    return;
  }

  /**
   * Fetches an array of properties for files
   *
   * @return Array list of properties arrays for files owned by this object
   */
  public function fetchAllProperties() {
    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    $class = get_class($this->obj);
    $rows = self::$attachmentTable->fetchRows('objectID', $this->obj->getID());
    foreach($rows as $row) {
      if ($row->objectType === $class) {
        $properties[$row->attachmentID] = array(
          'filename'    => $row->filename,
          'mime'        => $row->mime,
          'instanceID'  => $row->instanceID
        );
      }
    }
    if(isset($properties)) return $properties;
    
    return array();
  }

  /**
   * Stores the contents of a certain filename (rather than needing the
   * contents to be passed in)
   *
   * @param  string filename that will be stored
   * @param  Array  (optional) array of properties for this file
   * @return string id of newly stored file
   */
  public function storeFilename($filename, $properties = array()) {    
    if(!file_exists($filename))
      throw new Exception('Attempted to store a non-existent file');
      
    $contents = file_get_contents($filename);
    return $this->store($contents, $properties);
  }
  
  /**
   * Deletes all files associated with a particular instanceID
   *
   * @param mixed instanceID
   */
  public static function deleteByInstance($instanceID) {
    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    $where = self::$attachmentTable->getAdapter()->quoteInto('instanceID = ?', intVal($instanceID));    
    self::$attachmentTable->delete($where);
  }
  
  /**
   * Fetches all object IDs, arranged by objectType, that belong to the given instance
   *
   * Array returned is of the form:
   *
   *  array {
   *    ['<objectType>'] => array {
   *      [<attachmentID>] => array { <attachmentDetails> }
   *    }  
   *  }
   *
   * @param  integer|string instance ID
   * @return Array
   */
  public static function fetchObjectIdsByInstance($instanceID) {
    if (!isset(self::$attachmentTable)) self::$attachmentTable = QFrame_Db_Table::getTable('attachment');
    
    $objects = array();

    // Get all files assocated with this instance ID
    $rows = self::$attachmentTable->fetchRows('instanceID', intVal($instanceID));
    
    // Sort files by objectType and add them to the array to be returned
    foreach($rows as $row) {
      $properties = $row->toArray();
      unset($properties['objectType']);
      unset($properties['attachmentID']);
      if(!isset($row->objectType)) $objects[$row->objectType] = array();
      $objects[$row->objectType][$row->attachmentID] = $properties;
    }
    
    return $objects;
  }
}
