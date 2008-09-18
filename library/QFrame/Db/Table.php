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
  static $adapter;
  static $primaryCache;
  static $discriminatorCache;
  static $bulkID = 0;
  static $tables;
  static $tablesReferenceMap = array();
  static $preload = array();
  static $primaryKeysFieldTable = array();
  static $primaryKeysTableField = array();
  static $foreignKeysFieldTable = array();
  static $foreignKeysTableField = array();
  static $forceForeignKeys = array('sourceID' => true,
                                   'targetID' => true,
                                   'pageGUID' => true,
                                   'sectionGUID' => true,
                                   'questionGUID' => true,
                                   'objectID' => true,
                                   'parentID' => true);
  static $discriminatorKeysTableField = array('attachment' => null,
                                              'crypto' => null,
                                              'db_user' => null,
                                              'instance' => null,
                                              'locks' => null,
                                              'model' => null,
                                              'model_response' => 'modelID',
                                              'page' => 'instanceID',
                                              'page_reference' => 'instanceID',
                                              'questionnaire' => null,
                                              'question' => 'pageID',
                                              'question_prompt' => 'instanceID',
                                              'question_reference' => 'pageID',
                                              'question_type' => 'instanceID',
                                              'reference_detail' => 'instanceID',
                                              'reference' => 'instanceID',
                                              'response' => 'pageID',
                                              'role' => null,
                                              'rule' => 'instanceID',
                                              'section' => 'pageID',
                                              'section_reference' => 'pageID',
                                              'state' => null);

                                   
  /**
   * Convert a table name to a QFrame_Db_Table_* class name
   *
   * @param  string table name
   * @return string
   */
  private static function tableToClass($tableName) {
    return 'QFrame_Db_Table_' . implode('', array_map('ucfirst', explode('_', $tableName)));
  }
  
  /**
   * Return a list of relevant table names (administrative names excluded)
   *
   * @return array
   */
  public static function getTables() {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    return array_diff($adapter->listTables(), array('schema_info'));
  }
  
  /**
   * Scan the database and store the structures necessary to do mappings from table to table, etc
   */
  public static function scanDb() {    
    // empty out all of the cached db information
    array_splice(self::$tablesReferenceMap, 0);
    array_splice(self::$primaryKeysFieldTable, 0);
    array_splice(self::$foreignKeysFieldTable, 0);
    array_splice(self::$foreignKeysTableField, 0);
    
    $tableNames = self::getTables();
    
    foreach ($tableNames as $tableName) {
      $class = self::tableToClass($tableName);
      $table = new $class(null, true);
      foreach ($table->_metadata as $field => $data) {
        if ($data['PRIMARY'] === true) {
          self::$primaryKeysFieldTable[$field][$tableName] = true;
          self::$primaryKeysTableField[$tableName][$field] = true;
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
  
  function __construct ($config = array(), $skipReferenceMap = false) {
    parent::__construct($config);
    if ($skipReferenceMap === false && empty(self::$tablesReferenceMap)) {
      self::scanDb();
    }
    $tableName = $this->getTableName();
    if (isset(self::$tablesReferenceMap[$tableName])) {
      $this->_referenceMap = self::$tablesReferenceMap[$tableName];
    } 
    self::$adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
  }
  
  public function fetchRows($field, $id, $orderby = null, $discriminatorID = null) {
    $tableName = $this->getTablename();

    if (!isset(self::$foreignKeysFieldTable[$field])) {
      throw new Exception('Field must be a primary or foreign key');
    }

    $primaryCache = false;
    $discriminatorCache = false;
    $where = '1=1';

    if (isset(self::$discriminatorKeysTableField[$tableName]) && self::$discriminatorKeysTableField[$tableName] === $field) {
      if (isset(self::$discriminatorCache[$tableName][$id][$field][$id])) {
        return self::$discriminatorCache[$tableName][$id][$field][$id];
      }
      $discriminatorCache = true;
      $discriminatorID = $id;
    }
    // Currently no support for caching compound primary keys
    elseif (isset(self::$primaryKeysFieldTable[$field][$tableName]) && count(self::$primaryKeysTableField[$tableName]) === 1) {
      if (isset(self::$primaryCache[$tableName][$field][$id])) {
        return self::$primaryCache[$tableName][$field][$id];
      }
      $primaryCache = true;
    }

    if (isset($discriminatorID)) {
      $discriminatorField = self::$discriminatorKeysTableField[$tableName];
      if (isset(self::$discriminatorCache[$tableName][$discriminatorID])) {
        if (isset(self::$discriminatorCache[$tableName][$discriminatorID][$field][$id])) {
          return self::$discriminatorCache[$tableName][$discriminatorID][$field][$id];
        }
        return array();
      }
      $where .= self::$adapter->quoteInto(" AND {$discriminatorField} = ?", $discriminatorID);

    }

    $where .= self::$adapter->quoteInto(" AND {$field} = ?", $id);
    $rows = $this->fetchAll($where, $orderby);

    if ($discriminatorCache) {
      (isset(self::$foreignKeysTableField[$tableName])) ? $foreignKeys = self::$foreignKeysTableField[$tableName] : $foreignKeys = array();
      // For empty rowsets.  Put something in here so that we know we've already done the query.
      self::$discriminatorCache[$tableName][$discriminatorID][$field][$discriminatorID] = array();
      if (count($foreignKeys)) {
        foreach ($rows as $row) {
          foreach ($foreignKeys as $foreignKey => $data) {
            if (isset($row->$foreignKey)) {
              self::$discriminatorCache[$tableName][$discriminatorID][$foreignKey][$row->$foreignKey][] = $row;
            }
            // Currently no support for caching compound primary keys
            if (isset(self::$primaryKeysFieldTable[$foreignKey][$tableName]) && count(self::$primaryKeysTableField[$tableName]) === 1 && !isset(self::$primaryCache[$tableName][$foreignKey][$row->$foreignKey])) {
              self::$primaryCache[$tableName][$foreignKey][$row->$foreignKey][] = $row;
            }
          }
        }
      }
      return self::$discriminatorCache[$tableName][$discriminatorID][$field][$discriminatorID];
    }
    elseif ($primaryCache) {
      if ($rows->current()) 
        self::$primaryCache[$tableName][$field][$id][] = $rows->current();
      else // For empty rowsets.  Put something in here so that we know we've already done the query.
        self::$primaryCache[$tableName][$field][$id] = array();
      return self::$primaryCache[$tableName][$field][$id];
    }
    else {
      $array = array();
      foreach ($rows as $row) {
        $array[] = $row;
      }
      return $array;
    }

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
    unset(self::$preload[$tableName]);
    unset(self::$primaryCache[$tableName]);
    unset(self::$discriminatorCache[$tableName]);
  }
  
  public static function resetAll() {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $tableNames = self::getTables();
    foreach ($tableNames as $tableName) {
      QFrame_Db_Table::reset($tableName);
    }
  }
  
  public static function preloadPage($instanceID, $pageID) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $tableNames = self::getTables();
    foreach ($tableNames as $tableName) {
      if (isset(self::$preload[$tableName][$pageID])) continue;
      self::$preload[$tableName][$pageID] = true;
      $table = QFrame_Db_Table::getTable($tableName);
      if ($tableName === 'model_response') continue; // No need to preload this in normal usage
      if (isset(self::$primaryKeysFieldTable['pageID'][$tableName]) || 
          (isset(self::$discriminatorKeysTableField[$tableName]) && self::$discriminatorKeysTableField[$tableName] === 'pageID')) {
        if ($tableName === 'section' || $tableName === 'question')
          $table->fetchRows('pageID', $pageID, 'seqNumber');
        else
          $table->fetchRows('pageID', $pageID);
      }
      if (isset(self::$primaryKeysFieldTable['instanceID'][$tableName]) ||
          (isset(self::$discriminatorKeysTableField[$tableName]) && self::$discriminatorKeysTableField[$tableName] === 'instanceID')) {
        if ($tableName === 'page')
          $table->fetchRows('instanceID', $instanceID, 'seqNumber');
        else
          $table->fetchRows('instanceID', $instanceID);
      }
    }
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
