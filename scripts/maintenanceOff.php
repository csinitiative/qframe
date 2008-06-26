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
 * Command-line arguments
 */
$_ENV['REGQ_ENV'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : die('Need environment name as first argument');
$comment = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : null;

$core_path = dirname(__FILE__) . '/../core';

/*
 * Include a few very basic utility functions
 */
include($core_path . DIRECTORY_SEPARATOR . 'utility.php');

/*
 * Deal with the fact that magic_quotes_gpc might be turned on (though)
 * it would be better to just turn it off
 */
include(_path($core_path, 'magicquotes.php'));

/*
 * Set up a bunch of path constants that the application will use to refer
 * to various application directories
 */
include(_path($core_path, 'paths.php'));

/*
 * Deal with environment stuff including determining the current environment
 * and loading the configuration stuff for that environment
 */
include(_path($core_path, 'env.php'));

/*
 * Set up any dynamic properties (properties that rely on current environment configuration)
 */
include(_path($core_path, 'dynamic.php'));

/*
 * Modify maintenance configuration
 */
$maintenanceConfig = RegQ_Maintenance::instance();
$maintenanceConfig->maintenance = false;
$maintenanceConfig->save();
