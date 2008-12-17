<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

if (isset($_POST['do_login']) && isset($_POST['login']) && isset($_POST['password'])) {
	if ($_POST['login'] == '') {
		return_error();
		die(_('There was an error with your authentication'));
	}

	$prefs = Preferences::getInstance();
	if (! $prefs) {
		return_error();
		die(_('get Preferences failed'));
	}

	$mods_enable = $prefs->get('general', 'module_enable');
	if (!in_array('UserDB', $mods_enable)) {
		return_error();
		die(_('Module UserDB must be enabled'));
	}

	$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
	$userDB = new $mod_user_name();
	$user = $userDB->import($_POST['login']);
	if (!is_object($user)) {
		return_error();
		die(_('There was an error with your authentication'));
	}

	$ret = $userDB->authenticate($user, $_POST['password']);

	if ($ret == true) {
		$_SESSION['login'] = $_POST['login'];

		$lock = new Lock($_SESSION['login']);
		if ($lock->have_lock()) {
			$session = new Session($lock->session);

			if (!$session->session_alive()) {
				$default_settings = $prefs->get('general', 'session_settings_defaults');
				$persistent = $default_settings['persistent'];
				if (!$persistent)
					$lock->remove_lock();

				$already_online = 0;
			} else
				$already_online = 1;
		}

		if (isset($already_online) && $already_online == 1) {
			return_error();
			die(_('You already have a session active'));
		}

		//Logger::info('main', 'Login : ('.$row['id'].')'.$row['login']);

		return_ok();
		die(_('You are now logged in'));
	} else
		return_error();

	die(_('There was an error with your authentication'));
}

return_error();
die(_('Unknown error'));
