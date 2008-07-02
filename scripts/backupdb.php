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
 * Command-line arguments
 */
$_ENV['QFRAME_ENV'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : die('Need environment name as first argument');
$backupDir = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : die('Need backup directory as second argument');

/*
 *  Sanity check for backup directory
 */
if (!file_exists($backupDir) || !is_writable($backupDir) || is_file($backupDir)) {
  die("Backup directory not found or user lacks permission [" . $backupDir . "]");
}

/*
 * Include very basic utility functions
 */
include(implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'core', 'utility.php')));

/*
 * Set up a bunch of path constants that the application will use to refer
 * to various application directories
 */
include(_path(dirname(__FILE__), '..', 'core', 'paths.php'));

/*
 * Deal with environment stuff including determining the current environment
 * and loading the configuration stuff for that environment
 */
include(_path(CORE_PATH, 'env.php'));

/*
 * Get database connection information for the current environment
 */
$profiles = Spyc::YAMLLoad(_path(CONFIG_PATH, 'database.yml'));
$options = array_merge(array(
  'password'    => null
), $profiles[QFRAME_ENV]);

$backupFullPath = _path($backupDir, "{$options['dbname']}." . date("Ymd") . "." . date("His") . ".sql");

/*
 * Set up the base MySQL client command
 */
$command = "mysqldump --complete-insert --flush-logs --single-transaction " .
           "--result-file='{$backupFullPath}' " .
           "--host={$options['host']} ";
$user = isset($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : $options['username'];
$password =  isset($_SERVER['argv'][4]) ? $_SERVER['argv'][4] : $options['password'];
$command .= "--user={$user} ";
$command .= "--password={$password} ";
if(isset($options['port'])) $command .= "--port={$options['port']} ";
$command .= $options['dbname'];

/*
 * Run database dump command
 */
system($command);
