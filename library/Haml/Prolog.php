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
 
 
/**
 * @category   
 * @package    
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Haml_Prolog {
  
  /**
   * Lookup array of different prolog values
   *
   * The right side of a lookup can be another key or a translation (assumed to be a
   * translation if it is not a valid key).  Translations can also sometimes contain
   * replaceable values (such as <ENC> in the 'xml' prolog) that must be replaced with
   * a valid that should be specified after the prolog key (i.e. "!!! XML utf-8").
   * 
   * @var array
   */
  private static $lookup = array(
    'default'          => '1.0 transitional',
    '1.0'              => '1.0 transitional',
    'strict'           => '1.0 strict',
    'frameset'         => '1.0 frameset',
    'transitional'     => '1.0 transitional',
    '1.0 strict'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
    '1.0 frameset'     => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
    '1.0 transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
    '1.1'              => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
    'xml'              => '<?xml version="1.0" encoding="<ENC>" ?>'
  );
  
  /**
   * Content of this prolog (translated value)
   * @var string
   */
  private $content = '';
  
  /**
   * Class constructor
   *
   * Does all the real work for this class.  Takes a string and does the translation/replacement.
   *
   * @param string information following the !!! on a prolog line
   */
  function __construct($str) {
    if(trim($str) == '') $str = 'default';
    
    if(preg_match('/^\s*xml/i', $str)) {
      $encoding = (count(explode(' ', trim($str))) > 1) ? preg_replace('/^\s*xml\s*/i', '', $str) : 'utf-8';
      $this->content = preg_replace('/<ENC>/', $encoding, self::$lookup['xml']);
    }
    else {
      $keys = explode(' ', strtolower(trim($str)));
      sort($keys);
      $key = implode(' ', $keys);
      while(array_key_exists(self::$lookup[$key], self::$lookup)) $key = self::$lookup[$key];
      $this->content = self::$lookup[$key];
    }
  }
  
  /**
   * Returns the translated content
   *
   * @return string
   */
  function content() {
    return '<?php echo "' . addslashes($this->content) . '\n" ?>';
  }
}