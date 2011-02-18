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
 
// files to be checked and what is being checked for (r, w, x, or any combination of the three)
$checks = array(
  'log'                     => 'w',
  'application/views/cache' => 'rwx',
  'html/css'                => 'rwx',
  'tmp'                     => 'w'
);

// determine the path prefix to be used in all checks of file permissions
$prefix = implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..')) . DIRECTORY_SEPARATOR;

// perform each requested check
foreach($checks as $file => $permissions) {
  $abs_file = $prefix . $file;
  foreach(str_split($permissions) as $permission) {
    if(!file_exists($abs_file)) throw new Exception("File '{$file}' does not exist.");
    switch($permission) {
      case 'r':
        if(!is_readable($abs_file)) {
          throw new Exception("No read access to required file/directory, {$file}");
        }
        break;
      case 'w':
        if(!is_writable($abs_file)) {
          throw new Exception("No write access to required file/directory, {$file}");
        }
        break;
      case 'x':
        if(!is_executable($abs_file)) {
          throw new Exception("No execute access to required file/directory, {$file}");
        }
        break;
      default:
        throw new Exception("Invalid character, {$permission}, in permission string.");
    }
  }
}