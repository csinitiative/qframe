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
 * Set up core paths and change directory to PROJECT_PATH
 */
define('PROJECT_PATH', realpath(_path(dirname(__FILE__), '..')));
define('CORE_PATH', _path(PROJECT_PATH, 'core'));
define('LOG_PATH', _path(PROJECT_PATH, 'log'));
define('TEST_PATH', _path(PROJECT_PATH, 'test'));
define('APPLICATION_PATH', _path(PROJECT_PATH, 'application'));
define('LIBRARY_PATH', _path(PROJECT_PATH, 'library'));
define('CONFIG_PATH', _path(PROJECT_PATH, 'config'));
define('CONTROLLER_PATH', _path(APPLICATION_PATH, 'controllers'));

/*
 * Set up an include path that includes important app-specific paths
 */
set_include_path('.' . PATH_SEPARATOR . LIBRARY_PATH . PATH_SEPARATOR . get_include_path());
