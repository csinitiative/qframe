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
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
 

// Get database connection information for all environments
$db_config = CONFIG_PATH . DIRECTORY_SEPARATOR . 'database.yml';
$db_profiles = Spyc::YAMLLoad($db_config);

// Check for a valid environment
if(!isset($db_profiles[QFRAME_ENV]))
  die("Specified database environment '" . QFRAME_ENV . "' does not exist in '$db_config'.\n\n");

// Get the database connection information for this environment
$db_options = array_merge(array(
  'password'    => null
), $db_profiles[QFRAME_ENV]);

/*
 * Select the adapter to use (fallback = mysqli) and establish the database connection
 */
$adapter = 'mysqli';
if(class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers())) $adapter = 'pdo_mysql';
if($adapter === 'pdo_mysql') {
  $db_options = array_merge(array(
    'driver_options' => array(PDO::MYSQL_ATTR_LOCAL_INFILE => true)
  ), $db_options);
}
$db = Zend_Db::factory($adapter, $db_options);
if($adapter === 'pdo_mysql') {
  $db->getConnection()->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
}
Zend_Db_Table_Abstract::setDefaultAdapter($db);
