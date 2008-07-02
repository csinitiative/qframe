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
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class QFrame_View_Helper_FormOptions {
  
  /**
   * Generates an array that can be passed to the FormSelect helper as
   * an options list from an array of objects.
   *
   * @param  string property that should be used as the value
   * @param  string property that should be used as the label
   * @param  Array  collection of objects that all have the above properties
   * @return Array
   */
  public function formOptions($valueProperty, $labelProperty, $values) {
    $options = array();
    foreach($values as $value) {
      $options[$value->$valueProperty] = $value->$labelProperty;
    }
    return $options;
  }
}
