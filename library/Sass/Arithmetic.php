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
 * @category   Sass
 * @package    Sass
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class Sass_Arithmetic {
  
  // List of valid states for the tokenizer
  const STATE_BEGIN     = 10;  // Start state
  const STATE_COLOR     = 20;  // State in which the current token is a color (starts with #)
  const STATE_STRING    = 30;  // State in which the current token is a string (starts with ")
  const STATE_ESC       = 40;  // Inside a string, escape character seen
  const STATE_CONST     = 50;  // State in which the current token is a constant (starts with !)
  const STATE_VALUE     = 70;  // Any other kind of element (not a color, constant, etc.)
  const STATE_AFTERVAL  = 80;  // State we are in after finding some kind of value
  const STATE_AFTEROPER = 90;  // State we are in after some kind of operator
  const STATE_FINISH    = 100; // State that says we are totally done
  const STATE_NUMBER    = 110; // State where we are inside a numeric constant
  const STATE_CPAREN    = 120; // State where a closing parenthesis has been found
  const STATE_SQSTRING  = 130; // State where we are inside a single-quoted string
  const STATE_DQSTRING  = 140; // State where we are inside a double-quoted string
  const STATE_ESCAPE    = 150; // State where we are in a DQ string and the escape character has been found
  
  
  // List of types of tokens
  const TOKEN_OPAREN   = 0;   // Open parenthesis token
  const TOKEN_CPAREN   = 5;   // Closed parenthesis token
  const TOKEN_COLOR    = 10;  // Color constant token
  const TOKEN_CONCAT   = 20;  // Token representing a concatenation operation
  const TOKEN_SCONCAT  = 25;  // Token representing a concatenation operation (with a space)
  const TOKEN_ADD      = 30;  // Token representing an add operation
  const TOKEN_SUB      = 40;  // Token representing a subtract operation
  const TOKEN_CONST    = 50;  // Token representing a constant (! + identifier)
  const TOKEN_NUMBER   = 60;  // Token representing a numeric constant
  const TOKEN_STRING   = 70;  // Token representing an unquoted string
  
  /**
   * Expression that is being parsed
   * @var string
   */
  private $exp;

  /**
   * Array of available constants
   * @var array
   */
  private $constants;

  /**
   * Parsed expression (stored in case parse is called more than once)
   * @var string
   */
  private $parsed = NULL;

  /**
   * Index where tokenizing left off
   * @var integer
   */
  private $token_index = 0;

  /**
   * Current state of the tokenizer
   * @var integer
   */
  private $state = self::STATE_BEGIN;

  /**
   * Current token
   * @var string
   */
  private $token = '';

  /**
   * Stack of saved contexts (lvalues and operators for when parenthesis are encountered)
   * @var array
   */
  private $context_stack = array();

  /**
   * Current left side value
   * @var array
   */
  private $lvalue = null;

  /**
   * Current operator
   * @var integer
   */
  private $operator = null;

  /**
   * Class constructor
   *
   * Create a new Sass Arithmetic parser
   *
   * @param string expression to parse
   * @param array  map of available constants
   */
  public function __construct($expression, $constants) {
    $this->exp = $expression;
    $this->constants = $constants;
    
    // Define "character classes" (whitespace, identifier, etc)
    $this->ch_ident = array_merge(
      range('a', 'z'),
      range('A', 'Z'),
      range('0', '9'),
      array('_', '-')
    );
    $this->ch_hex = array_merge(range('0', '9'), range('a', 'f'), range('A', 'F'));
    $this->ch_numeric = array_merge(range('0', '9'), array('.'));
  }
  
  /**
   * Parses this expression or returns already parsed version of expression
   *
   * @return string
   */
  public function parse() {
    if(!is_null($this->parsed)) return $this->parsed;
    
    $token = $this->next_token();
    while(!is_null($token)) {
      if($token == self::TOKEN_CONST) $token = $this->resolve();
      
      switch($token) {
        case self::TOKEN_COLOR:
        case self::TOKEN_NUMBER:
        case self::TOKEN_STRING:
          if(is_null($this->lvalue)) $this->lvalue = array('token' => $token, 'value' => $this->token);
          elseif(is_null($this->operator)) throw new Exception('rvalue found with no operator');
          else {
            $this->lvalue = $this->operate($this->lvalue, array('token' => $token, 'value' => $this->token), $this->operator);
            $this->operator = null;
          }
          break;
          
        case self::TOKEN_ADD:
        case self::TOKEN_SUB:
        case self::TOKEN_CONCAT:
        case self::TOKEN_SCONCAT:
          if(is_null($this->lvalue)) throw new Exception('Operator found before lvalue');
          elseif(!is_null($this->operator)) throw new Exception('Second operator found before rvalue');
          else $this->operator = $token;
          break;
          
        case self::TOKEN_OPAREN:
          $this->save_context();
          break;
          
        case self::TOKEN_CPAREN:
          if(!is_null($this->lvalue)) {
            $rvalue = $this->lvalue;
            $this->restore_context();
            if(is_null($this->lvalue) && is_null($this->operator)) $this->lvalue = $rvalue;
            elseif(!is_null($this->lvalue) && !is_null($this->operator))
              $this->lvalue = $this->operate($this->lvalue, $rvalue, $this->operator);
            else throw new Exception('Operator without an lvalue');
            $this->operator = null;
          }
          else $this->restore_context();
          break;
      }
      
      $token = $this->next_token();
    }
    
    if(!is_null($this->operator) || is_null($this->lvalue)) throw new Exception('Invalid expression');
    $this->parsed = $this->lvalue['value'];
    return $this->parsed;
  }
  
  /**
   * (PRIVATE) Saves the current context (lvalue and operator) on the stack and clears those variables
   */
  private function save_context() {
    array_push($this->context_stack, array('lvalue' => $this->lvalue, 'operator' => $this->operator));
    $this->lvalue = null;
    $this->operator = null;
  }
  
  /**
   * (PRIVATE) Restores the last saved context (lvalue and operator) from the stack
   */
  private function restore_context() {
    if(count($this->context_stack) <= 0) throw new Exception('Unbalanced parenthesis');
    $values = array_pop($this->context_stack);
    $this->lvalue = $values['lvalue'];
    $this->operator = $values['operator'];
  }
  
  /**
   * (PRIVATE) Performs an arithmetic operation
   *
   * @param  array   lvalue
   * @param  array   rvalue
   * @param  integer operation
   * @return string
   */
  private function operate($lvalue, $rvalue, $operator) {
    switch($operator) {
      case self::TOKEN_CONCAT:
        return array('token' => self::TOKEN_STRING, 'value' => $lvalue['value'] . $rvalue['value']);
        break;
        
      case self::TOKEN_SCONCAT:
        return array('token' => self::TOKEN_STRING, 'value' => $lvalue['value'] . ' ' . $rvalue['value']);
        break;
        
      case self::TOKEN_ADD:
        if($lvalue['token'] == self::TOKEN_STRING || $rvalue['token'] == self::TOKEN_STRING)
          return $this->operate($lvalue, $rvalue, self::TOKEN_CONCAT);
        elseif($lvalue['token'] == self::TOKEN_COLOR && $rvalue['token'] == self::TOKEN_COLOR)
          return array('token' => self::TOKEN_COLOR, 'value' => $this->add_colors($lvalue['value'], $rvalue['value']));
        elseif($lvalue['token'] == self::TOKEN_NUMBER && $rvalue['token'] == self::TOKEN_NUMBER)
          return array('token' => self::TOKEN_NUMBER, 'value' => $lvalue['value'] + $rvalue['value']);
        throw new Exception('Addition is not valid for these operands');
        
      case self::TOKEN_SUB:
        if($lvalue['token'] == self::TOKEN_COLOR && $rvalue['token'] == self::TOKEN_COLOR)
          return array('token' => self::TOKEN_COLOR, 'value' => $this->subtract_colors($lvalue['value'], $rvalue['value']));
        elseif($lvalue['token'] == self::TOKEN_NUMBER && $rvalue['token'] == self::TOKEN_NUMBER)
          return array('token' => self::TOKEN_NUMBER, 'value' => $lvalue['value'] - $rvalue['value']);
        throw new Exception('Subtraction is not a valid operation for these operands');
    }
  }
  
  /**
   * (PRIVATE) Add two color values together
   *
   * @param  string first color
   * @param  string second color
   * @return string
   */
  private function add_colors($color1, $color2) {
    $color1 = $this->color_split(substr($color1, 1));
    $color2 = $this->color_split(substr($color2, 1));
    
    $red = hexdec($color1['red']) + hexdec($color2['red']);
    $green = hexdec($color1['green']) + hexdec($color2['green']);
    $blue = hexdec($color1['blue']) + hexdec($color2['blue']);
    
    $final_color = ($red > 255) ? 'ff' : dechex($red);
    $final_color .= ($green > 255) ? 'ff' : dechex($green);
    $final_color .= ($blue > 255) ? 'ff' : dechex($blue);
    
    return '#' . $final_color;
  }
  
  /**
   * (PRIVATE) Subtract one color from another
   *
   * @param  string first color
   * @param  string second color
   * @return string
   */
  private function subtract_colors($color1, $color2) {
    $color1 = $this->color_split(substr($color1, 1));
    $color2 = $this->color_split(substr($color2, 1));
    
    $red = hexdec($color1['red']) - hexdec($color2['red']);
    $green = hexdec($color1['green']) - hexdec($color2['green']);
    $blue = hexdec($color1['blue']) - hexdec($color2['blue']);
    
    $final_color = ($red < 0) ? '00' : dechex($red);
    $final_color .= ($green < 0) ? '00' : dechex($green);
    $final_color .= ($blue < 0) ? '00' : dechex($blue);
    
    return '#' . $final_color;
  }
  
  /**
   * (PRIVATE) Splits a color constant into red, green, and blue components
   *
   * @param  string color
   * @return array
   */
  private function color_split($color) {
    $split = array();
    
    if(strlen($color) == 3) {
      $chunks = str_split($color);
      $split['red'] = $chunks[0] . $chunks[0];
      $split['green'] = $chunks[1] . $chunks[1];
      $split['blue'] = $chunks[2] . $chunks[2];
    }
    elseif(strlen($color) == 6) {
      $chunks = str_split($color, 2);
      $split['red'] = $chunks[0];
      $split['green'] = $chunks[1];
      $split['blue'] = $chunks[2];
    }
    else throw new Exception('Invalid length for color constant');
    
    return $split;
  }
  
  /**
   * (PRIVATE) Resolves a constant or throws an exception if resolution is not
   * possible
   *
   * @return string
   */
  private function resolve() {
    $constant = substr($this->token, 1);
    if(array_key_exists($constant, $this->constants)) {
      $this->token = $this->constants[$constant];
      if(preg_match('/^#[0-9a-fA-F]{3,6}$/', $this->token)) return self::TOKEN_COLOR;
      elseif(is_numeric($this->token)) return self::TOKEN_NUMBER;
      else return self::TOKEN_STRING;
    }
    else throw new Exception('Undefined constant reference');
  }
  
  /**
   * (PRIVATE) Returns a string representation of a given token
   *
   * @param  integer Token to be looked up
   * @return string
   */
  private function token_name($token) {
    $class = new ReflectionClass('Sass_Arithmetic');
    foreach($class->getConstants() as $name => $value) {
      if(preg_match('/^TOKEN_/', $name) && $token == $value) return $name;
    }
    return $token;
  }
  
  /**
   * (PRIVATE) Returns the next available token or null if no tokens are available
   *
   * @return string
   */
  public function next_token() {
    $this->token = '';
    
    // If we are in the finished state, return null
    if($this->state == self::STATE_FINISH) return null;
    
    while($this->token_index < strlen($this->exp)) {
      $ch = $this->exp[$this->token_index];
      
      switch($this->state) {
        case self::STATE_BEGIN:
          switch($ch) {
            case ' ':
            case "\t":
              $this->token_index++;
              continue 3;
            case '#':
              $this->state = self::STATE_COLOR;
              break;
            case '!':
              $this->state = self::STATE_CONST;
              break;
            case '(':
              $this->token_index++;
              return self::TOKEN_OPAREN;
            case "'":
              $this->token_index++;
              $this->state = self::STATE_SQSTRING;
              continue 3;
            case '"':
              $this->token_index++;
              $this->state = self::STATE_DQSTRING;
              continue 3;
            default:
              if(is_numeric($ch) || $ch == '.') $this->state = self::STATE_NUMBER;
              else $this->state = self::STATE_STRING;
          }
          break;
        
        case self::STATE_CPAREN:
          $this->token_index++;
          $this->state = self::STATE_AFTERVAL;
          return self::TOKEN_CPAREN;
          
        case self::STATE_COLOR:
          switch($ch) {
            case ' ':
            case "\t":
            case '+':
            case '-':
              $this->state = self::STATE_AFTERVAL;
              return self::TOKEN_COLOR;
            case '(':
              $this->state = self::STATE_BEGIN;
              return self::TOKEN_COLOR;
            case ')':
              $this->state = self::STATE_CPAREN;
              return self::TOKEN_COLOR;
            default:
              if(!in_array($ch, $this->ch_hex)) throw new Exception('Invalid hex character found in color constant');
          }
          break;
          
        case self::STATE_CONST:
          switch($ch) {
            case ' ':
            case "\t":
            case '+':
            case '-':
              $this->state = self::STATE_AFTERVAL;
              return self::TOKEN_CONST;
            case '(':
              $this->state = self::STATE_BEGIN;
              return self::TOKEN_CONST;
            case ')':
              $this->state = self::STATE_CPAREN;
              return self::TOKEN_CONST;
            default:
              if(!in_array($ch, $this->ch_ident)) throw new Exception('Invalid character found in constant name');
          }
          break;
        
        case self::STATE_NUMBER:
          switch($ch) {
            case ' ':
            case "\t":
            case '+':
            case '-':
              $this->state = self::STATE_AFTERVAL;
              return self::TOKEN_NUMBER;
            case '(':
              $this->state = self::STATE_BEGIN;
              return self::TOKEN_NUMBER;
            case ')':
              $this->state = self::STATE_CPAREN;
              return self::TOKEN_NUMBER;
            default:
              if(!is_numeric($ch) && $ch != '.') {
                $this->state = self::STATE_STRING;
              }
          }
          break;
          
        case self::STATE_STRING:
          switch($ch) {
            case ' ':
            case "\t":
            case '+':
            case '-':
              $this->state = self::STATE_AFTERVAL;
              return self::TOKEN_STRING;
            case '(':
              $this->state = self::STATE_BEGIN;
              return self::TOKEN_STRING;
            case ')':
              $this->state = self::STATE_CPAREN;
              return self::TOKEN_STRING;
          }
          break;
          
        case self::STATE_SQSTRING:
          if($ch == "'") {
            $this->token_index++;
            $this->state = self::STATE_AFTERVAL;
            return self::TOKEN_STRING;
          }
          break;
          
        case self::STATE_DQSTRING:
          if($ch == '"') {
            $this->token_index++;
            $this->state = self::STATE_AFTERVAL;
            $this->token = eval('return "' . $this->token . '";');
            return self::TOKEN_STRING;
          }
          elseif($ch == "\\") $this->state = self::STATE_ESCAPE;
          break;
          
        case self::STATE_ESCAPE:
          $this->state = self::STATE_DQSTRING;
          break;
          
        case self::STATE_AFTERVAL:
          switch($ch) {
            case ' ':
            case "\t":
              $this->token_index++;
              continue 3;
            case '#':
            case '!':
            case "'":
            case '"':
              $this->state = self::STATE_AFTEROPER;
              return self::TOKEN_SCONCAT;
            case '(':
              $this->state = self::STATE_BEGIN;
              return self::TOKEN_SCONCAT;
            case ')':
              $this->token_index++;
              return self::TOKEN_CPAREN;
            case '+':
              $this->token_index++;
              $this->state = self::STATE_AFTEROPER;
              return self::TOKEN_ADD;
            case '-':
              $this->token_index++;
              $this->state = self::STATE_AFTEROPER;
              return self::TOKEN_SUB;
            default:
              if(is_numeric($ch) || $ch == '.') {
                $this->state = self::STATE_NUMBER;
                return self::TOKEN_SCONCAT;
              }
              else {
                $this->state = self::STATE_STRING;
                return self::TOKEN_SCONCAT;
              }
          }
          break;
          
        case self::STATE_AFTEROPER:
          switch($ch) {
            case ' ':
            case "\t":
              $this->token_index++;
              continue 3;
            case '#':
              $this->state = self::STATE_COLOR;
              break;
            case '!':
              $this->state = self::STATE_CONST;
              break;
            case "'":
              $this->token_index++;
              $this->state = self::STATE_SQSTRING;
              continue 3;
            case '"':
              $this->token_index++;
              $this->state = self::STATE_DQSTRING;
              continue 3;
            case '(':
              $this->token_index++;
              $this->state = self::STATE_BEGIN;
              return self::TOKEN_OPAREN;
            default:
              if(is_numeric($ch) || $ch == '.') {
                $this->state = self::STATE_NUMBER;
              }
              else $this->state = self::STATE_STRING;
          }
          break;
      }
      
      $this->token .= $ch;
      $this->token_index++;
    }
    
    // Clean up the final token that was being parsed when we ran out of input
    if($this->token != '') {
      $last_token = null;
      switch($this->state) {
        case self::STATE_COLOR:
          $last_token = self::TOKEN_COLOR;
          break;
        case self::STATE_CONST:
          $last_token = self::TOKEN_CONST;
          break;
        case self::STATE_NUMBER:
          $last_token = self::TOKEN_NUMBER;
          break;
        case self::STATE_STRING:
          $last_token = self::TOKEN_STRING;
          break;
        case self::STATE_CPAREN:
          $last_token = self::STATE_CPAREN;
          break;
      }
      if(!is_null($last_token)) {
        $this->state = self::STATE_FINISH;
        return $last_token;
      }
      else throw new Exception('Invalid final token');
    }
    
    // Default return value (we should never reach this point)
    return null;
  }
}