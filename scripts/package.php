#!/usr/bin/php -q
<?php
/**
 * This file is part of the CSI QFrame.
 *
 * The CSI QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
 
echo "\n";

// change to the root directory of the application
chdir(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..');

// setup (generate necessary filenames, etc)
$version = ($_SERVER['argc'] > 1) ? "-{$_SERVER['argv'][1]}" : '';
$profile = ($_SERVER['argc'] > 2) ? "{$_SERVER['argv'][2]}" : null;
$appName = ($profile !== null) ? $profile : 'qframe';
$basename = basename(getcwd());

// copy files 
if(!copyFiles($appName)) {
  cleanup($appName);
  die(formatMessage("== Packaging FAILED (copying files) ", '='));
}

// replace copied files with files from the selected package profile
if(!replace($profile)) {
  cleanup($appName);
  die(formatMessage("== Packaging FAILED (profile replace) ", '='));
}

// package up the remaining files (sans excluded files)
if(!package($appName, $version)) {
  cleanup($appName);
  die(formatMessage("== Packaging FAILED (packaging) ", '='));
}

// clean up after ourselves
cleanup($appName);

// spit out a success message and exit explicitly (in case somebody adds code after this point
// which they should not do)
echo formatMessage("== Packaging SUCCESSFUL ", '=');
exit;



/**
 * Returns a properly formatted string
 *
 * @param  string base string being output
 * @param  string padding string being used
 * @return string
 */
function formatMessage($message, $pad) {
  return str_pad($message, 80, $pad) . "\n";
}

/**
 * Replace core files with files from the selected package profile
 *
 * @param  string package profile to use
 * @param  string current directory (entry call should never include this parameter)
 * @return boolean
 */
function replace($profile, $directory = null) {
  // if this is the initial call
  if($directory === null) {
    // if no profile has been specified no replacement necessary, automatic success!
    if($profile === null) return true;
  
    // output what we are doing
    echo formatMessage('-- performing package profile replacements', ' ');
  
    // check to make sure the specified package profile exists
    if(!file_exists("package/{$profile}") || !is_dir("package/{$profile}")) return false;
    
    // set initial directory
    $directory = "package/{$profile}";
  }
  
  $tmpDirectory = preg_replace('/^package/', 'tmp', $directory);
  foreach(array_diff(scandir($directory), array('.', '..', '.svn')) as $file) {
    if(is_dir("{$directory}/{$file}")) {
      $return = replace($profile, "{$directory}/{$file}");
      if(!$return) return false;
    }
    else {
      exec("cp -P {$directory}/{$file} {$tmpDirectory}/", $output, $return);
      if($return !== 0) return false;
    }
  }
  
  return true;
}

/**
 * Clean up after we are done
 *
 * @param string name of the application that was packaged
 */
function cleanup($appName) {
  echo formatMessage('-- cleaning up', ' ');
  `rm -rf tmp/{$appName}`;
}

/**
 * Copy all files to be packaged to a directory in tmp
 *
 * @param  string name of the application being packaged
 * @return boolean
 */
function copyFiles($appName) {
  if(!file_exists("tmp/{$appName}")) mkdir("tmp/{$appName}");
  else { 
    echo formatMessage('-- clearing staging directory', ' ');
    foreach(scandir("tmp/{$appName}") as $file) {
      if($file !== '.' && $file !== '..') `rm -rf tmp/{$appName}/{$file}`;
    }
  }
  
  $noCopy = array('.', '..', '.exclude', 'tmp', 'package');
  $files = implode(' ', array_diff(scandir('.'), $noCopy));
  echo formatMessage('-- copying files', ' ');
  exec("cp -R {$files} tmp/{$appName}", $output, $return);
  `find tmp/{$appName} -name \".svn\" -exec rm -rf '{}' ';'`;
  return $return === 0;
}

/**
 * Package everything up
 *
 * @param  string name of the current application
 * @return boolean
 */
function package($appName) {
  echo formatMessage('-- creating archive', ' ');
  
  $exclusions = preg_split('/\s+/', file_get_contents('.exclude'));
  foreach($exclusions as $exclusion) {
    $exclusion = trim($exclusion);
    $exclusionFlags[] = "--exclude={$appName}/{$exclusion}";
  }
  $exclusionFlags = implode(' ', $exclusionFlags);
  $filename = "";
  
  // Include COPY_EXTENDED_ATTRIBUTES_DISABLE=true for packaging on Mac OS X
  $command = 'COPY_EXTENDED_ATTRIBUTES_DISABLE=true COPYFILE_DISABLE=true ' .
      "tar {$exclusionFlags} -cjvf {$appName}{$version}.tar.bz2 {$appName}";
  chdir('tmp');
  exec($command, $output, $return);
  $return = 0;
  return $return === 0;
}