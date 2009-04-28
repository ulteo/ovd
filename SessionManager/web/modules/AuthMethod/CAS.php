<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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

@include_once('CAS.php');

class AuthMethod_CAS extends AuthMethod {
	public function get_login() {
		$buf = $this->prefs->get('AuthMethod','CAS');
		$CAS_server_url = $buf['user_authenticate_cas_server_url'];
		if (! isset($CAS_server_url) || $CAS_server_url == '')
			return NULL;

		phpCAS::client(CAS_VERSION_2_0, parse_url($CAS_server_url, PHP_URL_HOST), parse_url($CAS_server_url, PHP_URL_PORT), '/cas');
		phpCAS::setNoCasServerValidation();
		phpCAS::forceAuthentication();

		if (! phpCAS::isAuthenticated())
			return NULL;

		$this->login = phpCAS::getUser();
		return $this->login;
	}

	public function authenticate($user_) {
		return true;
	}

	public static function prettyName() {
		return _('CAS authentication');
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		$buf = $prefs_->get('AuthMethod','CAS');
		$CAS_server_url = $buf['user_authenticate_cas_server_url'];
		if (! isset($CAS_server_url) || $CAS_server_url == '')
			return false;

		return true;
	}

	public static function configuration() {
		return array(
			new ConfigElement_input('user_authenticate_cas_server_url', _('CAS server URL'), _('CAS server URL'), _('CAS server URL'), 'http://cas.server.com:1234')
		);
	}
}
