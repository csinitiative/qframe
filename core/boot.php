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

$core_path = dirname(__FILE__);

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
 * If in maintenance mode, tell the user and exit.
 */
$maintenanceConfig = QFrame_Maintenance::instance();
if ($maintenanceConfig->isMaintenanceModeOn() === true) {
  die("{$maintenanceConfig->comment}");
}


/*
 * Require file that contains pure configuration (used for testing)
 * as well as routing.  Also include the file that sets up database
 * "stuff".
 */
require(_path($core_path, 'database.php'));



/*
 * Time for the bulk of the actual boot stuff that needs to be done (setting
 * up a router, setting controller paths, view paths, etc)
 */



/*
 * Get the front controller instance for the rest of the script to use and give it a controller
 * directory
 */
$front = Zend_Controller_Front::getInstance();
$front->setControllerDirectory(CONTROLLER_PATH);


/*
 * Register the logging and sanity checking plugins with the front controller
 */
$front->registerPlugin(new QFrame_Controller_Plugin_Log);


/*
 * Register the EzcExecution plugin with the front controller
 */
$front->registerPlugin(new QFrame_Controller_Plugin_EzcExecution);

/*
 * Register the base url setting controller plugin
 */
$front->registerPlugin(new QFrame_Controller_Plugin_BaseUrl);

/*
 * Set up a QFrame_View object, add the path to the helper directory, and set it up
 * as the default view object
 */
$view = new QFrame_View();
$view->addHelperPath(implode(DIRECTORY_SEPARATOR, array(APPLICATION_PATH, 'views', 'helpers')));
$view->addHelperPath(
  implode(DIRECTORY_SEPARATOR, array(LIBRARY_PATH, 'Zend', 'View', 'Helper')),
  'Zend_View_Helper'
);
$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer($view);
$viewRenderer->setViewSuffix('haml')
             ->setViewScriptPathSpec('scripts/:controller/:action.:suffix');
Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);


/*
 * Finally, we are going to actually add some routes...
 */
$router = $front->getRouter();
$router->addRoute(
  'default',
  new Zend_Controller_Router_Route(
    ':controller/:action/:id',
    array('controller' => 'index', 'action' => 'index', 'id' => 0)
  )
);


/*
 * Set a few session parameters:
 *   timeout => 30 minutes
 */
Zend_Session::start();
$session_ns = new Zend_Session_Namespace();
$session_ns->setExpirationSeconds(1800);

/*
 * And last of all dispatch the front controller
 */
$front->dispatch();
