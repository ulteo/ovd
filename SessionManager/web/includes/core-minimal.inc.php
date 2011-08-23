<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2008
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2009
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
if (function_exists('mb_internal_encoding'))
	mb_internal_encoding('UTF-8');

define('SESSIONMANAGER_ROOT', realpath(dirname(__FILE__).'/..'));
define('SESSIONMANAGER_ROOT_ADMIN', SESSIONMANAGER_ROOT.'/admin');

$buf = @ini_get('include_path');
@ini_set('include_path', $buf.':'.SESSIONMANAGER_ROOT.'/PEAR');

define('CLASSES_DIR', SESSIONMANAGER_ROOT.'/classes');
define('ABSTRACT_CLASSES_DIR', SESSIONMANAGER_ROOT.'/classes/abstract');
define('ADMIN_CLASSES_DIR', SESSIONMANAGER_ROOT_ADMIN.'/classes');
define('MODULES_DIR', SESSIONMANAGER_ROOT.'/modules');
define('EVENTS_DIR', SESSIONMANAGER_ROOT.'/events');
define('CALLBACKS_DIR', SESSIONMANAGER_ROOT.'/events/callbacks');

require_once(dirname(__FILE__).'/functions.inc.php');
require_once(dirname(__FILE__).'/load_balancing.inc.php');

require_once(dirname(__FILE__).'/defaults.inc.php');

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$buf = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	$buf = explode(';', $buf[0]);
	$buf = $buf[0];
} else
	$buf = 'en_GB';
$language = locale2unix($buf);
if (! in_admin()) {
	setlocale(LC_ALL, $language.'.UTF-8');
	$domain = 'uovdsmadmin';
	bindtextdomain($domain, LOCALE_DIR);
	textdomain($domain);
}

if (! file_exists(SESSIONMANAGER_CONF_FILE))
	die_error('Configuration file missing',__FILE__,__LINE__);

@include_once(SESSIONMANAGER_CONF_FILE);

$buf = conf_is_valid();
if ($buf !== true) {
	Logger::critical('main', 'Configuration not valid : '.$buf);
	die_error('Configuration not valid : '.$buf,__FILE__,__LINE__);
}

function __autoload($class_name) { //what about NameSpaces ?
	$class_files = array();

	if (!class_exists($class_name)) {
		$class_files []= CLASSES_DIR.'/'.$class_name.'.class.php';
		$class_files []= CLASSES_DIR.'/configelements/'.$class_name.'.class.php';
		$class_files []= CLASSES_DIR.'/events/'.$class_name.'.class.php';
		$class_files []= EVENTS_DIR.'/'.$class_name.'.class.php';
		$class_files []= CLASSES_DIR.'/tasks/'.$class_name.'.class.php';
		$class_files []= MODULES_DIR.'/'.$class_name.'.php';
		$class_files []= ABSTRACT_CLASSES_DIR.'/'.$class_name.'.class.php';
		$class_files []= ABSTRACT_CLASSES_DIR.'/liaison/'.$class_name.'.class.php';
		$class_files []= ADMIN_CLASSES_DIR.'/'.$class_name.'.class.php';

		$class_files []= MODULES_DIR.'/'.preg_replace('/_/', '/', $class_name, 1).'.php';

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

$autoload_die = false;
@session_start();
$autoload_die = true;
