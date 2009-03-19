<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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

class AuthMethod_Token extends AuthMethod {
	public function get_login() {
		$buf = $this->prefs->get('AuthMethod','Token');
		$token_url = $buf['user_authenticate_token_url'];
		if (! isset($token_url) or $token_url=='')
			return NULL;

		if (! isset($_REQUEST['token']))
			return NULL;

		$token_url = str_replace('%TOKEN%', $_REQUEST['token'], $token_url);

		$xml = query_url($token_url);

		$dom = new DomDocument();
		$ret = @$dom->loadXML($xml);
		if (! $ret) {
			Logger::error('main', 'Token webservice does not send a valid XML');
			return NULL;
		}
		$user_node = $dom->getElementsByTagname('user')->item(0);
		if (! is_object($user_node)) {
			Logger::error('main', 'Token webservice does not send a valid XML');
			return NULL;
		}
		if (! $user_node->hasAttribute('login')) {
			Logger::error('main', 'Token webservice does not send a valid XML');
			return NULL;
		}

		$this->login = $user_node->getAttribute('login');
		return $this->login;
	}

	public function authenticate($user_) {
		return true;
	}

	public static function prettyName() {
		return _('Token authentication');
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		$buf = $prefs_->get('AuthMethod','Token');
		$token_url = $buf['user_authenticate_token_url'];
		if (! isset($token_url) || $token_url=='')
			return false;

		if (! strstr($token_url, '%TOKEN%'))
			return false;

		return true;
	}

	public static function configuration() {
		return array(
			new ConfigElement('user_authenticate_token_url', _('Token validation URL'), _('If a token argument is sent to startsession, the system try to get a user login by request the token validation url.<br /><br />Put here the url to request if a token argument is sent to <i>startsession</i> instead of login/password.<br /><br />The special string <b>%TOKEN%</b> needs to be set because it\'s replaced by the token argument when the URL is requested.'), _('If a token argument is sent to startsession, the system try to get a user login by request the token validation url.<br /><br />Put here the url to request if a token argument is sent to <i>startsession</i> instead of login/password.<br /><br />The special string <b>%TOKEN%</b> needs to be set because it\'s replaced by the token argument when the URL is requested.'), 'http://trust.server.com?token=%TOKEN%', NULL, ConfigElement::$INPUT)
		);
	}
}
