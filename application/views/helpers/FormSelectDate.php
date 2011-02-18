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


/**
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class QFrame_View_Helper_FormSelectDate {
  
  public static $month_names = array(
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December'
  );
    
  /**
   * Generates drop down boxes for a date field
   *
   * @param  int    timestamp (in epoch time)
   * @param  array  options array
   * @param  string prefix for select element names
   * @return string
   */
  public function formSelectDate($date = null, $options = array(), $prefix = 'date') {
    if($date === null) $date = getdate();
    else $date = getdate($date);
    
    $defaults = array(
      'month'       => 'long',
      'pre_years'   => 5,
      'post_years'  => 5,
      'order'       => array('days', 'months', 'years')
    );
    $options = array_merge($defaults, $options);
    
    // Generate the days drop down
    $days = '<select id="' . $prefix . '_days" name="' . $prefix . '[days]">';
    for($i = 1; $i <= 31; $i++) {
      $selected = ($i == $date['mday']) ? ' selected="selected"' : '';
      $days .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
    }
    $days .= '</select>';
    
    // Generate the months drop down
    $months = '<select id="' . $prefix . '_months" name="' . $prefix . '[months]">';
    for($i = 1; $i <= 12; $i++) {
      $selected = ($i == $date['mon']) ? ' selected="selected"' : '';
      $months .= '<option value="' . $i . '"' . $selected . '>';
      if($options['month'] == 'long') $months .= self::$month_names[$i - 1];
      elseif($options['month'] == 'medium') $months .= substr(self::$month_names[$i - 1], 0, 3);
      else $months .= $i;
      $months .= '</option>';
    }
    $months .= '</select';
    
    // Generate the years drop down
    $years = '<select id="' . $prefix . '_years" name="' . $prefix . '[years]">';
    if(isset($options['total_years'])) {
      if($options['total_years'] % 2 == 0) {
        $options['pre_years'] = $options['total_years'] / 2 - 1;
        $options['post_years'] = $options['total_years'] / 2;
      }
      else {
        $options['pre_years'] = ($options['total_years'] - 1) / 2;
        $options['post_years'] = ($options['total_years'] - 1) / 2;
      }
    }
    for($i = $date['year'] - $options['pre_years']; $i < $date['year']; $i++)
      $years .= '<option value="' . $i . '">' . $i . '</option>';
    $years .= '<option value="' . $date['year'] . '" selected="selected">' . $date['year'] . '</option>';
    for($i = $date['year'] + 1; $i <= $date['year'] + $options['post_years']; $i++)
      $years .= '<option value="' . $i . '">' . $i . '</option>';
    $years .= '</select>';
    
    $output = '';
    foreach($options['order'] as $o) {
      if($o == 'days') $output .= $days;
      elseif($o == 'months') $output .= $months;
      elseif($o == 'years') $output .= $years;
    }
    return $output . "\n";
  }
}
