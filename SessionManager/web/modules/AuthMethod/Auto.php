<?php
/**
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
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
		
		$prefs = Preferences::getInstance();
		$config = $prefs->get('AuthMethod', 'Auto');
		
		if (array_key_exists('login', $_POST) && array_key_exists('uselogin', $config) && $config['uselogin'] == '1') {
			$this->login = $_POST['login'];
		}
		else {
			$this->login = 'u'.gen_unique_string();
		}
		
		$u = new User();
		$u->setAttribute('login', $this->login);
		$u->setAttribute('password', $u->getAttribute('login'));
		$u->setAttribute('displayname', 'user '.$u->getAttribute('login'));
		if ($userDB->add($u)) {
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
		$mods_enabled = $prefs_->get('general', 'module_enable');
		
		if (in_array('AuthMethod', $mods_enabled) == false) {
			// Auth Module is not enabled
			return false;
		}
		
		if (in_array('UserDB', $mods_enabled) == false) {
			// UserDB Module is not enabled
			return false;
		}
		
		$mod_app_name = 'UserDB_'.$prefs_->get('UserDB', 'enable');
		$userdb = new $mod_app_name();
		return $userdb->isWriteable();
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
	
	public static function configuration() {
		$ret = array();
		$c = new ConfigElement_select('uselogin', _('Use the given login to generate user'), _('Use the given login to generate user.'), _('Use the given login to generate user.'), 0);
		$c->setContentAvailable(array(0=>_('No'), 1=>_('Yes')));
		$ret []= $c;
		return $ret;
	}
}
 