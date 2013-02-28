<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2009, 2011, 2013
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

class AuthMethod_RemoteUser extends AuthMethod {
	public function get_login() {
		$buf = $this->prefs->get('AuthMethod','RemoteUser');
		$key = $buf['user_authenticate_trust'];
		if (! isset($key) || $key=='')
			return NULL;

		if (! isset($_SERVER[$key]) || $_SERVER[$key]=='')
			return NULL;

		$this->login = $_SERVER[$key];
		if (array_key_exists('remove_domain_if_exists', $buf) && $buf['remove_domain_if_exists'] == 1) {
			$atpos = strpos($this->login, '@');
			if ($atpos !== false)
				$this->login = substr($this->login, 0, $atpos);
		}
		return $this->login;
	}

	public function authenticate($user_) {
		if (! isset($this->login))
			return false;

		return (strtolower($this->login) == strtolower($user_->getAttribute('login')));
	}


	public static function prefsIsValid($prefs_, &$log=array()) {
		$buf = $prefs_->get('AuthMethod','RemoteUser');
		$key = $buf['user_authenticate_trust'];

		if ($key == '')
			return false;

		return true;
	}

	public static function configuration() {
		$ret = array();

		$c = new ConfigElement_input('user_authenticate_trust', 'REMOTE_USER');
		$ret[] = $c;

		$c = new ConfigElement_select('remove_domain_if_exists', 0);
		$c->setContentAvailable(array(0, 1));
		$ret[] = $c;

		return $ret;
	}
	
	public static function init($prefs_) {
		return true;
	}

	public static function enable() {
		return true;
	}
}
