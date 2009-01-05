<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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
mb_internal_encoding('UTF-8');

define('SESSIONMANAGER_ROOT', realpath(dirname(__FILE__).'/..'));
define('SESSIONMANAGER_ROOT_ADMIN', SESSIONMANAGER_ROOT.'/admin');

$buf = ini_get('include_path');
ini_set('include_path', $buf.':'.SESSIONMANAGER_ROOT.'/PEAR');

define('CLASSES_DIR', SESSIONMANAGER_ROOT.'/classes');
define('ADMIN_CLASSES_DIR', SESSIONMANAGER_ROOT.'/admin/classes');
define('MODULES_DIR', SESSIONMANAGER_ROOT.'/modules');
define('ADMIN_MODULES_DIR', SESSIONMANAGER_ROOT.'/admin/modules');
define('PLUGINS_DIR', SESSIONMANAGER_ROOT.'/plugins');

require_once(dirname(__FILE__).'/functions.inc.php');
require_once(dirname(__FILE__).'/load_balancing.inc.php');

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$buf = split('[,;]', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	$buf = $buf[0];
} else
	$buf = 'en_GB';
$language = locale2unix($buf);
setlocale(LC_ALL, $language);
$domain = 'uovdsm';
bindtextdomain($domain, '/usr/share/locale');
textdomain($domain);

require_once(dirname(__FILE__).'/defaults.inc.php');

$desktop_locale = $language;

if (!file_exists(SESSIONMANAGER_CONF_FILE))
	die_error(_('Configuration file missing'),__FILE__,__LINE__);

@include_once(SESSIONMANAGER_CONF_FILE);

$buf = conf_is_valid();
if ($buf !== true) {
	Logger::critical('main', 'Configuration not valid : '.$buf);
	die_error(_('Configuration not valid').' : '.$buf,__FILE__,__LINE__);
}

function __autoload($class_name) {
	$class_files = array();

	if (!class_exists($class_name)) {
		$class_files []= CLASSES_DIR.'/'.$class_name.'.class.php';
		$class_files []= ADMIN_CLASSES_DIR.'/'.$class_name.'.class.php';

		$class_files []= MODULES_DIR.'/'.preg_replace('/_/', '/', $class_name, 1).'.php';
		if (substr($class_name, 0, 6) == 'admin_')
			$class_files []= ADMIN_MODULES_DIR.'/'.preg_replace('/_/', '/', substr($class_name, 6), 1).'.php';

		$class_files []= PLUGINS_DIR.'/'.strtolower(substr($class_name, 7)).'.php';

		if (substr($class_name, 0, 3) == 'FS_')
			$class_files []= PLUGINS_DIR.'/FS/'.preg_replace('/FS_/', '', $class_name, 1).'.php';

		foreach ($class_files as $class_file) {
			if (file_exists($class_file)) {
				require_once($class_file);
				return;
			}
		}

		die_error('Class \''.$class_name.'\' not found',__FILE__,__LINE__);
	}
}

session_start();
