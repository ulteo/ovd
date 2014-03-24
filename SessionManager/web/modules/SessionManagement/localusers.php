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

class SessionManagement_localusers extends SessionManagement_internal {
	protected $override_password = false;
	
	public static function getServerRoles() {
		return array(Server::SERVER_ROLE_APS, Server::SERVER_ROLE_WEBAPPS);
	}
	
	public static function getApplicationServerTypes() {
		return array(Server::SERVER_TYPE_LINUX);
	}
	
	public function generateApplicationServerCredentials() {
		$prefs = $this->prefs->get('SessionManagement', 'localusers');
		if ($prefs['override_password'] != 0 || ! (array_key_exists('password', $_POST) && strlen($_POST['password']) >0)) {
			$this->override_password = true;
		}
		
		if ($this->override_password) {
			$password = gen_string(3, 'abcdefghijklmnopqrstuvwxyz').gen_string(2, '0123456789').gen_string(3, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		}
		else {
			$password = $_POST['password'];
		}
		
		$this->credentials[Server::SERVER_ROLE_APS]['login'] = $this->user->getAttribute('login');
		$this->credentials[Server::SERVER_ROLE_APS]['password'] = $password;
		
		return true;
	}
	
	public function appendToSessionCreateXML($dom_) {
		$environment_node = $dom_->createElement('environment');
		$environment_node->setAttribute('id', 'Local');
		if ($this->override_password) {
			$environment_node->setAttribute('update_password', 'true');
		}
		
		$dom_->documentElement->appendChild($environment_node);
		
		return;
	}
	
	public static function prettyName() {
		return _('Local Users');
	}
	
	public static function configuration() {
		$ret = array();
		
		$c = new ConfigElement_select('override_password', 0);
		$c->setContentAvailable(array(0, 1));
		$ret []= $c;
		
		return $ret;
	}
	
	public static function isDefault() {
		return false;
	}
}
