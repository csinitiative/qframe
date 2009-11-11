<?php
/**
 * This file is part of the CSI SIG.
 *
 * The CSI SIG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI SIG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/** Haml_Line */
require_once 'Haml/Line.php';


/** Haml_Element */
require_once 'Haml/Element.php';


/** Haml_Prolog */
require_once 'Haml/Prolog.php';


/**
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Haml_Parser {
  /**
   * String representing one indentation level
   * @var string
   */
  private $indent_string = NULL;

  /**
   * Current indent level
   * @var integer
   */
  private $indent_level = 0;

  /**
   * Original template (in Haml form)
   * @var string
   */
  private $template = NULL;

  /**
   * Character set to use
   * @var string
   */
  private $charset = 'UTF-8';

  /**
   * Stack of unclosed tags (push on > indent, pop on < indent)
   * @var array
   */
  private $tag_stack = array();

  /**
   * Number of blank lines since the last non-blank line
   * @var integer
   */
  private $blank_lines = 0;

  /**
   * If not null, the indent level at which output is no longer hidden
   * @var integer
   */
  private $hide_until = null;

  /**
   * Class constructor
   *
   * Simply accepts a template and assigns it to the appropriate member
   *
   * @param string Haml template to be parsed
   * @param string (optional) character set to use (defaults to UTF-8)
   */
  function __construct($template, $charset = 'UTF-8') {
    $this->template = $template;
    $this->charset = $charset;
  }

  /**
   * Renders a template
   *
   * Renders a template by breaking the template up by newline and using {@link render_line}
   * to render each line.  Does scan ahead to the next line (where a next line exists) so it
   * can tell render_line whether this line has children.
   *
   * @return string
   */
  function render() {
    $output = '';
    $lines = explode("\n", $this->template);
    foreach($lines as $index => $line) {
      $next_level = ($index < count($lines) - 1) ? $this->get_indent_level($lines[$index + 1]) : 0;
      $rendered = $this->render_line($line, $next_level);
      if(!is_null($rendered)) $output .= $rendered . "\n";
    }
    for($i = count($this->tag_stack) - 1; $i >= 0; $i--) {
      $tag = array_pop($this->tag_stack);
	    $output .= str_repeat($this->indent_string, $i) . $tag->end() . "\n";
    }

    /*
     * Remove closing ?> and opening <?php when they are adjoined by only whitespace
     */
    $output = preg_replace('/\?>\s*<\?php/', '', $output);

    return $output;
  }

  /**
   * (PRIVATE) Render a line (sans white space)
   *
   * Processes white space and renders a single line using {@link Line::parse}.  Pushes and pops
   * to/from the tag stack where necessary.
   *
   * @param   string  the line (in Haml) to be rendered
   * @param   integer the level of the next line
   * @return  string
   * @see     Haml_Line
   */
  private function render_line($line, $next_level) {
    $current_indent = $this->get_indent_level($line);
    if($this->hide_until !== null && $current_indent > $this->hide_until) return null;
    elseif($this->hide_until !== null) $this->hide_until = null;


    if(trim($line) == '') {
      $this->blank_lines++;
      return null;
    }

  	$content = $this->indent($line) . str_repeat($this->indent_string, $this->indent_level);
  	$parsed = Haml_Line::parse($line, $current_indent < $next_level, $this->charset);
    if($parsed instanceof Haml_Element) {
      if($parsed->hide_content()) {
        $this->hide_until = $current_indent;
        return null;
      }
      $content .= $parsed->start();
      if(!$parsed->is_closed()) array_push($this->tag_stack, $parsed);
    }
    else if($parsed instanceof Haml_Prolog) $content .= $parsed->content();
    else $content .= $parsed;
  	return $content;
  }

  /**
   * (PRIVATE) Determine the indentation for the given line using
   *
   * Also, if this is the first indented line, sets the indention string for the template
   * and removes leading whitespace from the line.
   *
   * @param   string line to process
   * @return  string
   * @see     #get_indent_level
   */
  private function indent(&$line) {
    $content = '';
  	$current_indent_level = $this->get_indent_level($line);
  	if($current_indent_level < $this->indent_level) {
  	  for($i = count($this->tag_stack) - 1; $i >= $current_indent_level; $i--) {
  	    $tag = array_pop($this->tag_stack);
  	    $content .= str_repeat($this->indent_string, $i) . $tag->end() . "\n";
	    }
    }
    for($i = 0; $i < $this->blank_lines; $i++) $content .= "\n";
    $this->blank_lines = 0;
  	$this->indent_level = $current_indent_level;
  	$line = trim($line);
  	return $content;
  }

  /**
   * (PRIVATE) Fetches the indent level for a given line
   *
   * @param  string  line to process
   * @return integer
   */
  private function get_indent_level($line) {
    if(is_null($this->indent_string) && preg_match('/^\s+/', $line, $matches)) {
      $this->indent_string = $matches[0];
      return 1;
    }
    else if(!is_null($this->indent_string)) {
      $whitespace = self::whitespace($line);
      return preg_match_all('/' . $this->indent_string . '/', $whitespace, $matches);
    }
    else return 0;
  }

  /**
   * (PRIVATE) Returns leading whitespace for a given string
   *
   * @param  string string to process
   * @return string
   */
  private static function whitespace($str) {
    return (preg_match('/^\s+/', $str, $matches)) ? $matches[0] : '';
  }
}