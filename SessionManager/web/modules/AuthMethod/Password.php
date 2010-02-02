<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
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

class AuthMethod_Password extends AuthMethod {
	public function get_login() {
		if (! isset($_POST['login']))
			return NULL;

		$this->login = $_POST['login'];
		return $_POST['login'];
	}

	public function authenticate($user_) {
		if ($this->userDB->needPassword() && (! isset($_POST['password']) || $_POST['password'] == ''))
			return false;

		$ret = $this->userDB->authenticate($user_, $_POST['password']);
		if ($ret == false)
			return false;

		//Dirty?
		$_SESSION['password'] = $_POST['password'];

		return true;
	}

	public static function prettyName() {
		return _('Login/Password authentication');
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}

	public static function isDefault() {
		return true;
	}
	
	public static function init($prefs_) {
		return true;
	}

	public static function enable() {
		return true;
	}
}
