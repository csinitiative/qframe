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

// Check that the script is being run as root
if(trim(`whoami`) != 'root') {
  echo "This script must be run as root\n";
  exit;
}

// Use command line argument as user:group if provided
if($argc > 1) {
  $user_group = explode(':', $argv[1]);
  if(count($user_group) > 1) {
    $root_group_id = $user_group[1];
  }
  if($user_group[0] != '') {
    $web_server_user = $user_group[0];
  }
} 

// Determine the user that the web server is running as (if not provided on the command line)
if(!isset($web_server_user)) {
  $apache_config_files = array(
    '/etc/httpd/conf/httpd.conf',
    '/etc/apache2/httpd.conf',
    '/usr/local/apache2/conf/httpd.conf',
    '/etc/apache2/apache2.conf'
  );
  foreach($apache_config_files as $file) {
    if(file_exists($file)) {
      if(preg_match('/^\s*User\s+(\S+)/m', file_get_contents($file), $matches)) {
        $web_server_user = $matches[1];
      }
    }
  }
  if(!isset($web_server_user)) {
    echo "Could not determine the user that Apache runs as.  Try specifying the apache user " .
         "on the command line as <apache_user>[:<root_group>].\n";
    exit;
  }
}

// Determine what primary group the root user belongs to (if not provided on the command line)
if(!isset($root_group_id)) {
  $passwd = file_get_contents('/etc/passwd');
  $group = file_get_contents('/etc/group');
  if(preg_match('/^root:.+?:\d+?:(\d+?)/m', $passwd, $matches)) {
    $root_group_id = $matches[1];
  }
  elseif(preg_match('/^root:.+?:\d+?:(\d+?)/m', $passwd, $matches)) {
    $root_group_id = $matches[1];
  }
  if(!isset($root_group_id)) {
    echo "Could not determine what group the root user belongs to.  Try specifying the root " .
         "group on the command line as [<apache_user>]:<root_group>.\n";
    exit;
  }
}

/*
 * Determine whether or not SELinux is enabled and enforcing and run a couple of commands
 * if it is
 */
if(file_exists('/selinux/enforce')) {
  $qframe_path = realpath(dirname(__FILE__) . '/..');

  $enforcing = file_get_contents('/selinux/enforce');
  $found = 1;

  if(file_exists('/sbin/chcon')) $chcon = '/sbin/chcon';
  elseif(file_exists('/usr/sbin/chcon')) $chcon = '/usr/sbin/chcon';
  elseif(file_exists('/bin/chcon')) $chcon = '/bin/chcon';
  elseif(file_exists('/usr/bin/chcon')) $chcon = '/usr/bin/chcon';
  elseif(!$enforcing) {
    echo "Warning: SELinux context attributes were not updated because the chcon command was not found.  You may ignore this since SELinux is not in enforce mode.\n";
    $found = 0;
  }
  else {
    echo "SELinux is enforcing but location of the chcon binary could not be determined\n";
    exit;
  }
  
  if(file_exists('/sbin/setsebool')) $setsebool = '/sbin/setsebool';
  elseif(file_exists('/usr/sbin/setsebool')) $setsebool = '/usr/sbin/setsebool';
  elseif(file_exists('/bin/setsebool')) $setsebool = '/bin/setsebool';
  elseif(file_exists('/usr/bin/setsebool')) $setsebool = '/usr/bin/setsebool';
  elseif($found && !$enforcing) {
    echo "Warning: SELinux httpd policies were not updated because the setsebool command was not found.  You may ignore this since SELinux is not in enforce mode.\n";
    $found = 0;
  }
  else {
    echo "SELinux is enforcing but location of the setsebool binary could not be determined\n";
    exit;
  }
  
  if ($found) {
    `{$chcon} -R -u system_u -r object_r -t httpd_sys_content_t {$qframe_path}`;
    `{$setsebool} -P httpd_can_network_connect=1`;
  }
}

// Set up the list of directories and the permissions that each will have
$permissions = array(
  'log' => array(
    'own' => "{$web_server_user}:",
    'mod' => "0775"
  ),
  'application/views/cache' => array(
    'own' => "{$web_server_user}:",
    'mod' => "0775"
  ),
  'html/css' => array(
    'own'       => "{$web_server_user}:",
    'mod'       => "0775",
    'recurse'   => false,
    'contents'  => true 
  ),
  'tmp' => array(
    'own' => "{$web_server_user}:",
    'mod' => "0700"
  )
);

// Change directory to the base directory of the application
chdir(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..');

// Establish baseline ownership and permissions
echo "Setting baseline permissions (this may take a while)...\n";

// Set ownership of all files to root:root at first
`chown -R root:{$root_group_id} .`;
// Set directories to 755
`find . -type d -exec chmod 755 '{}' ';'`;
// Set non-directories to 644
`find . ! -type d -exec chmod 644 '{}' ';'`;
// Set anything in the scripts directory to 755
`find scripts -exec chmod 755 '{}' ';'`;

// Go through all of the directories in the permissions array applying the requested permissions
foreach($permissions as $directory => $settings) {
  //If file or directory doesn't exist, then create it doesn't exist
  if(!file_exists($directory)) {
    echo "File or directory '{$directory}' does not exist, attempting to create.\n";
    $rv = mkdir($directory);

    if(!$rv){
      echo "Cannot create '{$directory}'.\n";
    }
  }

  // Print a message to let the user know what directory is being changed
  echo "Processing settings for '{$directory}'...\n";
  
  // Change file mode if that change is requested by settings
  if(isset($settings['mod'])) {
    // Set up any necessary flags
    $flags = '';
    if(!isset($settings['recurse']) || $settings['recurse']) $flags .= '-R';

    // Done using backticks because PHP wrapper "chmod()" does not support recursive mode
    // changes
    `chmod {$flags} {$settings['mod']} {$directory}`;
    
    // If the "contents" option is set, also set the mode on the contents
    if(isset($settings['contents']) && $settings['contents']) {
      `find {$directory} -maxdepth 1 ! -type d -exec chmod {$settings['mod']} '{}' ';'`;
    }

    echo "  changing file mode to {$settings['mod']}\n";
  }

  // Change file permissions if that change is requested by settings
  if(isset($settings['own'])) {
    // Set up any necessary flags
    $flags = '';
    if(!isset($settings['recurse']) || $settings['recurse']) $flags .= '-R';
    
    // Done using backticks because of a peculiarity in Mac OS where the user www resolves
    // to the user _www when using the native chmod but not when using the PHP function
    `chown {$flags} {$settings['own']} {$directory}`;
    
    // If the "contents" option is set, also set the ownership on the contents
    if(isset($settings['contents']) && $settings['contents']) {
      `find {$directory} -maxdepth 1 ! -type d -exec chown {$settings['own']} '{}' ';'`;
    }
    
    echo "  changing file ownership to {$settings['own']}\n";
  }
}

// Print a success message
echo "\n------------------------------\nPermissions updated successfully\n";
