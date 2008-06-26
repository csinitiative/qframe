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
 * @category   RegQ
 * @package    RegQ_Test
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
 

 /**
  * @category   RegQ
  * @package    RegQ_Test
  * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
  * @license    http://www.gnu.org/licenses/   GNU General Public License v3
  */
class RegQ_Test_Stat {

  /**
   * Stores the list of paths to be monitored
   * @var array
   */
  private $paths;
  
  /**
   * Stores the base path of the paths being monitored
   * @var string
   */
  private $base;

  /**
   * Construct a new Stat object with a given set of paths and a base path
   *
   * @param array  paths to monitor
   * @param string base path
   */
  public function __construct($paths, $base) {    
    $this->paths = $paths;
    $this->base = $base;
    foreach($this->paths as $index => $path)
      $this->paths[$index] = $this->base . DIRECTORY_SEPARATOR . $path;
  }
  
  /**
   * Monitor the given set of paths returning only when changes have been
   * detected
   *
   * @return array
   */
  public function monitor() {
    $current_mtimes = self::mtime_hash($this->paths);
    while(true) {
      $changes = self::compare_mtimes($current_mtimes);
      if(count($changes) > 0) return $changes;
      sleep(1);
    }
  }
  
  
  /**
   * (PRIVATE) Construct a hash of .php files and directories that are reside
   * in one of the paths being monitored.  It is declared static since it uses
   * recursion and thus cannot make use of any instance variables anyway.
   *
   * @param  array set of paths to construct a hash for
   * @param  array results (so far)
   * @return array
   */
  private static function mtime_hash($paths, &$hash = array()) {
    foreach($paths as $path) {
      $hash[$path] = filemtime($path);
      $contents = scandir($path);
      $next_paths = array();
      foreach($contents as $file) {
        $realfile = $path . DIRECTORY_SEPARATOR . $file;
        if($file[0] != '.' && is_dir($realfile)) array_push($next_paths, $realfile);
        elseif($file[0] != '.' && (substr($file, -4, 4) == '.php' || substr($file, -4, 4) == '.yml'))
          $hash[$realfile] = filemtime($realfile);
      }
      self::mtime_hash($next_paths, $hash);
    }
    return $hash;
  }
  
  /**
   * (PRIVATE) Compare the mtimes of the files and directories contained
   * in a hash generated by self::mtime_hash to those files and directories
   * current mtimes and return the set of files and directories that have
   * changed.
   *
   * @param  array set of paths (with mtimes) to check
   * @return array
   */
  private static function compare_mtimes($times) {
    $changes = array();
    foreach($times as $path => $time) {
      if(!file_exists($path)) $changes[] = $path;
      elseif(filemtime($path) != $time) $changes[] = $path;
    }
    return $changes;
  }
}

/*
$current_mtimes = mtime_hash($paths);
while(true) {
  $changes = compare_mtimes($current_mtimes);
  if(count($changes) > 0) {
    $current_mtimes = mtime_hash($paths);
  }
  
  sleep(1);
}
*/