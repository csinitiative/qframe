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
class RegQ_View_Helper_InstancedataHelpers {

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
  * Generates a drop down box listing all questionnaires
  *
  * @param  integer current questionnaire (or null if no current questionnaire)
  * @param string element name
  * @return string
  */
  public function questionnaireSelect($questionnaireID, $name = 'questionnaire') {
    if($questionnaireID === null) $options[0] = ' ';
    $questionnaires = QuestionnaireModel::getAllQuestionnaires();
    foreach($questionnaires as $questionnaire) {
      $questionnaireName = $this->view->h($questionnaire->questionnaireName);
      $questionnaireVersion = $this->view->h($questionnaire->questionnaireVersion);
      $revision = $this->view->h($questionnaire->revision);
      if(!isset($options[$questionnaire->questionnaireID])) {
        $options[$questionnaire->questionnaireID] = "{$questionnaireName} {$questionnaireVersion}";
        if ($revision != 1) {
          $options[$questionnaire->questionnaireID] .= " r. {$revision}";
        }
      }
    }
    return $this->view->formSelect($name, $questionnaireID, null, $options);
  }
  
  /**
   * Generates a drop down box listing all instances that belong to the chosen instance
   *
   * @param  Array   list of all instances to which this user has access
   * @param  integer id of questionnaire that has been selected
   * @return string
   */
  public function instanceSelect($instances, $questionnaire, $name = 'instance', $instanceID = null) {
  	
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
    
    return $this->view->formSelect($name, $instanceID, null, $options);
  }
  
 /**
  * Generates a drop down box listing all crypto profiles
  *
  * @param  integer current crypto profile (or null if no current profile)
  * @param string element name
  * @return string
  */
  public function cryptoSelect($cryptoID, $name = 'cryptoID') {
    $options[0] = 'none';
    $cryptos = CryptoModel::getAllProfiles();
    foreach($cryptos as $crypto) {
      $profileName = $this->view->h($crypto->name);
      if(!isset($options[$crypto->cryptoID])) {
        $options[$crypto->cryptoID] = $profileName;
      }
    }
    return $this->view->formSelect($name, $cryptoID, null, $options);
  }
  
}
