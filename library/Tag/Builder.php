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
 * @category   Tag
 * @package    Tag
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * @category   Tag
 * @package    Tag
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class Tag_Builder {

  /**
   * Spit out a single tag with the name of the method called
   *
   * @param  string method that was called
   * @param  Array  list of arguments passed to the method
   * @return string
   */
  public function __call($method, $args) {
    $tag = "<{$method}";
    
    if(count($args) > 0 && is_array($args[0])) {
      foreach(array_shift($args) as $key => $value) {
        $tag .= " {$key}=\"{$value}\"";
      }
    }
    
    if(count($args) == 0) $tag .= ' />';
    else {
      $tag .= '>' . implode($args) . "</{$method}>";
    }
    
    return $tag;
  }
  
  /**
   * Spit out a collection of <option> tags
   *
   * @param  Array  list of options
   * @param  string selected value
   * @param  Array  (optional) collection of properties for each option
   * @return string
   */
  public function options($options, $selected, $properties = array(), $separator = '&nbsp;&nbsp;') {    
    $rendered = '';
    foreach($options as $key => $value) {
      $optionProperties = array_merge(array('value' => $key), $properties);
      if(intval($key) === intval($selected)) $optionProperties['selected'] = 'selected';
      $rendered .= $this->option(
        $optionProperties,
        $value
      );
    }
    return $rendered;
  }
  
  /**
   * Spit out either a select box or collection of radio buttons
   * depending on whether the count of options exceeds the passed
   * in threshold
   *
   * @param  string  name of the element to be generated
   * @param  Array   list of options
   * @param  string  selected value
   * @param  Array   (optional) collection of properties for each option
   * @param  Array   (optional) collection of options to the method
   * @return string
   */
  public function selectOrRadio($name, $options, $selected, $properties = array(),
      $args = array()) {
    $args = array_merge(array(
      'threshold'   => 4,
      'separator'   => '&nbsp;&nbsp;'
    ), $args);
    
    $rendered = '';
    // if the count of options is greater than the threshold, render as a select
    if(count($options) > $args['threshold']) {
      $rendered .= $this->select(
        array_merge($properties, array('name' => $name)),
        $this->options($options, $selected)
      );
    }
    
    // otherwise render as radio buttons
    else {
      // hidden element with id = name of this element...provides a consistent
      // way of locating the position of elements using javascript
      $rendered .= $this->input(
        array('id' => $name, 'type' => 'hidden', 'value' => 'IGNORE')
      );
      
      // output each option as a radio button and label pairing with $separator
      // between them
      foreach($options as $key => $value) {
        $radioProperties = array_merge(
          array('type' => 'radio', 'name' => $name, 'value' => $key),
          $properties
        );
        if(intval($key) === intval($selected)) $radioProperties['checked'] = 'checked';
        $radios[] = $this->input(
          $radioProperties
        ) . $this->label(
          $value
        );
      }
      if(isset($radios)) $rendered .= implode($radios, $args['separator']);
    }
    return $rendered;
  }
  
  /**
   * Spit out a text box
   *
   * @param  string name/id for this text box
   * @param  string (optional) default value for this text box
   * @param  Array  (optional) properties to apply to this text box
   * @return string
   */
  public function text($name, $value = null, $properties = array()) {
    if(!is_null($value)) $properties['value'] = $value;
    $properties = array_merge($properties, array(
      'type'    => 'text',
      'name'    => $name,
      'id'      => $name
    ));
    return $this->input($properties);
  }
  
  /**
   * Spit out a link (<a href...>)
   *
   * @param  string  target (url)
   * @param  string  contents
   * @param  boolean (optional) whether to prepend the application's
   * base URL onto the target
   * @param  Array   (optional) other properties for the generated a tag
   * @return string
   */
  public function link($target, $content, $prepend = true, $properties = array()) {
    if($prepend) $target = Zend_Controller_Front::getInstance()->getBaseUrl() . "/{$target}";
    return $this->a(
      array_merge($properties, array('href' => $target)),
      $content
    );
  }
  
  /**
   * Spit out an image tab (<img src=...>)
   *
   * @param  string  image file to use
   * @param  Array   (optional) additional properties for the image
   * @param  boolean (optional) whether to prepend application image path
   */
  public function image($file, $properties = array(), $prepend = true) {
    if($prepend) $file = Zend_Controller_Front::getInstance()->getBaseUrl() . "/images/{$file}";
    return $this->img(
      array_merge(array('src' => $file, 'alt' => ''), $properties)
    );
  }
}