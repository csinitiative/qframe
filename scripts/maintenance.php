#!/usr/bin/php -q
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
 * Command-line arguments
 */
if($_SERVER['argc'] < 3)
  die("Usage: maintenance.php <on | off> <environment> [<message>]\n\n");

$state = $_SERVER['argv'][1];
define('QFRAME_ENV', $_SERVER['argv'][2]);
$comment = isset($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : null;

if($state !== 'on' && $state !== 'off')
  die("You must specify 'on' or 'off' as the first argument\n\n");
  

/*
 * Include a few very basic utility functions
 */
include(dirname(__FILE__) . '/../core/load.php');


/*
 * Modify maintenance configuration
 */
$config = QFrame_Maintenance::instance();
$config->maintenance = ($state === 'on');
if(isset($comment)) {
  $config->comment = $comment;
}
$config->save();
