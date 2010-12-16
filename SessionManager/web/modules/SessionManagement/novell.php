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

class SessionManagement_novell extends SessionManagement {
	public function authenticate() {
		$userDB_enabled = $this->prefs->get('UserDB', 'enable');
		if ($userDB_enabled != 'ldap') {
			Logger::error('main', 'SessionManagement_novell::authenticate - UserDB module is not set to use LDAP');
			return false;
		}

		$userDB = UserDB::getInstance();

		$authMethods = $this->prefs->get('AuthMethod', 'enable');
		if (! is_array($authMethods)) {
			Logger::error('main', 'SessionManagement_novell::authenticate - No AuthMethod enabled');
			return false;
		}

		if (! in_array('Password', $authMethods)) {
			Logger::error('main', 'SessionManagement_novell::authenticate - No "Password" AuthMethod enabled');
			return false;
		}

		$authMethod_module = 'AuthMethod_Password';
		$authMethod = new $authMethod_module($this->prefs, $userDB);

		Logger::debug('main', 'SessionManagement_novell::authenticate - Trying "'.$authMethod_module.'"');

		$user_login = $authMethod->get_login();
		if (is_null($user_login)) {
			Logger::debug('main', 'SessionManagement_novell::authenticate - Unable to get a valid login');
			continue;
		}

		$this->user = $userDB->import($user_login);
		if (! is_object($this->user)) {
			Logger::debug('main', 'SessionManagement_novell::authenticate - Unable to import a valid user with login "'.$user_login.'"');
			continue;
		}

		$buf = $authMethod->authenticate($this->user);
		if ($buf === true) {
			Logger::debug('main', 'SessionManagement_novell::authenticate - Now authenticated as "'.$user_login.'"');
			return true;
		}

		Logger::error('main', 'SessionManagement_novell::authenticate - Authentication failed for "'.$user_login.'"');

		$this->user = false;

		return false;
	}

	public function buildServersList($roles_=array(Server::SERVER_ROLE_APS)) {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement_novell::buildServersList - User is not authenticated, aborting');
			throw_response(AUTH_FAILED);
		}

		$this->servers = array(
			Server::SERVER_ROLE_APS	=>	array(),
			Server::SERVER_ROLE_FS	=>	array()
		);

		foreach ($roles_ as $role) {
			switch ($role) {
				case Server::SERVER_ROLE_APS:
					$servers = $this->user->getAvailableServers('windows');
					if (is_null($servers) || count($servers) == 0) {
						$event = new SessionStart(array('user' => $this->user));
						$event->setAttribute('ok', false);
						$event->setAttribute('error', _('No available server'));
						$event->emit();

						Logger::error('main', 'SessionManagement_novell::buildServersList - No "windows" server found for User "'.$this->user->getAttribute('login').'", aborting');
						return false;
					}

					foreach ($servers as $server) {
						$this->servers[Server::SERVER_ROLE_APS][$server->fqdn] = array(
							'status' => Session::SESSION_STATUS_CREATED
						);
					}
					break;
			}
		}

		return true;
	}

	public function generateCredentials($roles_=array(Server::SERVER_ROLE_APS)) {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement_novell::generateCredentials - User is not authenticated, aborting');
			throw_response(AUTH_FAILED);
		}

		$this->credentials = array(
			Server::SERVER_ROLE_APS	=>	array()
		);

		foreach ($roles_ as $role) {
			switch ($role) {
				case Server::SERVER_ROLE_APS:
					$this->credentials[Server::SERVER_ROLE_APS]['login'] = $_POST['login'];
					$this->credentials[Server::SERVER_ROLE_APS]['password'] = $_POST['password'];
					break;
			}
		}

		return true;
	}

	public function appendToSessionCreateXML($dom_) {
		$userDB = UserDB::getInstance();

		$environment_node = $dom_->createElement('environment');
		$environment_node->setAttribute('id', 'Novell');
		$environment_node->setAttribute('server', $userDB->config['host']);
		$environment_node->setAttribute('tree', suffix2domain($userDB->config['suffix']));
		$environment_node->setAttribute('login', $_POST['login']);
		$environment_node->setAttribute('password', $_POST['password']);

		$dom_->documentElement->appendChild($environment_node);

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
		return _('Novell');
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