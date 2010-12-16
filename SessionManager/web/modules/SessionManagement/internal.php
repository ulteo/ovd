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

class SessionManagement_internal extends SessionManagement {
	public function authenticate() {
		$userDB = UserDB::getInstance();

		$authMethods = $this->prefs->get('AuthMethod', 'enable');
		if (! is_array($authMethods)) {
			Logger::error('main', 'SessionManagement_internal::authenticate - No AuthMethod enabled');
			return false;
		}

		foreach ($authMethods as $authMethod) {
			$authMethod_module = 'AuthMethod_'.$authMethod;
			$authMethod = new $authMethod_module($this->prefs, $userDB);

			Logger::debug('main', 'SessionManagement_internal::authenticate - Trying "'.$authMethod_module.'"');

			$user_login = $authMethod->get_login();
			if (is_null($user_login)) {
				Logger::debug('main', 'SessionManagement_internal::authenticate - Unable to get a valid login, switching to next AuthMethod');
				continue;
			}

			$this->user = $userDB->import($user_login);
			if (! is_object($this->user)) {
				Logger::debug('main', 'SessionManagement_internal::authenticate - Unable to import a valid user with login "'.$user_login.'", switching to next AuthMethod');
				continue;
			}

			$buf = $authMethod->authenticate($this->user);
			if ($buf === true) {
				Logger::debug('main', 'SessionManagement_internal::authenticate - Now authenticated as "'.$user_login.'"');
				return true;
			}

			Logger::error('main', 'SessionManagement_internal::authenticate - Authentication failed for "'.$user_login.'", switching to next AuthMethod');
		}

		Logger::error('main', 'SessionManagement_internal::authenticate - Authentication failed');

		$this->user = false;

		return false;
	}

	public function generateCredentials($roles_=array(Server::SERVER_ROLE_APS, Server::SERVER_ROLE_FS)) {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement_internal::generateCredentials - User is not authenticated, aborting');
			throw_response(AUTH_FAILED);
		}

		$this->credentials = array(
			Server::SERVER_ROLE_APS	=>	array(),
			Server::SERVER_ROLE_FS	=>	array()
		);

		foreach ($roles_ as $role) {
			switch ($role) {
				case Server::SERVER_ROLE_APS:
					$this->credentials[Server::SERVER_ROLE_APS]['login'] = 'u'.time().gen_string(5).'_APS'; //hardcoded
					$this->credentials[Server::SERVER_ROLE_APS]['password'] = gen_string(3, 'abcdefghijklmnopqrstuvwxyz').gen_string(2, '0123456789').gen_string(3, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
					break;
				case Server::SERVER_ROLE_FS:
					$this->credentials[Server::SERVER_ROLE_FS]['login'] = 'u'.time().gen_string(6).'_FS'; //hardcoded
					$this->credentials[Server::SERVER_ROLE_FS]['password'] = gen_string(3, 'abcdefghijklmnopqrstuvwxyz').gen_string(2, '0123456789').gen_string(3, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
					break;
			}
		}

		return true;
	}

	public function appendToSessionCreateXML($dom_) {
		return;
	}

	/* Module methods */
	public static function configuration() {
		return array();
	}

	public static function prefsIsValid($prefs_) {
		return true;
	}

	public static function prettyName() {
		return _('Internal');
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
