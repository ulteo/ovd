<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
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
	protected $userDB = false;
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
		$userDB_enabled = $this->prefs->get('UserDB', 'enable');
		if (! in_array($userDB_enabled, $this->getUserDB())) {
			Logger::error('main', 'SessionManagement::initialize - UserDB "'.$userDB_enabled.'" is not compatible with the current integration settings');
			return false;
		}

		$max_sessions_number = $this->prefs->get('general', 'max_sessions_number');
		if ($max_sessions_number != 0) {
			$session_number = Abstract_Session::countByStatus();
			if ($session_number >= $max_sessions_number) {
				Logger::error('main', 'SessionManagement::buildServersList - Maximum number of sessions already started '.$session_number);
				throw_response(SERVICE_NOT_AVAILABLE);
			}
		}

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
			if ($user_node->hasAttribute('token'))
				$_REQUEST['token'] = $user_node->getAttribute('token');
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
		$this->userDB = UserDB::getInstance();

		if (isset($_SESSION) && is_array($_SESSION) && array_key_exists('user_login', $_SESSION)) {
			$this->user = $this->userDB->import($_SESSION['user_login']);
			if (! is_object($this->user)) {
				Logger::debug('main', 'SessionManagement::authenticate - Unable to import a valid user with login "'.$_SESSION['user_login'].'"');
				return false;
			}

			return true;
		}

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

			$authMethods[$authMethod_name_] = $authMethod_name_;
		}

		if (array_key_exists('Password', $authMethods)) {
			unset($authMethods['Password']);
			$authMethods['Password'] = 'Password';
		}

		foreach ($authMethods as $authMethod_name_) {
			$authMethod_module = 'AuthMethod_'.$authMethod_name_;
			$authMethod = new $authMethod_module($this->prefs, $this->userDB);

			Logger::debug('main', 'SessionManagement::authenticate - Trying "'.$authMethod_module.'"');

			$user_login = $authMethod->get_login();
			if (is_null($user_login)) {
				Logger::debug('main', 'SessionManagement::authenticate - Unable to get a valid login');
				continue;
			}

			$this->user = $this->userDB->import($user_login);
			if (! is_object($this->user)) {
				Logger::debug('main', 'SessionManagement::authenticate - Unable to import a valid user with login "'.$user_login.'"');
				continue;
			}

			$buf = $authMethod->authenticate($this->user);
			if ($buf === true) {
				$this->authMethod = $authMethod;

				Logger::debug('main', 'SessionManagement::authenticate - Now authenticated as "'.$user_login.'"');
				return true;
			}

			Logger::error('main', 'SessionManagement::authenticate - Authentication failed for "'.$user_login.'"');
			continue;
		}

		Logger::error('main', 'SessionManagement::authenticate - Authentication failed');

		$this->user = false;

		return false;
	}

	public function buildServersList() {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::buildServersList - User is not authenticated, aborting');
			throw_response(AUTH_FAILED);
		}

		$serverRoles = $this->getServerRoles();

		$this->servers = array();

		foreach ($serverRoles as $role) {
			if (! array_key_exists($role, $this->servers))
				$this->servers[$role] = array();

			switch ($role) {
				case Server::SERVER_ROLE_APS:
					$applicationServerTypes = $this->getApplicationServerTypes();

					$servers = array();

					foreach ($applicationServerTypes as $type) {
						$buf = $this->user->getAvailableServers($type);
						if (is_null($buf) || ! is_array($buf))
							return false;

						$servers = array_merge($servers, $buf);
					}

					$slave_server_settings = $this->prefs->get('general', 'slave_server_settings');
					if (is_array($slave_server_settings) && array_key_exists('use_max_sessions_limit', $slave_server_settings) && $slave_server_settings['use_max_sessions_limit'] == 1) {
						foreach ($servers as $k => $server) {
							if (! isset($server->max_sessions) || $server->max_sessions == 0)
								continue;

							$total = Abstract_Session::countByServer($server->fqdn);
							if ($total >= $server->max_sessions) {
								Logger::warning('main', 'SessionManagement::buildServersList - Server \''.$server->fqdn.'\' has reached its "max sessions" limit, sessions cannot be launched on it anymore');
								unset($servers[$k]);
							}
						}
					}

					if (count($servers) == 0) {
						$event = new SessionStart(array('user' => $this->user));
						$event->setAttribute('ok', false);
						$event->setAttribute('error', _('No available server'));
						$event->emit();

						Logger::error('main', 'SessionManagement::buildServersList - No "'.$role.'" server found for User "'.$this->user->getAttribute('login').'", aborting');
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

	public function generateCredentials() {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::generateCredentials - User is not authenticated, aborting');
			throw_response(AUTH_FAILED);
		}

		$serverRoles = $this->getServerRoles();

		$this->credentials = array();

		foreach ($serverRoles as $role) {
			if (! array_key_exists($role, $this->credentials))
				$this->credentials[$role] = array();

			switch ($role) {
				case Server::SERVER_ROLE_APS:
					$this->generateApplicationServerCredentials();
					break;
				case Server::SERVER_ROLE_FS:
					$this->generateFileServerCredentials();
					break;
			}
		}

		return true;
	}

	public function generateApplicationServerCredentials() {
		return false;
	}

	public function generateFileServerCredentials() {
		return false;
	}

	public function getDesktopServer($type_='any') {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::getDesktopServer("'.$type_.'") - User is not authenticated, aborting');
			throw_response(AUTH_FAILED);
		}

		$this->desktop_server = false;

		switch ($type_) {
			case Server::SERVER_TYPE_LINUX:
			case Server::SERVER_TYPE_WINDOWS:
				$user_applications = $this->user->applications($type_, true);

				$usable_servers = array();
				foreach ($this->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
					$server = Abstract_Server::load($fqdn);
					if (! $server)
						continue;

					if ($server->getAttribute('type') == $type_) {
						$usable_servers[$server->fqdn] = 0;

						$server_applications = $server->getApplications();
						foreach ($server_applications as $server_application) {
							if (in_array($server_application, $user_applications))
								$usable_servers[$server->fqdn] += 1;
						}
					}
				}
				break;
			case 'any':
			default:
				$user_applications = $this->user->applications(NULL, true);

				$usable_servers = array();
				foreach ($this->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
					$server = Abstract_Server::load($fqdn);
					if (! $server)
						continue;

					$usable_servers[$server->fqdn] = 0;

					$server_applications = $server->getApplications();
					foreach ($server_applications as $server_application) {
						if (in_array($server_application, $user_applications))
							$usable_servers[$server->fqdn] += 1;
					}
				}
				break;
		}
		arsort($usable_servers);

		if (count($usable_servers) > 0)
			$this->desktop_server = array_shift(array_keys($usable_servers));

		if (! $this->desktop_server) {
			Logger::error('main', 'SessionManagement::getDesktopServer("'.$type_.'") - No desktop server found for User "'.$this->user->getAttribute('login').'", aborting');
			return false;
		}

		return $this->desktop_server;
	}

	public function appendToSessionCreateXML($dom_) {
		return;
	}

	public function end() {
		if (array_key_exists('user_login', $_SESSION))
			unset($_SESSION['user_login']);

		return;
	}
}
