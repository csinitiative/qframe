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
class QFrame_Db_Table extends Zend_Db_Table_Abstract {
  private $locked = false;
  private $bulkFileName;
  private $bulkFileHandle;
  private static $preload = false;
  static $discriminators;
  static $cache;
  static $bulkID = 0;
  static $tables;
  static $tablesReferenceMap;
  static $primaryKeysFieldTable = array();
  static $foreignKeysFieldTable = array();
  static $foreignKeysTableField = array();
  static $forceForeignKeys = array('sourceID' => true,
                                   'targetID' => true,
                                   'pageGUID' => true,
                                   'sectionGUID' => true,
                                   'questionGUID' => true,
                                   'objectID' => true,
                                   'parentID' => true);
                                   
  /**
   * Convert a table name to a QFrame_Db_Table_* class name
   *
   * @param  string table name
   * @return string
   */
  private static function tableToClass($tableName) {
    return 'QFrame_Db_Table_' . implode('', array_map('ucfirst', explode('_', $tableName)));
  }
  
  function __construct ($config = array(), $skipReferenceMap = false) {
    parent::__construct($config);
    if ($skipReferenceMap === false && is_null(self::$tablesReferenceMap)) {
      $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
      
      // get a list of table names that are not 'schema_info' which is used internally by the
      // migrations system
      $tableNames = array_diff($adapter->listTables(), array('schema_info'));
      
      foreach ($tableNames as $tableName) {
        $class = self::tableToClass($tableName);
        $table = new $class(null, true);
        foreach ($table->_metadata as $field => $data) {
          if ($data['PRIMARY'] === true) {
            self::$primaryKeysFieldTable[$field][$tableName] = true;
          }
        }
      }
      foreach ($tableNames as $tableName) {
        $class = self::tableToClass($tableName);
        $table = new $class(null, true);
        foreach ($table->_metadata as $field => $data) {
          if (isset(self::$primaryKeysFieldTable[$field]) || isset(self::$forceForeignKeys[$field])) {
            self::$foreignKeysFieldTable[$field][$tableName] = true;
            self::$foreignKeysTableField[$tableName][$field] = true;
          }
        }
      }
      foreach ($tableNames as $tableName) {
        $class = self::tableToClass($tableName);
        $table = new $class(null, true);
        self::$tablesReferenceMap[$tableName] = array();
        foreach ($table->_metadata as $field => $data) {
          if (isset(self::$foreignKeysFieldTable[$field])) {
            foreach (self::$foreignKeysFieldTable[$field] as $foreignTableName => $data) {
              $relationshipName = "{$foreignTableName}_{$tableName}"; 
              self::$tablesReferenceMap[$tableName][$relationshipName] = array('columns'       => $field,
                                                                               'refTableClass' => self::tableToClass($foreignTableName),
                                                                               'refColumns'    => $field);
            }
          }
        }
      }
    }
    $tableName = $this->getTableName();
    if (isset(self::$tablesReferenceMap[$tableName])) {
      $this->_referenceMap = self::$tablesReferenceMap[$tableName];
    } 
  }
  
  public function fetchRows($field, $id, $orderby = null, $instanceID = null) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $tableName = $this->getTablename();
    if (!isset(self::$foreignKeysFieldTable[$field])) {
      throw new Exception('Field must be a primary or foreign key');
    }
    elseif (!isset(self::$forceForeignKeys[$field]) && isset(self::$cache[$tableName][$field][$id])) {
      return self::$cache[$tableName][$field][$id];
    }
    elseif (isset($instanceID) && isset(self::$discriminators[$tableName][$instanceID])) {
      if (isset(self::$cache[$tableName][$field][$id])) {
        return self::$cache[$tableName][$field][$id];
      }
      return array();
    }
    elseif ($field === 'instanceID') {
      self::$discriminators[$tableName][$id] = true;
    }
    $where = $adapter->quoteInto("{$field} = ?", $id);
    $rows = $this->fetchAll($where, $orderby);
    $foreignKeys = self::$foreignKeysTableField[$tableName];
    self::$cache[$tableName][$field][$id] = array();
    if (count($foreignKeys)) {
      foreach ($rows as $row) {
        foreach ($foreignKeys as $foreignKey => $data) {
          if (isset($row->$foreignKey)) {
            self::$cache[$tableName][$foreignKey][$row->$foreignKey][] = $row;
          }
        }
      }
    }
    else {
      self::$cache[$tableName][$field][$id] = $rows;
    }
    return self::$cache[$tableName][$field][$id]; 
  }
  
  public function getTableName() {
    foreach ($this->_metadata as $field => $data) {
      return $data['TABLE_NAME'];
    }
  }
  
  public function insertBulk($array) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    if ($this->locked === false) {
      $this->locked = true;
      $this->bulkFileName = tempnam(PROJECT_PATH . DIRECTORY_SEPARATOR . 'tmp', 'bulkdb');
      chmod($this->bulkFileName, 0755);
      $this->bulkFileHandle = fopen($this->bulkFileName, "w");
    }
    
    self::$bulkID++;
    $id = intVal($array['instanceID'] . str_pad(self::$bulkID, 8, "0", STR_PAD_LEFT)); 
    
    $values = array();
    foreach ($this->_metadata as $field => $data) {
      if ($data['PRIMARY'] === true && !isset($array[$field])) {
        $values[] = $id;
      }
      elseif (isset($array[$field])) {
        $values[] = self::_escape($array[$field]); 
      }
      else {
        $values[] = null;
      }
    }
    $line = join(',', $values) . "\n";
    fwrite($this->bulkFileHandle, $line);
   
    return $id;
  }

  public function processBulk() {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    if ($this->locked === true) {
      $this->locked = false;
      fclose($this->bulkFileHandle);
      $statement = "LOAD DATA LOCAL INFILE '" . $this->bulkFileName . "' INTO TABLE " . $this->_name . " FIELDS TERMINATED BY ',' "
                 . '(' . join(',', array_keys($this->_metadata)) . ')';
      $adapter->query($statement);
      unlink($this->bulkFileName);
    }
  }
  
  public static function reset($tableName) {
    unset(self::$discriminators[$tableName]);
    unset(self::$cache[$tableName]);
    self::$preload = false;
  }
  
  public static function resetAll() {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $tableNames = $adapter->listTables();
    self::$preload = false;
    foreach ($tableNames as $tableName) {
      QFrame_Db_Table::reset($tableName);
    }
  }
  
  public static function preloadAll($instanceID, $pageID) {
    if (self::$preload) return;
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $tableNames = $adapter->listTables();
    foreach ($tableNames as $tableName) {
      if ($tableName === 'question') {
        $table = QFrame_Db_Table::getTable($tableName);
        $table->fetchRows('pageID', $pageID);
      }
      else {
        $table = QFrame_Db_Table::getTable($tableName);
        if (isset($table->_metadata['instanceID'])) {
          $table->fetchRows('instanceID', $instanceID);
        }
      }
    }
    self::$preload = true;
  }
  
  public static function getTable($tableName) {
    if (!isset(self::$tables[$tableName])) {
      $class = self::tableToClass($tableName);
      self::$tables[$tableName] = new $class();
    }
    return self::$tables[$tableName];
  }
  
  public static function _escape($string) {
    return str_replace(
      array("\\", "\n", ","),
      array("\\\\", "\\\n", "\\,"),
      $string
    );
  }
}
