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
 * @category   RegQ_View
 * @package    RegQ_View_Helper
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   RegQ_View
 * @package    RegQ_View_Helper
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class RegQ_View_Helper_RoleHelpers {

  /**
   * Stores the associated view for persistence
   * @var Zend_View_Interface
   */
  private $view = null;
  
  /**
   * Sets the associated view (should be called automatically by the view)
   *
   * @param Zend_View_Interface
   */
  public function setView($view) {
    $this->view = $view;
  }
  
  /**
   * Generates a drop down box listing all instruments given an array of instances to which
   * the user has rights.
   *
   * @param  Array   list of all instances to which this user has access
   * @param  integer currently selected instrument (or null if no instrument is currently selected)
   * @return string
   */
   public function instrumentSelect($instances, $instrument) {
     if($instrument === null) $options[0] = ' ';
     foreach($instances as $instance) {
       $instrumentName = $this->view->h($instance->instrumentName);
       $instrumentVersion = $this->view->h($instance->instrumentVersion);
       if(!isset($options[$instance->instrumentID])) {
         $options[$instance->instrumentID] = "{$instrumentName} {$instrumentVersion}";
       }
     }
     return $this->view->formSelect('instrument', $instrument, null, $options);
   }

   /**
    * Generates a drop down box listing all instances that belong to the chosen instance
    *
    * @param  Array   list of all instances to which this user has access
    * @param  integer if of instrument that has been selected
    * @return string
    */
   public function instanceSelect($instances, $instrument) {
     if($instrument === null) {
       $options[0] = 'Select a questionnaire';
     }
     else {
       $options[0] = ' ';
       foreach($instances as $instance) {
         $instrumentName = $this->view->h($instance->instrumentName);
         $instanceName = $this->view->h($instance->instanceName);
         if($instance->instrumentID == $instrument) {
           $options[$instance->instanceID] = $instanceName;
         }
       }
     }

     return $this->view->formSelect('instance', null, null, $options);
   }
}
