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
abstract class Migration_Adapter_Mysql extends Migration_Adapter {

  /**
   * Maps abtract types to the correct MySQL type
   *
   * @param  string  type to be mapped
   * @param  integer (optional) size
   * @return string
   */
  public function mapType($type, $size = null) {
    switch(strtolower($type)) {
      case 'integer':
        return 'INT';
      case 'decimal':
      case 'float':
      case 'datetime':
      case 'date':
      case 'timestamp':
      case 'time':
      case 'text':
        return strtoupper($type);
      case 'string':
        $size = ($size) ? $size : 255;
        return "VARCHAR({$size})";
      case 'binary':
        return 'BLOB';
      case 'boolean':
        return 'TINYINT';
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
    if(count($column) < 2)
      throw new Exception("Invalid column specifier " . '[ "' . implode('","', $column) . '" ]');
      
    $name = $column[0];
    $type = $column[1];
    $limit = (isset($column[2])) ? $column[2] : null;
    $default = (isset($column[3])) ? $this->dbAdapter->quote($column[3]) : null;
    $null = (isset($column[4]) && !$column[4]) ? false : true;
    
    $column = "  {$this->dbAdapter->quoteIdentifier($name)} {$this->mapType($type, $limit)}";
    if($default) $column .= " DEFAULT {$default}";
    if(!$null) $column .= ' NOT NULL';
    if($options['primary'] === $name && (!isset($options['auto']) || $options['auto'])) {
      $column .= ' AUTO_INCREMENT';
    }
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
      array_unshift($columns, array('id', 'integer', null, 0, false));
      $options['primary'] = 'id';
    }
    
    // check for a valid primary key
    if(!$this->validPrimaryKey($options['primary'], $columns)) {
      die("Specified primary key column does not exist [{$options['primary']}]\n\n");
    }
    
    // build up the column definitions 
    $query = "CREATE TABLE {$this->dbAdapter->quoteIdentifier($name)} (\n";
    foreach($columns as $column) {
      $columnStrings[] = $this->generateColumn($column, $options);
    }
    
    // add a "column definition" for a primary key if necessary
    if($options['primary']) {
      $columnStrings[] = "  PRIMARY KEY({$this->dbAdapter->quoteIdentifier($options['primary'])})";
    }
    
    // explode column definitions into SQL, tack on the last little bit, and run the query
    $query .= implode(",\n", $columnStrings);
    $query .= "\n) ENGINE InnoDB DEFAULT CHARSET='utf8'";
    return $this->dbAdapter->query($query);
  }
  
  /**
   * Check to make sure that the passed in primary key is a valid column
   *
   * @param  string primary key name
   * @param  array  column list
   * @return boolean
   */
  protected final function validPrimaryKey($key, $columns) {
    foreach($columns as $column) if($column[0] === $key) return true;
    return false;
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
    ), $options);
    
    // quote columns as identifiers
    foreach($columns as $column) $quotedColumns[] = $this->dbAdapter->quoteIdentifier($column);
    $quotedColumns = implode(',', $quotedColumns);
    
    // build the query
    $query = "CREATE INDEX {$this->dbAdapter->quoteIdentifier($options['name'])}\n";
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
}