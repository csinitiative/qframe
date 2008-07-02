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

chdir(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..');

$version = ($_SERVER['argc'] > 1) ? "-{$_SERVER['argv'][1]}" : '';
$basename = basename(getcwd());
$exclusions = preg_split('/\s+/', file_get_contents('.exclude'));
foreach($exclusions as $exclusion) {
  $exclusion = trim($exclusion);
  $exclusion_flags[] = "--exclude={$basename}/{$exclusion}";
}
$exclusion_flags = implode(' ', $exclusion_flags);
// Include COPY_EXTENDED_ATTRIBUTES_DISABLE=true for packaging on Mac OS X
$command = 'COPY_EXTENDED_ATTRIBUTES_DISABLE=true COPYFILE_DISABLE=true ' .
    "tar {$exclusion_flags} -cjvf {$basename}/tmp/qframe{$version}.tar.bz2 {$basename}";

chdir('..');
system($command, $return);
if($return != 0) echo "\n------------------------------\nPackaging failed!\n";
else echo "\n------------------------------\nPackaging was successful!\n";