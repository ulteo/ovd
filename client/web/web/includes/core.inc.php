<?php
/**
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
 * Author David LECHEVALIER <david@ulteo.com> 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
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

define('WEB_CLIENT_ROOT', realpath(dirname(__FILE__).'/..'));

$buf = @ini_get('include_path');
@ini_set('include_path', WEB_CLIENT_ROOT.'/PEAR:'.$buf);

define('WEB_CLIENT_CONF_DIR', '/etc/ulteo/webclient');
define('WEB_CLIENT_CONF_FILE', '/etc/ulteo/webclient/config.inc.php');

if (file_exists(WEB_CLIENT_CONF_FILE))
	include_once(WEB_CLIENT_CONF_FILE);

define('LOCALE_DIR', '/usr/share/locale');

require_once(dirname(__FILE__).'/functions.inc.php');

function __autoload($class_name) {
	if (class_exists($class_name))
		return;
	
	$class_files = array();
	
	$class_dir = dirname(dirname(__FILE__)).'/classes';
	$class_file = $class_dir.'/'.$class_name.'.class.php';
	if (! file_exists($class_file))
		die('Class \''.$class_name.'\' not found');
	
	require_once($class_file);
}

@session_start();

if (! array_key_exists('ovd-client', $_SESSION))
	$_SESSION['ovd-client'] = array();

$sessionmanager_url = NULL;
if (defined('SESSIONMANAGER_HOST'))
	$sessionmanager_url = 'https://'.SESSIONMANAGER_HOST.'/ovd/client';
elseif (array_key_exists('sessionmanager_url', $_SESSION['ovd-client']))
	$sessionmanager_url = $_SESSION['ovd-client']['sessionmanager_url'];

$debug_mode = false;
if (defined('DEBUG_MODE') && DEBUG_MODE === true)
	$debug_mode = true;

if (! defined('OPTION_LANGUAGE_DEFAULT'))
	define('OPTION_LANGUAGE_DEFAULT', 'en-us');

if (! defined('OPTION_LANGUAGE_AUTO_DETECT'))
	define('OPTION_LANGUAGE_AUTO_DETECT', true);

if (! defined('OPTION_FORCE_LANGUAGE'))
	define('OPTION_FORCE_LANGUAGE', false);


$user_language = OPTION_LANGUAGE_DEFAULT;
if (OPTION_LANGUAGE_AUTO_DETECT === true) {
	// Autodetect language from browser settings
	$browser_languages = detectBrowserLanguage(get_available_languages());
	if (count($browser_languages) > 0) {
		$user_language = $browser_languages[0];
	}
}

if (! defined('OPTION_KEYMAP_DEFAULT'))
	define('OPTION_KEYMAP_DEFAULT', 'en-us');

if (! defined('OPTION_KEYMAP_AUTO_DETECT'))
	define('OPTION_KEYMAP_AUTO_DETECT', true);

if (! defined('OPTION_FORCE_KEYMAP'))
	define('OPTION_FORCE_KEYMAP', false);

$user_keymap = OPTION_KEYMAP_DEFAULT;

if (! defined('OPTION_CONFIRM_LOGOUT'))
	define('OPTION_CONFIRM_LOGOUT', 'never');
