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
 * @category   Sass
 * @package    Sass
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * Sass_Line
 */
require_once 'Sass/Line.php';


/**
 * Sass_Element
 */
require_once 'Sass/Element.php';


/**
 * Sass_Block
 */
require_once 'Sass/Block.php';


/**
 * Sass_Constant
 */
require_once 'Sass/Constant.php';


/**
 * @category   Sass
 * @package    Sass
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class Sass_Parser {
  
  /**
   * Contents of the SASS stylesheet to be rendered
   * @var string
   */
  private $sass;
  
  /**
   * Rendered SASS (stored in case {@link render} is called more than once)
   * @var string
   */
  private $rendered = NULL;
  
  /**
   * String that is being used to signify a single level of indentation
   * @var string
   */
  private $indent_string = '  ';

  /**
   * Current indentation level
   * @var integer
   */
  private $indent_level;
  
  /**
   * Count of blank lines (sometimes necessary to ensure that blank lines appear
   * where they belong in the output stream)
   * @var integer
   */
  private $blank_lines = 0;

  /**
   * Stack of blocks (that may be future parents of other blocks)
   * @var array
   */
  private $block_stack = array();

  /**
   * Stores the current block
   * @var Haml_Element
   */
  private $current_block = NULL;

  /**
   * Stores the current parent block
   * @var Haml_Element
   */
  private $parent_block = NULL;

  /**
   * Stores the list of constants for this parser instance
   * @var array
   */
  private $constants = array();

  /**
   * Class constructor
   *
   * Create a new SASS parser
   *
   * @param string SASS to be rendered
   */
  public function __construct($sass) {
    $this->sass = $sass;
  }
  
  /**
   * Render SASS returning valid CSS
   *
   * @return string
   */
  public function render() {
    // If this Parser has already been rendered, just return the result
    if(!is_null($this->rendered)) return $rendered;
    
    // Setup
    $this->rendered = '';
    $last_indent_level = 0;
    
    // Loop through each line
    foreach(explode("\n", $this->sass) as $line) {
      
      // Process indentation and parse the line
      $this->indent_level = $this->get_indent_level($line);
      $indent = str_repeat($this->indent_string, $this->indent_level);
      $parsed = Sass_Line::parse($line);
      if($parsed instanceof Sass_Style) $parsed->parse_value($this->constants);
      
      // Process the parsing result
      $this->process_parsed($parsed);
      
      //Clean up
      $last_indent_level = $this->indent_level;
    }
    
    $this->rendered .= $this->current_block->content() . "\n";
    
    // Return the fully rendered content
    return $this->rendered;
  }
  
  /**
   * Processes the return value from the parsing of a line
   *
   * @param  Sass_Element parsed object
   * @return string       the rendered line
   */
  public function process_parsed($parsed) {
    // If a blank line object is returned...
    if($parsed instanceof Sass_Blank) $this->blank_lines++;
    
    // Or a constant is returned
    elseif($parsed instanceof Sass_Constant) $this->add_constant($parsed->name(), $parsed->content());
    
    // Or a block is returned....
    elseif($parsed instanceof Sass_Block) {
      $parsed->indent($this->indent_level, $this->indent_string);
      
      // If there is a currently open block...
      if(!is_null($this->current_block)) {
        $this->rendered .= $this->current_block->content() . "\n";
        
        // Update the new block's parent selector if it has a parent and is at the same level
        // as the last block
        if($parsed->indent_level() == $this->current_block->indent_level() && !is_null($this->parent_block)) {
          $parsed->parent_selector($this->parent_block->selector());
        }
        
        // If the indent level has increased by one, this becomes the new parent block
        elseif($parsed->indent_level() == $this->current_block->indent_level() + 1){
          $this->parent_block = $this->current_block;
          $parsed->parent_selector($this->parent_block->selector());
          array_push($this->block_stack, $this->current_block);
        }
        
        // If the indent level has increased by more than one, something is wrong
        elseif($parsed->indent_level() > $this->current_block->indent_level()){
          throw new Exception('Only one level of indentation can be added at one time');
        }
        
        // If the indent level has decreased pop the appropriate number of blocks from the stack
        elseif($parsed->indent_level() < $this->current_block->indent_level()){
          for($i = $this->current_block->indent_level(); $i >= $parsed->indent_level(); $i--) {
            $this->parent_block = array_pop($this->block_stack);
          }
          if(!is_null($this->parent_block)) $parsed->parent_selector($this->parent_block->selector());
        }
      }
      
      // Clean up
      $this->current_block = $parsed;
      for($i = 0; $i < $this->blank_lines; $i++) $this->rendered .= "\n";
      $this->blank_lines = 0;
    }  
      
    // Otherwise, add this content to the current block
    elseif(!is_null($this->current_block) && !($parsed instanceof Sass_Blank)) {
      for($i = 0; $i < $this->blank_lines; $i++) $this->current_block->add_style($indent);
      $this->blank_lines = 0;
      $this->current_block->add_style($parsed->content());
    }
  }
  
  /**
   * (PRIVATE) Adds a constant to this template
   *
   * @param string Name of the constant to add
   * @param string Value of the constant to add
   */
  private function add_constant($name, $value) {
    $this->constants[$name] = $value;
  }
  
  /**
   * (PRIVATE) Fetches the indent level for a given line
   *
   * @param  string  line to process
   * @return integer
   */
  private function get_indent_level(&$line) {
    $whitespace = self::whitespace($line);
    return preg_match_all('/' . $this->indent_string . '/', $whitespace, $matches);
  }
  
  /**
   * (PRIVATE) Returns leading whitespace for a given string
   *
   * @param  string string to process
   * @return string
   */
  private static function whitespace(&$str) {
    return (preg_match('/^\s+/', $str, $matches) && $str = trim($str)) ? $matches[0] : '';
  }
}