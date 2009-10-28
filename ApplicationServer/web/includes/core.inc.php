<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 *
 * This program is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/
require_once(dirname(__FILE__).'/session_path.inc.php');

define('APS_ROOT', realpath(dirname(__FILE__).'/../'));

define('MAXTIME', 20);

require_once(dirname(__FILE__).'/functions.inc.php');

function __autoload($class_name) {
	$class_files = array();

	if (!class_exists($class_name)) {
		$class_files []= APS_ROOT.'/classes/'.$class_name.'.class.php';

		foreach ($class_files as $class_file)
			if (file_exists($class_file))
				require_once($class_file);
	}
}

if (! isset($_SESSION))
	session_start();
