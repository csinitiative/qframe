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
 * Get database connection information for the current environment
 */
$db_profiles = Spyc::YAMLLoad(CONFIG_PATH . DIRECTORY_SEPARATOR . 'database.yml');
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
