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

abstract class SessionManagement extends Module {
	protected static $instance = NULL;
	protected $prefs = false;
	protected $authMethod = false;
	public $user = false;
	public $desktop_server = false;
	public $servers = array();
	public $credentials = array();

	public static function getInstance() {
		if (is_null(self::$instance)) {
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed', __FILE__, __LINE__);

			$enabled_modules = $prefs->get('general','module_enable');
			if (! in_array('SessionManagement', $enabled_modules))
				die_error('SessionManagement module must be enabled', __FILE__, __LINE__);

			$SessionManagement_module_name = 'SessionManagement_'.$prefs->get('SessionManagement', 'enable');
			self::$instance = new $SessionManagement_module_name();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->prefs = Preferences::getInstance();
		if (! $this->prefs) {
			Logger::critical('main', 'SessionManagement::__construct - get Preferences failed');
			throw_response(INTERNAL_ERROR);
		}

		$system_in_maintenance = $this->prefs->get('general', 'system_in_maintenance');
		if ($system_in_maintenance == '1') {
			Logger::error('main', 'SessionManagement::__construct - The system is on maintenance mode');
			throw_response(IN_MAINTENANCE);
		}
	}

	public function initialize() {
		return true;
	}

	public function parseClientRequest($xml_) {
		if (! $xml_ || strlen($xml_) == 0) {
			Logger::error('main', 'SessionManagement::parseClientRequest - Empty content');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$buf = @$dom->loadXML($xml_);
		if (! $buf) {
			Logger::error('main', 'SessionManagement::parseClientRequest - Not an XML');
			return false;
		}

		if (! $dom->hasChildNodes()) {
			Logger::error('main', 'SessionManagement::parseClientRequest - Empty XML');
			return false;
		}

		$session_node = $dom->getElementsByTagname('session')->item(0);
		if (is_null($session_node)) {
			Logger::error('main', 'SessionManagement::parseClientRequest - No "session" node');
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
			Logger::error('main', 'SessionManagement::parseSessionCreate - Empty content');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$buf = @$dom->loadXML($xml_);
		if (! $buf) {
			Logger::error('main', 'SessionManagement::parseSessionCreate - Not an XML');
			return false;
		}

		if (! $dom->hasChildNodes()) {
			Logger::error('main', 'SessionManagement::parseSessionCreate - Empty XML');
			return false;
		}

		$node = $dom->getElementsByTagname('session')->item(0);
		if (is_null($node)) {
			Logger::error('main', 'SessionManagement::parseSessionCreate - No "session" node');
			return false;
		}

		if (! $node->hasAttribute('id')) {
			Logger::error('main', 'SessionManagement::parseSessionCreate - No "id" attribute in "session" node');
			return false;
		}

		return true;
	}

	public function authenticate() {
		$userDB = UserDB::getInstance();

		$authMethods_enabled = $this->prefs->get('AuthMethod', 'enable');
		if (! is_array($authMethods_enabled)) {
			Logger::error('main', 'SessionManagement::authenticate - No AuthMethod enabled');
			return false;
		}

		$authMethods = array();
		foreach ($this->getAuthMethods() as $authMethod_name_) {
			if (! in_array($authMethod_name_, $authMethods_enabled)) {
				Logger::debug('main', 'SessionManagement::authenticate - AuthMethod "'.$authMethod_name_.'" is not enabled');
				continue;
			}

			$authMethods[] = $authMethod_name_;
		}

		foreach ($authMethods as $authMethod_name_) {
			$authMethod_module = 'AuthMethod_'.$authMethod_name_;
			$authMethod = new $authMethod_module($this->prefs, $userDB);

			Logger::debug('main', 'SessionManagement::authenticate - Trying "'.$authMethod_module.'"');

			$user_login = $authMethod->get_login();
			if (is_null($user_login)) {
				Logger::debug('main', 'SessionManagement::authenticate - Unable to get a valid login');
				return false;
			}

			$this->user = $userDB->import($user_login);
			if (! is_object($this->user)) {
				Logger::debug('main', 'SessionManagement::authenticate - Unable to import a valid user with login "'.$user_login.'"');
				return false;
			}

			$buf = $authMethod->authenticate($this->user);
			if ($buf === true) {
				$this->authMethod = $authMethod;

				Logger::debug('main', 'SessionManagement::authenticate - Now authenticated as "'.$user_login.'"');
				return true;
			}

			Logger::error('main', 'SessionManagement::authenticate - Authentication failed for "'.$user_login.'"');
			return false;
		}

		Logger::error('main', 'SessionManagement::authenticate - Authentication failed');

		$this->user = false;

		return false;
	}

	public function buildServersList($roles_=array(Server::SERVER_ROLE_APS, Server::SERVER_ROLE_FS)) {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::buildServersList - User is not authenticated, aborting');
			throw_response(AUTH_FAILED);
		}

		$this->servers = array(
			Server::SERVER_ROLE_APS	=>	array(),
			Server::SERVER_ROLE_FS	=>	array()
		);

		foreach ($roles_ as $role) {
			switch ($role) {
				case Server::SERVER_ROLE_APS:
					$servers = $this->user->getAvailableServers();
					if (is_null($servers) || count($servers) == 0) {
						$event = new SessionStart(array('user' => $this->user));
						$event->setAttribute('ok', false);
						$event->setAttribute('error', _('No available server'));
						$event->emit();

						Logger::error('main', 'SessionManagement::buildServersList - No server found for User "'.$this->user->getAttribute('login').'", aborting');
						return false;
					}

					foreach ($servers as $server) {
						$this->servers[Server::SERVER_ROLE_APS][$server->fqdn] = array(
							'status' => Session::SESSION_STATUS_CREATED
						);
					}
					break;
				case Server::SERVER_ROLE_FS:
					break;
			}
		}

		return true;
	}

	abstract public function generateCredentials($roles_=array(Server::SERVER_ROLE_APS, Server::SERVER_ROLE_FS));

	public function getDesktopServer($type_='any') {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::getDesktopServer("'.$type_.'") - User is not authenticated, aborting');
			throw_response(AUTH_FAILED);
		}

		$this->desktop_server = false;

		switch ($type_) {
			case 'linux':
			case 'windows':
				foreach ($this->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
					$server = Abstract_Server::load($fqdn);
					if (! $server)
						continue;

					if ($server->getAttribute('type') == $type_) {
						$this->desktop_server = $server->fqdn;
						break;
					}
				}

				if (! $this->desktop_server) {
					Logger::error('main', 'SessionManagement::getDesktopServer("'.$type_.'") - No "'.$type_.'" desktop server found for User "'.$this->user->getAttribute('login').'", aborting');
					return false;
				}
				break;
			case 'any':
			default:
				foreach ($this->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
					$server = Abstract_Server::load($fqdn);
					if (! $server)
						continue;

					$this->desktop_server = $server->fqdn;
					break;
				}

				if (! $this->desktop_server) {
					Logger::error('main', 'SessionManagement::getDesktopServer("'.$type_.'") - No desktop server found for User "'.$this->user->getAttribute('login').'", aborting');
					return false;
				}
				break;
		}

		return $this->desktop_server;
	}

	abstract public function appendToSessionCreateXML($dom_);
}
