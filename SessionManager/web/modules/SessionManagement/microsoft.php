<?php
/**
 * Copyright (C) 2010 Ulteo SAS
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

class SessionManagement_microsoft extends SessionManagement {
	public static function getUserDB() {
		return array('activedirectory');
	}

	public static function getAuthMethods() {
		return array('Password');
	}

	public static function getServerRoles() {
		return array(Server::SERVER_ROLE_APS);
	}

	public static function getApplicationServerTypes() {
		return array(Server::SERVER_TYPE_WINDOWS);
	}

	public function generateApplicationServerCredentials() {
		$this->credentials[Server::SERVER_ROLE_APS]['login'] = $_POST['login'].'@'.$this->userDB->config_ad['domain'];
		$this->credentials[Server::SERVER_ROLE_APS]['password'] = $_POST['password'];

		return true;
	}

	public function appendToSessionCreateXML($dom_) {
		$environment_node = $dom_->createElement('environment');
		$environment_node->setAttribute('id', 'Microsoft');
		$environment_node->setAttribute('domain', $this->userDB->config_ad['domain']);
		$dom_->documentElement->appendChild($environment_node);

		return;
	}

	/* Module methods */
	public static function configuration() {
		return array();
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}

	public static function prettyName() {
		return _('Microsoft');
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
