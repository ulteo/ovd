<?php
/**
 * Copyright (C) 2010-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012, 2013
 * Author David LECHEVALIER <david@ulteo.com> 2012-2014
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013, 2014
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
			$this->mode = $session_node->getAttribute('mode');

		if ($session_node->hasAttribute('language'))
			$this->language = $session_node->getAttribute('language');

		if ($session_node->hasAttribute('timezone'))
			$this->timezone = $session_node->getAttribute('timezone');

		if ($session_node->hasAttribute('no_desktop'))
			$this->no_desktop = true;
		
		$user_node = $dom->getElementsByTagname('user')->item(0);
		if (! is_null($user_node)) {
			if ($user_node->hasAttribute('login'))
				$_POST['login'] = $user_node->getAttribute('login');
			if ($user_node->hasAttribute('password'))
				$_POST['password'] = $user_node->getAttribute('password');
			if ($user_node->hasAttribute('token'))
				$this->token = $user_node->getAttribute('token');
		}

		$start_node = $dom->getElementsByTagname('start')->item(0);
		$this->start_apps = array();
		if (! is_null($start_node)) {
			$application_nodes = $start_node->getElementsByTagname('application');
			foreach ($application_nodes as $application_node) {
				if (! $application_node->hasAttribute('id'))
					continue;
				
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
				
				$this->start_apps[] = $r;
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

		$servers_count = 0;
		foreach ($serverRoles as $role) {
			if (! array_key_exists($role, $this->servers))
				$this->servers[$role] = array();

			switch ($role) {
				case Server::SERVER_ROLE_APS:
					$default_settings = $this->user->getSessionSettings('session_settings_defaults');
					$bypass_servers_restrictions = ($default_settings['bypass_servers_restrictions'] == 1);
					$servers = $this->chooseApplicationServers($bypass_servers_restrictions);
					
					if (! is_array($servers)) {
						return false;
					}

					foreach ($servers as $server) {
						$servers_count++;
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
							foreach ($sharedfolders as $sharedfolder_id => $info) {
								$sharedfolder = $info['share'];
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
									'dir'		=>	$sharedfolder->id,
									'name'		=>	$sharedfolder->name,
									'mode'		=>	$info['mode']
								);
							}
						}
					}

					break;
				case Server::SERVER_ROLE_WEBAPPS:
					$servers = $this->chooseWebAppServers();
					if (! is_array($servers)) {
						break;
					}
					Logger::debug('main', 'SessionManagement::buildServersList - found '.count($servers).' webapp server(s)');

					foreach ($servers as $server) {
						$servers_count++;
						$this->servers[Server::SERVER_ROLE_WEBAPPS][$server->id] = array(
							'status' => Session::SESSION_STATUS_CREATED
						);
					}
					break;
			}
		}

		if ($servers_count < 1){
			Logger::error('main', 'SessionManagement::buildServersList - no server found!');
			return false;
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
				case Server::SERVER_ROLE_WEBAPPS:
					$this->generateWebAppServerCredentials();
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

	public function generateWebAppServerCredentials() {
		$this->credentials[Server::SERVER_ROLE_WEBAPPS]['login'] = 'u'.time().gen_string(5).'_WAS'; //hardcoded
		$this->credentials[Server::SERVER_ROLE_WEBAPPS]['password'] = gen_string(3, 'abcdefghijklmnopqrstuvwxyz').gen_string(2, '0123456789').gen_string(3, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');

		return true;
	}

	public function getDesktopServer($bypass_server_restrictions_ = true) {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::getDesktopServer() - User is not authenticated, aborting');
			return false;
		}
		
		$windows_applications_count = 0;
		$linux_applications_count = 0;
		$all_applications = $this->user->applications();
		
		foreach($all_applications as $application) {
			if($application->getAttribute('type') == Server::SERVER_TYPE_LINUX) {
				$linux_applications_count++;
			}
			
			if($application->getAttribute('type') == Server::SERVER_TYPE_WINDOWS) {
				$windows_applications_count++;
			}
			
		}

		$servers = $this->getAvailableApplicationServers();
		$remote_desktop_settings = $this->user->getSessionSettings('remote_desktop_settings');
		
		if (array_key_exists('desktop_type', $remote_desktop_settings) && $remote_desktop_settings['desktop_type'] != 'any') {
			foreach ($servers as $id => $server) {
				if ($server->getAttribute('type') != $remote_desktop_settings['desktop_type'])
					unset($servers[$id]);
			}
		}
		else {
			foreach ($servers as $id => $server) {
				if ($server->getAttribute('type') == Server::SERVER_TYPE_LINUX && $linux_applications_count == 0)
					unset($servers[$id]);
				
				if ($server->getAttribute('type') == Server::SERVER_TYPE_WINDOWS && $windows_applications_count == 0)
					unset($servers[$id]);
			}
		}
		
		$allowed_servers = (array_key_exists('allowed_desktop_servers', $remote_desktop_settings))?$remote_desktop_settings['allowed_desktop_servers']:array();
		if (count($allowed_servers)>0) {
			foreach ($servers as $id => $server) {
				// can be server id or fqdn
				if (! in_array($server->id, $allowed_servers) && ! in_array($server->fqdn, $allowed_servers) && ! in_array($server->getExternalName(), $allowed_servers))
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
		$desktop_server = $servers[$server_id];
		if ($bypass_server_restrictions_ !== true) {
			$desktop_server = null;
			$restricted_servers = $this->user->get_published_servers_for_user();
			
			if (count($restricted_servers) == 0) {
				// if there is no published server for this user, we reverse the process
				// we delete from the available list all the published servers
				$restricted_servers = $this->user->get_all_published_servers();
			}
			
			foreach($servers as $server) {
				$server_id = $server->getAttribute('id');
				if (in_array($server_id, $restricted_servers)) {
					$desktop_server = $server;
					break;
				}
			}
			
			if ($desktop_server == null) {
				Logger::error('main', 'No desktop server available for user '.$this->user->getAttribute('login').": servers group restriction");
				return false;
			}
		}
		
		$this->desktop_server = $desktop_server;
		
		return true;
	}

	public function chooseApplicationServers($bypass_server_restrictions_ = true) {
		$have_webapp = false;
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::chooseApplicationServers - User is not authenticated, aborting');
			return false;
		}
		
		$applications = array();
		$all_applications = $this->user->applications();
		
		// Filter applications by type
		foreach($all_applications as $application) {
			if(in_array($application->getAttribute('type'), array(Server::SERVER_TYPE_LINUX, Server::SERVER_TYPE_WINDOWS))){
				array_push($applications, $application);
			}
			if($application->getAttribute('type') == 'webapp')
				$have_webapp = true;
		}

		$nb_application_to_publish = count($applications);
		
		if ($nb_application_to_publish == 0) {
			if ($have_webapp == true)
				return array();
			
			$event = new SessionStart(array('user' => $this->user));
			$event->setAttribute('ok', false);
			$event->setAttribute('error', 'No available application');
			$event->emit();

			Logger::error('main', 'SessionManagement::choose_applications_servers - No applications published for User "'.$this->user->getAttribute('login').'", aborting');
			return false;
		}
		
		$servers = array();
		$servers_available = $this->getAvailableApplicationServers();
		
		if ($bypass_server_restrictions_ !== true) {
			$restricted_servers = $this->user->get_published_servers_for_user();
			if (empty($restricted_servers)) {
				$restricted_servers = $this->user->get_all_published_servers();
			}
			
			foreach($servers_available as $server_id => $server) {
				if (! in_array($server->getAttribute('id'), $restricted_servers)) {
					unset($servers_available[$server_id]);
				}
			}
		}
		
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
	
	public function chooseWebAppServers() {
		if (! $this->user) {
			Logger::error('main', 'SessionManagement::chooseWebAppServers - User is not authenticated, aborting');
			return false;
		}
		
		$applications = array();
		$all_applications = $this->user->applications();
		
		// Filter applications by type
		foreach($all_applications as $application) {
			if($application->getAttribute('type') == 'webapp'){
				$applications[] = $application;
		}
		}

		if (count($applications) == 0) {
			$event = new SessionStart(array('user' => $this->user));
			$event->setAttribute('ok', false);
			$event->setAttribute('error', _('No available application'));
			$event->emit();

			Logger::info('main', 'No webapp published for User "'.$this->user->getAttribute('login').'"');
			return false;
		} else {
			Logger::debug('main', 'SessionManagement::chooseWebAppServers - found '.count($applications).' webapp(s)');
		}
		
		foreach($applications as $application)
			$this->applications[$application->getAttribute('id')] = $application;

		$servers = Abstract_Server::load_available_by_role(Server::SERVER_ROLE_WEBAPPS);
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
	
	public function prepareFSAccess($session_) {
		if (! array_key_exists(Server::SERVER_ROLE_FS, $session_->servers)) {
			return true;
		}
		
		$default_settings = $this->user->getSessionSettings('session_settings_defaults');
		$need_valid_profile = ($default_settings['start_without_profile'] == 1);
		$need_all_sharedFolders = ($default_settings['start_without_all_sharedfolders'] == 1);
		$user_login_fs = $session_->settings['fs_access_login'];
		$user_password_fs = $session_->settings['fs_access_password'];
		
		foreach ($session_->servers[Server::SERVER_ROLE_FS] as $server_id => $netfolders) {
			$mounts = array();
			$server = Abstract_Server::load($server_id);
			if (! $server)
				continue;
			
			foreach ($netfolders as $netfolder) {
				$quota = 0;
				$mode = 'rw';
				if ($netfolder['type'] == 'profile')
					$quota = $default_settings['quota'];
				
				if ($netfolder['type'] == 'sharedfolder')
					$mode = $netfolder['mode'];
				
				$mounts[$netfolder['dir']] = array('quota' => $quota, 'mode' => $mode);
			}
			
			if (! $server->orderFSAccessEnable($user_login_fs, $user_password_fs, $mounts)) {
				if (($need_valid_profile && $netfolder['type'] == 'profile') || ($need_all_sharedFolders && $netfolder['type'] == 'sharedfolder')) {
					Logger::error('main', 'SessionManagement::prepareFSAccess - Cannot enable FS access for User \''.$this->user->getAttribute('login').'\' on Server \''.$server->fqdn.'\', aborting');
					$session->orderDeletion(true, Session::SESSION_END_STATUS_ERROR);
					return false;
				}
			}
		}
		
		return true;
	}
	
	public function prepareWebappsAccess($session_) {
		if (! array_key_exists(Server::SERVER_ROLE_WEBAPPS, $session_->servers)) {
			return true;
		}
		
		$prepare_servers = array();
		foreach ($session_->servers[Server::SERVER_ROLE_WEBAPPS] as $server_id => $data) {
			$prepare_servers[] = $server_id;
		}
		
		$user_login_webapps = $session_->settings['webapps_access_login'];
		$user_password_webapps = $session_->settings['webapps_access_password'];

		$count_prepare_servers = 0;
		foreach ($prepare_servers as $prepare_server) {
			$count_prepare_servers++;
			
			$server = Abstract_Server::load($prepare_server);
			if (! $server)
				continue;
			
			if (! array_key_exists(Server::SERVER_ROLE_WEBAPPS, $server->getRoles()))
				continue;
			
			$dom = new DomDocument('1.0', 'utf-8');
			$session_node = $dom->createElement('session');
			$session_node->setAttribute('id', $session_->id);
			$session_node->setAttribute('mode', Session::MODE_APPLICATIONS);
			$user_node = $dom->createElement('user');
			$user_node->setAttribute('login', $user_login_webapps);
			$user_node->setAttribute('password', $user_password_webapps);
			$user_node->setAttribute('USER_LOGIN', $_POST['login']);
			$user_node->setAttribute('USER_PASSWD', $_POST['password']);
			$user_node->setAttribute('displayName', $this->user->getAttribute('displayname'));
			$session_node->appendChild($user_node);
			
			$applications_node = $dom->createElement('applications');
			foreach ($session_->getPublishedApplications() as $application) {
				if ($application->getAttribute('type') != 'webapp')
					continue;
				
				$application_node = $dom->createElement('application');
				$application_node->setAttribute('id', $application->getAttribute('id'));
				$application_node->setAttribute('type', 'webapp');
				$application_node->setAttribute('name', $application->getAttribute('name'));
				$applications_node->appendChild($application_node);
			}
			$session_node->appendChild($applications_node);
				
			$dom->appendChild($session_node);
			
			$this->appendToSessionCreateXML($dom);
			
			$xml = $dom->saveXML();
			
			$ret_xml = query_url_post_xml($server->getBaseURL().'/webapps/session/create', $xml);
			$ret = $this->parseSessionCreate($ret_xml);
			if (! $ret) {
				Logger::critical('main', 'SessionManagement::prepareWebappsAccess - Unable to create Session \''.$session_->id.'\' for User \''.$session_->user_login.'\' on Server \''.$server->fqdn.'\', aborting');
				$session_->orderDeletion(true, Session::SESSION_END_STATUS_ERROR);
				
				return false;
			}
			
			$ret_dom = new DomDocument('1.0', 'utf-8');
			$ret_buf = @$ret_dom->loadXML($ret_xml);
			$node = $ret_dom->getElementsByTagname('session')->item(0);
			$webapps_url = $node->getAttribute('webapps-scheme').'://'.$server->getExternalName().':'.$node->getAttribute('webapps-port');
			$session_->settings['webapps-url'] = $webapps_url;
			
			// Make sure that session object is uptodate
			$buf = Abstract_Session::load($session_->id);
			$buf->setServerStatus($server->id, Session::SESSION_STATUS_READY, NULL, Server::SERVER_ROLE_WEBAPPS);
			$buf->settings['webapps-url'] = $webapps_url;
			Abstract_Session::save($buf);
		}
		
		return true;
	}


	public function prepareAPSAccess($session_) {
		$remote_desktop_settings = $this->user->getSessionSettings('remote_desktop_settings');
		$default_settings = $this->user->getSessionSettings('session_settings_defaults');
		$prepare_servers = array();
		
		# No_desktop option management
		if (isset($this->no_desktop) && $this->no_desktop === true) {
			if ($authorize_no_desktop  === true)
				$no_desktop_process = 1;
			else
				Logger::warning('main', 'SessionManagement::prepareAPSAccess - Cannot apply no_desktop parameter because policy forbid it');
		}
		
		if ($default_settings['use_known_drives'] == 1)
			$use_known_drives = 'true';
		
		$profile_mode = $default_settings['profile_mode'];
		$use_local_ime = $session_->settings['use_local_ime'];
		$desktop_icons = $remote_desktop_settings['desktop_icons'];
		$need_valid_profile = ($default_settings['start_without_profile'] == 0);
		$user_login_aps = $session_->settings['aps_access_login'];
		$user_password_aps = $session_->settings['aps_access_password'];
		$user_login_fs = $session_->settings['fs_access_login'];
		$user_password_fs = $session_->settings['fs_access_password'];
		
		$remote_desktop_settings = $this->user->getSessionSettings('remote_desktop_settings');
		$allow_external_applications = array_key_exists('allow_external_applications', $remote_desktop_settings) && $remote_desktop_settings['allow_external_applications'] == 1;
		if (isset($this->language))
			$locale = locale2unix($this->language);
		else
			$locale = $this->user->getLocale();
		
		if (isset($this->timezone) && $this->timezone != '')
			$timezone = $this->timezone;
	
		if ($session_->mode == Session::MODE_DESKTOP) {
			$have_external_apps = false;
			if (array_key_exists(Server::SERVER_ROLE_APS, $session_->servers)) {
				$have_external_apps |= (count($session_->servers[Server::SERVER_ROLE_APS]) > 1);
			}
			
			if (array_key_exists(Server::SERVER_ROLE_WEBAPPS, $session_->servers)) {
				$have_external_apps |= (count($session_->servers[Server::SERVER_ROLE_WEBAPPS]) > 0);
			}
			
			if ($session_->mode == Session::MODE_DESKTOP && $allow_external_applications && $have_external_apps) {
				$external_apps_token = new Token(gen_unique_string());
				$external_apps_token->type = 'external_apps';
				$external_apps_token->link_to = $session_->id;
				$external_apps_token->valid_until = 0;
				Abstract_Token::save($external_apps_token);
			}
			
			$prepare_servers[] = $session_->server;
		}
		
		if ($session_->mode == Session::MODE_APPLICATIONS || ($session_->mode == Session::MODE_DESKTOP && $allow_external_applications)) {
			foreach ($session_->servers[Server::SERVER_ROLE_APS] as $server_id => $data) {
				if ($session_->mode == Session::MODE_DESKTOP && $allow_external_applications && $server_id == $session_->server)
					continue;

				$prepare_servers[] = $server_id;
			}
		}

		$count_prepare_servers = 0;
		foreach ($prepare_servers as $prepare_server) {
			$count_prepare_servers++;
			
			$server = Abstract_Server::load($prepare_server);
			if (! $server)
				continue;

			if (! array_key_exists(Server::SERVER_ROLE_APS, $server->getRoles()))
				continue;
			
			$server_applications = $server->getApplications();
			if (! is_array($server_applications))
				$server_applications = array();
			
			$available_applications = array();
			foreach ($server_applications as $server_application)
				$available_applications[] = $server_application->getAttribute('id');
			
			$dom = new DomDocument('1.0', 'utf-8');
			
			$session_node = $dom->createElement('session');
			$session_node->setAttribute('id', $session_->id);
			$session_node->setAttribute('mode', (($session_->mode == Session::MODE_DESKTOP && $count_prepare_servers == 1)?Session::MODE_DESKTOP:Session::MODE_APPLICATIONS));
			
			// OvdShell Configuration
			$shell_node = $dom->createElement('shell');
			$session_node->appendChild($shell_node);
			
			if (isset($external_apps_token)) {
				$setting_node = $dom->createElement('setting');
				$setting_node->setAttribute('name', 'external_apps_token');
				$setting_node->setAttribute('value', $external_apps_token->id);
				$shell_node->appendChild($setting_node);
			}
			
			if (isset($this->start_apps) && is_array($this->start_apps)) {
				$start_apps = $this->start_apps;
				
				$applicationDB = ApplicationDB::getInstance();
				
				foreach ($start_apps as $start_app) {
					$app = $applicationDB->import($start_app['id']);
					
					if (! is_object($app)) {
						Logger::error('main', 'SessionManagement::prepareAPSAccess - No such application for id \''.$start_app['id'].'\'');
						throw_response(SERVICE_NOT_AVAILABLE);
					}
					
					$apps = $session_->getPublishedApplications();
					
					$ok = false;
					foreach ($apps as $user_app) {
						if ($user_app->getAttribute('id') == $start_app['id']) {
							$ok = true;
							break;
						}
					}
					
					if ($ok === false) {
						Logger::error('main', 'SessionManagement::prepareAPSAccess - Application not available for user \''.$user->getAttribute('login').'\' id \''.$start_app['id'].'\'');
						return false;
					}
				}
			}
			
			
			foreach (array('no_desktop_process', 'use_known_drives', 'profile_mode', 'use_local_ime') as $parameter) {
				if (! isset($$parameter))
					continue;
				
				$setting_node = $dom->createElement('setting');
				$setting_node->setAttribute('name', $parameter);
				$setting_node->setAttribute('value', $$parameter);
				$shell_node->appendChild($setting_node);
			}
			
			foreach (array('desktop_icons', 'locale', 'timezone', 'need_valid_profile') as $parameter) {
				if (! isset($$parameter))
					continue;
				
				$parameter_node = $dom->createElement('parameter');
				$parameter_node->setAttribute('name', $parameter);
				$parameter_node->setAttribute('value', $$parameter);
				$session_node->appendChild($parameter_node);
			}
		
			$scripts = $this->user->scripts();
			if (is_array($scripts)) {
				$scripts_node = $dom->createElement('scripts');
				foreach ($scripts as $script) {
					$script_node = $dom->createElement('script');
					$script_node->setAttribute('id', $script->getAttribute('id'));
					$script_node->setAttribute('type', $script->getAttribute('type'));
					$script_node->setAttribute('name', $script->getAttribute('name'));
					$scripts_node->appendChild($script_node);
				}
				$shell_node->appendChild($scripts_node);
			}
			
			$user_node = $dom->createElement('user');
			$user_node->setAttribute('login', $user_login_aps);
			$user_node->setAttribute('password', $user_password_aps);
			$user_node->setAttribute('displayName', $this->user->getAttribute('displayname'));
			$session_node->appendChild($user_node);
			
			if (array_key_exists(Server::SERVER_ROLE_FS, $session_->servers)) {
				foreach ($session_->servers[Server::SERVER_ROLE_FS] as $server_id => $netfolders) {
					$fs_server = Abstract_Server::load($server_id);
					foreach ($netfolders as $netfolder) {
						$uri = 'cifs://'.$fs_server->getExternalName().'/'.$netfolder['dir'];
						
						$netfolder_node = $dom->createElement($netfolder['type']);
						$netfolder_node->setAttribute('rid', $netfolder['rid']);
						$netfolder_node->setAttribute('uri', $uri);
						
						if ($netfolder['type'] == 'profile') {
							$netfolder_node->setAttribute('profile_mode', $profile_mode);
						}
						if ($netfolder['type'] == 'sharedfolder') {
							$netfolder_node->setAttribute('name', $netfolder['name']);
							$netfolder_node->setAttribute('mode', $netfolder['mode']);
						}
						
						$netfolder_node->setAttribute('login', $user_login_fs);
						$netfolder_node->setAttribute('password', $user_password_fs);
						$session_node->appendChild($netfolder_node);
					}
				}
			}
			
			foreach ($this->forced_sharedfolders as $share) {
				$sharedfolder_node = $dom->createElement('sharedfolder');
				$sharedfolder_node->setAttribute('rid', $share['rid']);
				$sharedfolder_node->setAttribute('uri', $share['uri']);
				$sharedfolder_node->setAttribute('name', $share['name']);
				if (array_key_exists('login', $share) && array_key_exists('password', $share)) {
					$sharedfolder_node->setAttribute('login', $share['login']);
					$sharedfolder_node->setAttribute('password', $share['password']);
				}
				
				$session_node->appendChild($sharedfolder_node);
			}
			
			// Pass custom shared folders to the server
			foreach (Plugin::dispatch('getSharedFolders', $server) as $plugin=>$results) {
				foreach ($results as $sharedfolder) {
					$sharedfolder_ok = true;
					$sharedfolder_node = $dom->createElement('sharedfolder');
					foreach (array('uri', 'name', 'rid') as $key) {
						if (array_key_exists($key, $sharedfolder)) {
							$sharedfolder_node->setAttribute($key, $sharedfolder[$key]);
						} else {
							Logger::error('main', 'SharedFolder is missing '.$key.' parameter in '.$plugin);
							$sharedfolder_ok = false;
						}
					}
					foreach (array('login', 'password') as $key) {
						if (array_key_exists($key, $sharedfolder)) {
							$sharedfolder_node->setAttribute($key, $sharedfolder[$key]);
						}
					}
					if (($have_login = array_key_exists('login', $sharedfolder)) != array_key_exists('password', $sharedfolder) && $have_login) {
						Logger::error('main', 'SharedFolder login and password are both required if one is present in '.$plugin);
						$sharedfolder_ok = false;
					}
					if ($sharedfolder_ok) {
						$session_node->appendChild($sharedfolder_node);
					}
				}
			}
			
			foreach ($session_->getPublishedApplications() as $application) {
				if ($application->getAttribute('type') != $server->getAttribute('type'))
					continue;
				
				if (! in_array($application->getAttribute('id'), $available_applications))
					continue;
				
				$application_node = $dom->createElement('application');
				$application_node->setAttribute('id', $application->getAttribute('id'));
				$application_node->setAttribute('name', $application->getAttribute('name'));
				if (! $application->getAttribute('static'))
					$application_node->setAttribute('mode', 'local');
				else
					$application_node->setAttribute('mode', 'static');
				
				$session_node->appendChild($application_node);
			}
			
			if (isset($start_apps) && is_array($start_apps)) {
				$start_node = $dom->createElement('start');
				foreach ($start_apps as $start_app) {
					$application_node = $dom->createElement('application');
					$application_node->setAttribute('app_id', $start_app['id']);
					if (array_key_exists('arg', $start_app) && ! is_null($start_app['arg']))
						$application_node->setAttribute('arg', $start_app['arg']);
					
					if (array_key_exists('file', $start_app)) {
						$file_node = $dom->createElement('file');
						$file_node->setAttribute('type', $start_app['file']['type']);
						$file_node->setAttribute('location', $start_app['file']['location']);
						$file_node->setAttribute('path', $start_app['file']['path']);
						
						$application_node->appendChild($file_node);
					}
					
					$start_node->appendChild($application_node);
				}
				$session_node->appendChild($start_node);
			}
			
			$dom->appendChild($session_node);
			
			$this->appendToSessionCreateXML($dom);
			
			$xml = $dom->saveXML();
			
			$session_create_xml = query_url_post_xml($server->getBaseURL().'/aps/session/create', $xml);
			$ret = $this->parseSessionCreate($session_create_xml);
			if (! $ret) {
				Logger::critical('main', 'SessionManagement::prepareAPSAccess - Unable to create Session \''.$session->id.'\' for User \''.$session->user_login.'\' on Server \''.$server->fqdn.'\', aborting');
				$session->orderDeletion(true, Session::SESSION_END_STATUS_ERROR);
				return false;
			}
		}
		
		return true;
		
	}
	
	public function prepareSession($session) {
		if (! $this->prepareFSAccess($session)) {
			return false;
		}
		
		if (! $this->prepareAPSAccess($session)) {
			return false;
		}
		
		if (! $this->prepareWebappsAccess($session)) {
			return false;
		}
		
		return true;
	}
}
