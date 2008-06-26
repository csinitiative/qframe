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
class RegQ_Config {
  
  /**
   * Singleton instance
   * @var RegQ_Config
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
    $this->defaults = Spyc::YAMLLoad(CONFIG_PATH . DIRECTORY_SEPARATOR . 'default.yml');
  }
  
  /**
   * Returns the singleton object for this class
   * @return RegQ_Config
   */
  public static function instance() {
    if(self::$instance === null) self::$instance = new RegQ_Config;
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
   * Returns the value of the requested property (or throws an exception if said
   * property doesn't exist)
   *
   * @param  string property name
   * @return string
   */
  public function __get($property) {
    if(!isset($this->$property))
      throw new Exception('Non-existent configuration option requested');
    
    if(!isset($GLOBALS['regq_env']) || !isset($GLOBALS['regq_env'][$property]))
      return $this->defaults[$property];
      
    return $GLOBALS['regq_env'][$property];
  }
}