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
 
$core_path = dirname(__FILE__);

/*
 * Include a few very basic utility functions
 */
include($core_path . DIRECTORY_SEPARATOR . 'utility.php');

/*
 * Deal with the fact that magic_quotes_gpc might be turned on (though)
 * it would be better to just turn it off
 */
include(_path($core_path, 'magicquotes.php'));

/*
 * Set up a bunch of path constants that the application will use to refer
 * to various application directories
 */
include(_path($core_path, 'paths.php'));

/*
 * Deal with environment stuff including determining the current environment
 * and loading the configuration stuff for that environment
 */
include(_path($core_path, 'env.php'));

/*
 * Set up any dynamic properties (properties that rely on current environment configuration)
 */
include(_path($core_path, 'dynamic.php'));
