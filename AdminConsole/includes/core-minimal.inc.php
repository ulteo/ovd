<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2012
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
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
require_once(dirname(__FILE__).'/defaults.inc.php');
require_once(dirname(__FILE__).'/functions.inc.php');
require_once(dirname(__FILE__).'/misc.inc.php');

if (! defined('SYSTEM_CONF_FILE') or ! file_exists(SYSTEM_CONF_FILE)) {
	die('Configuration file missing');
}

@include_once(SYSTEM_CONF_FILE);

if (! defined('SESSIONMANAGER_HOST')) {
	die('NO Session Manager host defined');
}


define('ADMIN_ROOT', realpath(dirname(dirname(__FILE__))));
define('CLASSES_DIR', ADMIN_ROOT.'/classes');

function __autoload($class_name) { //what about NameSpaces ?
	$class_files = array();
	
	if (!class_exists($class_name)) {
		$class_files []= CLASSES_DIR.'/'.$class_name.'.class.php';
		
		foreach ($class_files as $class_file) {
			if (file_exists($class_file)) {
				require_once($class_file);
				return;
			}
		}
		
		if (isset($autoload_die) && $autoload_die === true)
			die_error('Class \''.$class_name.'\' not found',__FILE__,__LINE__);
	}
}

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$buf = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	$buf = explode(';', $buf[0]);
	
	set_language($buf[0]);
	
}

require_once(dirname(__FILE__).'/template_server.inc.php');

@session_start();
