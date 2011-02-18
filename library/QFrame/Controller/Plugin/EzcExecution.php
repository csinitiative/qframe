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

require(PROJECT_PATH . '/library/ezcomponents/Base/src/base.php');
require(PROJECT_PATH . '/library/ezcomponents/Base/src/features.php');
require(PROJECT_PATH . '/library/ezcomponents/Execution/src/interfaces/execution_handler.php');
require(PROJECT_PATH . '/library/ezcomponents/Execution/src/execution.php');

/**
 * @category   QFrame
 * @package    QFrame
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class QFrame_Controller_Plugin_EzcExecution extends Zend_Controller_Plugin_Abstract implements ezcExecutionErrorHandler {
  
  /**
   * Logger object
   * @var Zend_Log
   */
  private $logger;
  
  /**
   * Class constructor
   *
   * Initialize a new Logging controller plugin
   */
  public function __construct() {
    ezcExecution::init('QFrame_Controller_Plugin_EzcExecution');
  }
  
  /**
   * Notifies ezcExecution that there was a graceful exit
   */
  public function postDispatch() {
    ezcExecution::cleanExit();
  }

  /**
   * ezcExecution error handler
   */
  public static function onError(Exception $e = NULL) {
    print "A fatal error occurred.  Please consult the administrator of this application.  If you are experiencing this error after an import operation, the administrator may need to increase the memory limit or max execution time.";
  }

}
