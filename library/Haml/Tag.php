<?php
/**
 * This file is part of the CSI SIG.
 *
 * The CSI SIG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI SIG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */

/** Haml_Element */
require_once 'Haml/Element.php';


/**
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class Haml_Tag implements Haml_Element {  
  
  /**
   * Tag type (a, div, span, etc.)
   * @var string
   */
  private $type = '';

  /**
   * Tag ID (<div id="...">)
   * @var string
   */
  private $id = NULL;

  /**
   * Array of tag classes (<div class="... ...">)
   * @var array
   */
  private $classes = NULL;

  /**
   * Other tag attributes (<a href="...">)
   * @var array
   */
  private $attributes = NULL;

  /**
   * Content for tags with content on the same line (<div>...</div>)
   * @var string
   */
  private $content = NULL;

  /**
   * Whether this tag is self closed (<meta ... />)
   * @var boolean
   */
  private $self_close = false;

  /**
   * Class constructor
   *
   * @param string tag type (a, div, etc)
   * @param string tag id (<div id="...">)
   * @param array  tag classes (<div class="... ...">)
   * @param array  tag attributes other than id and class (<a href="..." target="...">)
   */
  function __construct($type, $id = NULL, $classes = NULL, $attributes = NULL) {
    $this->type = strtolower($type);
    if(!is_null($id)) $this->id = $id;
    if(!is_null($classes)) $this->classes = $classes;
    if(!is_null($attributes)) $this->attributes = $attributes;
  }
  
  /**
   * Set the id of this tag
   *
   * @param string id to set
   */
  function set_id($id) {
    $this->id = $id;
  }
  
  /**
   * Add a class name to this tag
   *
   * @param string name of the class to add
   */
  function add_class($class) {
    if(is_null($this->classes)) $this->classes = array($class);
    else array_push($this->classes, $class);
  }
  
  /**
   * Set the attribute list to the given array
   *
   * @param array complete list of attributes to set (will overwrite existing attributes)
   */
  function set_attributes($attributes) {
    $this->attributes = $attributes;
  }
  
  /**
   * Set this tag as self-closing
   */
  function self_close() {
    $this->self_close = true;
  }
  
  /**
   * Set the content of this tag
   *
   * @param string content to set for this tag
   */
  function set_content($content) {
    $this->content = $content;
  }
  
  /**
   * Whether or not this tag is self closed
   *
   * @return boolean
   */
  function is_closed() {
    return ($this->script_with_src() || !is_null($this->content) || $this->self_close);
  }

  /**
   * Is this a "script" tag with a "src" attribute
   *
   * If so this tag will need to be closed despite having no content (because of the HTML
   * quirk that "script" tags cannot be self-closed even if they have a "src" attribute)
   * resulting in tags of the form "<script ... src="..."></script>" rather than
   * "<script ... src="..." />". 
   *
   * @return boolean
   */
  function script_with_src() {
    return ($this->type == 'script' && array_key_exists('src', $this->attributes));
  }
  
  /**
   * Start tag for this tag (self-closed where appropriate)
   *
   * @return string
   */
  function start() {
    $tag = '<' . $this->type;
    if(!is_null($this->id)) $tag .= ' id="' . $this->id . '"';
    if(!is_null($this->classes)) $tag .= ' class="' . join(' ', $this->classes) . '"';
    if(!is_null($this->attributes)) {
      $tag .= "<?php \$tag=''; \$attributes={$this->attributes}; ";
      $tag .= 'foreach($attributes as $attribute => $value) ';
      $tag .= "\$tag .= \" {\$attribute}=\\\"{\$value}\\\"\"; ";
      $tag .= "echo \$tag; ?>";
    }
    if($this->self_close) $tag .= ' /';
    $tag .= '>';
    
    if(!is_null($this->content) || $this->script_with_src()) $tag .= $this->content . $this->end();
    
    return $tag;
  }
  
  /**
   * End tag for this tag (will not always be needed, in the case of self-closed tags
   * for example)
   *
   * @return string
   */
  function end() {
    return '</' . $this->type . '>';
  }

  /**
   * Whether or not to hide content (just don't normally)
   *
   * @return boolean
   */
  public function hide_content() {
    return false;
  }
}