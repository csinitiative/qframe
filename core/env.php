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
 * The version of this RegQ application
 */
define('REGQ_VERSION', '1.0');

/*
 * Set the current environment
 */
$env = 'development';
if(isset($_ENV['REGQ_ENV'])) {
  $env = $_ENV['REGQ_ENV'];
}
elseif(function_exists('apache_getenv') && apache_getenv('REGQ_ENV')) {
  $env = apache_getenv('REGQ_ENV');
}
define('REGQ_ENV', $env);

/*
 * If there is a file in the config/environments directory named <REGQ_ENV>.yml
 * load it and place it in $GLOBALS['regq_env']
 */
$env_file = implode(DIRECTORY_SEPARATOR, array(
  dirname(__FILE__),
  '..',
  'config',
  'environments',
  REGQ_ENV . '.yml'
));
if(file_exists($env_file)) {
  $GLOBALS['regq_env'] = Spyc::YAMLLoad($env_file);
}

/*
 * If there is a file in the config/environments directory named <REGQ_ENV>_maintenance.yml
 * load it and place it in $GLOBALS['regq_maintenance']
 */
$env_file = implode(DIRECTORY_SEPARATOR, array(
  dirname(__FILE__),
  '..',
  'config',
  'environments',
  REGQ_ENV . '_maintenance.yml'
));
if(file_exists($env_file)) {
  $GLOBALS['regq_maintenance'] = Spyc::YAMLLoad($env_file);
}
