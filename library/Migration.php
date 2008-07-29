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
abstract class Migration {
  
  /**
   * Stores strings and their start times (so the elapsed time can be printed when completed)
   * @var array
   */
  private static $times = array();
  
  /**
   * Pop a time record off the stack and print the corresponding information
   */
  public static function popTime() {
    $time = array_pop(self::$times);
    $base = "{$time['base']}{$time['stop']}";
    $elapsed = round(microtime(true) - $time['time'], 4) . 's';
    if($time['parenthesize']) $elapsed = "({$elapsed})";
    
    if($time['padString'] !== null) {
      echo str_pad("{$base}{$elapsed} ", $time['padCount'], $time['padString']);
    }
    else {
      echo "{$base}{$elapsed}";
    }
    echo "\n";
  }
  
  /**
   * Push a start time record with enough information to pop the record and print the proper
   * line when finished
   *
   * @param string  base string that will be printed for both start and stop
   * @param string  (optional) string that will be concatenated onto the base string on start
   * @param string  (optional) string that will concatenated onto the on stop
   * @param boolean (optional) whether to parenthesize the elapsed time when printed
   * @param string  (optional) string that will be used to pad start and stop string
   * @param integer (optional) how much padding there will be
   */
  public static function pushTime($base, $start = '', $stop = '', $parenthesize = false,
                                  $padString = null, $padCount = 0) {
    if($padString !== null) echo str_pad("{$base}{$start}", $padCount, $padString);
    else echo "{$base}{$start}";
    echo "\n";
    array_push(self::$times, array(
      'base'         => $base,
      'stop'         => $stop,
      'time'         => microtime(true),
      'padString'    => $padString,
      'padCount'     => $padCount,
      'parenthesize' => $parenthesize
    ));
  }
    
  /**
   * Create a new table
   *
   * @param  string name for the new table
   * @param  array  options for creating the new table
   * @param  array  array of columns for the new table
   * @return boolean
   */
  public final function createTable($name, $options, $columns) {
    self::pushTime('', "-- createTable({$name})", '   -> ');
    Migration_Adapter::getAdapter()->createTable($name, $options, $columns);
    self::popTime();
  }
  
  /**
   * Drop an existing table
   *
   * @param  string name of the table to drop
   * @return boolean
   */
  public final function dropTable($name) {
    self::pushTime('', "-- dropTable({$name})", '   -> ');
    Migration_Adapter::getAdapter()->dropTable($name);
    self::popTime();
  }
  
  /**
   * Add an index to an existing table
   *
   * @param  string table we are adding the index to
   * @param  array  list of columns being indexed
   * @param  array  (optional) list of options to use when generating the index
   * @return boolean
   */
  public final function createIndex($table, array $columns, array $options = array()) {
    $columnString = implode(',', $columns);
    self::pushTime('', "-- createIndex({$table}[{$columnString}])", '   -> ');
    Migration_Adapter::getAdapter()->createIndex($table, $columns, $options);
    self::popTime();
  }
  
  /**
   * Drop an existing index
   *
   * @param  string        table we are dropping the index from
   * @param  array|string  list of columns being indexed OR explicit name of the index
   * @return boolean
   */
  public function dropIndex($table, $columns) {
    $columnString = (is_array($columns)) ? implode(',', $columns) : $columns;
    self::pushTime('', "-- dropIndex({$table}[{$columnString}])", '   -> ');
    Migration_Adapter::getAdapter()->dropIndex($table, $columns);
    self::popTime();
  }
  
  /**
   * Required method that will be called when migrating UP past this migration
   */
  abstract public function up();
  
  /**
   * Required method that will be called when migrating DOWN past this migration
   */
  abstract public function down();
}