#!/usr/bin/php -q
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
 

/*
 * Load the core of the QFrame application
 */
include(dirname(__FILE__) . '/../core/load.php');

/*
 * Require file that contains pure configuration (used for testing)
 * as well as routing.  Also include the file that sets up database
 * "stuff".
 */
require(_path(CORE_PATH, 'database.php'));

// set the migration path
$migrationPath = _path(PROJECT_PATH, 'db', 'migrations');

// parse command line arguments
if($_SERVER['argc'] < 2) {}
elseif(strtolower($_SERVER['argv'][1]) === 'new') {
  if($_SERVER['argc'] < 3) die("You must specify a name for the new migration\n\n");
  Migration_Generator::generate($migrationPath, $_SERVER['argv'][2]);
}
elseif(!preg_match('/^\d+$/', $_SERVER['argv'][1])) {
  die("First argument to this script must be either 'new' or numeric.\n\n");
}
else {
  $target = $_SERVER['argv'][1];
}

// print a message indicating that we are starting migration
echo "(starting migration)\n";

/*
 * Get all of the files in the db/migrations directory and run the ones
 * that need to be run
 */
$migrations = array();
$migrateUp = (!isset($target) || Migration_Adapter::getAdapter()->getSchemaVersion() <= $target);
foreach(scandir($migrationPath) as $file) {
  if(preg_match('/\.php$/', $file)) {
    include(_path($migrationPath, $file));
    $className = preg_replace('/^\d+_|\.php$/', '', $file);
    if(!class_exists($className)) {
      die("Filename '{$file}' must contain the class '{$className}'\n\n");
    }
    
    if(preg_match('/^(\d+)_/', $file, $matches)) {
      $migrations[$matches[1]] = $className;
    }
    else {
      die("Migration filenames must start with an integer [{$file}]\n\n");
    }
  }
}

// Set target to the maximum version if it is not already set
if(!isset($target)) $target = max(array_keys($migrations));

// Check to make sure start and target are not the same (otherwise, exit)
if(Migration_Adapter::getAdapter()->getSchemaVersion() == $target) exit;

// Check to make sure target migration exists
if(!in_array($target, array_keys($migrations))) {
  die("Specified target migration does not exist\n\n");
}

// Sort migrations
if($migrateUp) ksort($migrations);
else krsort($migrations);

// Sort migrations that need to run and run 'em
foreach($migrations as $version => $migration) {
  // continue to the next iteration if we are already past this version
  $current = Migration_Adapter::getAdapter()->getSchemaVersion();
  if(($migrateUp && $version <= $current) || (!$migrateUp && $version > $current)) continue;
  
  // break from the loop if we have reached the desired version
  if(($migrateUp && $version > $target) || (!$migrateUp && $version <= $target)) break;
  
  // print a message indicating that we are starting a migration
  Migration::pushTime("== {$version} {$migration}: migrat", 'ing ', 'ed ', true, '=', 80);
    
  // create an instance of the migration class and call it's up (or down) method
  $class = new ReflectionClass($migration);
  $instance = $class->newInstance();
  if($migrateUp) Migration_Adapter::getAdapter()->up($instance, $version);
  else Migration_Adapter::getAdapter()->down($instance, $version);
  
  // print a message indicating that we are down with this migration
  Migration::popTime();
}

// If we have gotten to this point make sure the schema version is set to the target (especially
// important for migrating down since we never actually run the target migration)
Migration_Adapter::getAdapter()->setSchemaVersion($target);

// Print a final newline
echo "\n";
