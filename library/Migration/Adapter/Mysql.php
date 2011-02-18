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
 * @category   Migration
 * @package    Migration
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * @category   Migration
 * @package    Migration
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
abstract class Migration_Adapter_Mysql extends Migration_Adapter {
  
  /**
   * Expands a format like 2M, 2K, 2G, etc. to actual bytes
   *
   * @param  string shorthand notated numeric string
   * @return integer
   */
  private static function expandNumericString($string) {
    $string = preg_replace('/K$/', '000', $string);
    $string = preg_replace('/M$/', '000000', $string);
    $string = preg_replace('/G$/', '000000000', $string);
    if(!is_numeric($string)) throw new Exception("Invalid number string [{$string}]");
    return intVal($string);
  }

  /**
   * Maps abtract types to the correct MySQL type
   *
   * @param  string  type to be mapped
   * @param  integer (optional) limit
   * @return string
   */
  public function mapType($type, $limit = null) {
    switch(strtolower($type)) {
      case 'integer':
        switch(true) {
          case $limit === null:
            return 'INT';
          case $limit <= 4:
            return 'TINYINT';
          case $limit <= 6:
            return 'SMALLINT';
          case $limit <= 9:
            return 'MEDIUMINT';
          case $limit <= 11:
            return 'INT';
          default:
            return 'BIGINT';
        }
      case 'decimal':
      case 'float':
      case 'datetime':
      case 'date':
      case 'timestamp':
      case 'time':
        return strtoupper($type);
      case 'string':
        $limit = ($limit) ? $limit : 255;
        return "VARCHAR({$limit})";
      case 'text':
        if($limit !== null) $limit = self::expandNumericString($limit);
        switch(true) {
          case $limit === null:
            return 'TEXT';
          case $limit <= 255:
            return 'TINYTEXT';
          case $limit <= 65535:
            return 'TEXT';
          case $limit <= 16777215:
            return 'MEDIUMTEXT';
          default:
            return 'LONGTEXT';
        }
      case 'binary':
        if($limit !== null) $limit = self::expandNumericString($limit);
        switch(true) {
          case $limit === null:
            return 'BLOB';
          case $limit <= 255:
            return 'TINYBLOB';
          case $limit <= 65535:
            return 'BLOB';
          case $limit <= 16777215:
            return 'MEDIUMBLOB';
          default:
            return 'LONGBLOB';
        }
      case 'boolean':
        return 'TINYINT(1)';
      default:
        throw new Exception("Unknown abstract type '{$type}'");
    }
  }
  
  /**
   * Convert an array of column elements into the text to create that column
   *
   * @param  array column parameters
   * @return string
   */
  public function generateColumn($column, array $options) {
    // check column array count to make sure there is enough data
    if(count($column) < 2)
      throw new Exception("Invalid column specifier " . '[ "' . implode('","', $column) . '" ]');
      
    // merge default column values with some sensible defaults
    if(count($column) == 2) $column[2] = array();
    $colOpts = array_merge(array(
      'limit'   => null,
      'default' => null,
      'null'    => false
    ), $column[2]);
    
    // set up some variables so that the column string we build will make more sense
    $unquotedName = $column[0];
    $name = $this->dbAdapter->quoteIdentifier($column[0]);
    $type = $this->mapType($column[1], $colOpts['limit']);
    $default = (isset($colOpts['default'])) ? $this->dbAdapter->quote($colOpts['default']) : null;
    $null = (isset($colOpts['null']) && !$colOpts['null']) ? false : true;

    // build the column string
    $column = "{$name} {$type}";
    if($default !== null) $column .= " DEFAULT {$default}";
    if(!$null) $column .= ' NOT NULL';
    if($options['primary'] === $unquotedName && (!isset($options['auto']) || $options['auto'])) {
      $column .= ' AUTO_INCREMENT';
    }
    
    // return the built column
    return $column;
  }
  
  /**
   * Create a new table
   *
   * @param  string table name
   * @param  array  options for creating the table
   * @param  array  array of columns (which is each an array)
   * @return boolean
   */
  public function createTable($name, array $options, array $columns) {
    // check for a 'primary' option and act appropriately based on what its value is
    if(!isset($options['primary'])) {
      array_unshift($columns, array('id', 'integer', array('null' => false)));
      $options['primary'] = 'id';
    }
    
    // check for a valid primary key
    if($options['primary'] && !$this->validPrimaryKey($options['primary'], $columns)) {
      die("Specified primary key column does not exist [{$options['primary']}]\n\n");
    }
    
    // if $options['primary'] is an array, join it all up, otherwise just quote it
    if(is_array($options['primary'])) {
      $adapter = $this->dbAdapter;
      $quotedKeys = array_map(array($adapter, 'quoteIdentifier'), $options['primary']);
      $primary = implode(',', $quotedKeys);
    }
    elseif($options['primary']) {
      $primary = $this->dbAdapter->quoteIdentifier($options['primary']);
    }
    
    // build up the column definitions 
    $query = "CREATE TABLE {$this->dbAdapter->quoteIdentifier($name)} (\n";
    foreach($columns as $column) {
      $columnStrings[] = '  ' . $this->generateColumn($column, $options);
    }
    
    // add a "column definition" for a primary key if necessary
    if($options['primary']) {
      $columnStrings[] = "  PRIMARY KEY({$primary})";
    }
    
    // explode column definitions into SQL, tack on the last little bit, and run the query
    $query .= implode(",\n", $columnStrings);
    $query .= "\n) ENGINE InnoDB DEFAULT CHARSET='utf8'";
    return $this->dbAdapter->query($query);
  }
  
