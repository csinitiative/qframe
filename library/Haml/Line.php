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


/** Haml_Prolog */
require_once 'Haml/Prolog.php';


/** Haml_Comment */
require_once 'Haml/Comment.php';


/** Haml_Tag */
require_once 'Haml/Tag.php';


/** Haml_Block */
require_once 'Haml/Block.php';


/**
 * @category   Haml
 * @package    Haml
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class Haml_Line {
  const STATE_BEGIN = 0;
  const STATE_END = 5;
  const STATE_TAG = 10;
  const STATE_ID = 15;
  const STATE_CLASS = 20;
  const STATE_ATTRS = 25;
  const STATE_SQS = 30;
  const STATE_DQS = 35;
  const STATE_ESC = 40;
  const STATE_CONTENT = 45;
  const STATE_UNESCAPED = 47;
  const STATE_AFTERTAG = 50;
  const STATE_CODE = 55;

  /**
   * List of symbols with simple state transitions
   * @var array
   */
  private static $symbols = array(
    '#'  => self::STATE_ID,
    '.'  => self::STATE_CLASS,
    '{'  => self::STATE_ATTRS,
    ' '  => self::STATE_CONTENT,
    '='  => self::STATE_CODE,
    "\n" => self::STATE_END
  );

  /**
   * Character by character line-parser
   *
   * This parser is effectively a "simple" state machine beginning at STATE_BEGIN and
   * finishing in either STATE_AFTERTAG (for self-closed tags or tags whose content
   * is indented on subsequent lines) or STATE_CONTENT (for tags whose content is
   * contained on a line with the tag)
   *
   * For a detailed guide to Haml syntax visit
   * {@link http://haml.hamptoncatlin.com/docs/haml}.  For a list of Haml features
   * that are not implemented by this parser check the README file included in this
   * distribution.
   *
   * @param   string  the string being parsed
   * @param   boolean species whether this line has children (lines with a greater
   * indentation level and immediately following this line)
   * @param   string  (optional) character set to use when parsing this line
   * @return  Haml_Element
   */
  static function parse($str, $has_children, $charset = 'UTF-8') {
    $token = '';
    $tag = NULL;
    $wrap_content = false;
    $escape_content = true;
    $echo_content = false;
    $hide_comment = false;
    $comment = false;

    if($str == '') return '';

    if(substr($str, 0, 3) == '!!!') return new Haml_Prolog(trim(substr($str, 3)));
    if(trim($str) == '/') return new Haml_Comment();

    $state = self::STATE_BEGIN;
    $str .= "\n";
    for($i = 0; $i < strlen($str); $i++) {
      $ch = $str[$i];
      switch($state) {

      /* The beginning state (mostly recognizes special characters that are valid line-leaders) */
      case self::STATE_BEGIN:
        if($ch == '%') $state = self::STATE_TAG;
        else if($ch == '#') {
          $tag = new Haml_Tag('div');
          $state = self::STATE_ID;
        }
        else if($ch == '.') {
          $tag = new Haml_Tag('div');
          $state = self::STATE_CLASS;
        }
        else if($ch == '=') {
          $wrap_content = true;
          $echo_content = true;
          $state = self::STATE_CONTENT;
        }
        else if($ch == '-') {
          $wrap_content = true;
          $state = self::STATE_CONTENT;
        }
        else if($ch == '/') {
          $comment = true;
          $state = self::STATE_CONTENT;
        }
        else if($ch == '!') {
          $state = self::STATE_UNESCAPED;
        }
        else if($ch == "\\") $state = self::STATE_CONTENT;
        else {
          $state = self::STATE_CONTENT;
          $token .= $ch;
        }
        break;

      /* State where the first character we saw was a !, need to see if the next character is = */
      case self::STATE_UNESCAPED:
        if($ch == '=') {
          $wrap_content = true;
          $escape_content = false;
          $echo_content = true;
        }
        else {
          $state = self::STATE_CONTENT;
        }
        break;

      /* State where we are inside of the actual tag name ('%' as a line-leader) */
      case self::STATE_TAG:
        if(array_key_exists($ch, self::$symbols)) {
          $tag = new Haml_Tag($token);
          $token = '';
          $state = self::$symbols[$ch];
        }
        elseif($ch == '/') {
          $tag = new Haml_Tag($token);
          $tag->self_close();
          return $tag;
        }
        else $token .= $ch;
        break;

      /* State where we are inside of an ID (line began with a '#' or a '#' was found
       * while in STATE_TAG or STATE_CLASS)
       */
      case self::STATE_ID:
        if(array_key_exists($ch, self::$symbols)) {
          $tag->set_id($token);
          $token = '';
          $state = self::$symbols[$ch];
        }
        else $token .= $ch;
        break;

      /* See above comment replacing 'ID' with 'Class' */
      case self::STATE_CLASS:
        if(array_key_exists($ch, self::$symbols)) {
          $tag->add_class($token);
          $token = '';
          $state = self::$symbols[$ch];
        }
        else $token .= $ch;
        break;

      /* Line began with an '=', hence the line contains code that must be wrapped/echoed */
      case self::STATE_CODE:
        if($ch == ' ') {
          $token .= $ch;
          $wrap_content = true;
          $echo_content = true;
          $state = self::STATE_CONTENT;
        }
        else throw new Exception("Invalid tag string '" . $line . "'");
        break;

      /* Double-quoted string found in attribute list (in other words, don't break out of
       * the attribute list of a } and recognize the escape character)
       */
      case self::STATE_DQS:
        if($ch == '"') $state = self::STATE_ATTRS;
        else if($ch == "\\") $state = self::STATE_ESC;
        $token .= $ch;
        break;

      /* Basically a place holder state to give a free ride to the character following
       * the escape character while inside a double-quoted string
       */
      case self::STATE_ESC:
        $state = self::STATE_DQS;
        $token .= $ch;
        break;

      /* Single-quoted string found in attribute list (see above minus the bit about escape
       * characters)
       */
      case self::STATE_SQS:
        if($ch == "'") $state = self::STATE_ATTRS;
        $token .= $ch;
        break;

      /* This state occurs as an attribute list is ending or just after a tag without an
       * attribute list.  It basically serves to look for the few things that can occur
       * in this situation (a space for normal content, / for a self-closing tag, or =
       * for code on one line).
       */
      case self::STATE_AFTERTAG:
        if($ch == '=') {
          $wrap_content = true;
          $echo_content = true;
          $state = self::STATE_CONTENT;
        }
        elseif($ch == ' ') $state = self::STATE_CONTENT;
        elseif($ch == '/') {
          $tag->self_close();
          return $tag;
        }
        break;

      /* Essentially a catch-all state for when the real work is done.  Just collects
       * characters until a newline is found at which time it wraps (or doesn't wrap)
       * content and either adds content to the tag that has been created for this line
       * and passes the tag back or just passes the content back if this line does not
       * contain a tag
       */
      case self::STATE_CONTENT:
        if($wrap_content && !$echo_content && $ch === '#') return new Haml_Comment(true);
        if($ch == "\n") {
          if($wrap_content) {
            if($echo_content && $escape_content) {
              $token = "htmlentities({$token}, ENT_COMPAT, \"{$charset}\")";
            }
            if($echo_content) $token = ' echo ' . $token . " . \"\\n\";";
            else if($has_children && is_null($tag)) $tag = new Haml_Block(trim($token));
            else $token .= ';';
            $token = '<?php' . $token . ' ?>';
          }
          elseif($comment) $token = '<!--' . $token . ' -->';
          if(is_null($tag)) return $token;
          if($token != '' && !($tag instanceof Haml_Block)) $tag->set_content($token);
          return $tag;
        }
        else $token .= $ch;
        break;

      /* Inside an attribute list and *not* inside a string */
      case self::STATE_ATTRS:
        if($ch == '}') {
          $tag->set_attributes("array({$token})");
          $token = '';
          $state = self::STATE_AFTERTAG;
          break;
        }
        else if($ch == "'") $state = self::STATE_SQS;
        else if($ch == '"') $state = self::STATE_DQS;

      /* Catch-all */
      default:
        $token .= $ch;
      }
    }

    /* Should never get here but a good idea to have in case an editor is used that
     * does any analysis fo the code and determines that this function is not
     * guaranteed to return
     */
    return $tag;
  }
}