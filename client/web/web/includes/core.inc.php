<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
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

define('WEB_CLIENT_ROOT', realpath(dirname(__FILE__).'/..'));

$buf = @ini_get('include_path');
@ini_set('include_path', $buf.':'.WEB_CLIENT_ROOT.'/PEAR');

define('WEB_CLIENT_CONF_DIR', '/etc/ulteo/webclient');
define('WEB_CLIENT_CONF_FILE', '/etc/ulteo/webclient/config.inc.php');

if (file_exists(WEB_CLIENT_CONF_FILE))
	include_once(WEB_CLIENT_CONF_FILE);

define('LOCALE_DIR', '/usr/share/locale');

require_once(dirname(__FILE__).'/functions.inc.php');

@session_start();

if (! array_key_exists('ovd-client', $_SESSION))
	$_SESSION['ovd-client'] = array();

$sessionmanager_url = NULL;
if (defined('SESSIONMANAGER_HOST'))
	$sessionmanager_url = 'https://'.SESSIONMANAGER_HOST.'/ovd/client';
elseif (array_key_exists('sessionmanager_url', $_SESSION['ovd-client']))
	$sessionmanager_url = $_SESSION['ovd-client']['sessionmanager_url'];

$debug_mode = false;
if (defined('DEBUG_MODE') && DEBUG_MODE == 1)
	$debug_mode = true;

$user_language = 'en-us';
if (defined('OPTION_LANGUAGE_DEFAULT'))
	$user_language = OPTION_LANGUAGE_DEFAULT;

if (! defined('OPTION_LANGUAGE_AUTO_DETECT'))
	define('OPTION_LANGUAGE_AUTO_DETECT', true);

if (! defined('OPTION_FORCE_LANGUAGE'))
	define('OPTION_FORCE_LANGUAGE', false);


if (OPTION_LANGUAGE_AUTO_DETECT === true) {
	// Autodetect language from browser settings
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$buf = explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
		$buf = explode(';', $buf[0]);
		$user_language = strtolower(str_replace('_', '-', $buf[0]));
	}
}

$user_keymap = 'en-us';
if (defined('OPTION_KEYMAP_DEFAULT'))
	$user_keymap = OPTION_KEYMAP_DEFAULT;

if (! defined('OPTION_KEYMAP_AUTO_DETECT'))
	define('OPTION_KEYMAP_AUTO_DETECT', true);

if (! defined('OPTION_FORCE_KEYMAP'))
	define('OPTION_FORCE_KEYMAP', false);
