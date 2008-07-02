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
 * @category   QFrame_View
 * @package    QFrame_View_Helper
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   QFrame_View
 * @package    QFrame_View_Helper
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class QFrame_View_Helper_DashboardHelpers {
  
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
  * Generates a drop down box listing all questionnaires given an array of instances to which
  * the user has rights.
  *
  * @param  Array   list of all instances to which this user has access
  * @param  integer currently selected questionnaire (or null if no questionnaire is currently selected)
  * @return string
  */
  public function questionnaireSelect($instances, $questionnaire) {
    if($questionnaire === null) $options[0] = ' ';
    foreach($instances as $instance) {
      $questionnaireName = $this->view->h($instance->questionnaireName);
      $questionnaireVersion = $this->view->h($instance->questionnaireVersion);
      $revision = $this->view->h($instance->revision);
      if(!isset($options[$instance->questionnaireID])) {
        $options[$instance->questionnaireID] = "{$questionnaireName} {$questionnaireVersion}";
        if ($revision != 1) {
          $options[$instance->questionnaireID] .= " (rev. {$revision})";
        }
      }
    }
    return $this->view->formSelect('questionnaire', $questionnaire, null, $options);
  }
  
  /**
   * Generates a drop down box listing all instances that belong to the chosen instance
   *
   * @param  Array   list of all instances to which this user has access
   * @param  integer if of questionnaire that has been selected
   * @return string
   */
  public function instanceSelect($instances, $questionnaire) {
    if($questionnaire === null) {
      $options[0] = 'Select a questionnaire';
    }
    else {
      $options[0] = ' ';
      foreach($instances as $instance) {
        $questionnaireName = $this->view->h($instance->questionnaireName);
        $instanceName = $this->view->h($instance->instanceName);
        if($instance->questionnaireID == $questionnaire) {
          $options[$instance->instanceID] = $instanceName;
        }
      }
    }
    
    return $this->view->formSelect('instance', null, null, $options);
  }
}
