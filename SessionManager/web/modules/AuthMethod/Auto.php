<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
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

class AuthMethod_Auto extends AuthMethod {
	public function get_login() {
		$userDB = UserDB::getInstance();
		if (! is_object($userDB))
			return NULL;
		
		$u = new User();
		$buf = gen_unique_string();
		$u->setAttribute('login', 'u'.$buf);
		$u->setAttribute('password', $u->getAttribute('login'));
		$u->setAttribute('displayname', 'user '.$u->getAttribute('login'));
		if (  $userDB->add($u)) {
			$user = $userDB->import($u->getAttribute('login'));
		}
		else {
			Logger::error('main', 'AuthMethod::Auto::get_login failed to add user '.$u->getAttribute('login'));
			return NULL;
		}
		
		if (! is_object($user)) {
			return NULL;
		}
		
		$this->login = $user->getAttribute('login');
		
		return $this->login;
	}

	public function authenticate($user_) {
		$_SESSION['password'] = $user_->getAttribute('login');

		return true;
	}

	public static function prettyName() {
		return _('Auto authentication');
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}

	public static function isDefault() {
		return false;
	}
	
	public static function init($prefs_) {
		return true;
	}

	public static function enable() {
		return true;
	}
}
 