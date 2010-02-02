<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
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

@include_once('CAS.php');

class AuthMethod_CAS extends AuthMethod {
	public function get_login() {
		Logger::debug('main', 'AuthMethod_CAS::get_login()');

		if (! isset($_SESSION['backup_sso']) || ! is_array($_SESSION['backup_sso']))
			$_SESSION['backup_sso'] = array();

		foreach ($_REQUEST as $k => $v)
			$_SESSION['backup_sso'][$k] = $v;

		$buf = $this->prefs->get('AuthMethod','CAS');
		$CAS_server_url = $buf['user_authenticate_cas_server_url'];
		if (! isset($CAS_server_url) || $CAS_server_url == '') {
			Logger::error('main', 'AuthMethod_CAS::get_login() - Unable to find CAS server url in Preferences');
			return NULL;
		}

		phpCAS::client(CAS_VERSION_2_0, parse_url($CAS_server_url, PHP_URL_HOST), parse_url($CAS_server_url, PHP_URL_PORT), parse_url($CAS_server_url, PHP_URL_PATH));
		Logger::debug('main', 'AuthMethod_CAS::get_login() - Parsing URL - Host:"'.parse_url($CAS_server_url, PHP_URL_HOST).'" Port:"'.parse_url($CAS_server_url, PHP_URL_PORT).'" Path:"'.parse_url($CAS_server_url, PHP_URL_PATH).'"');
		phpCAS::setNoCasServerValidation();

		if (! phpCAS::forceAuthentication()) {
			Logger::error('main', 'AuthMethod_CAS::get_login() - phpCAS::forceAuthentication failed');
			return NULL;
		}

		if (! phpCAS::isAuthenticated()) {
			Logger::error('main', 'AuthMethod_CAS::get_login() - phpCAS::isAuthenticated failed');
			return NULL;
		}

		$this->login = phpCAS::getUser();

		foreach ($_SESSION['backup_sso'] as $k => $v) {
			if (isset($_REQUEST[$k]))
				continue;
			$_REQUEST[$k] = $v;
		}

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
	
	public static function init($prefs_) {
		return true;
	}

	public static function enable() {
		return true;
	}
}
