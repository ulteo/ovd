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
		return array(Server::SERVER_ROLE_APS, Server::SERVER_ROLE_FS);
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
		$this->credentials[Server::SERVER_ROLE_APS]['password'] = gen_string(3, 'abcdefghijklmnopqrstuvwxyz').gen_string(2, '0123456789').gen_string(3, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');

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

		$c = new ConfigElement_select('generate_aps_login', _("Which login should be used for the ApplicationServer's generated user?"), _("Which login should be used for the ApplicationServer's generated user?"), _("Which login should be used for the ApplicationServer's generated user?"), 1);
		$c->setContentAvailable(array(0=>_('Use given login'), 1=>_('Auto-generate')));
		$ret []= $c;

		return $ret;
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
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
