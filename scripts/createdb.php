#!/usr/bin/php -q
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
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */

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
), $profiles[REGQ_ENV]);

/*
 * Set up the base MySQL client command
 */
$command = "mysql -u {$options['username']} ";
if(isset($options['password'])) $command .= "--password={$options['password']} ";
$command .= "{$options['dbname']}";

/*
 * Run the schema.sql and indexes.sql files against the appropriate database
 */
chdir(_path(PROJECT_PATH, 'sql'));
echo `{$command} < schema.sql`;
echo `{$command} < indexes.sql`;