  /**
   * Check to make sure that the passed in primary key is a valid column
   *
   * @param  array|string primary key name
   * @param  array        column list
   * @return boolean
   */
  protected final function validPrimaryKey($key, $columns) {
    foreach($columns as $column) $columnNames[] = $column[0];
    if(is_array($key)) {
      foreach($key as $k) {
        if(!in_array($k, $columnNames)) return false;
      }
    }
    else {
      if(!in_array($key, $columnNames)) return false;      
    }
    return true;
  }
  
  /**
   * Drop a table
   *
   * @param  string table name
   * @return boolean
   */
  public function dropTable($table) {
    $query = "DROP TABLE {$this->dbAdapter->quoteIdentifier($table)}";
    return $this->dbAdapter->query($query);
  }
  
  /**
   * Add an index to an existing table
   *
   * @param  string table we are adding the index to
   * @param  array  list of columns being indexed
   * @param  array  (optional) list of options to use when generating the index
   * @return boolean
   */
  public function createIndex($table, array $columns, array $options = array()) {
    // merge the options we got with a default set of options
    $options = array_merge(array(
      'name'  => strtolower(implode('_', $columns)) . '_index',
      'unique'  => false,
    ), $options);

    $unique = $options['unique'] ? 'UNIQUE' : '';
    
    // quote columns as identifiers
    foreach($columns as $column) $quotedColumns[] = $this->dbAdapter->quoteIdentifier($column);
    $quotedColumns = implode(',', $quotedColumns);
    
    // build the query
    $query = "CREATE {$unique} INDEX {$this->dbAdapter->quoteIdentifier($options['name'])}\n";
    if(isset($options['type'])) $query .= "  USING {$options['type']}\n";
    $query .= "  ON {$this->dbAdapter->quoteIdentifier($table)}({$quotedColumns})";
    
    // run the query and return
    return $this->dbAdapter->query($query);
  }
  
  /**
   * Drop an existing index
   *
   * @param  string        table we are dropping the index from
   * @param  array|string  list of columns being indexed OR explicit name of the index
   * @return boolean
   */
  public function dropIndex($table, $columns) {
    if(is_array($columns)) $columns = strtolower(implode('_', $columns)) . '_index';
    $name = $this->dbAdapter->quoteIdentifier($columns);
    $table = $this->dbAdapter->quoteIdentifier($table);
    return $this->dbAdapter->query("DROP INDEX {$name} ON {$table}");
  }
  
  /**
   * Add a column to an existing table
   *
   * @param  string table name
   * @param  string new column name
   * @param  string new column type
   * @param  array  column definition
   * @return boolean
   */
  public function addColumn($table, $name, $type, array $options = array()) {
    $this->addOrModifyColumn(true, $table, $name, $type, $options);
  }
  
  /**
   * Remove a column from an existing table
   *
   * @param  string table name
   * @param  string column name
   * @return boolean
   */
  public function removeColumn($table, $name) {
    $table = $this->dbAdapter->quoteIdentifier($table);
    $column = $this->dbAdapter->quoteIdentifier($name);
    return $this->dbAdapter->query("ALTER TABLE {$table} DROP COLUMN {$column}");
  }
  
  /**
   * Alter an existing column
   *
   * @param  string table name
   * @param  string column name
   * @param  string new column type
   * @param  array  column definition
   * @return boolean
   */
  public function alterColumn($table, $name, $type, array $options = array()) {
    $this->addOrModifyColumn(false, $table, $name, $type, $options);
  }
  
  /**
   * Add a new column or modify an existing column
   *
   * @param  boolean is this a new column?
   * @param  string  table name
   * @param  string  column name
   * @param  string  new column type
   * @param  array   column definition
   * @return boolean
   */
  private function addOrModifyColumn($new, $table, $name, $type, array $options) {
    $verb = ($new) ? 'ADD' : 'MODIFY';
    $column = $this->generateColumn(array($name, $type, $options), array('primary' => false));
    $table = $this->dbAdapter->quoteIdentifier($table);
    return $this->dbAdapter->query("ALTER TABLE {$table} {$verb} COLUMN {$column}");
  }
}
