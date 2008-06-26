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
 * @category   RegQ
 * @package    RegQ
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   RegQ
 * @package    RegQ
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class RegQ_Maintenance {
  
  /**
   * Singleton instance
   * @var RegQ_Maintenance
   */
  private static $instance = null;
  
  /**
   * Default values for supported configuration options
   * @var array
   */
  private $defaults;

  /**
   * (PRIVATE) Constructor.  Private as this is a singleton class (which it
   * only has to be because PHP has no way of implementing dynamic static
   * properties)
   */
  private function __construct() {
    $this->defaults = Spyc::YAMLLoad(CONFIG_PATH . DIRECTORY_SEPARATOR . 'default_maintenance.yml');
  }
  
  /**
   * Returns the singleton object for this class
   * @return RegQ_Config
   */
  public static function instance() {
    if(self::$instance === null) self::$instance = new RegQ_Maintenance;
    return self::$instance;
  }
  
  /**
   * Returns whether or not a particular configuration option exists
   *
   * @param  string name of the option we are checking for
   * @return bool
   */
  public function __isset($property) {
    return isset($this->defaults[$property]);
  }
  
  /**
   * Sets a value for the maintenance configuration file.
   *
   * @param  string name of the property
   * @param  value of the property
   */
  public function __set($property, $value) {
    $GLOBALS['regq_maintenance'][$property] = $value;
  }

  /**
   * Returns the value of the requested property (or throws an exception if said
   * property doesn't exist)
   *
   * @param  string property name
   * @return string
   */
  public function __get($property) {
    if(!isset($this->$property))
      throw new Exception('Non-existent maintenance configuration option requested');
    
    if(!isset($GLOBALS['regq_maintenance']) || !isset($GLOBALS['regq_maintenance'][$property]))
      return $this->defaults[$property];
      
    return $GLOBALS['regq_maintenance'][$property];
  }

  /**
   * Returns true if 'maintenance: true' in the maintenance yaml file.  Returns false otherwise. 
   *
   * @return bool
   */ 
  public function isMaintenanceModeOn() {
     if ($GLOBALS['regq_maintenance']['maintenance'] === true) return true;
     
     return false;
  }

  /**
   * Saves the properties as a yaml file
   */
  public function save() {
    $yaml = Spyc::YAMLDump($GLOBALS['regq_maintenance'], 2, 0);
    $fullPath = _path(PROJECT_PATH, 'config', 'environments', REGQ_ENV) . '_maintenance.yml';
    file_put_contents($fullPath, $yaml);
    if (!is_writable($fullPath)) {
      throw new Exception('Unable to write maintenance configuration file [' . $fullPath . ']');
    }
  }

}
