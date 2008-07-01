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
 * Include a few very basic core functions
 */
include(implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'core', 'utility.php')));

$core_path = _path(dirname(__FILE__), '..', 'core');

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
 * Include file that contains pure configuration (used for testing)
 * as well as routing.  Also include the file that sets up database
 * "stuff".
 */
include(_path(CORE_PATH, 'database.php'));

/*
 * Set up any dynamic properties (properties that rely on current environment configuration)
 */
include(_path($core_path, 'dynamic.php'));

// perform mock authentication
$auth_adapter = new RegQ_Auth_Adapter('admin', 'admin');
$auth = Zend_Auth::getInstance();
$auth->authenticate($auth_adapter);

$content = file_get_contents(_path(PROJECT_PATH, 'xml', 'sig-3-1-questionnaire-definition.xml'));
QuestionnaireModel::importXML($content);
InstanceModel::importXML($content, 'Acme Bank');
