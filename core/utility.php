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
 

/*
 * Function to automatically load classes that are named in "the standard" way
 * (the class in the file called Foo/Bar.php being named Foo_Bar, and the Foo
 * directory residing somewhere in the include_path)
 */
function __autoload($class) {
  /*
   * Look through the include path to see if a file for this class can be found
   */
  $class_path = preg_replace('/_/', DIRECTORY_SEPARATOR, $class) . '.php';
  foreach(explode(PATH_SEPARATOR, get_include_path()) as $path) {
    if(file_exists($path . DIRECTORY_SEPARATOR . $class_path)) {
      require_once($path . DIRECTORY_SEPARATOR . $class_path);
      return;
    }
  }
  
  /*
   * If we have gotten to this point the requested class is nowhere in the include path
   * ...time to look for a few patterns that might make this a "special class"
   */
  if(preg_match('/Model$/', $class)) {
    $model_path = implode(DIRECTORY_SEPARATOR, array(APPLICATION_PATH, 'models', $class . '.php'));
    if(file_exists($model_path)) {
      require_once($model_path);
      return;
    }
  }

  DOMPDF_autoload($class);
}

/*
 * Function to build a path from a variable number of path components
 */
function _path() {
  $args = func_get_args();
  return implode(DIRECTORY_SEPARATOR, $args);
}

/*
 * Function to determine if a variable is "blank"
 */
function _blank($var) {
  return ($var === null || $var === '' || $var === 0);
}
