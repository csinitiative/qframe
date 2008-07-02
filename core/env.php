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
 * The version of this QFrame application
 */
define('QFRAME_VERSION', '1.0');

/*
 * Set the current environment
 */
$env = 'development';
if(isset($_ENV['QFRAME_ENV'])) {
  $env = $_ENV['QFRAME_ENV'];
}
elseif(function_exists('apache_getenv') && apache_getenv('QFRAME_ENV')) {
  $env = apache_getenv('QFRAME_ENV');
}
define('QFRAME_ENV', $env);

/*
 * If there is a file in the config/environments directory named <QFRAME_ENV>.yml
 * load it and place it in $GLOBALS['qframe_env']
 */
$env_file = implode(DIRECTORY_SEPARATOR, array(
  dirname(__FILE__),
  '..',
  'config',
  'environments',
  QFRAME_ENV . '.yml'
));
if(file_exists($env_file)) {
  $GLOBALS['qframe_env'] = Spyc::YAMLLoad($env_file);
}

/*
 * If there is a file in the config/environments directory named <QFRAME_ENV>_maintenance.yml
 * load it and place it in $GLOBALS['qframe_maintenance']
 */
$env_file = implode(DIRECTORY_SEPARATOR, array(
  dirname(__FILE__),
  '..',
  'config',
  'environments',
  QFRAME_ENV . '_maintenance.yml'
));
if(file_exists($env_file)) {
  $GLOBALS['qframe_maintenance'] = Spyc::YAMLLoad($env_file);
}
