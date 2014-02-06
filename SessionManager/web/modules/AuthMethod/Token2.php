<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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

class AuthMethod_Token2 extends AuthMethod {
	public function get_login() {
		$buf = $this->prefs->get('AuthMethod','Token2');
		$tokens = $buf['tokens'];
		
		if (! array_key_exists('token', $_REQUEST)) {
			Logger::warning('main', 'Missing parameter : token');
			return NULL;
		}
		
		if (! array_key_exists($_REQUEST['token'], $tokens)) {
			Logger::warning('main', 'Unauthorized token');
			return NULL;
		}
		
		$this->login = $tokens[$_REQUEST['token']];
		return $this->login;
	}

	public function authenticate($user_) {
		return true;
	}

	public static function prettyName() {
		return _('Standalone token authentication');
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}
	
	public static function configuration() {
		return array(
			new ConfigElement_dictionary('tokens',
				array()),
		);
	}
	
	public static function init($prefs_) {
		return true;
	}

	public static function enable() {
		return true;
	}
}
