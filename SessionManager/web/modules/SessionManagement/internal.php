<?php
/**
 * Copyright (C) 2010-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013, 2014
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
	public static function getUserDB() {
		$prefs = Preferences::getInstance();
		$available_modules = $prefs->getAvailableModule();
		return array_keys($available_modules['UserDB']);
	}

	public static function getAuthMethods() {
		$prefs = Preferences::getInstance();

		return $prefs->get('AuthMethod', 'enable');
	}

	public static function getServerRoles() {
		return array(Server::SERVER_ROLE_APS, Server::SERVER_ROLE_FS, Server::SERVER_ROLE_WEBAPPS);
	}

	public static function getApplicationServerTypes() {
		return array(Server::SERVER_TYPE_LINUX, Server::SERVER_TYPE_WINDOWS);
	}

	public function generateApplicationServerCredentials() {
		$buf = $this->prefs->get('SessionManagement', 'internal');
		if (! array_key_exists('generate_aps_login', $buf) || $buf['generate_aps_login'] != 0)
			$this->credentials[Server::SERVER_ROLE_APS]['login'] = 'u'.time().gen_string(5).'_APS'; //hardcoded
		else
			$this->credentials[Server::SERVER_ROLE_APS]['login'] = $this->user->getAttribute('login');
		
		if (! array_key_exists('generate_aps_password', $buf) || $buf['generate_aps_password'] != 0)
			$this->credentials[Server::SERVER_ROLE_APS]['password'] = gen_string(3, 'abcdefghijklmnopqrstuvwxyz').gen_string(2, '0123456789').gen_string(3, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		else
			$this->credentials[Server::SERVER_ROLE_APS]['password'] = $_SESSION['password'];
		
		return true;
	}

	public function generateFileServerCredentials() {
		$this->credentials[Server::SERVER_ROLE_FS]['login'] = 'u'.time().gen_string(6).'_FS'; //hardcoded
		$this->credentials[Server::SERVER_ROLE_FS]['password'] = gen_string(3, 'abcdefghijklmnopqrstuvwxyz').gen_string(2, '0123456789').gen_string(3, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');

		return true;
	}

	/* Module methods */
	public static function configuration() {
		$ret = array();

		$c = new ConfigElement_select('generate_aps_login', 0);
		$c->setContentAvailable(array(0, 1));
		$ret []= $c;
		
		$c = new ConfigElement_select('generate_aps_password', 1);
		$c->setContentAvailable(array(0, 1));
		$ret []= $c;

		return $ret;
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
