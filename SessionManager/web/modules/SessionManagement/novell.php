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
	public static function getAuthMethods() {
		return array('Password');
	}

	public static function getServerRoles() {
		return array(Server::SERVER_ROLE_APS);
	}

	public static function getApplicationServerTypes() {
		return array(Server::SERVER_TYPE_WINDOWS);
	}

	public function initialize() {
		$userDB_enabled = $this->prefs->get('UserDB', 'enable');
		if ($userDB_enabled != 'ldap') {
			Logger::error('main', 'SessionManagement_novell::authenticate - UserDB module is not set to use LDAP');
			return false;
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
