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
class RegQ_View_Helper_InstrumentdataHelpers {

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
  public function instrumentSelect($instrumentID, $name = 'instrument') {
    if($instrumentID === null) $options[0] = ' ';
    $instruments = InstrumentModel::getAllInstruments();
    foreach($instruments as $instrument) {
      $instrumentName = $this->view->h($instrument->instrumentName);
      $instrumentVersion = $this->view->h($instrument->instrumentVersion);
      $revision = $this->view->h($instrument->revision);
      if(!isset($options[$instrument->instrumentID])) {
        $options[$instrument->instrumentID] = "{$instrumentName} {$instrumentVersion}";
        if ($revision != 1) {
          $options[$instrument->instrumentID] .= " r. {$revision}";
        }
      }
    }
    return $this->view->formSelect($name, $instrumentID, null, $options);
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
