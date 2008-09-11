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
 * @category   Migration
 * @package    Migration
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   Migration
 * @package    Migration
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
abstract class Migration_Adapter {
  
  /**
   * Store the proper migration adapter
   * @var Migration_Adapter
   */
  private static $adapter = null;
  
  /**
   * Store the proper database adapter
   * @var Zend_Db_Adapter_Abstract
   */
  protected $dbAdapter;
  
  /**
   * Store the last error message encountered by this adapter
   * @var string
   */
  protected $error = '';
  
  /**
   * Protected constructor (prevent instantiation of more than one class)
   *
   * @param Zend_Db_Adapter_Abstract database adapter that will be used for db operations
   */
  public final function __construct(Zend_Db_Adapter_Abstract $adapter) {
    $this->dbAdapter = $adapter;
  }
  
  /**
   * Fetch the schema version of the current database
   *
   * @return string
   */
  public final function getSchemaVersion() {
    if(!in_array('schema_info', $this->dbAdapter->listTables())) {
      $this->createTable('schema_info', array('primary' => false), array(
        array('version', 'string')
      ));
      $this->dbAdapter->insert('schema_info', array('version' => '00000000000000'));
      return '00000000000000';
    }
    return $this->dbAdapter->fetchOne(
      $this->dbAdapter->select()->from('schema_info', 'version')->limit(1)
    );
  }
  
  /**
   * Set the schema version to a new value
   *
   * @param string new schema version
   */
  public final function setSchemaVersion($version) {
    $this->dbAdapter->update('schema_info', array('version' => $version));
  }
  
  /**
   * Return the Migration_Adapter in use by this migration adapter 
   *
   * @return Migration_Adapter
   */
  public static final function getAdapter() {
    if(self::$adapter === null) {
      $zendAdapter = Zend_Db_Table_Abstract::getDefaultAdapter();
      $adapterClass = preg_replace('/^Zend_Db/', 'Migration', get_class($zendAdapter));
      if(!class_exists($adapterClass)) {
        throw new Exception('Missing migration adapter class for ' . get_class($zendAdapter));
      }

      $class = new ReflectionClass($adapterClass);
      self::$adapter = $class->newInstance($zendAdapter);
    }
    
    return self::$adapter;
  }
  
  /**
   * Start a transaction (either using QFrame transactions or straight db transactions)
   *
   * @param  Migration migration we are currently running
   * @return mixed
   */
  private final function startTransaction(Migration $migration) {
    if(class_exists('QFrame_Db_SerializableTransaction')) {
      $result = QFrame_Db_SerializableTransaction::startSerializableTransaction();
    }
    else {
      $result = $this->dbAdapter->beginTransaction();
    }
    
    if(!$result) 
      throw new Exception('Could not begin a transaction for migration ' . get_class($migration));
      
    return $result;
  }
  
  /**
   * Commit a transaction (see comment above)
   *
   * @param mixed     transaction identifier (whatever was passed back from startTransaction())
   * @param Migration migration that is currently running
   */
  private final function commitTransaction($transaction, Migration $migration) {
    if(class_exists('QFrame_Db_SerializableTransaction')) {
      $result = QFrame_Db_SerializableTransaction::dbCommit($transaction);
    }
    else {
      if(!$this->dbAdapter->commit()) {
        throw new Exception('Could not commit transaction for migration ' . get_class($migration));
      }
    }
  }
  
  /**
   * Migrate up
   *
   * @param Migration migration to be applied
   * @param string    version we are migrating to
   */
  public final function up(Migration $migration, $version) {
    $result = $this->startTransaction($migration);
      
    $migration->up();
    $this->setSchemaVersion($version);
    
    $this->commitTransaction($result, $migration);
  }
  
  /**
   * Migrate down
   *
   * @param Migration migration to be applied
   * @param string    version we are migrating to
   */
  public final function down(Migration $migration, $version) {
    $result = $this->startTransaction($migration);
      
    $migration->down();
    $this->setSchemaVersion($version);
    
    $this->commitTransaction($result, $migration);
  }
  
  /**
   * Set the error message associated with a failure and return false
   *
   * @param  string failure message
   * @return boolean
   */
  protected final function setError($message) {
    $this->error = $message;
    return false;
  }
  
  /**
   * Gets the last error message encountered by this adapter
   *
   * @return string
   */
  public final function getError() {
    return $this->error;
  }
    
  /**
   * Returns the correct type specifier given a type and a size
   *
   * @param  string  abstract type
   * @param  integer (optional) size specifier
   * @return string
   */
  abstract public function mapType($type, $size = null);
  
  /**
   * Create a new table
   *
   * @param  string table name
   * @param  array  options for creating the table
   * @param  array  array of columns (which is each an array)
   * @return boolean
   */
  abstract public function createTable($name, array $options, array $columns);
  
  /**
   * Drop a table
   *
   * @param  string table name
   * @return boolean
   */
  abstract public function dropTable($table);
  
  /**
   * Add an index to an existing table
   *
   * @param  string table we are adding the index to
   * @param  array  list of columns being indexed
   * @param  array  (optional) list of options to use when generating the index
   * @return boolean
   */
  abstract public function createIndex($table, array $columns, array $options = array());
  
  /**
   * Drop an existing index
   *
   * @param  string        table we are dropping the index from
   * @param  array|string  list of columns being indexed OR explicit name of the index
   * @return boolean
   */
  abstract public function dropIndex($table, $columns);
  
  
  /**
   * Add a column to an existing table
   *
   * @param  string table name
   * @param  array  column definition
   * @return boolean
   */
  abstract public function addColumn($table, $name, $type, array $options = array());
  
  /**
   * Remove a column from an existing table
   *
   * @param  string table name
   * @param  string column name
   * @return boolean
   */
  abstract public function removeColumn($table, $name);
}
