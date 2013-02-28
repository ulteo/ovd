<?php
/**
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012, 2013
 * Author David LECHEVALIER <david@ulteo.com> 2012
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
	public $applications = array();
	public $sharedfolders_known_rid= array();
	public $forced_sharedfolders = array();

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
			throw new Exception('Internal error: unable to get preference instance');
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
				return false;
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

		if ($session_node->hasAttribute('no_desktop'))
			$_SESSION['no_desktop'] = true;
		
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
				if (! $application_node->hasAttribute('id'))
					continue;
				
				if (! isset($_REQUEST['start_apps']) || ! is_array($_REQUEST['start_apps']))
					$_REQUEST['start_apps'] = array();

				$r = array('id' => $application_node->getAttribute('id'));
				
				if ($application_node->hasAttribute('file_type') && 
				    $application_node->hasAttribute('file_location') && 
				    $application_node->hasAttribute('file_path')) {
					$r['file'] = array();
					$r['file']['type'] = $application_node->getAttribute('file_type');
					$r['file']['location'] = $application_node->getAttribute('file_location');
					$r['file']['path'] = $application_node->getAttribute('file_path');
				}
				else if ($application_node->hasAttribute('arg'))
					$r['arg'] = $application_node->getAttribute('arg');
				
				$_REQUEST['start_apps'][] = $r;
			}
		}

		$session_settings = $this->prefs->get('general', 'session_settings_defaults');
		$can_force_sharedfolders = ($session_settings['can_force_sharedfolders'] == 1);
		if ($can_force_sharedfolders) {
			$share_required_keys = array('rid', 'uri');
			
			$sharedfolder_nodes = $dom->getElementsByTagname('sharedfolder');
			foreach ($sharedfolder_nodes as $sharedfolder_node) {
				$share = array();
				
				if (! $sharedfolder_node->hasAttribute('uri')) {
					Logger::error('main', 'Force shared folder - Usage: missing "uri" attribute');
					continue;
				}
				
				$share['uri'] = $sharedfolder_node->getAttribute('uri');
				
				foreach (array('rid', 'name', 'login', 'password') as $key) {
					if (! $sharedfolder_node->hasAttribute($key))
						continue;
					
					$share[$key] = $sharedfolder_node->getAttribute($key);
				}
				
				$ret = parse_url($share['uri']);
				if ($ret === FALSE) {
					Logger::error('main', 'Force shared folder - Unrecognized URI "'.$share['uri'].'"');
					continue;
				}
				
				if (! in_array($ret['scheme'], array('cifs', 'webdav', 'webdavs'))) {
					Logger::error('main', 'Force shared folder - Usage: unsupported protocol "'.$ret['scheme'].'"');
					continue;
				}
				
				if (array_key_exists('login', $share) !== array_key_exists('password', $share)) {
					Logger::error('main', 'Force shared folder - missing credentials for "'.$ret['scheme'].'": require both login and password');
					continue;
				}
				
				// Check if match RID syntax
				if (array_key_exists('rid', $share) && ! preg_match('/^[a-zA-Z0-9\-_]{4,32}$/', $share['rid'])) {
					Logger::warning('main', 'Force shared folder - rid "'.$share['rid'].'" does not match valid syntax ([a-zA-Z0-9\-_]{4,32}), generating a random one');
					unset($share['rid']);
				}
				
				if (! array_key_exists('rid', $share))
					$share['rid'] = $this->find_uniq_rid('share');
				
				$this->sharedfolders_known_rid[]= $share['rid'];
				
				if (! array_key_exists('name', $share))
					$share['name'] = $share['rid'];
				
				$this->forced_sharedfolders[]= $share;
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

	public function buildServersList($simulation_mode = false) {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::buildServersList - User is not authenticated, aborting');
			return false;
		}

		$serverRoles = $this->getServerRoles();

		$this->servers = array();

		foreach ($serverRoles as $role) {
			if (! array_key_exists($role, $this->servers))
				$this->servers[$role] = array();

			switch ($role) {
				case Server::SERVER_ROLE_APS:
					$servers = $this->chooseApplicationServers();
					if (! is_array($servers)) {
						return false;
					}

					foreach ($servers as $server) {
						$this->servers[Server::SERVER_ROLE_APS][$server->id] = array(
							'status' => Session::SESSION_STATUS_CREATED
						);
					}
					break;
				case Server::SERVER_ROLE_FS:
					if (get_class($this) != 'SessionManagement_internal') {
						Logger::error('main', 'SessionManagement::buildServersList - Role "'.$role.'" is not compatible with the current integration mode ('.substr(get_class($this), strlen('SessionManagement_')).'), aborting');
						return false;
					}

					$prefs = Preferences::getInstance();
					if (! $prefs)
						return false;
					
					$mods_enable = $prefs->get('general', 'module_enable');
					
					$default_settings = $this->user->getSessionSettings('session_settings_defaults');
					$enable_profiles = ($default_settings['enable_profiles'] == 1);
					$auto_create_profile = ($default_settings['auto_create_profile'] == 1 && in_array('ProfileDB', $mods_enable));
					$start_without_profile = ($default_settings['start_without_profile'] == 1);
					$enable_sharedfolders = ($default_settings['enable_sharedfolders'] == 1 && in_array('SharedFolderDB', $mods_enable));
					$start_without_all_sharedfolders = ($default_settings['start_without_all_sharedfolders'] == 1);

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
									if ($fileserver->id != $profile->server)
										continue;

									if (! array_key_exists($fileserver->id, $this->servers[Server::SERVER_ROLE_FS]))
										$this->servers[Server::SERVER_ROLE_FS][$fileserver->id] = array();

									$this->servers[Server::SERVER_ROLE_FS][$fileserver->id][] = array(
										'type'		=>	'profile',
										'rid'		=>	$this->find_uniq_rid('profile', true),
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

									if (! $simulation_mode) {
										$profile = new Profile(NULL, $fileserver->id, NetworkFolder::NF_STATUS_OK);

										if (! $profileDB->addToServer($profile, $fileserver)) {
											Logger::error('main', 'SessionManagement::buildServersList - Auto-creation of Profile for User "'.$this->user->getAttribute('login').'" failed (unable to add the Profile to the FileServer)');
											return false;
										}

										if (! $profile->addUser($this->user)) {
											Logger::error('main', 'SessionManagement::buildServersList - Auto-creation of Profile for User "'.$this->user->getAttribute('login').'" failed (unable to associate the User to the Profile)');
											return false;
										}

										if (! array_key_exists($fileserver->id, $this->servers[Server::SERVER_ROLE_FS]))
											$this->servers[Server::SERVER_ROLE_FS][$fileserver->id] = array();

										$this->servers[Server::SERVER_ROLE_FS][$fileserver->id][] = array(
											'type'		=>	'profile',
											'rid'		=>	$this->find_uniq_rid('profile', true),
											'server'	=>	$fileserver,
											'dir'		=>	$profile->id
										);
									}
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

								if (! array_key_exists($fileserver->id, $this->servers[Server::SERVER_ROLE_FS]))
									$this->servers[Server::SERVER_ROLE_FS][$fileserver->id] = array();

								$this->servers[Server::SERVER_ROLE_FS][$fileserver->id][] = array(
									'type'		=>	'sharedfolder',
									'rid'		=>	$this->find_uniq_rid('sharedfolder', true),
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
			return false;
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

	public function getDesktopServer() {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::getDesktopServer() - User is not authenticated, aborting');
			return false;
		}
		
		$servers = $this->getAvailableApplicationServers();
		$remote_desktop_settings = $this->user->getSessionSettings('remote_desktop_settings');
		
		if (array_key_exists('desktop_type', $remote_desktop_settings) && $remote_desktop_settings['desktop_type'] != 'any') {
			foreach ($servers as $id => $server) {
				if ($server->getAttribute('type') != $remote_desktop_settings['desktop_type'])
					unset($servers[$id]);
			}
		}
		
		$allowed_servers = (array_key_exists('allowed_desktop_servers', $remote_desktop_settings))?$remote_desktop_settings['allowed_desktop_servers']:array();
		if (count($allowed_servers)>0) {
			foreach ($servers as $id => $server) {
				// can be server id or fqdn
				if (! in_array($server->id, $allowed_servers) && ! in_array($server->fqdn, $allowed_servers))
					unset($servers[$id]);
			}
		}
		if (count($servers) == 0) {
			Logger::error('main', 'No desktop server available for user '.$this->user->getAttribute('login'));
			return false;
		}
		
		if (count($servers) > 1) {
			// Fire load balancing algorithm
			$servers = Server::fire_load_balancing($servers, Server::SERVER_ROLE_APS, array('user' => $this->user));
		}
		
		$servers_id = array_keys($servers);
		$server_id = $servers_id[0];
		$this->desktop_server = $servers[$server_id];
		
		return true;
	}

	public function chooseApplicationServers() {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::chooseApplicationServers - User is not authenticated, aborting');
			return false;
		}
		
		$applications = $this->user->applications();
		$nb_application_to_publish = count($applications);
		
		if ($nb_application_to_publish == 0) {
			$event = new SessionStart(array('user' => $this->user));
			$event->setAttribute('ok', false);
			$event->setAttribute('error', 'No available application');
			$event->emit();

			Logger::error('main', 'SessionManagement::choose_applications_servers - No applications published for User "'.$this->user->getAttribute('login').'", aborting');
			return false;
		}
		
		$servers = array();
		$servers_available = $this->getAvailableApplicationServers();
		$servers_ordered = Server::fire_load_balancing($servers_available, Server::SERVER_ROLE_APS, array('user' => $this->user));
		
		if ($this->desktop_server !== false) {
			// We take the desktop servers applications first
			$servers[]= $this->desktop_server;
			
			$application_choosen = $this->removeApplicationsAvailableOnServer($applications, $this->desktop_server);
			foreach($application_choosen as $application)
				$this->applications[$application->getAttribute('id')] = $application;
		}
		
		foreach($servers_ordered as $server_id => $server) {
			if (count($applications) == 0)
				break;
			
			$application_choosen = $this->removeApplicationsAvailableOnServer($applications, $server);
			if (count($application_choosen) == 0)
				continue;
			
			$servers[$server_id] = $server;
			foreach($application_choosen as $application)
				$this->applications[$application->getAttribute('id')] = $application;
		}
		
		$remote_desktop_settings = $this->user->getSessionSettings('session_settings_defaults');
		$launch_without_apps = ($remote_desktop_settings['launch_without_apps'] == 1); 
		
		// If there are still some non published applications ...
		if (count($applications) > 0) {
			// If we didn't puslish any application ...
			if (count($applications) == $nb_application_to_publish) {
				
				$event = new SessionStart(array('user' => $this->user));
				$event->setAttribute('ok', false);
				$event->setAttribute('error', 'No available application');
				$event->emit();
				
				Logger::error('main', 'SessionManagement::choose_applications_servers - Unable to publish any application for User "'.$this->user->getAttribute('login').'", aborting');
				return false;
			}
			
			// Or if we must statisfy all application ...
			if ($launch_without_apps === false) {
				$event = new SessionStart(array('user' => $this->user));
				$event->setAttribute('ok', false);
				$event->setAttribute('error', 'No available server');
				$event->emit();
				
				Logger::error('main', 'SessionManagement::choose_applications_servers - Unable to build a server list for User "'.$this->user->getAttribute('login').'", aborting');
				return false;
			}
		}
		
		return $servers;
	}
	
	private function removeApplicationsAvailableOnServer(&$applications_, $server_) {
		$application_choosen = array();
		$applications_from_server = $server_->getApplications();
		foreach ($applications_from_server as $k => $an_server_application) {
			if (in_array($an_server_application, $applications_))
				$application_choosen[]= $an_server_application;
		}
		
		foreach($application_choosen as $application) {
			$key = array_search($application, $applications_, true);
			if ($key === false)
				continue;
			
			unset($applications_[$key]);
		}
		
		return $application_choosen;
	}
	
	public function getAvailableApplicationServers() {
		$servers = Abstract_Server::load_available_by_role(Server::SERVER_ROLE_APS);
		
		$slave_server_settings = $this->prefs->get('general', 'slave_server_settings');
		if (is_array($slave_server_settings) && array_key_exists('use_max_sessions_limit', $slave_server_settings) && $slave_server_settings['use_max_sessions_limit'] = 1) {
			foreach ($servers as $k => $server) {
				if (! isset($server->max_sessions) || $server->max_sessions == 0)
					continue;

				$total = Abstract_Session::countByServer($server->id);
				if ($total >= $server->max_sessions) {
					Logger::warning('main', 'SessionManagement::buildServersList - Server \''.$server->fqdn.'\' has reached its "max sessions" limit, sessions cannot be launched on it anymore');
					unset($servers[$k]);
				}
			}
		}
		
		return $servers;
	}
	
	public function appendToSessionCreateXML($dom_) {
		return;
	}

	public function end() {
		if (array_key_exists('user_login', $_SESSION))
			unset($_SESSION['user_login']);

		return;
	}
	
	public function find_uniq_rid($pattern, $register = false) {
		$i = 0;
		$item = $pattern;
		while (in_array($item, $this->sharedfolders_known_rid))
			$item = $pattern.'_'.(++$i);
		
		$this->sharedfolders_known_rid[]= $item;
		return $item;
	}
}
