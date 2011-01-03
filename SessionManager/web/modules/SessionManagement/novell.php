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

class SessionManagement_novell extends SessionManagement {
	public static function getUserDB() {
		return array('ldap');
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
		$this->credentials[Server::SERVER_ROLE_APS]['login'] = $_POST['login'];
		$this->credentials[Server::SERVER_ROLE_APS]['password'] = $_POST['password'];

		return true;
	}

	public function appendToSessionCreateXML($dom_) {
		$environment_node = $dom_->createElement('environment');
		$environment_node->setAttribute('id', 'Novell');

		$config = $this->prefs->get('SessionManagement', 'novell');
		$dlu = ($config['dlu'] == 1);

		if ($dlu) {
			$environment_node->setAttribute('dlu', 'yes');
		} else {
			$environment_node->setAttribute('server', $this->userDB->config['host']);
			$environment_node->setAttribute('tree', suffix2domain($this->userDB->config['suffix']));
			$environment_node->setAttribute('login', $_POST['login']);
			$environment_node->setAttribute('password', $_POST['password']);
		}

		$dom_->documentElement->appendChild($environment_node);

		return;
	}

	/* Module methods */
	public static function configuration() {
		$ret = array();

		$c = new ConfigElement_select('dlu', _('Manage users by ZENworks DLU instead of native method'), _('Manage users by ZENworks DLU instead of native method'), _('Manage users by ZENworks DLU instead of native method'), 0);
		$c->setContentAvailable(array(0=>_('no'), 1=>_('yes')));
		$ret[] = $c;

		return $ret;
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}

	public static function prettyName() {
		return _('Novell');
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
