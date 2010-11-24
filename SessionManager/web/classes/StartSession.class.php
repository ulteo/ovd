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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class StartSession {
	private $prefs = false;
	public $user = false;

	public function __construct() {
		$this->prefs = Preferences::getInstance();
		if (! $this->prefs) {
			Logger::critical('main', 'StartSession::__construct - get Preferences failed');
			throw_response(INTERNAL_ERROR);
		}

		$system_in_maintenance = $this->prefs->get('general', 'system_in_maintenance');
		if ($system_in_maintenance == '1') {
			Logger::error('main', 'StartSession::__construct - The system is on maintenance mode');
			throw_response(IN_MAINTENANCE);
		}
	}

	public function parseClientRequest($xml_) {
		if (! $xml_ || strlen($xml_) == 0) {
			Logger::error('main', 'StartSession::parseClientRequest - Empty content');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$buf = @$dom->loadXML($xml_);
		if (! $buf) {
			Logger::error('main', 'StartSession::parseClientRequest - Not an XML');
			return false;
		}

		if (! $dom->hasChildNodes()) {
			Logger::error('main', 'StartSession::parseClientRequest - Empty XML');
			return false;
		}

		$session_node = $dom->getElementsByTagname('session')->item(0);
		if (is_null($session_node)) {
			Logger::error('main', 'StartSession::parseClientRequest - No "session" node');
			return false;
		}

		if ($session_node->hasAttribute('mode'))
			$_SESSION['mode'] = $session_node->getAttribute('mode');

		if ($session_node->hasAttribute('language'))
			$_REQUEST['language'] = $session_node->getAttribute('language');

		if ($session_node->hasAttribute('timezone'))
			$_REQUEST['timezone'] = $session_node->getAttribute('timezone');

		$user_node = $dom->getElementsByTagname('user')->item(0);
		if (! is_null($user_node)) {
			if ($user_node->hasAttribute('login'))
				$_POST['login'] = $user_node->getAttribute('login');
			if ($user_node->hasAttribute('password'))
				$_POST['password'] = $user_node->getAttribute('password');
		}

		$start_node = $dom->getElementsByTagname('start')->item(0);
		if (! is_null($start_node)) {
			$application_nodes = $start_node->getElementsByTagname('application');
			foreach ($application_nodes as $application_node) {
				if (! isset($_REQUEST['start_apps']) || ! is_array($_REQUEST['start_apps']))
					$_REQUEST['start_apps'] = array();

				$_REQUEST['start_apps'][] = array(
					'id'	=>	$application_node->getAttribute('id'),
					'arg'	=>	(($application_node->hasAttribute('arg'))?$application_node->getAttribute('arg'):NULL)
				);
			}
		}

		return true;
	}

	public function parseSessionCreate($xml_) {
		if (! $xml_ || strlen($xml_) == 0) {
			Logger::error('main', 'StartSession::parseSessionCreate - Empty content');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$buf = @$dom->loadXML($xml_);
		if (! $buf) {
			Logger::error('main', 'StartSession::parseSessionCreate - Not an XML');
			return false;
		}

		if (! $dom->hasChildNodes()) {
			Logger::error('main', 'StartSession::parseSessionCreate - Empty XML');
			return false;
		}

		$node = $dom->getElementsByTagname('session')->item(0);
		if (is_null($node)) {
			Logger::error('main', 'StartSession::parseSessionCreate - No "session" node');
			return false;
		}

		if (! $node->hasAttribute('id')) {
			Logger::error('main', 'StartSession::parseSessionCreate - No "id" attribute in "session" node');
			return false;
		}

		return true;
	}

	public function authenticate() {
		if (! in_array('UserDB', $this->prefs->get('general', 'module_enable'))) {
			Logger::error('main', 'StartSession::authenticate - UserDB module is not enabled');
			return false;
		}

		$userDB_module = 'UserDB_'.$this->prefs->get('UserDB', 'enable');
		$userDB = new $userDB_module();

		$authMethods = $this->prefs->get('AuthMethod', 'enable');
		if (! is_array($authMethods)) {
			Logger::error('main', 'StartSession::authenticate - No AuthMethod enabled');
			return false;
		}

		foreach ($authMethods as $authMethod) {
			$authMethod_module = 'AuthMethod_'.$authMethod;
			$authMethod = new $authMethod_module($this->prefs, $userDB);

			Logger::debug('main', 'StartSession::authenticate - Trying "'.$authMethod_module."'");

			$user_login = $authMethod->get_login();
			if (is_null($user_login)) {
				Logger::debug('main', 'StartSession::authenticate - Unable to get a valid login, switching to next AuthMethod');
				continue;
			}

			$this->user = $userDB->import($user_login);
			if (! is_object($this->user)) {
				Logger::debug('main', 'StartSession::authenticate - Unable to import a valid user with login "'.$user_login.'", switching to next AuthMethod');
				continue;
			}

			$buf = $authMethod->authenticate($this->user);
			if ($buf === true) {
				Logger::debug('main', 'StartSession::authenticate - Now authenticated as "'.$user_login.'"');
				return true;
			}
		}

		Logger::error('main', 'StartSession::authenticate - Authentication failed');

		$this->user = false;

		return false;
	}
}
