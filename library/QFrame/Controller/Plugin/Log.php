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


/**
 * @category   QFrame
 * @package    QFrame
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class QFrame_Controller_Plugin_Log extends Zend_Controller_Plugin_Abstract {
  
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
    if(!Zend_Registry::isRegistered('logger')) {
      $this->logger = new Zend_Log();
      $this->logger->addWriter(
        new Zend_Log_Writer_Stream(LOG_PATH . DIRECTORY_SEPARATOR . 'application.log')
      );
      Zend_Registry::set('logger', $this->logger);
    }
    else $this->logger = Zend_Registry::get('logger');
  }
  
  /**
   * Outputs query profiling information to the log
   *
   * @param Zend_Log         logger object to log to
   * @param Zend_Db_Profiler profiler
   */
  public function profileQueries($logger, $profiler) {
    if($profiler->getEnabled()) {
      if($queries = $profiler->getQueryProfiles()) {
        foreach ($queries as $query) {
          $logger->log($query->getQuery(), Zend_Log::INFO);
          $logger->log("Ran in " . $query->getElapsedSecs() . " seconds", Zend_Log::INFO);
        }
      }
      $logger->log("  Ran {$profiler->getTotalNumQueries()} queries in " .
          "{$profiler->getTotalElapsedSecs()} seconds", Zend_Log::INFO);
    }
  }
  
  /**
   * Log preDispatch information
   */
  public function preDispatch() {
    $this->logger->log("Dispatching to {$this->dispatchInfo()}", Zend_Log::INFO);
  }
  
  /**
   * Log postDispatch information
   */
  public function postDispatch() {
    $this->profileQueries($this->logger, Zend_Db_Table::getDefaultAdapter()->getProfiler());
    $this->logger->log("Finished dispatch to {$this->dispatchInfo()}", Zend_Log::INFO);
  }
  
  /**
   * Output controller => 'X', view => 'Y' style controller/view information
   */
  private function dispatchInfo() {
    $request = $this->getRequest();
    $msg = "controller => '{$request->getControllerName()}'";
    $msg .= ", action => '{$request->getActionName()}'";
    if($request->getParam('id')) $msg .= ", id => '{$request->getParam('id')}'";
    
    return $msg;
  }
}
