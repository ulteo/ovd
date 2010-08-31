<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
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
function do_login() {
	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);

	$mods_enable = $prefs->get('general', 'module_enable');
	if (! in_array('UserDB', $mods_enable))
		die_error('Module UserDB must be enabled',__FILE__,__LINE__);

	$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
	$userDB = new $mod_user_name();

	$auth_methods = $prefs->get('AuthMethod', 'enable');
	if (! is_array($auth_methods)) {
		Logger::error('main', 'No valid AuthMethod enabled', __FILE__, __LINE__);
		return false;
	}

	foreach ($auth_methods as $auth_method) {
		$mod_authmethod_name = 'AuthMethod_'.$auth_method;
		$authmethod = new $mod_authmethod_name($prefs, $userDB);

		$user_login = $authmethod->get_login();
		if (is_null($user_login))
			continue;

		$user = $userDB->import($user_login);
		if (! is_object($user))
			continue;

		$buf = $authmethod->authenticate($user);
		if ($buf === true) {
			$_SESSION['login'] = $user_login;
			return true;
		}
	}

	Logger::error('main', 'Authentication failed', __FILE__, __LINE__);
	return false;
}
