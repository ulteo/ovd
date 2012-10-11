<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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
					if (get_class($this) != 'SessionManagement_internal') {
						Logger::error('main', 'SessionManagement::buildServersList - Role "'.$role.'" is not compatible with the current integration mode ('.substr(get_class($this), strlen('SessionManagement_')).'), aborting');
						return false;
					}

					$default_settings = $this->user->getSessionSettings('session_settings_defaults');
					$enable_profiles = (($default_settings['enable_profiles'] == 1)?true:false);
					$auto_create_profile = (($default_settings['auto_create_profile'] == 1)?true:false);
					$start_without_profile = (($default_settings['start_without_profile'] == 1)?true:false);
					$enable_sharedfolders = (($default_settings['enable_sharedfolders'] == 1)?true:false);
					$start_without_all_sharedfolders = (($default_settings['start_without_all_sharedfolders'] == 1)?true:false);

					if ($enable_profiles) {
						$fileservers = Abstract_Server::load_available_by_role(Server::SERVER_ROLE_FS);

						if (count($fileservers) > 0) {
							$profiles = $this->user->getProfiles();
							if (! is_array($profiles)) {
								Logger::error('main', 'SessionManagement::buildServersList - getProfiles() failed for User "'.$this->user->getAttribute('login').'", aborting');
								return false;
							}

							if (count($profiles) == 1) {
								Logger::debug('main', 'SessionManagement::buildServersList - User "'.$this->user->getAttribute('login').'" already have a Profile, using it');

								$profile = array_pop($profiles);

								foreach ($fileservers as $fileserver) {
									if ($fileserver->fqdn != $profile->server)
										continue;

									if (! array_key_exists($fileserver->fqdn, $this->servers[Server::SERVER_ROLE_FS]))
										$this->servers[Server::SERVER_ROLE_FS][$fileserver->fqdn] = array();

									$this->servers[Server::SERVER_ROLE_FS][$fileserver->fqdn][] = array(
										'type'		=>	'profile',
										'server'	=>	$fileserver,
										'dir'		=>	$profile->id
									);

									break;
								}
							} else {
								Logger::debug('main', 'SessionManagement::buildServersList - User "'.$this->user->getAttribute('login').'" does not have a Profile for now, checking for auto-creation');

								if ($auto_create_profile) {
									Logger::debug('main', 'SessionManagement::buildServersList - User "'.$this->user->getAttribute('login').'" Profile will be auto-created and used');

									$profileDB = ProfileDB::getInstance();

									$fileserver = $profileDB->chooseFileServer();
									if (! is_object($fileserver)) {
										Logger::error('main', 'SessionManagement::buildServersList - Auto-creation of Profile for User "'.$this->user->getAttribute('login').'" failed (unable to get a valid FileServer)');
										return false;
									}

									$profile = new Profile();
									$profile->server = $fileserver->getAttribute('fqdn');

									if (! $profileDB->addToServer($profile, $fileserver)) {
										Logger::error('main', 'SessionManagement::buildServersList - Auto-creation of Profile for User "'.$this->user->getAttribute('login').'" failed (unable to add the Profile to the FileServer)');
										return false;
									}

									if (! $profile->addUser($this->user)) {
										Logger::error('main', 'SessionManagement::buildServersList - Auto-creation of Profile for User "'.$this->user->getAttribute('login').'" failed (unable to associate the User to the Profile)');
										return false;
									}

									if (! array_key_exists($fileserver->fqdn, $this->servers[Server::SERVER_ROLE_FS]))
										$this->servers[Server::SERVER_ROLE_FS][$fileserver->fqdn] = array();

									$this->servers[Server::SERVER_ROLE_FS][$fileserver->fqdn][] = array(
										'type'		=>	'profile',
										'server'	=>	$fileserver,
										'dir'		=>	$profile->id
									);
								} else {
									Logger::debug('main', 'SessionManagement::buildServersList - Auto-creation of Profile for User "'.$this->user->getAttribute('login').'" disabled, checking for session without Profile');

									if (! $start_without_profile) {
										Logger::error('main', 'SessionManagement::buildServersList - User "'.$this->user->getAttribute('login').'" does not have a valid Profile, aborting');

										return false;
									}

									Logger::debug('main', 'SessionManagement::buildServersList - User "'.$this->user->getAttribute('login').'" can start a session without a valid Profile, proceeding');
								}
							}
						} else {
							Logger::debug('main', 'SessionManagement::buildServersList - No "'.$role.'" server found for User "'.$this->user->getAttribute('login').'", checking for session without Profile');

							if (! $start_without_profile) {
								Logger::error('main', 'SessionManagement::buildServersList - User "'.$this->user->getAttribute('login').'" does not have a valid Profile, aborting');

								return false;
							}

							Logger::debug('main', 'SessionManagement::buildServersList - User "'.$this->user->getAttribute('login').'" can start a session without a valid Profile, proceeding');
						}
					}

					if ($enable_sharedfolders) {
						$sharedfolders = $this->user->getSharedFolders();
						if (! is_array($sharedfolders)) {
							Logger::error('main', 'SessionManagement::buildServersList - getSharedFolders() failed for User "'.$this->user->getAttribute('login').'", aborting');
							return false;
						}

						if (count($sharedfolders) > 0) {
							foreach ($sharedfolders as $sharedfolder) {
								$fileserver = Abstract_Server::load($sharedfolder->server);
								if (! $fileserver || ! $fileserver->isOnline() || $fileserver->getAttribute('locked')) {
									Logger::warning('main', 'SessionManagement::buildServersList - Server "'.$sharedfolder->server.'" for SharedFolder "'.$sharedfolder->id.'" is not available');

									if (! $start_without_all_sharedfolders) {
										Logger::error('main', 'SessionManagement::buildServersList - User "'.$this->user->getAttribute('login').'" does not have all SharedFolders available, aborting');

										return false;
									} else {
										Logger::debug('main', 'SessionManagement::buildServersList - User "'.$this->user->getAttribute('login').'" can start a session without all SharedFolders available, proceeding');

										continue;
									}
								}

								if (! array_key_exists($fileserver->fqdn, $this->servers[Server::SERVER_ROLE_FS]))
									$this->servers[Server::SERVER_ROLE_FS][$fileserver->fqdn] = array();

								$this->servers[Server::SERVER_ROLE_FS][$fileserver->fqdn][] = array(
									'type'		=>	'sharedfolder',
									'server'	=>	$fileserver,
									'dir'		=>	$sharedfolder->id,
									'name'		=>	$sharedfolder->name
								);
							}
						}
					}

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
		
		$allowed_servers = array();
		
		$remote_desktop_settings = $this->user->getSessionSettings('remote_desktop_settings');
		if (array_key_exists('allowed_desktop_servers', $remote_desktop_settings))
			$allowed_servers = $remote_desktop_settings['allowed_desktop_servers'];

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
					
					if (count($allowed_servers)>0) {
						if (! in_array($fqdn, $allowed_servers)) {
							Logger::info('main', 'SessionManagement::getDesktopServer("'.$type_.'") - can\'t used server "'.$fqdn.'" as desktop server because not in allowed desktop servers list');
							continue;
						}
					}

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
					
					if (count($allowed_servers)>0) {
						if (! in_array($fqdn, $allowed_servers)) {
							Logger::info('main', 'SessionManagement::getDesktopServer("'.$type_.'") - can\'t used server "'.$fqdn.'" as desktop server because not in allowed desktop servers list');
							continue;
						}
					}

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
