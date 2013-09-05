<?php
/**
 * Copyright (C) 2012-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
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

require_once(dirname(__FILE__).'/includes/core.inc.php');

class OvdAdminSoap {
	private $user_login;
	private $prefs;
	private $logged_as_ovd_user = false;
	private $user_rights = array();
	
	public function __construct() {
		if (! array_key_exists('PHP_AUTH_USER', $_SERVER) || ! array_key_exists('PHP_AUTH_PW', $_SERVER)) {
			throw new SoapFault('auth_failed', 'Authentication Failed');
		}
		
		$ret = $this->authenticate_admin($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		if ($ret !== true) {
			$ret = $this->authenticate_ovd_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
			if ($ret !== true) {
				throw new SoapFault('auth_failed', 'Authentication Failed');
			}
			
			$this->logged_as_ovd_user = true;
		}
		
		$this->user_login = $_SERVER['PHP_AUTH_USER'];
		$this->prefs = Preferences::getInstance();
		
		// Load the policy to apply restrictions
		if ($this->logged_as_ovd_user) { // if non admin user_profiles
			$userDB = UserDB::getInstance();
			$user = $userDB->import($this->user_login);
			
			$policy = $user->getPolicy();
		}
		else { // Ovd admin
			$policies = $this->prefs->get('general', 'policy');
			$default_policy = $policies['default_policy'];
			$elements = $this->prefs->getElements('general', 'policy');
			if (array_key_exists('default_policy', $elements) == false) {
				Logger::error('api', 'User::getPolicy, default_policy not found on general policy');
				return array();
			}
			
			$policy = $elements['default_policy']->content_available;
			foreach ($policy as $k => $v) {
				$policy[$v] = true;
			}
		}
		
		$this->user_rights = $policy;
	}
	
	private static function authenticate_admin($login_, $password_) {
		if (! defined('SESSIONMANAGER_ADMIN_LOGIN') or ! defined('SESSIONMANAGER_ADMIN_PASSWORD')) {
			return false;
		}
		
		if ($login_ != SESSIONMANAGER_ADMIN_LOGIN or md5($password_) != SESSIONMANAGER_ADMIN_PASSWORD) {
			return false;
		}
		
		return true;
	}
	
	private static function authenticate_ovd_user($login_, $password_) {
		if (Preferences::fileExists() === false) {
			Logger::info('api', 'Admin authentication: authenticate_ovd_user the system is not configured');
			return false;
		}
		
		if (Preferences::moduleIsEnabled('UserDB') === false) {
			Logger::info('api', 'Admin authentication: module UserDB is not enabled');
			return false;
		}
		
		$userDB = UserDB::getInstance();
		$user = $userDB->import($login_);
		if (!is_object($user)) {
			Logger::info('api', 'Admin authentication: authenticate_ovd_user authentication failed: user(login='.$login_.') does not exist');
			return false;
		}
		
		$auth = $userDB->authenticate($user, $password_);
		if (!$auth) {
			Logger::info('api', 'Admin authentication: authentication failed for user(login='.$login_.'): wrong password');
			return false;
		}
		
		// the user exists, does he have right to log in the admin panel ?
		$policy = $user->getPolicy();
		if (! array_key_exists('canUseAdminPanel', $policy) or $policy['canUseAdminPanel'] !== true) {
			Logger::info('api', 'Admin authentication: failed to log in '.$login_.' : access denied to admin panel');
			return false;
		}
		
		return true;
	}
	
	private function check_authorized($key_) {
		if (! array_key_exists($key_, $this->user_rights) || $this->user_rights[$key_] !== true) {
			throw new SoapFault('not_authorized', 'Not Authorized "'.$key_.'"');
		}
	}
	
	public function test_link_connected() {
		return true;
	}
	
	public function getInitialConfiguration() {
		$c = array(
			'version' => OVD_SM_VERSION,
			'max_items_per_page' => $this->prefs->get('general', 'max_items_per_page'),
			'admin_language' => $this->prefs->get('general', 'admin_language'),
			'system_inited' => file_exists(SESSIONMANAGER_CONFFILE_SERIALIZED),
			'system_in_maintenance' => $this->prefs->get('general', 'system_in_maintenance'),
		);
		
		if (file_exists(SESSIONMANAGER_CONFFILE_SERIALIZED)) {
			$c['settings_last_backup'] = @filemtime(SESSIONMANAGER_CONFFILE_SERIALIZED);
		}
		
		if (Preferences::moduleIsEnabled('UserDB')) {
			$userDB = UserDB::getInstance();
			$b = array(
				'name' => $userDB->prettyName(),
				'writable' => $userDB->isWriteable(),
			);
		
			$c['UserDB'] = $b;
		}
		
		if (Preferences::moduleIsEnabled('UserGroupDB')) {
			$userGroupDB = UserGroupDB::getInstance();
			$b = array(
				'name' => $userGroupDB->prettyName(),
				'writable' => $userGroupDB->isWriteable(),
			);
		
			$c['UserGroupDB'] = $b;
		}
		
		if (Preferences::moduleIsEnabled('ApplicationDB')) {
			$applicationDB = ApplicationDB::getInstance();
			$b = array(
				'name' => $applicationDB->prettyName(),
				'writable' => $applicationDB->isWriteable(),
			);
		
			$c['ApplicationDB'] = $b;
		}
		
		if (Preferences::moduleIsEnabled('UserGroupDBDynamic')) {
			$c['UserGroupDBDynamic'] = true;
		}
		
		if (Preferences::moduleIsEnabled('UserGroupDBDynamicCached')) {
			$c['UserGroupDBDynamicCached'] = true;
		}
		
		if (Preferences::moduleIsEnabled('ProfileDB')) {
			$c['ProfileDB'] = true;
		}
		if (Preferences::moduleIsEnabled('SharedFolderDB')) {
			$c['SharedFolderDB'] = true;
		}
		
		$c['policy'] = $this->user_rights;
		
		return $c;
	}
	
	private function log_action($name_, $info_ = array()) {
		$where = $_SERVER['REMOTE_ADDR'];
		
		$headers = apache_request_headers();
		foreach($headers as $k => $v) {
			// Internet explorer send http headers in lower cases...
			$k = strtolower($k);
			if ($k == 'x-forwarded-for') {
				$where = $v;
			}
		}
		
		$a = new AdminAction(null, $this->user_login, $name_, $where);
		$a->infos = $info_;
		Abstract_AdminAction::save($a);
	}

	public function system_switch_maintenance($value_) {
		$this->check_authorized('manageServers');
		
		$prefs = new Preferences_admin();
		if (! $prefs) {
			return false;
		}
		
		$prefs->set('general', 'system_in_maintenance', (($value_ === false)?1:0));
		$prefs->backup();
		$this->log_action('system_switch_maintenance', array('value' => $value_));
		
		return true;
	}
	
	public function administrator_password_set($password_) {
		$this->check_authorized('manageConfiguration');
		
		$contents_conf = file_get_contents(SESSIONMANAGER_CONF_FILE, LOCK_EX);
		$contents = explode("\n", $contents_conf);
		
		foreach ($contents as $k => $line) {
			if (preg_match('/^define\([\'"]([^,\'"]+)[\'"],[\ ]{0,1}[\'"]([^,]+)[\'"]\);$/', $line, $matches)) {
				if ($matches[1] == 'SESSIONMANAGER_ADMIN_PASSWORD') {
					$contents[$k] = "define('SESSIONMANAGER_ADMIN_PASSWORD', '".md5($password_)."');";
					break;
				}
			}
		}
		
		$implode = implode("\n", $contents);
		$ret = file_put_contents(SESSIONMANAGER_CONF_FILE, $implode, LOCK_EX);
		if ($ret === false) {
			return $ret;
		}
		
		$this->log_action('administrator_password_set');
		return true;
	}
	
	private static function get_pref_element_type($element_) {
		$b = get_class($element_);
		
		$c = substr($b, strlen('ConfigElement_'));
		if ($c == 'dictionary') {
			$c = 'hash';
		}
		
		return $c;
	}
	
	private static function pref_element2dict($element_) {
		$e = array(
			'id' => $element_->id,
			'value' => $element_->content,
			'type' => self::get_pref_element_type($element_),
			'default_value' => $element_->content_default,
		);
		
		if ($e['type'] == 'select') {
			$refs = $element_->references;
			if (count($refs) > 0) {
				$e['references'] = $refs;
			}
		}
		
		if ($element_->content_available && is_array($element_->content_available)) {
			$e['possible_values'] = $element_->content_available;
		}
		
		return $e;
	}
	
	private static function pref_elements2dict($elements) {
		$ret = array('is_node_tree' => true);
		
		foreach ($elements as $container => $elements2) {
			if (is_array($elements2)) {
				$ret[$container] = self::pref_elements2dict($elements2);
			}
			else {
				$e = self::pref_element2dict($elements2);
				$ret[$e['id']] = $e;
			}
		}
		
		return $ret;
	}
	
	private static function import_elements_content_from_dict($elements_, $settings_) {
		$diff = array();
		foreach ($elements_ as $container => $elements) {
			if (! array_key_exists($container, $settings_)) {
				continue;
			}
			
			if (is_array($elements)) {
				$diff_sub = self::import_elements_content_from_dict($elements, $settings_[$container]);
				foreach($diff_sub as $k => $v) {
					$diff[$container.'.'.$k] = $v;
				}
			}
			else {
				if ($elements->contentEqualsTo($settings_[$container])) {
					continue;
				}
				
				$c_old = $elements->content;
				$c_new = $settings_[$container];
				if (self::get_pref_element_type($elements) == 'password') {
					$c_old = '*****';
					$c_new = '*******';
				}
				
				$diff[$container] = array('old' => $c_old, 'new' => $c_new);
				
				$elements->content = $settings_[$container];
			}
		}
		
		return $diff;
	}
	
	public function settings_get() {
		$this->check_authorized('viewConfiguration');
		
		$keys = $this->prefs->getKeys();
		
		$ret = array();
		
		foreach ($keys as $key_name) {
			//$elements = $this->prefs->elements[$key_name];
			$ret[$key_name] = self::pref_elements2dict($this->prefs->elements[$key_name]);
		}
		
		return $ret;
	}
	
	public function settings_set($settings_) {
		$this->check_authorized('manageConfiguration');
		
		// saving preferences
		$prefs = new Preferences_admin();
		
		$diff = array();
		
		$keys = $prefs->getKeys();
		foreach ($keys as $key_name) {
			if (! array_key_exists($key_name, $settings_)) {
				continue;
			}
			
			$diff_sub = $this->import_elements_content_from_dict($prefs->elements[$key_name], $settings_[$key_name]);
			foreach($diff_sub as $k => $v) {
				$diff[$key_name.'.'.$k] = $v;
			}
		}
		
		$ret = $prefs->isValid();
		if ( $ret !== true) {
			return false;
		}
		
		$ret = $prefs->backup();
		if ($ret <= 0) {
			return false;
		}
		
		$ret = $this->hooks_system_integration_changes($prefs, $diff);
		
		// configuration saved
		if (count($diff) > 0) {
			$this->log_action('settings_set', $diff);
		}
		return true;
	}
	
	public function settings_domain_integration_preview($settings_) {
		$this->check_authorized('manageConfiguration');
		
		// saving preferences
		$prefs = new Preferences_admin();
		
		$keys = $prefs->getKeys();
		foreach ($keys as $key_name) {
			if (! array_key_exists($key_name, $settings_)) {
				continue;
			}
			
			$this->import_elements_content_from_dict($prefs->elements[$key_name], $settings_[$key_name]);
		}
		
		$mod_user_name = 'UserDB_'.$prefs->get('UserDB','enable');
		$userDB = new $mod_user_name();
		if (! $userDB->prefsIsValid($prefs, $log)) {//??
			return $log;
		}
		
		$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
		$userGroupDB = new $mod_usergroup_name();
		if (! $userGroupDB->prefsIsValid($prefs, $log)) {
			return $log;
		}
		
		return $log;
	}
	
	private static function check_pref_change($diff_, $pattern_) {
		foreach($diff_ as $k => $v) {
			if (str_startswith($k, $pattern_)) {
				return true;
			}
		}
		
		return false;
	}
	
	private function hooks_system_integration_changes($prefs_, $diff_) {
		$userdb_changed_enable = (array_key_exists('UserDB.enable', $diff_));
		$usersgroupdb_changed_enable = (array_key_exists('UserGroupDB.enable', $diff_));
		$has_changed_u = self::check_pref_change($diff_, 'UserDB.'.$prefs_->get('UserDB', 'enable'));
		$has_changed_ug = self::check_pref_change($diff_, 'UserGroupDB.'.$prefs_->get('UserGroupDB', 'enable'));
		
		$userDB = UserDB::getInstance();
		$userGroupDB = UserGroupDB::getInstance();
		
		// Clean orphan user settings
		if ($userdb_changed_enable) {
			Abstract_User_Preferences::delete_all();
		}
		else if ($has_changed_u) {
			$users = Abstract_User_Preferences::get_users();
			$users_to_remove = array();
			foreach($users as $login) {
				$user = $userDB->import($login);
				if ($user) {
					continue;
				}
				
				array_push($users_to_remove, $login);
			}
			
			if (count($users_to_remove) > 0) {
				Abstract_User_Preferences::deleteByUserLogins($users_to_remove);
			}
		}
		
		// Clean orphan users group settings
		if ($usersgroupdb_changed_enable) {
			Abstract_UserGroup_Preferences::delete_all();
		}
		else if ($has_changed_ug) {
			$groups = Abstract_UserGroup_Preferences::get_usersgroups();
			$groups_to_remove = array();
			foreach($users as $group_id) {
				$group = $userGroupDB->import($group_id);
				if ($group) {
					continue;
				}
				
				array_push($groups_to_remove, $group);
			}
			
			if (count($groups_to_remove) > 0) {
				Abstract_UserGroup_Preferences::deleteByUserGroupIds($groups_to_remove);
			}
		}
		
		// Users groups rules
		if ($usersgroupdb_changed_enable) {
			Abstract_UserGroup_Rule::delete_all();
		}
		else if ($has_changed_ug) {
			$groups = Abstract_UserGroup_Rule::get_usersgroups();
			$groups_to_remove = array();
			foreach($users as $group_id) {
				$group = $userGroupDB->import($group_id);
				if ($group) {
					continue;
				}
				
				array_push($groups_to_remove, $group);
			}
			
			if (count($groups_to_remove) > 0) {
				Abstract_UserGroup_Rule::deleteByUserGroupIds($groups_to_remove);
			}
		}
		
		// Unset default usersgroup
		if ($usersgroupdb_changed_enable) {
			$prefs_->set('general', 'user_default_group', NULL);
			$prefs_->backup();
		}
		else if ($has_changed_ug) {
			$v = $prefs_->get('general', 'user_default_group');
			if (! is_null($v)) {
				$group = $userGroupDB->import($v);
				if (! $group) {
					$prefs_->set('general', 'user_default_group', NULL);
					$prefs_->backup();
				}
			}
		}
		
		// Clean users - usersgroup liaisons
		if ($userdb_changed_enable) {
			// Remove Users from user groups
			$ret = Abstract_Liaison::delete('UsersGroup', NULL, NULL);
			if (! $ret) {
				Logger::error('api', 'Unable to remove Users from UserGroups');
			}
		}
		else if ($userGroupDB->isWriteable() && ($has_changed_u && $has_changed_ug)) {
			$liaisons = Abstract_Liaison::load('UsersGroup', NULL, NULL);
			foreach ($liaisons as $liaison) {
				$must_remove_liaisons = false;
				$user = $userDB->import($liaison->element);
				if (! $user) {
					$must_remove_liaisons = true;
				}
				
				if (! $must_remove_liaisons) {
					$group = $userGroupDB->import($liaison->group);
					if (! $group) {
						$must_remove_liaisons = true;
					}
				}
				
				if (! $must_remove_liaisons) {
					continue;
				}
				
				// Delete this liaison
				$liaisons = Abstract_Liaison::load('UsersGroup', $liaison->element, $liaison->group);
			}
		}
		
		// Clean users - profile liaisons
		if ($userdb_changed_enable) {
			$ret = Abstract_Liaison::delete('UserProfile', NULL, NULL);
			if (! $ret) {
				Logger::error('api', 'Unable to remove Users - Profiles matches');
			}
		}
		else if ($has_changed_u) {
			$liaisons = Abstract_Liaison::load('UserProfile', NULL, NULL);
			foreach ($liaisons as $liaison) {
				// check if profile must become orphan
				$user = $userDB->import($liaison->element);
				if ($user) {
					continue;
				}
				
				// Delete this liaison
				$liaisons = Abstract_Liaison::load('UserProfile', $liaison->element, $liaison->group);
			}
		}
		
		// Clean publication liaisons
		if ($usersgroupdb_changed_enable) {
			// Remove Publications
			$ret = Abstract_Liaison::delete('UsersGroupApplicationsGroup', NULL, NULL);
			if (! $ret) {
				Logger::error('api', 'Unable to remove Publications');
			}
		}
		else if ($has_changed_ug) {
			$liaisons = Abstract_Liaison::load('UsersGroupApplicationsGroup', NULL, NULL);
			foreach ($liaisons as $liaison) {
				$group = $userGroupDB->import($liaison->element);
				if ($group) {
					continue;
				}
				
				// Delete this liaison
				$liaisons = Abstract_Liaison::load('UsersGroupApplicationsGroup', $liaison->element, $liaison->group);
			}
		}
		
		if (in_array('SharedFolderDB', $prefs_->get('general', 'module_enable'))) {
			$sharedfolderdb = SharedFolderDB::getInstance();
			
			// Clean usersgroup - profile liaisons
			if ($usersgroupdb_changed_enable) {
				$sharedfolderdb->clear_publications();
			}
			else if ($has_changed_ug) {
				// TODO:check in each publication if users group still exists. If not: delete the publication
			}
		}
	}
	
	public function servers_list($filter = null) {
		$this->check_authorized('viewServers');
		
		if ($filter == 'online') {
			$servers = Abstract_Server::load_by_status(Server::SERVER_STATUS_ONLINE);
		}
		else if ($filter == 'unregistered') {
			$servers = Abstract_Server::load_registered(false);
		}
		else if ($filter == 'role_aps') {
			$servers = Abstract_Server::load_available_by_role(Server::SERVER_ROLE_APS, true);
		}
		else {
			$servers = Abstract_Server::load_registered(true);
		}
		
		$ret = array();
		foreach($servers as $server) {
			if (! $server->getAttribute('registered')) {
				if ($server->getAttribute('type') == '')
					$server->isOK();
				
				if ($server->getAttribute('cpu_model') == '')
					$server->getMonitoring();
				
				Abstract_Server::save($server);
			}
		
			$s = array(
				'id' => $server->id,
				'fqdn' => $server->fqdn,
				'status' => $server->getAttribute('status'),
				'registered' => $server->getAttribute('registered'),
				'locked' => $server->getAttribute('locked'),
				'type' => $server->getAttribute('type'),
				'version' => $server->getAttribute('version'),
				
				'cpu_model' => $server->getAttribute('cpu_model'),
				'cpu_nb_cores' => $server->getAttribute('cpu_nb_cores'),
				'ram_total' => $server->getAttribute('ram_total'),
				'cpu_load' => $server->getAttribute('cpu_load'),
				'ram_used' => $server->getAttribute('ram_used'),
				'timestamp' => $server->getAttribute('timestamp'),
			);
			
			if (! $server->getAttribute('registered')) {
				$s['can_register'] = $server->isOK();
			}
			
			foreach(array('roles', 'roles_disabled', 'display_name', 'external_name', 'rdp_port', 'max_sessions', 'ulteo_system', 'windows_domain', 'disk_total',
			'disk_free') as $key) {
				if ($server->hasAttribute($key))
					$s[$key] = $server->getAttribute($key);
			}
			
			$ret[$s['id']] = $s;
		}
		
		return $ret;
	}
	
	public function server_info($id_) {
		$this->check_authorized('viewServers');
		
		$server = Abstract_Server::load($id_);
		if (! $server)
			return null;
		
		if ($server->isOnline()) {
			$server->getMonitoring(); // to change ! because direct dialog with server
		}
		
		$s = array(
			'id' => $server->id,
			'fqdn' => $server->fqdn,
			'status' => $server->getAttribute('status'),
			'registered' => $server->getAttribute('registered'),
			'locked' => $server->getAttribute('locked'),
			'type' => $server->getAttribute('type'),
			'version' => $server->getAttribute('version'),
			
			'cpu_model' => $server->getAttribute('cpu_model'),
			'cpu_nb_cores' => $server->getAttribute('cpu_nb_cores'),
			'ram_total' => $server->getAttribute('ram_total'),
			'cpu_load' => $server->getAttribute('cpu_load'),
			'ram_used' => $server->getAttribute('ram_used'),
			'timestamp' => $server->getAttribute('timestamp'),
		);
		
		foreach(array('roles', 'roles_disabled', 'display_name', 'external_name', 'rdp_port', 'max_sessions', 'ulteo_system', 'windows_domain', 'disk_total',
		'disk_free') as $key) {
			if ($server->hasAttribute($key))
				$s[$key] = $server->getAttribute($key);
		}
		
		$s['max_sessions_default'] = $server->getDefaultMaxSessions();
		
		$sessions = Abstract_Session::getByServer($server->id);
		$s['sessions_number'] = count($sessions);
		
		if (array_key_exists(Server::SERVER_ROLE_APS, $server->roles) && $server->roles[Server::SERVER_ROLE_APS] === true) {
			if ($server->isOnline()) {
				$buf = $server->updateApplications();  // to change ! because direct dialog with server
				if (! $buf)
					popup_error(_('Cannot list available applications')); // to change ?
			}
			
			$applications = array();
			$ls = Abstract_Liaison::load('ApplicationServer', NULL, $id_);
			if (! is_array($ls)) {
				Logger::error('api', 'SERVER::getApplications elements is not array');
			}
			else {
				$applicationDB = ApplicationDB::getInstance();
				$applications = array();
				foreach ($ls as $l) {
					$app = $applicationDB->import($l->element);
					if (! $app) {
						continue;
					}
					
					$applications[$app->getAttribute('id')]= $app->getAttribute('name');
				}
			}
			
			$s['applications'] = $applications;
		}
		
		if (array_key_exists(Server::SERVER_ROLE_FS, $server->roles) && $server->roles[Server::SERVER_ROLE_FS] === true) {
			if (Preferences::moduleIsEnabled('SharedFolderDB')) {
				$sharedfolderdb = SharedFolderDB::getInstance();
				$networkfolders = $sharedfolderdb->importFromServer($server->id);
				
				foreach($networkfolders as $networkfolder) {
					if (! array_key_exists('shared_folders', $s))
						$s['shared_folders'] = array();
					
					$nf = array(
						'id' => $networkfolder->id,
						'name' => $networkfolder->name,
						'status' => $networkfolder->status,
					);
					
					
					$groups = $networkfolder->getPublishedUserGroups();
					if (is_array($groups) && count($groups) > 0) {
						$nf['groups'] = array();
						foreach ($groups as $mode => $groups2) {
							$nf['groups'][$mode] = array();
							foreach ($groups2 as $group) {
								$nf['groups'][$mode][$group->getUniqueID()] = $group->name;
							}
						}
					}
					
					$sessions = Abstract_Session::getByNetworkFolder($networkfolder->id);
					$nf['sessions_nb'] = count($sessions);
					
					$s['shared_folders'][$nf['id']] = $nf;
				}
			}
			
			if (Preferences::moduleIsEnabled('ProfileDB')) {
				$profiledb = ProfileDB::getInstance();
				$networkfolders = $profiledb->importFromServer($server->id);
				
				foreach($networkfolders as $networkfolder) {
					if (! array_key_exists('user_profiles', $s))
						$s['user_profiles'] = array();
					
					$up = array(
						'id' => $networkfolder->id,
						'status' => $networkfolder->status,
					);
					
					$users = $networkfolder->getUsers();
					if (is_array($users) && count($users) > 0) {
						$nf['users'] = array();
						foreach ($users as $a_user) {
							$up['users'][$a_user->getAttribute('login')] = $a_user->getAttribute('displayname');
						}
					}
					
					$sessions = Abstract_Session::getByNetworkFolder($networkfolder->id);
					$nf['sessions_nb'] = count($sessions);
					
					$s['user_profiles'][$up['id']] = $up;
				}
			}
			
			$networkfolders = Abstract_Network_Folder::load_orphans();
			foreach($networkfolders as $networkfolder) {
				if (! array_key_exists('orphan_folders', $s))
					$s['orphan_folders'] = array();
				
				$s['orphan_folders'][ $networkfolder->id] = $networkfolder->id;
			}
		}
		
		return $s;
	}
	
	public function server_remove($server_id_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		$sessions = Abstract_Session::getByServer($server->id);
		if (count($sessions) > 0) {
			Logger::error('api', sprintf('Unable to delete the server "%s" because there are active sessions on it.', $server->getDisplayName()));
			return false;
		}
		
		$server->orderDeletion();
		Abstract_Server::delete($server->id);
		$this->log_action('server_remove', array('id' => $server->id, 'name' => $server->getDisplayName()));
		return true;
	}
	
	public function server_register($server_id_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		$res = $server->register();
		if (! $res) {
			Logger::error('api', sprintf('Failed to register Server "%s"', $server->getDisplayName()));
			return false;
		}
		
		Abstract_Server::save($server);
		$this->log_action('server_register', array('id' => $server->id, 'name' => $server->getDisplayName()));
		return true;
	}
	
	public function server_switch_maintenance($server_id_, $maintenance_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		if ($maintenance_ === false && ! $server->isOnline()) {
			Logger::error('api', sprintf('Unable to swtich server "%s" to maintenance mode: server is not online', $server_id_));
			return false;
		}
		
		$maintenance_old = $server->getAttribute('locked');
		if ($maintenance_old === $maintenance_) {
			return true;
		}
		
		$server->setAttribute('locked', ($maintenance_ === true));
		Abstract_Server::save($server);
		$this->log_action('server_switch_maintenance', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => array(
			'old' => $maintenance_old,
			'new' => ($maintenance_ === true)
		)));
		return true;
	}
	
	public function server_set_available_sessions($server_id_, $nb_session_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		$nb_session_old = $server->getAttribute('max_sessions');
		if ($nb_session_old == $nb_session_) {
			return true;
		}
		
		$server->setAttribute('max_sessions', $nb_session_);
		
		Abstract_Server::save($server);
		$this->log_action('server_set_available_sessions', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => array(
			'old' => $nb_session_old,
			'new' => $nb_session_,
		)));
		return true;
	}
	
	public function server_set_display_name($server_id_, $display_name_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		$display_name_old = $server->getAttribute('display_name');
		if ($display_name_old == $display_name_) {
			return true;
		}
		
		$server->setAttribute('display_name', $display_name_);
		Abstract_Server::save($server);
		$this->log_action('server_set_display_name', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => array(
			'old' => $display_name_old,
			'new' => $display_name_,
		)));
		return true;
	}
	
	public function server_unset_display_name($server_id_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		$display_name_old = $server->getAttribute('display_name');
		if (is_null($display_name_old)) {
			return true;
		}
		
		$server->setAttribute('display_name', null);
		Abstract_Server::save($server);
		$this->log_action('server_unset_display_name', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => array(
			'old' => $display_name_old,
			'new' => null,
		)));
		return true;
	}
	
	public function server_set_fqdn($server_id_, $fqdn_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		if (! validate_ip($fqdn_) && ! validate_fqdn($fqdn_)) {
			Logger::error('api', sprintf('Internal name "%s" is invalid', $fqdn_));
			return false;
		}
		
		$fqdn_old = $server->fqdn;
		if ($fqdn_old == $fqdn_) {
			return true;
		}
		
		$server2 = Abstract_Server::load_by_fqdn($fqdn_);
		if ($server2 != null && $server->id != $server_id_) {
			Logger::error('api', sprintf('Internal name "%s" is already used for another server', $fqdn_));
			return false;
		}
		
		$server->fqdn = $fqdn_;
		Abstract_Server::save($server);
		$this->log_action('server_set_fqdn', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => array(
			'old' => $fqdn_old,
			'new' => $fqdn_,
		)));
		return true;
	}
	
	public function server_set_external_name($server_id_, $external_name_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		if (! validate_ip($external_name_) && ! validate_fqdn($external_name_)) {
			Logger::error('api', sprintf('Redirection name "%s" is invalid', $external_name_));
			return false;
		}
		
		$external_name_old = $server->getAttribute('external_name');
		if ($external_name_ == $external_name_old) {
			return true;
		}
		
		$server->setAttribute('external_name', $external_name_);
		Abstract_Server::save($server);
		$this->log_action('server_set_external_name', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => array(
			'old' => $external_name_old,
			'new' => $external_name_,
		)));
		return true;
	}
	
	public function server_unset_external_name($server_id_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		$external_name_old = $server->getAttribute('external_name');
		if (is_null($external_name_old )) {
			return true;
		}

		$server->setAttribute('external_name', null);
		Abstract_Server::save($server);
		$this->log_action('server_unset_external_name', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => array(
			'old' => $external_name_old,
			'new' => null,
		)));
		return true;
	}
	
	public function server_set_rdp_port($server_id_, $rdp_port_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		$rdp_port_old = $server->getAttribute('rdp_port');
		if ($rdp_port_ == $rdp_port_old) {
			return true;
		}
		
		$server->setAttribute('rdp_port', $rdp_port_);
		Abstract_Server::save($server);
		$this->log_action('server_set_rdp_port', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => array(
			'old' => $rdp_port_old,
			'new' => $rdp_port_,
		)));
		return true;
	}
	
	public function server_unset_rdp_port($server_id_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		$rdp_port_old = $server->getAttribute('rdp_port');
		if (is_null($rdp_port_old)) {
			return true;
		}
		
		$server->setAttribute('rdp_port', null);
		Abstract_Server::save($server);
		$this->log_action('server_unset_rdp_port', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => array(
			'old' => $rdp_port_old,
			'new' => null,
		)));
		return true;
	}
	
	public function server_role_enable($server_id_, $role_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		if (! array_key_exists($role_, $server->roles)) {
			Logger::error('api', sprintf('%s is not an available role', $role_));
			return false;
		}
		
		if (! array_key_exists($role_, $server->roles_disabled)) {
			Logger::error('api', sprintf('Nothing to save. Role %s is already enabled', $role_));
			return false;
		}
		
		unset($server->roles_disabled[$role_]);
		Abstract_Server::save($server);
		$this->log_action('server_role_enable', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => $role_));
		return true;
	}
	
	public function server_role_disable($server_id_, $role_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return false;
		}
		
		if (! array_key_exists($role_, $server->roles)) {
			Logger::error('api', sprintf('%s is not an available role', $role_));
			return false;
		}
		
		if (array_key_exists($role_, $server->roles_disabled)) {
			Logger::error('api', sprintf('Nothing to save. Role %s is already disabled', $role_));
			return false;
		}
		
		$server->roles_disabled[$role_] = true;
		Abstract_Server::save($server);
		
		$this->log_action('server_role_disable', array('id' => $server->id, 'name' => $server->getDisplayName(), 'value' => $role_));
		return true;
	}
	
	public function server_add_static_application($application_id_, $server_id_) {
		$this->check_authorized('manageServers');
		
		$applicationDB = ApplicationDB::getInstance();
		$app = $applicationDB->import($application_id_);
		if (! $app) {
			return false;
		}
		
		if ($app->getAttribute('static') == false) {
			return false;
		}
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			return false;
		}
		
		Abstract_Liaison::save('ApplicationServer', $application_id_, $server_id_);
		$server->syncStaticApplications();
		
		$this->log_action('server_add_static_application', array(
			'server_id' => $server->id, 'server_name' => $server->getDisplayName(),
			'application_id' => $application_id_, 'application_name' => $app->getAttribute('name')
		));
		return true;
	}
	
	public function server_remove_static_application($application_id_, $server_id_) {
		$this->check_authorized('manageServers');
		
		$applicationDB = ApplicationDB::getInstance();
		$app = $applicationDB->import($application_id_);
		if (! $app) {
			return false;
		}
		
		if ($app->getAttribute('static') == false) {
			return false;
		}
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			return false;
		}
		
		Abstract_Liaison::delete('ApplicationServer', $application_id_, $server_id_);
		
		$this->log_action('server_add_static_application', array(
			'server_id' => $server->id, 'server_name' => $server->getDisplayName(),
			'application_id' => $application_id_, 'application_name' => $app->getAttribute('name')
		));
		return true;
	}
	
	private static function generate_task_array($task_) {
		$t = array(
			'id' => $task_->id,
			'type' => get_class($task_),
			't_begin' => $task_->t_begin,
			't_end' => $task_->t_end,
			'server' => $task_->server,
			'status' => $task_->status,
			'request' => $task_->getRequest(),
		);
		
		if ($task_->applications) {
			$t['applications'] = $task_->applications;
		}
		
		return $t;
	}
	
	public function tasks_list() {
		$this->check_authorized('viewServers');
		
		$tm = new Tasks_Manager();
		$tm->load_all();
		$tm->refresh_all();
		
		$ret = array();
		foreach($tm->tasks as $task) {
			$t = self::generate_task_array($task);
			
			$ret[$t['id']] = $t;
		}
		
		return $ret;
	}
	
	public function task_info($id_) {
		$this->check_authorized('viewServers');
		
		$tm = new Tasks_Manager();
		$tm->load_all();
		$tm->refresh_all();
		
		
		$task = false;
		foreach($tm->tasks as $t) {
			if ($t->id == $id_) {
				$task = $t;
				break;
			}
		}
		
		if ($task === false)
			return null;
		
		$t = self::generate_task_array($task);
		$t['job_id'] = $task->job_id;
		$t['infos'] = $task->get_AllInfos();
		$t['packages'] = $task->getPackages();
		
		return $t;
	}
	
	public function task_remove($task_id_) {
		$tm = new Tasks_Manager();
		$tm->load_all();
		$tm->refresh_all();
		
		$task = false;
		foreach($tm->tasks as $t) {
			if ($task_id_ = $t->id) {
				$task = $t;
				break;
			}
		}
		
		if ($task === false) {
			Logger::error('api', sprintf('Unknown task "%s"', $task_id_));
			return false;
		}
		
		if (! ($task->succeed() || $task->failed())) {
			Logger::error('api', sprintf('Task "%s" not removable', $task_id_));
			return false;
		}
		
		$tm->remove($task_id_);
		$this->log_action('task_remove', array('id' => $task_id_));
		return true;
	}
	
	public function task_debian_install_packages($server_id_, $line_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return null;
		}
		
		$tm = new Tasks_Manager();
		$tm->load_all();
		$tm->refresh_all();
		
		$t = new Task_install_from_line(0, $server_id_, $line_);
		$tm->add($t);
		
		$this->log_action('task_debian_install_packages', array(
			'server_id' => $server->id, 'server_name' => $server->getDisplayName(),
			'packages' => $line_
		));
		return $t->id;
	}
	
	public function task_debian_installable_application($server_id_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return null;
		}
		
		$task = new Task_available_applications('', $server_id_);
		$manager = new Tasks_Manager();
		$manager->add($task);
		
		$this->log_action('task_debian_installable_application', array(
			'server_id' => $server->id, 'server_name' => $server->getDisplayName(),
		));
		return $task->id;
	}
	
	public function task_debian_upgrade($server_id_) {
		$this->check_authorized('manageServers');
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return null;
		}
		
		$tm = new Tasks_Manager();
		$tm->load_all();
		$tm->refresh_all();
		
		
		$t = new Task_upgrade(0, $server_id_);
		
		$tm->add($t);
		
		$this->log_action('task_debian_upgrade', array(
			'server_id' => $server->id, 'server_name' => $server->getDisplayName(),
		));
		return $t->id;
	}
	
	public function task_debian_server_replicate($server_id_, $server_ref_id_) {
		$this->check_authorized('manageServers');
		
		$server_from = Abstract_Server::load($server_ref_id_);
		if (! is_object($server_from)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_ref_id_));
			return false;
		}
		
		$server_to = Abstract_Server::load($server_ref_id_);
		if (! is_object($server_to)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_ref_id_));
			return false;
		}
		
		$applications_from = $server_from->getApplications();
		
		if (! array_key_exists(Server::SERVER_ROLE_APS, $server_to->roles)) {
			return false;
		}
		
		$applications_to = $server_to->getApplications();
		
		$to_delete = array();
		foreach($applications_to as $app) {
			if (! in_array($app, $applications_from)) {
				$to_delete[]= $app;
			}
		}
		
		$to_install = array();
		foreach($applications_from as $app) {
			if (! in_array($app, $applications_to)) {
				$to_install[]= $app;
			}
		}
		
		//FIX ME ?
		$tm = new Tasks_Manager();
		if (count($to_delete) > 0) {
			$t = new Task_remove(0, $server_id, $to_delete);
			$tm->add($t);
		}
		if (count($to_install) > 0) {
			$t = new Task_install(0, $server_id, $to_install);
			$tm->add($t);
		}
		
		$this->log_action('task_debian_server_replicate', array(
			'server_master_id' => $server_from->id, 'server_master_name' => $server_from->getDisplayName(),
			'server_id' => $server_to->id, 'server_name' => $server_to->getDisplayName(),
			'delete' => $to_delete, 'install' => $to_install,
		));
		return true;
	}
	
	public function task_debian_application_install($application_id_, $server_id_) {
		$this->check_authorized('manageServers');
		
		$applicationDB = ApplicationDB::getInstance();
		$app = $applicationDB->import($application_id_);
		if (! $app) {
			return null;
		}
		
		if ($app->getAttribute('static') != false) {
			return null;
		}
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return null;
		}
		
		$tm = new Tasks_Manager();
		$t = new Task_install(0, $server_id_, array($app));
		$tm->add($t);
		
		$this->log_action('task_debian_application_install', array(
			'server_id' => $server->id, 'server_name' => $server->getDisplayName(),
			'application_id' => $application_id_, 'application_name' => $app->getAttribute('name'),
		));
		return $t->id;
	}
	
	public function task_debian_application_remove($application_id_, $server_id_) {
		$this->check_authorized('manageServers');
		
		$applicationDB = ApplicationDB::getInstance();
		$app = $applicationDB->import($application_id_);
		if (! $app) {
			return null;
		}
		
		if ($app->getAttribute('static') != false) {
			return null;
		}
		
		$server = Abstract_Server::load($server_id_);
		if (! is_object($server)) {
			Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
			return null;
		}
		
		$tm = new Tasks_Manager();
		$t = new Task_remove(0, $server_id_, array($app));
		$tm->add($t);
		
		$this->log_action('task_debian_application_remove', array(
			'server_id' => $server->id, 'server_name' => $server->getDisplayName(),
			'application_id' => $application_id_, 'application_name' => $app->getAttribute('name'),
		));
		return $t->id;
	}
	
	private static function generate_application_array($application_) {
		return array(
			'id' => $application_->getAttribute('id'),
			'name' => $application_->getAttribute('name'),
			'description' => $application_->getAttribute('description'),
			'type' => $application_->getAttribute('type'),
			'executable_path' => $application_->getAttribute('executable_path'),
			'package' => $application_->getAttribute('package'),
			'published' => $application_->getAttribute('published'),
			'desktopfile' => $application_->getAttribute('desktopfile'),
			'static' => $application_->getAttribute('static'),
		);
	}
	
	public function applications_list($type_) {
		$this->check_authorized('viewApplications');
	
		$applicationDB = ApplicationDB::getInstance();
		$applications = $applicationDB->getList($type_);
	
		$ret = array();
		foreach($applications as $application) {
			$a = self::generate_application_array($application);
			$ret[$a['id']] = $a;
		}
		
		return $ret;
	}
	
	
	public function application_info($id_) {
		$this->check_authorized('viewApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		
		$application = $applicationDB->import($id_);
		if (!is_object($application))
			return null;
		
		$a = self::generate_application_array($application);
		
		$liaisons = Abstract_Liaison::load('ApplicationServer', $application->getAttribute('id'), NULL);
		if (count($liaisons) > 0) {
			$a['servers'] = array();
			foreach ($liaisons as $liaison) {
				$server = Abstract_Server::load($liaison->group);
				if (! $server) {
					continue;
				}
				
				$a['servers'][$server->id] = $server->getDisplayName();
			}
		}
		
		$liaisons = Abstract_Liaison::load('AppsGroup', $application->getAttribute('id'), NULL);
		if (count($liaisons) > 0) {
			$a['groups'] = array();
			foreach ($liaisons as $liaison) {
				$group = $applicationsGroupDB->import($liaison->group);
				if (! is_object($group)) {
					continue;
				}
			
				$a['groups'][$group->id]= $group->name;
			}
		}
		
		$mimes = $application->getMimeTypes();
		if (count($mimes) > 0) {
			$a['mimetypes'] = $mimes;
		}
		
		$tm = new Tasks_Manager();
		$tm->load_from_application($id_);
		
		if (count($tm->tasks) > 0) {
			$a['tasks'] = array();
			foreach($tm->tasks as $task) {
				$t = self::generate_task_array($task);
				$a['tasks'][$t['id']] = $t;
			}
		}
		
		return $a;
	}
	
	public function application_remove($id_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$id_.'"');
			return false;
		}
		
		$ret = $applicationDB->remove($app);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('application_remove', array('id' => $id_, 'name' => $app->getAttribute('name')));
		return true;
	}
	
	public function application_publish($id_, $publish_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$id_.'"');
			return false;
		}
		
		$publish_old = $app->getAttribute('published');
		if ($publish_ == $publish_old) {
			return true;
		}
		
		$app->setAttribute('published', (($publish_===true)?1:0)); // to check !
		
		$res = $applicationDB->update($app);
		if (! $res) {
			return false;
		}
		
		$this->log_action('application_publish', array(
			'application_id' => $application_id_, 'application_name' => $app->getAttribute('name'),
			'value' => array(
				'old' => $publish_old,
				'new' => (($publish_===true)?1:0),
			)
		));
		return true;
	}
	
	
	public function applications_remove_orphans() {
		$this->check_authorized('manageApplications');
		
		$applicationsDB = ApplicationDB::getInstance();
		if (! $applicationsDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$apps = $applicationsDB->getList();
		$success = true;
		if (! is_array($apps)) {
			return false;
		}
		
		$orphan_apps = array();
		foreach ($apps as $app) {
			if ($app->isOrphan()) {
				$ret = $applicationsDB->remove($app);
				if ($ret !== true) {
					$success = false;
					break;
				}
				
				array_push($orphan_apps, $app->getAttribute('name'));
			}
		}
		
		$this->log_action('applications_remove_orphans', $orphan_apps);
		return $success;
	}
	
	public function application_clone($id_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$id_.'"');
			return false;
		}
		
		$icon_path = $app->getIconPath();
		$servers_liaisons = Abstract_Liaison::load('ApplicationServer', $app->getAttribute('id'), NULL);
		
		$app->unsetAttribute('id');
		$app->setAttribute('static', 1);
		$app->setAttribute('revision', 1);
		$ret = $applicationDB->add($app);
		if (! $ret) {
			return false;
		}
		
		// Clone Icon
		if ($app->haveIcon()) {
			// We remove the application icon if already exists because it shouldn't
			$app->delIcon();
		}

		$path_rw = $app->getIconPathRW();
		if (is_writable2($path_rw)) {
			@file_put_contents($path_rw, @file_get_contents($icon_path));
		}
		
		// Clone servers list
		foreach ($servers_liaisons as $liaison) {
			Abstract_Liaison::save('ApplicationServer', $app->getAttribute('id'), $liaison->group);
			$buf_server = Abstract_Server::load($liaison->group);
			if (is_object($buf_server))
				$buf_server->syncStaticApplications();
		}
		
		$this->log_action('application_clone', array('application_id' => $id_, 'application_name' => $app->getAttribute('name')));
		return true;
	}
	
	public function application_icon_get($id_) {
		$this->check_authorized('viewApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		$app = $applicationDB->import($id_);
		if (! is_object($app)) {
			return null;
		}

		if (! file_exists($app->getIconPath())) {
			return null;
		}

		$buf = @file_get_contents($app->getIconPath());
		
		return base64_encode($buf);
	}
	
	public function application_icon_set($id_, $icon_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$id_.'"');
			return false;
		}
		
		if (get_classes_startwith('Imagick') == array()) {
			Logger::error('api', 'Unable to setApplicationIcon: Imagick is not available');
			return false;
		}

		$path_rw = $app->getIconPathRW();
		if (! is_writable2($path_rw)) {
			Logger::error('api', 'Unable to write icon file for application '.$app->getAttribute('id').': '.$path_rw.' is not writable');
			return false;
		}
		
		try {
			$mypicture = new Imagick();
			$mypicture->readImageBlob(base64_decode($icon_));
			$mypicture->scaleImage(32, 0);
			$mypicture->writeImage($app->getIconPathRW());
		}
		catch (Exception $e) {
			Logger::error('api', 'Given icon for application '.$app->getAttribute('id').': '.$path_rw.' is not a valid image');
		}
		
		if ($app->getAttribute('static')) {
			$app->setAttribute('revision', ($app->getAttribute('revision')+1));
			$applicationDB->update($app);
		}
		
		$this->log_action('application_icon_set', array('application_id' => $app->getAttribute('id'), 'application_name' => $app->getAttribute('name')));
		return true;
	}
	
	public function application_icon_getFromServer($application_id_, $server_id_) {
		$this->check_authorized('viewApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		$app = $applicationDB->import($application_id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$id_.'"');
			return null;
		}
		
		$server = Abstract_Server::load($server_id_);
		if (! $server) {
			Logger::error('api', 'Unknown server "'.$server_id_.'"');
			return null;
		}
		
		if (! $server->isOnline()) {
			return null;
		}
		
		$ret = query_url($server->getBaseURL().'/aps/application/icon/'.$app->getAttribute('id'));
		if (! $ret)
			return null;

		try {
			$mypicture = new Imagick();
			$mypicture->readImageBlob($ret);
		}
		catch (Exception $e) {
			Logger::error('api', 'Icon from server '.$server_id_.' for application '.$app->getAttribute('id').' is not a valid image');
			return null;
		}
		
		return base64_encode($ret);
	}
	
	public function application_icon_setFromServer($application_id_, $server_id_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($application_id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$application_id_.'"');
			return false;
		}
		
		$server = Abstract_Server::load($server_id_);
		if (! $server) {
			Logger::error('api', 'Unknown server "'.$server_id_.'"');
			return false;
		}

		if (! $server->isOnline()) {
			Logger::error('api', 'Server "'.$server_id_.'" is not online');
			return false;
		}
		
		$server->getApplicationIcon($app->getAttribute('id'));
		$this->log_action('application_icon_setFromServer', array(
			'application_id' => $app->getAttribute('id'),
			'application_name' => $app->getAttribute('name'),
			'server_id' => $server->id,
			'server_name' => $server->getDisplayName(),
		));
		return true;
	}
	
	public function application_static_add($name_, $description_, $type_, $command_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return null;
		}
		
		$app = new Application(NULL, $name_, $description_, $type_, $command_);
		if (! $applicationDB->isOK($app)) {
			Logger::error('api', 'Application is not ok');
			return null;
		}
		
		$app->unsetAttribute('id');
		$app->setAttribute('static', 1);
		$app->setAttribute('revision', 1);
		$ret = $applicationDB->add($app);
		if (! $ret) {
			Logger::error('api', 'Failed to add application "'.$app->getAttribute('name').'"');
			return null;
		}
		
		if ($app->haveIcon()) {
			// We remove the application icon if already exists because it shouldn't
			$app->delIcon();
		}
		
		$servers = Abstract_Server::load_available_by_type($app->getAttribute('type'), true);
		foreach ($servers as $server) {
			$server->syncStaticApplications();
		}
		
		$this->log_action('application_static_add', array(
			'id' => $app->getAttribute('id'),
			'name' => $app->getAttribute('name'),
			'type' => $type_,
			'command' => $command_,
		));
		return $app->getAttribute('id');
	}
	
	public function application_static_remove($id_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$id_.'"');
			return false;
		}
		
		Abstract_Liaison::delete('ApplicationServer', $app->getAttribute('id'), NULL);
		$ret = $applicationDB->remove($app);
		if (! $ret) {
			Logger::error('api', sprintf("Failed to delete application '%s'", $app->getAttribute('name')));
			return false;
		}
		
		$servers = Abstract_Server::load_available_by_type($app->getAttribute('type'), true);
		foreach ($servers as $server) {
			$server->syncStaticApplications();
		}
		
		$this->log_action('application_static_add', array(
			'id' => $app->getAttribute('id'),
			'name' => $app->getAttribute('name'),
		));
		return true;
	}
	
	public function application_static_removeIcon($id_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$id_.'"');
			return false;
		}
		
		$app->delIcon();
		
		$app->setAttribute('revision', ($app->getAttribute('revision')+1));
		$applicationDB->update($app);
		
		$servers = Abstract_Server::load_available_by_type($app->getAttribute('type'), true);
		foreach ($servers as $server) {
			$server->syncStaticApplications();
		}
		
		$this->log_action('application_static_removeIcon', array(
			'id' => $app->getAttribute('id'),
			'name' => $app->getAttribute('name'),
		));
		return true;
	}
	
	public function application_static_modify($id_, $name_, $description_, $command_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$id_.'"');
			return false;
		}
		
		$modify = false;
		$changes = array();
		if ($name_ != null && $name_ != $app->getAttribute('name')) {
			$changes['name'] = array('old' => $app->getAttribute('name'), 'new' => $name_);
			$app->setAttribute('name', $name_);
			$modify = true;
		}
		
		if ($description_ != null && $description_ != $app->getAttribute('description')) {
			$changes['description'] = array('old' => $app->getAttribute('description'), 'new' => $description_);
			$app->setAttribute('description', $description_);
			$modify = true;
		}
		
		if ($command_ != null && $command_ != $app->getAttribute('executable_path')) {
			$changes['command'] = array('old' => $app->getAttribute('executable_path'), 'new' => $command_);
			$app->setAttribute('executable_path', $command_);
			$modify = true;
		}
		
		if (! $modify) {
			return false;
		}
		
		$app->setAttribute('revision', ($app->getAttribute('revision')+1));
		$ret = $applicationDB->update($app);
		if (! $ret) {
			Logger::error('api', 'Failed to modify application "'.$app->getAttribute('name').'"');
			return false;
		}
		
		$servers = Abstract_Server::load_available_by_type($app->getAttribute('type'), true);
		foreach ($servers as $server) {
			$server->syncStaticApplications();
		}
		
		$this->log_action('application_static_modify', array_merge($changes, array(
			'id' => $app->getAttribute('id'),
			'name' => $app->getAttribute('name'),
		)));
		return true;
	}
	
	public function application_weblink_add($name_, $description_, $url_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return null;
		}
		
		$app = new Application_weblink(NULL, $name_, $description_, $url_);
		if (! $applicationDB->isOK($app)) {
			Logger::error('api', 'Application is not ok');
			return null;
		}
		
		$app->unsetAttribute('id');
		$app->setAttribute('static', 1);
		$app->setAttribute('revision', 1);
		
		$ret = $applicationDB->add($app);
		if (! $ret) {
			Logger::error('api', 'Failed to add application "'.$app->getAttribute('name').'"');
			return null;
		}
		
		if ($app->haveIcon()) {
			// We remove the application icon if already exists because it shouldn't
			$app->delIcon();
		}
		
		$this->log_action('application_weblink_add', array(
			'id' => $app->getAttribute('id'),
			'name' => $app->getAttribute('name'),
		));
		return $app->getAttribute('id');
	}
	
	public function application_webapp_add($name_, $description_, $configuration_) {
		Logger::info('api', sprintf('Name: %s, Description: %s, Configuration: %s', $name_, $description_, $configuration_));
		$application_id = NULL;
		$this->check_authorized('manageApplications');

		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::info('api', 'ApplicationDB is not writable');
			return null;
		}

		$app = new Application_webapp(NULL, $name_, $description_);
		if (! $applicationDB->isOK($app)) {
			Logger::info('api', 'Web application is not ok');
			return null;
		}

		$app->unsetAttribute('id');
		$app->setAttribute('static', 1);
		$app->setAttribute('revision', 1);

		$ret = $applicationDB->add($app);
		if (! $ret) {
			Logger::info('api', 'Failed to add application "'.$app->getAttribute('name').'"');
			return null;
		}
		// Fetch the id
		$application_id = $app->getAttribute('id');

		if ($app->haveIcon()) {
			// We remove the application icon if already exists because it shouldn't
			$app->delIcon();
		}

		$webapp_configuration_object = new Application_webapp_configuration(NULL, $application_id, $configuration_);
		// If we use a remote file, it's undocumented and there's no information about what will they provide.
		if($configuration_ != NULL) {
			$webapp_application_DB = WebAppConfDB::getInstance();
			if (! $webapp_application_DB->isWriteable()) {
				Logger::info('api', 'WebAppConfDB is not writable');
				return null;
			}

			if (! $webapp_application_DB->isOK($webapp_configuration_object)) {
				Logger::info('api', 'Web application configuration is not ok');
				$removed_stale_application = $this->application_webapp_remove($application_id);
				if($removed_stale_application == false){
					Logger::info('api', 'Failed to remove static application "'.$app->getAttribute('name').'". Remove it manually');
				}
				return null;
			}

			$configuration_id = $webapp_application_DB->add($webapp_configuration_object);
			if (! $configuration_id) {
				Logger::info('api', 'Failed to add application configuration for "'.$app->getAttribute('name').'". Removing stale application');
				$removed_stale_application = $this->application_webapp_remove($application_id);
				if($removed_stale_application == false){
					Logger::info('api', 'Failed to remove static application "'.$app->getAttribute('name').'". Remove it manually');
				}
				return null;
			}
			else {
				Logger::info('api', 'Added configuration for "'. $app->getAttribute('name').'"');
			}
		}
		else {
			// TODO Pending Gateway:dialog.py output			
		}
        
        $servers = Abstract_Server::load_available_by_role('webapps', true);
		foreach ($servers as $server) {
			$server->syncWebApplications();
		}
        
		$this->log_action('application_webapp_add', array(
			'id' => $app->getAttribute('id'),
			'name' => $app->getAttribute('name'),
		));
		return $application_id;
	}
   
	public function application_webapp_get_raw_configuration($application_id) {
		$configuration = NULL;
		$webapp_application_DB = WebAppConfDB::getInstance();
		$webapp_configuration_object = $webapp_application_DB->search($application_id);
		if ($webapp_configuration_object != NULL) {
			$configuration = $webapp_configuration_object->getAttribute('raw_configuration');
		}
		return $configuration;
	}

	public function application_webapp_set_raw_configuration($application_id, $configuration) {
		$webapp_application_DB = WebAppConfDB::getInstance();
		$webapp_configuration_object = $webapp_application_DB->search($application_id);
		if ($webapp_configuration_object != NULL) {
			$webapp_configuration_object->setAttribute('raw_configuration', $configuration);
            if (! $webapp_application_DB->isOK($webapp_configuration_object)) {
				Logger::info('api', 'Web application configuration is not ok');
                return false;
            }
            $webapp_application_DB->update($webapp_configuration_object);
            
            $servers = Abstract_Server::load_available_by_role('webapps', true);
            foreach ($servers as $server) {
                $server->syncWebApplications();
            }
        
            return true;
		}
		return false;
	}
    
    public function application_webapp_clone($id_) {
        Logger::info('api', "application_webapp_clone");
        $this->check_authorized('manageApplications');
        
        $applicationDB = ApplicationDB::getInstance();
        if (! $applicationDB->isWriteable()) {
            Logger::error('api', 'ApplicationDB is not writable');
            return false;
        }
        $webapp_application_DB = WebAppConfDB::getInstance();
        if (! $webapp_application_DB->isWriteable()) {
            Logger::info('api', 'WebAppConfDB is not writable');
            return null;
        }
        
        $app = $applicationDB->import($id_);
        if (! is_object($app)) {
            Logger::error('api', 'Unknown application "'.$id_.'"');
            return false;
        }
        
        $icon_path = $app->getIconPath();

        $app->unsetAttribute('id');
        $app->setAttribute('static', 1);
        $app->setAttribute('revision', 1);
        $ret = $applicationDB->add($app);
        if (! $ret) {
            return false;
        }
        
        // Clone Icon
        if ($app->haveIcon()) {
            // We remove the application icon if already exists because it shouldn't
            $app->delIcon();
        }

        $path_rw = $app->getIconPathRW();
        if (is_writable2($path_rw)) {
            @file_put_contents($path_rw, @file_get_contents($icon_path));
        }
        
        // Clone YAML config
        $app_configuration = $webapp_application_DB->search($id_);
        if ($app_configuration != NULL) {
            $application_id = $app->getAttribute('id');
            $app_configuration->unsetAttribute('id');
            $app_configuration->setAttribute('application_id', $application_id);

            if (! $webapp_application_DB->isOK($app_configuration)) {
                Logger::info('api', 'Application configuration is not ok');
                $removed_stale_application = $this->application_webapp_remove($application_id);
                if($removed_stale_application == false){
                    Logger::info('api', 'Failed to remove static application "'.$app->getAttribute('name').'". Remove it manually');
                }
                return null;
            }

            $configuration_id = $webapp_application_DB->add($app_configuration);
            if (! $configuration_id) {
                Logger::info('api', 'Failed to add application configuration for "'.$app->getAttribute('name').'". Removing stale application');
                $removed_stale_application = $this->application_webapp_remove($application_id);
                if($removed_stale_application == false){
                    Logger::info('api', 'Failed to remove static application "'.$app->getAttribute('name').'". Remove it manually');
                }
                return null;
            }
            else {
                Logger::info('api', 'Added configuration for "'. $app->getAttribute('name').'"');
            }
        }

        $servers = Abstract_Server::load_available_by_role('webapps', true);
		foreach ($servers as $server) {
			$server->syncWebApplications();
		}
        
		$this->log_action('application_webapp_clone', array(
			'id' => $app->getAttribute('id'),
			'name' => $app->getAttribute('name'),
		));
        return true;
    }
    
	public function application_webapp_remove($application_id) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
        
        $webapp_application_DB = WebAppConfDB::getInstance();
        if (! $webapp_application_DB->isWriteable()) {
            Logger::info('api', 'WebAppConfDB is not writable');
            return null;
        }
        
		$app = $applicationDB->import($application_id);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown web application "'.$application_id.'"');
			return false;
		}

        $webapp_configuration_object = $webapp_application_DB->search($application_id);
		if ($webapp_configuration_object != NULL)
            $webapp_application_DB->remove($webapp_configuration_object);

		$ret = $applicationDB->remove($app);
		if (! $ret) {
			Logger::error('api', sprintf("Failed to delete web application '%s'", $app->getAttribute('name')));
			return false;
		}
		
		$servers = Abstract_Server::load_available_by_role('webapps', true);
		foreach ($servers as $server) {
			$server->syncWebApplications();
		}
		
		$this->log_action('application_webapp_clone', array(
			'id' => $app->getAttribute('id'),
			'name' => $app->getAttribute('name'),
		));
		return true;
	}
	
	public function default_browser_get() {
		$this->check_authorized('viewApplications');
		
		return $this->prefs->get('general', 'default_browser');
	}
	
	public function default_browser_set($type_, $application_id_) {
		$this->check_authorized('manageApplications');
		
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
			Logger::error('api', 'Failed to import Preference_Admin');
			return false;
		}
		
		$applicationDB = ApplicationDB::getInstance();
		$app = $applicationDB->import($application_id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$application_id_.'"');
			return false;
		}
		
		$browsers_old = $prefs->get('general', 'default_browser');
		$browsers = $prefs->get('general', 'default_browser');
		$browsers[$type_] = $app->getAttribute('id');
		$prefs->set('general', 'default_browser', $browsers);
		$prefs->backup();
		
		$this->log_action('default_browser_set', array('value' => array(
			'old' => $browsers_old,
			'new' => $browsers,
		)));
		return true;
	}
	
	public function default_browser_unset($type_) {
		$this->check_authorized('manageApplications');
		
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
			Logger::error('api', 'Failed to import Preference_Admin');
			return false;
		}
		
		$browsers_old = $prefs->get('general', 'default_browser');
		$browsers = $prefs->get('general', 'default_browser');
		$browsers[$type_] = null;
		$prefs->set('general', 'default_browser', $browsers);
		$prefs->backup();
		
		$this->log_action('default_browser_unset', array('value' => array(
			'old' => $browsers_old,
			'new' => $browsers,
		)));
		return true;
	}
	
	public function applications_groups_list() {
		$this->check_authorized('viewApplicationsGroups');
		
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		$groups = $applicationsGroupDB->getList();
		
		$ret = array();
		foreach($groups as $item) {
			$g = array(
				'id' => $item->id,
				'name' => $item->name,
				'description' => $item->description,
				'published' => $item->published,
			);
			
			$ret[$g['id']] = $g;
		}
		
		return $ret;
	}
	
	public function applications_group_info($id_) {
		$this->check_authorized('viewApplicationsGroups');
		
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		$userGroupDB = UserGroupDB::getInstance();
		
		$group = $applicationsGroupDB->import($id_);
		if (! is_object($group)) {
			return null;
		}
		
		$g = array(
			'id' => $group->id,
			'name' => $group->name,
			'description' => $group->description,
			'published' => $group->published,
		);
		
		$liaisons = Abstract_Liaison::load('AppsGroup', NULL, $id_);
		if (count($liaisons)) {
			$g['applications'] = array();
			foreach ($liaisons as $liaison) {
				$g['applications'][]= $liaison->element;
			}
		}
		
		$liaisons = Abstract_Liaison::load('UsersGroupApplicationsGroup', NULL, $id_);
		if (count($liaisons)) {
			$group_ids = array();
			foreach ($liaisons as $liaison) {
				array_push($group_ids, $liaison->element);
			}
			
			$groups = $userGroupDB->imports($group_ids);
			$exported_groups = array();
			foreach($groups as $group_id => $group) {
				$exported_groups[$group_id] = $group->name;
			}
			
			if (count($exported_groups) > 0) {
				$g['usersgroups'] = $exported_groups;
			}
		}
		
		return $g;
	}
	
	public function applications_group_add($name_, $description_) {
		$this->check_authorized('manageApplicationsGroups');
		
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		if (! $applicationsGroupDB->isWriteable()) {
			Logger::error('api', 'Applications Group Database is not writeable');
			return null;
		}
		
		$g = new AppsGroup(NULL, $name_, $description_, 1);
		$res = $applicationsGroupDB->add($g);
		if (! $res) {
			Logger::error('api', sprintf("Unable to create applications group '%s'", $name));
			return null;
		}
		
		$this->log_action('applications_group_add', array('name' => $name_));
		return $g->id;
	}
	
	public function applications_group_remove($id_) {
		$this->check_authorized('manageApplicationsGroups');
		
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		if (! $applicationsGroupDB->isWriteable()) {
			Logger::error('api', 'Applications Group Database is not writeable');
			return false;
		}
		
		$group = $applicationsGroupDB->import($id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Unknown applications group "%s"', $id_));
			return false;
		}
		
		if (! $applicationsGroupDB->remove($group)) {
			Logger::error('api', sprintf('Unable to remove applications group "%s"', $group->name));
			return false;
		}
		
		$this->log_action('applications_group_remove', array('name' => $group->name));
		return true;
	}
	
	public function applications_group_modify($id_, $name_, $description_, $published_) {
		$this->check_authorized('manageApplicationsGroups');
		
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		if (! $applicationsGroupDB->isWriteable()) {
			Logger::error('api', 'Applications Group Database is not writeable');
			return false;
		}
		
		$group = $applicationsGroupDB->import($id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Unknown applications group "%s"', $id_));
			return false;
		}
		
		$has_change = false;
		$changes = array();
		
		if ($name_ != null && $name_ != $group->name) {
			$changes['name'] = array('old' => $group->name, 'new' => $name_);
			$group->name = $name_;
			
			$has_change = true;
		}
		
		if ($description_ != null && $description_ != $group->description) {
			$changes['description'] = array('old' => $group->description, 'new' => $description_);
			$group->description = $description_;
			$has_change = true;
		}
		
		if ($published_ !== null && $published_ !== $group->published) {
			$changes['published'] = array('old' => $group->published, 'new' => (bool)$published_);
			$group->published = (bool)$published_;
			$has_change = true;
		}
		
		if (! $has_change) {
			return false;
		}
		
		$ret = $applicationsGroupDB->update($group);
		
		$changes['name'] = $group->name;
		$this->log_action('applications_group_modify', array_merge($changes, array('name' => $group->name)));
		return $ret;
	}
	
	public function applications_group_add_application($application_id, $group_id_) {
		$this->check_authorized('manageApplicationsGroups');
		
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		$group = $applicationsGroupDB->import($group_id_);
		if (! is_object($group)) {
			return false;
		}
		
		$ret = Abstract_Liaison::save('AppsGroup', $application_id, $group_id_);
		if ($ret !== true) {
			return false;
		}
		
		$this->log_action('applications_group_add_application', array(
			'application_id' => $application_id,
			'group' => $group->name,
		));
		return true;
	}
	
	public function applications_group_remove_application($application_id, $group_id_) {
		$this->check_authorized('manageApplicationsGroups');
		
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		$group = $applicationsGroupDB->import($group_id_);
		if (! is_object($group)) {
			return true;
		}
		
		$ret = Abstract_Liaison::delete('AppsGroup', $application_id, $group_id_);
		if ($ret !== true) {
			return false;
		}
		
		$this->log_action('applications_group_remove_application', array(
			'application_id' => $application_id,
			'group' => $group->name,
		));
		return true;
	}
	
	public function mime_types_list() {
		$this->check_authorized('viewApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		return $applicationDB->getAllMimeTypes();
	}
	
	public function mime_type_info($id_) {
		$this->check_authorized('viewApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		$applications = $applicationDB->getApplicationsWithMimetype($id_);
		
		$ret = array('id' => $id_, 'applications' => array());
		foreach($applications as $application) {
			$a = self::generate_application_array($application);
			$ret['applications'][$a['id']] = $a;
		}
		
		return $ret;
	}
	
	public function application_add_mime_type($application_id_, $mime_type_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($application_id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$application_id_.'"');
			return false;
		}
		
		$mimes = $app->getMimeTypes();
		if (in_array($mime_type_, $mimes)) {
			Logger::error('api', 'Application "'.$app->getAttribute('name').'" already have mime type "'.$mime_type_.'"');
			return false;
		}
		
		$mimes []= $mime_type_;
		$app->setMimeTypes($mimes);
		
		$ret = $applicationDB->update($app);
		if (! $ret) {
			Logger::error('api', 'Failed to modify application "'.$app->getAttribute('name').'"');
			return false;
		}
		
		$this->log_action('application_add_mime_type', array(
			'application_id' => $app->getAttribute('id'),
			'application_name' => $app->getAttribute('name'),
			'mime_type' => $mime_type_,
		));
		return true;
	}
	
	public function applications_remove_mime_type($application_id_, $mime_type_) {
		$this->check_authorized('manageApplications');
		
		$applicationDB = ApplicationDB::getInstance();
		if (! $applicationDB->isWriteable()) {
			Logger::error('api', 'ApplicationDB is not writable');
			return false;
		}
		
		$app = $applicationDB->import($application_id_);
		if (! is_object($app)) {
			Logger::error('api', 'Unknown application "'.$application_id_.'"');
			return false;
		}
		
		$mimes = $app->getMimeTypes();
		if (! in_array($mime_type_, $mimes)) {
			Logger::error('api', 'Application "'.$app->getAttribute('name').'" doesn\'t have mime type "'.$mime_type_.'"');
			return false;
		}
		
		unset($mimes[array_search($mime_type_, $mimes)]);
		$app->setMimeTypes($mimes);
		
		$ret = $applicationDB->update($app);
		if (! $ret) {
			Logger::error('api', 'Failed to modify application "'.$app->getAttribute('name').'"');
			return false;
		}
		
		$this->log_action('applications_remove_mime_type', array(
			'application_id' => $app->getAttribute('id'),
			'application_name' => $app->getAttribute('name'),
			'mime_type' => $mime_type_,
		));
		return true;
	}
	
	public function users_list() {
		$this->check_authorized('viewUsers');
		
		$userDB = UserDB::getInstance();
		
		//// to change !!!
		
		$ret = array();
		foreach($result as $user) {
			$u = array(
				'login' => $user->getAttribute('login'),
				'displayname' => $user->getAttribute('displayname'),
			);
			
			$ret[$user->getAttribute('login')] = $u;
		}
		
		return $ret;
	}
	
	public function users_list_partial($search_item_, $search_fields_) {
		$this->check_authorized('viewUsers');
		
		if (!is_array($search_fields_)) {
			$search_fields_ = array('login', 'displayname');
		}
	
		$userDB = UserDB::getInstance();
		
		$search_limit = $this->prefs->get('general', 'max_items_per_page');
		
		list($result, $partial) = $userDB->getUsersContains($search_item_, $search_fields_, $search_limit);
		
		$ret = array();
		foreach($result as $user) {
			$u = array(
				'login' => $user->getAttribute('login'),
				'displayname' => $user->getAttribute('displayname'),
			);
			
			$ret[$user->getAttribute('login')] = $u;
		}
		
		return array('partial' => $partial, 'data' => $ret);
	}
	
	public function user_info($id_) {
		$this->check_authorized('viewUsers');
		
		$userDB = UserDB::getInstance();
		$user = $userDB->import($id_);
		if (! $user)
			return null;
		
		$u = array();
		foreach($user->getAttributesList() as $attr) {
			$u[$attr] = $user->getAttribute($attr);
		}
		
		$u['locale'] = $user->getLocale();
		
		$groups = $user->usersGroups();
		if (count($groups) > 0) {
			$u['groups'] = array();
			foreach($groups as $group_id => $group) {
				$u['groups'][$group->getUniqueID()] = $group->name;
			}
		}
		
		$applications = $user->applications();
		 if (is_array($applications) && count($applications) > 0) {
			$u['applications'] = array();
			foreach ($applications as $application) {
				$u['applications'][$application->getAttribute('id')] = $application->getAttribute('name');
			}
		}
		
		$sessions = Abstract_Session::getByUser($id_);
		if (count($sessions) > 0) {
			$u['sessions'] = array();
			
			foreach($sessions as $session) {
				$u['sessions'][$session->id] = $session->getAttribute('start_time');
			}
		}
		
		$session_settings = $user->getSessionSettings('session_settings_defaults');
		if (array_key_exists('enable_profiles', $session_settings) && $session_settings['enable_profiles'] == 1) {
			$profiles = $user->getProfiles();
			
			if (count($profiles) > 0) {
				$u['profiles'] = array();
				
				foreach($profiles as $profile) {
					$server = Abstract_Server::load($profile->server);
					if (! $server) {
						continue;
					}
					
					$p = array(
						'id' => $profile->id,
						'server_id' => $server->id,
						'server_name' => $server->getDisplayName(),
					);
					
					$u['profiles'][$p['id']] = $p;
				}
			}
		}
		
		// Settings
		$u['settings'] = array();
		$u['settings_default'] = array();
		$session_prefs_categs = array('session_settings_defaults', 'remote_desktop_settings',  'remote_applications_settings');
		foreach ($session_prefs_categs as $session_prefs_categ) {
			$u['settings'][$session_prefs_categ] = array();
			$u['settings_default'][$session_prefs_categ] = array();
			
			$session_prefs = $this->prefs->getElements('general', $session_prefs_categ);
			foreach($session_prefs as $session_pref) {
				$e = self::pref_element2dict($session_pref);
				$u['settings_default'][$session_prefs_categ][$e['id']] = $e;
			}
			
			$user_prefs = Abstract_User_Preferences::loadByUserLogin($user->getAttribute('login'), 'general', $session_prefs_categ);
			foreach($user_prefs as $user_pref) {
				$u['settings'][$session_prefs_categ][$user_pref->element_id] = $user_pref->value;
			}
		}
		
		return $u;
	}
	
	public function user_add($login_, $displayname_, $password_) {
		$this->check_authorized('manageUsers');
		
		$userDB = UserDB::getInstance();
		if (! $userDB->isWriteable()) {
			Logger::error('api', 'UserDB is not writable');
			return false;
		}
		
		$u = new User();
		$u->setAttribute('login', $login_);
		$u->setAttribute('displayname', $displayname_);
		$u->setAttribute('password', $password_);
		
		$res = $userDB->add($u);
		if (! $res) {
			Logger::error('api', sprintf('Unable to create user "%s"', $_REQUEST['login']));
			return false;
		}
		
		$this->log_action('user_add', array(
			'login' => $login_,
			'displayname' => $displayname_,
		));
		return true;
	}
	
	public function user_remove($login_) {
		$this->check_authorized('manageUsers');
		
		$userDB = UserDB::getInstance();
		if (! $userDB->isWriteable()) {
			Logger::error('api', 'UserDB is not writable');
			return false;
		}
		
		$user = $userDB->import($login_);
		if (! is_object($user)) {
			Logger::error('api', sprintf('Unknown application "%s"', $login_));
			return false;
		}
		
		$sessions = Abstract_Session::getByUser($login_);
		if (count($sessions) > 0) {
			Logger::error('api', sprintf('Unable to delete user "%s" because he has an active session', $login_));
			return false;
		}
		
		if (Preferences::moduleIsEnabled('ProfileDB')) {
			$netfolders = $user->getProfiles();
			if (is_array($netfolders)) {
				$profiledb = ProfileDB::getInstance();
				foreach ($netfolders as $netfolder) {
					$profiledb->remove($netfolder->id);
					$server = Abstract_Server::load($netfolder->server);
					if ($profiledb->isInternal())
						$server->deleteNetworkFolder($netfolder->id, true);
				}
			}
		}
		
		$res = $userDB->remove($user);
		if (! $res) {
			return false;
		}
		
		$this->log_action('user_remove', array('login' => $login_));
		return true;
	}
	
	public function user_modify($login_, $displayname_, $password_) {
		$this->check_authorized('manageUsers');
		
		$userDB = UserDB::getInstance();
		if (! $userDB->isWriteable()) {
			Logger::error('api', 'UserDB is not writable');
			return false;
		}
		
		$user = $userDB->import($login_);
		if (! is_object($user)) {
			Logger::error('api', sprintf('Unknown application "%s"', $login_));
			return false;
		}
		
		$has_change = false;
		$changes = array();
		
		if ($displayname_ != null && $displayname_ != $user->getAttribute('displayname')) {
			$changes['displayname'] = array('old' => $user->getAttribute('displayname'), 'new' => $displayname_);
			$user->setAttribute('displayname', $displayname_);
			$has_change = true;
		}
		
		if ($password_ != null && $password_ != $user->getAttribute('password')) {
			$changes['password'] = array('old' => '****', 'new' => '********');
			$user->setAttribute('password', $password_);
			$has_change = true;
		}
		
		if (! $has_change) {
			return false;
		}
		
		$res = $userDB->update($user);
		
		$this->log_action('user_modify', array_merge(array('login' => $login_), $changes));
		return true;
	}
	
	public function users_populate($override_, $password_) {
		$this->check_authorized('manageUsers');
		
		$userDB = UserDB::getInstance();
		if (! $userDB->isWriteable()) {
			Logger::error('api', 'UserDB is not writable');
			return false;
		}
		
		$ret = $userDB->populate($override_, $password_);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('users_populate');
		return $ret;
	}
	
	public function user_settings_set($user_id_, $container_, $setting_, $value_) {
		$this->check_authorized('manageUsers');
		
		$userDB = UserDB::getInstance();
		$user = $userDB->import($user_id_);
		if (! is_object($user)) {
			return false;
		}
		
		$prefs = Preferences::getInstance();
		$session_settings_defaults = $prefs->getElements('general', $container_);
		if (! array_key_exists($setting_, $session_settings_defaults)) {
			return false;
		}
		
		$config_element = clone $session_settings_defaults[$setting_];
		$ugp = new User_Preferences($user->getAttribute('login'), 'general', $container_, $setting_, $config_element->content);
		if (! is_null($value_)) {
			$ugp->value = $value_;
		}
		
		$ret = Abstract_User_Preferences::save($ugp);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('user_settings_set', array('login' => $user->getAttribute('login'), $container_.'_'.$setting_ => array(
			'old' => $session_settings_defaults[$setting_]->content,
			'new' => $config_element->content,
		)));
		return true;
	}
	
	public function user_settings_remove($user_id_, $container_, $setting_) {
		$this->check_authorized('manageUsers');
		
		$userDB = UserDB::getInstance();
		$user = $userDB->import($user_id_);
		if (! is_object($user)) {
			return false;
		}
		
		$prefs = Preferences::getInstance();
		$session_settings_defaults = $prefs->getElements('general', $container_);
		if (! array_key_exists($setting_, $session_settings_defaults)) {
			return false;
		}
		
		$ret = Abstract_User_Preferences::delete($user->getAttribute('login'), 'general', $container_, $setting_);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('user_settings_remove', array('login' => $user->getAttribute('login'), $container_.'_'.$setting_ => null));
		return true;
	}
	
	private static function generate_usersgroup_array($group_) {
		return array(
			'id' => $group_->getUniqueID(),
			'name' => $group_->name,
			'description' => $group_->description,
			'published' => $group_->published,
			'type' => $group_->type,
			'default' => $group_->isDefault(),
		);
	}
	
	public function users_groups_list_partial($search_item_, $search_fields_) {
		$this->check_authorized('viewUsersGroups');
		
		if (!is_array($search_fields_)) {
			$search_fields_ = array('name', 'description');
		}
		
		$userGroupDB = UserGroupDB::getInstance();
		
		$search_limit = $this->prefs->get('general', 'max_items_per_page');
		
		list($result, $partial) = $userGroupDB->getGroupsContains($search_item_, $search_fields_, $search_limit);
		
		$ret = array();
		foreach($result as $group) {
			$g = self::generate_usersgroup_array($group);
			$ret[$g['id']] = $g;
		}
		
		return array('partial' => $partial, 'data' => $ret);
	}
	
	public function users_group_info($id_) {
		$this->check_authorized('viewUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		$group = $userGroupDB->import($id_);
		if (! $group)
			return null;
		
		$g = self::generate_usersgroup_array($group);
		
		$users = $group->usersLogin();
		if (count($users) > 0) {
			$userDB = UserDB::getInstance();
			$users2 = $userDB->imports($users);
			
			$s['users'] = array();
			foreach($users2 as $user) {
				$g['users'][$user->getAttribute('login')] = $user->getAttribute('displayname');
			}
		}
		
		$ags = $group->appsGroups();
		if (count($ags) > 0) {
			$g['applicationsgroups'] = array();
			foreach($ags as $ag) {
				$g['applicationsgroups'][$ag->id] = $ag->name;
			}
		}
		
		if (Preferences::moduleIsEnabled('SharedFolderDB')) {
			$sharedfolderdb = SharedFolderDB::getInstance();
			$sharedfolders = $sharedfolderdb->importFromUsergroup($group->getUniqueID());
			
			if (count($sharedfolders) > 0) {
				$g['shared_folders'] = array();
				foreach($sharedfolders as $sharedfolder) {
					$g['shared_folders'][$sharedfolder->id] = $sharedfolder->name;
				}
			}
		}
		
		// Policy
		$policy = $group->getPolicy();
		$prefs_policy = $this->prefs->get('general', 'policy');
		$default_policy = $prefs_policy['default_policy'];
		$g['policy'] = $policy;
		$g['default_policy'] = array();
		foreach($policy as $key => $value) {
			$g['default_policy'][$key] = in_array($key, $default_policy);
		}
		
		// Settings
		$g['settings'] = array();
		$g['settings_default'] = array();
		
		$session_prefs_categs = array('session_settings_defaults', 'remote_desktop_settings',  'remote_applications_settings');
		foreach ($session_prefs_categs as $session_prefs_categ) {
			$session_prefs = $this->prefs->getElements('general', $session_prefs_categ);
			foreach($session_prefs as $session_pref) {
				$e = self::pref_element2dict($session_pref);
				$g['settings_default'][$session_prefs_categ][$e['id']] = $e;
			}
			
			$group_prefs = Abstract_UserGroup_Preferences::loadByUserGroupId($group->getUniqueID(), 'general', $session_prefs_categ);
			foreach($group_prefs as $group_pref) {
				$g['settings'][$session_prefs_categ][$group_pref->element_id] = $group_pref->value;
			}
		}
		
		return $g;
	}
	
	public function users_group_add($name_, $description_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		if (! $userGroupDB->isWriteable()) {
			Logger::error('api', 'Users Group Database is not writeable');
			return null;
		}
		
		$g = new UsersGroup(NULL, $name_, $description_, 1);
		
		$res = $userGroupDB->add($g);
		if (! $res) {
			Logger::error('api', 'Unable to create group '.$name_);
			return null;
		}
		
		$this->log_action('users_group_add', array('name' => $name_));
		return $g->getUniqueID();
	}
	
	public function users_group_remove($id_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		$group = $userGroupDB->import($id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Failed to import Usergroup "%s"', $id_));
			return false;
		}
		
		if ($group->type == 'static') {
			if (! $userGroupDB->isWriteable()) {
				Logger::error('api', 'Users Group Database is not writeable');
				return false;
			}
		}
		
		$ret = $userGroupDB->remove($group);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('users_group_remove', array('name' => $group->name));
		return true;
	}
	
	public function users_group_modify($id_, $name_, $description_, $published_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		$group = $userGroupDB->import($id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Failed to import Usergroup "%s"', $id_));
			return false;
		}
		
		if ($group->type == 'static') {
			if (! $userGroupDB->isWriteable()) {
				Logger::error('api', 'Users Group Database is not writeable');
				return false;
			}
		}
		
		$has_change = false;
		$changes = array();
		if ($name_ != null && $name_ != $group->name) {
			$changes['name'] = array('old' => $group->name, 'new' => $name_);
			$group->name = $name_;
			$has_change = true;
		}
		
		if ($description_ != null && $description_ != $group->description) {
			$changes['description'] = array('old' => $group->description, 'new' => $description_);
			$group->description = $description_;
			$has_change = true;
		}
		
		if ($published_ !== null && $published_ !== $group->published) {
			$changes['published'] = array('old' => $group->published, 'new' => (bool)$published_);
			$group->published = (bool)$published_;
			$has_change = true;
		}
		
		if (! $has_change)
			return false;
		
		$res = $userGroupDB->update($group);
		if (! $res) {
			return false;
		}
		
		$this->log_action('users_group_modify', array_merge(array('name' => $group->name), $changes));
		return true;
	}
	
	public function system_set_default_users_group($id_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		$group = $userGroupDB->import($id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Failed to import Usergroup "%s"', $id_));
			return false;
		}
		
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
			Logger::error('api', 'Failed to import Preference_Admin');
			return false;
		}
		
		$user_default_group_old = $prefs->get('general', 'user_default_group');
		if ($user_default_group_old == $group->getUniqueID()) {
			return true;
		}
		
		$mods_enable = $prefs->set('general', 'user_default_group', $group->getUniqueID());
		if (! $prefs->backup()) {
			Logger::error('api', 'Unable to save prefs');
			return false;
		}
		
		$this->log_action('system_set_default_users_group', array('value' => array(
			'old' => $user_default_group_old,
			'new' => $group->getUniqueID(),
		)));
		return true;
	}
	
	public function system_unset_default_users_group() {
		$this->check_authorized('manageUsersGroups');
		
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
			Logger::error('api', 'Failed to import Preference_Admin');
			return false;
		}
		
		$user_default_group_old = $prefs->get('general', 'user_default_group');
		if (is_null($user_default_group_old)) {
			return true;
		}
		
		$mods_enable = $prefs->set('general', 'user_default_group', null);
		if (! $prefs->backup()) {
			Logger::error('api', 'Unable to save prefs');
			return false;
		}
		
		$this->log_action('system_unset_default_users_group', array('value' => array(
			'old' => $user_default_group_old,
			'new' => null,
		)));
		return true;
	}
	
	public function users_group_dynamic_add($name_, $description_, $validation_type_) {
		$this->check_authorized('manageUsersGroups');
		
		if (Preferences::moduleIsEnabled('UserGroupDBDynamic') == false) {
			Logger::error('api', 'UserGroupDBDynamic module must be enabled');
			return false;
		}
		
		$g = new UsersGroup_dynamic(NULL, $name_, $description, 1, array(), $validation_type_);
		
		$res = $userGroupDB->add($g);
		if (! $res) {
			Logger::error('api', 'Unable to create group '.$name_);
			return false;
		}
		
		$this->log_action('users_group_dynamic_add', array('name' => $name_));
		return $g->getUniqueID();
	}
	
	public function users_group_dynamic_modify($id_, $rules_, $validation_type_) {
		$this->check_authorized('manageUsersGroups');
		
		if (Preferences::moduleIsEnabled('UserGroupDBDynamic') == false) {
			Logger::error('api', 'UserGroupDBDynamic module must be enabled');
			return false;
		}
		
		$group = $userGroupDB->import($id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Failed to import Usergroup "%s"', $id_));
			return false;
		}
		
		$rules = array();
		foreach ($rules_ as $rule) {
			if ($rule['value'] == '') {
				return false;
			}
			
			$buf = new UserGroup_Rule(NULL);
			$buf->attribute = $rule['attribute'];
			$buf->type = $rule['type'];
			$buf->value = $rule['value'];
			$buf->usergroup_id = $id_;
			
			$rules[] = $buf;
		}
		
		$group->rules = $rules;
		$group->validation_type = $validation_type_;
		
		$res = $userGroupDB->update($group);
		$this->log_action('users_group_dynamic_modify', array('name' => $name_));
		return $res;
	}
	
	public function users_group_dynamic_cached_add($name_, $description_, $validation_type_, $schedule_) {
		$this->check_authorized('manageUsersGroups');
		
		if (Preferences::moduleIsEnabled('UserGroupDBDynamicCached') == false) {
			Logger::error('api', 'UserGroupDBDynamicCached module must be enabled');
			return false;
		}
		
		$g = new UsersGroup_dynamic_cached(NULL, $name_, $description, 1, array(), $validation_type_, $schedule_);
		
		$res = $userGroupDB->add($g);
		if (! $res) {
			Logger::error('api', 'Unable to create group '.$name_);
			return false;
		}
		
		$this->log_action('users_group_dynamic_cached_add', array('name' => $name_));
		return $g->getUniqueID();
	}
	
	public function users_group_dynamic_cached_set_schedule($id_, $schedule_) {
		$this->check_authorized('manageUsersGroups');
		
		if (Preferences::moduleIsEnabled('UserGroupDBDynamicCached') == false) {
			Logger::error('api', 'UserGroupDBDynamicCached module must be enabled');
			return false;
		}
		
		$group = $userGroupDB->import($id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Failed to import Usergroup "%s"', $id_));
			return false;
		}
	
		$group->schedule = $schedule_;
		
		$res = $userGroupDB->update($group);
		$this->log_action('users_group_dynamic_cached_set_schedule', array('name' => $name_, 'schedule' => $schedule_));
		return $res;
	}
	
	public function users_group_settings_set($group_id_, $container_, $setting_, $value_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		$group = $userGroupDB->import($group_id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Failed to import Usergroup "%s"', $id_));
			return false;
		}
		
		$prefs = Preferences::getInstance();
		$session_settings_defaults = $prefs->getElements('general', $container_);
		if (! array_key_exists($setting_, $session_settings_defaults)) {
			return false;
		}
		
		$config_element = clone $session_settings_defaults[$setting_];
		$ugp = new UserGroup_Preferences($group->getUniqueID(), 'general', $container_, $setting_, $config_element->content);
		if (! is_null($value_)) {
			$ugp->value = $value_;
		}
		
		$ret = Abstract_UserGroup_Preferences::save($ugp);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('users_group_settings_set', array('group' => $group->name, $container_.'_'.$setting_ => array(
			'old' => $session_settings_defaults[$setting_]->content,
			'new' => $config_element->content,
		)));
		return true;
	}
	
	public function users_group_settings_remove($group_id_, $container_, $setting_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		$group = $userGroupDB->import($group_id_);
		if (! is_object($group)) {
			return false;
		}
		
		$prefs = Preferences::getInstance();
		$session_settings_defaults = $prefs->getElements('general', $container_);
		if (! array_key_exists($setting_, $session_settings_defaults)) {
			return false;
		}
		
		$ret = Abstract_UserGroup_Preferences::delete($group->getUniqueID(), 'general', $container_, $setting_);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('users_group_settings_remove', array('group' => $group->name, $container_.'_'.$setting_ => null));
		return true;
	}
	
	public function users_group_add_policy($group_id_, $rule_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		$group = $userGroupDB->import($group_id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Failed to import Usergroup "%s"', $group_id_));
			return false;
		}
		
		$policy_old = $group->getPolicy(false);
		$policy = $group->getPolicy(false);
		$policy[$rule_] = true;
		
		$group->updatePolicy($policy);
		$this->log_action('users_group_add_policy', array('group' => $group->name, 'value' => array(
			'old' => $policy_old,
			'new' => $policy,
		)));
		return true;
	}
	
	public function users_group_remove_policy($group_id_, $rule_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		$group = $userGroupDB->import($group_id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Failed to import Usergroup "%s"', $group_id_));
			return false;
		}
		
		$policy_old = $group->getPolicy(false);
		$policy = $group->getPolicy(false);
		$policy[$rule_] = false;
		
		$group->updatePolicy($policy);
		$this->log_action('users_group_remove_policy', array('group' => $group->name, 'value' => array(
			'old' => $policy_old,
			'new' => $policy,
		)));
		return true;
	}
	
	public function users_group_add_user($user_id_, $group_id_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		if (! $userGroupDB->isWriteable()) {
			Logger::error('api', 'Users Group Database is not writeable');
			return false;
		}
		
		$group = $userGroupDB->import($group_id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Unknown users group "%s"', $group_id_));
			return false;
		}
		
		$ret = Abstract_Liaison::save('UsersGroup', $user_id_, $group_id_);
		if ($ret !== true) {
			Logger::error('api', sprintf('Unable to add user %s to group "%s"', $user_id_, $group->name));
			return false;
		}
		
		$this->log_action('users_group_add_user', array('group' => $group->name, 'login' => $user_id_));
		return true;
	}
	
	public function users_group_remove_user($user_id_, $group_id_) {
		$this->check_authorized('manageUsersGroups');
		
		$userGroupDB = UserGroupDB::getInstance();
		if (! $userGroupDB->isWriteable()) {
			Logger::error('api', 'Users Group Database is not writeable');
			return false;
		}
		
		$group = $userGroupDB->import($group_id_);
		if (! is_object($group)) {
			Logger::error('api', sprintf('Unknown users group "%s"', $group_id_));
			return false;
		}
		
		if ($group->isDefault()) {
			Logger::error('api', sprintf('Unable to add users to group %s, group already contain all users (default users group)', $group->name));
			return false;
		}

		Abstract_Liaison::delete('UsersGroup', $user_id_, $group_id_);
		$this->log_action('users_group_remove_user', array('group' => $group->name, 'login' => $user_id_));
		return true;
	}
	
	public function publication_add($users_group_, $applications_group_) {
		$this->check_authorized('managePublications');
		
		$usersGroupDB = UserGroupDB::getInstance();
		$usergroup = $usersGroupDB->import($users_group_);
		if (is_object($usergroup) == false) {
			Logger::error('api', sprintf("Importing usergroup '%s' failed", $users_group_));
			return false;
		}
		
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		$applicationsgroup = $applicationsGroupDB->import($applications_group_);
		if (is_object($applicationsgroup) == false) {
			Logger::error('api', sprintf("Importing applications group '%s' failed", $applications_group_));
			return false;
		}
		
		$l = Abstract_Liaison::load('UsersGroupApplicationsGroup', $users_group_, $applications_group_);
		if (! is_null($l)) {
			Logger::error('api', 'This publication already exists');
			return false;
		}
		
		$ret = Abstract_Liaison::save('UsersGroupApplicationsGroup', $users_group_, $applications_group_);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('publication_add', array('users_group' => $usergroup->name, 'applications_group' => $applicationsgroup->name));
		return $ret;
	}
	
	public function publication_remove($users_group_, $applications_group_) {
		$this->check_authorized('managePublications');
		
		$usersGroupDB = UserGroupDB::getInstance();
		$usergroup = $usersGroupDB->import($users_group_);
		if (is_object($usergroup) == false) {
			Logger::error('api', sprintf("Importing usergroup '%s' failed", $users_group_));
			return false;
		}
		
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		$applicationsgroup = $applicationsGroupDB->import($applications_group_);
		if (is_object($applicationsgroup) == false) {
			Logger::error('api', sprintf("Importing applications group '%s' failed", $applications_group_));
			return false;
		}
		
		$l = Abstract_Liaison::load('UsersGroupApplicationsGroup', $users_group_, $applications_group_);
		if (is_null($l)) {
			Logger::error('api', 'This publication doesn\'t exists');
			return false;
		}
		
		$ret = Abstract_Liaison::delete('UsersGroupApplicationsGroup', $users_group_, $applications_group_);
		if (! $ret) {
			return true;
		}
		
		$this->log_action('publication_remove', array('users_group' => $usergroup->name, 'applications_group' => $applicationsgroup->name));
		return $ret;
	}
	
	public function shared_folders_list() {
		$this->check_authorized('viewSharedFolders');
		
		if (! Preferences::moduleIsEnabled('SharedFolderDB')) {
			return null;
		}
		
		$sharedfolderdb = SharedFolderDB::getInstance();
		$sharedfolders = $sharedfolderdb->getList();
		
		$ret = array();
		foreach($sharedfolders as $sharedfolder) {
			$s = array(
				'id' => $sharedfolder->id,
				'name' => $sharedfolder->name,
				'server' => $sharedfolder->server,
				'status' => $sharedfolder->status,
			);
			
			$ret[$s['id']] = $s;
		}
		
		return $ret;
	}
	
	public function shared_folder_info($id_) {
		$this->check_authorized('viewSharedFolders');
		
		if (! Preferences::moduleIsEnabled('SharedFolderDB')) {
			return null;
		}
		
		$userGroupDB = UserGroupDB::getInstance();
		
		$sharedfolderdb = SharedFolderDB::getInstance();
		$sharedfolder = $sharedfolderdb->import($id_);
		if (! is_object($sharedfolder)) {
			return null;
		}
		
		$s = array(
			'id' => $sharedfolder->id,
			'name' => $sharedfolder->name,
			'server' => $sharedfolder->server,
			'status' => $sharedfolder->status,
		);
		
		$groups = $sharedfolder->getPublishedUserGroups();
		if (count($groups) > 0) {
			$s['groups'] = array();
			foreach ($groups as $mode => $groups2) {
				$s['groups'][$mode] = array();
				foreach ($groups2 as $group) {
					$s['groups'][$mode][$group->getUniqueID()] = $group->name;
				}
			}
		}
		
		return $s;
	}
	
	public function shared_folder_add($name_, $server_id_) {
		$this->check_authorized('manageSharedFolders');
		
		if (! Preferences::moduleIsEnabled('SharedFolderDB')) {
			Logger::error('api', 'Shared folder management is not enabled');
			return null;
		}
		
		$sharedfolderdb = SharedFolderDB::getInstance();
		
		$buf = $sharedfolderdb->importFromName($name_);
		if (count($buf) > 0) {
			Logger::error('api', 'A shared folder with this name already exists');
			return null;
		}
		
		if ($server_id_ != null) {
			$a_server = Abstract_Server::load($server_id_);
			if (! is_object($a_server)) {
				Logger::error('api', sprintf('Unknown server "%s"', $server_id_));
				return null;
			}
		}
		else {
			$a_server = $sharedfolderdb->chooseFileServer();
			if (is_object($a_server) === false) {
				Logger::error('api', 'No server avalaible for shared folder');
				return null;
			}
		}
		
		$sharedfolder = new SharedFolder(NULL, $name_, $a_server->id, NetworkFolder::NF_STATUS_OK);
		
		$ret = $sharedfolderdb->addToServer($sharedfolder, $a_server);
		if (! $ret) {
			Logger::error('api', 'Unable to add shared folder');
			return null;
		}
		
		$this->log_action('shared_folder_add', array('name' => $name_, 'server_id' => $a_server->id, 'server_name' => $a_server->getDisplayName()));
		return $sharedfolder->id;
	}
	
	public function shared_folder_remove($id_) {
		$this->check_authorized('manageSharedFolders');
		
		if (! Preferences::moduleIsEnabled('SharedFolderDB')) {
			Logger::error('api', 'Shared folder management is not enabled');
			return false;
		}
		
		$sharedfolderdb = SharedFolderDB::getInstance();
		
		$sharedfolder = $sharedfolderdb->import($id_);
		if (! is_object($sharedfolder)) {
			Logger::error('api', sprintf('Unknown shared folder "%s"', $sharedfolder));
			return false;
		}
		
		$res = $sharedfolderdb->remove($sharedfolder->id);
		if ($res !== true) {
			Logger::error('api', sprintf('Unable to delete network folder "%s"', $sharedfolder->name));
			return false;
		}
		
		$server = Abstract_Server::load($sharedfolder->server);
		if ($sharedfolderdb->isInternal()) {
			$server->deleteNetworkFolder($sharedfolder->id, true);
		}
		
		$this->log_action('shared_folder_remove', array('name' => $sharedfolder->name, 'server_id' => $server->id, 'server_name' => $server->getDisplayName()));
		return true;
	}
	
	public function shared_folder_rename($id_, $name_) {
		$this->check_authorized('manageSharedFolders');
		
		if (! Preferences::moduleIsEnabled('SharedFolderDB')) {
			Logger::error('api', 'Shared folder management is not enabled');
			return false;
		}
		
		$sharedfolderdb = SharedFolderDB::getInstance();
		
		$sharedfolder = $sharedfolderdb->import($id_);
		if (! is_object($sharedfolder)) {
			Logger::error('api', sprintf('Unknown shared folder "%s"', $sharedfolder));
			return false;
		}
		
		if ($name_ == $sharedfolder->name) {
			return false;
		}
		
		if ($sharedfolderdb->exists($name_)) {
			Logger::error('api', 'A shared folder with this name already exists');
			return false;
		}
		
		$name_old = $sharedfolder->name;
		if ($name_ == $name_old) {
			return true;
		}
		
		$sharedfolder->name = $name_;
		$ret = $sharedfolderdb->update($sharedfolder);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('shared_folder_rename', array('name' => array(
			'old' => $name_old,
			'new' => $name_
		)));
		return true;
	}
	
	public function shared_folder_add_group($group_id_, $share_id_, $mode_) {
		$this->check_authorized('manageSharedFolders');
		
		if (! Preferences::moduleIsEnabled('SharedFolderDB')) {
			Logger::error('api', 'Shared folder management is not enabled');
			return false;
		}
		
		if (! in_array($mode_, array('ro', 'rw'))) {
			Logger::error('api', 'Invalid "mode" argument');
			return false;
		}
		
		$sharedfolderdb = SharedFolderDB::getInstance();
		$sharedfolder = $sharedfolderdb->import($share_id_);
		if (! is_object($sharedfolder)) {
			Logger::error('api', sprintf('Unknown shared folder "%s"', $share_id_));
			return false;
		}
		
		$usergroupDB = UserGroupDB::getInstance();
		$group = $usergroupDB->import($group_id_);
		if (is_object($group) === false) {
			Logger::error('api', sprintf('Unknown users group "%s"', $group_id_));
			return false;
		}
		
		$ret = $sharedfolder->addUserGroup($group, $mode_);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('shared_folder_add_group', array('share_name' => $sharedfolder->name, 'group_name' => $group->name));
		return true;
	}
	
	public function shared_folder_remove_group($group_id_, $share_id_) {
		$this->check_authorized('manageSharedFolders');
		
		if (! Preferences::moduleIsEnabled('SharedFolderDB')) {
			Logger::error('api', 'Shared folder management is not enabled');
			return false;
		}
		
		$sharedfolderdb = SharedFolderDB::getInstance();
		$sharedfolder = $sharedfolderdb->import($share_id_);
		if (! is_object($sharedfolder)) {
			Logger::error('api', sprintf('Unknown shared folder "%s"', $share_id_));
			return false;
		}
		
		$usergroupDB = UserGroupDB::getInstance();
		$group = $usergroupDB->import($group_id_);
		if (is_object($group) === false) {
			Logger::error('api', sprintf('Unknown users group "%s"', $group_id_));
			return false;
		}
		
		$ret = $sharedfolder->delUserGroup($group);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('shared_folder_remove_group', array('share_name' => $sharedfolder->name, 'group_name' => $group->name));
		return true;
	}
	
	public function users_profiles_list() {
		$this->check_authorized('viewSharedFolders');
		
		if (! Preferences::moduleIsEnabled('ProfileDB')) {
			return null;
		}
		
		$profiledb = ProfileDB::getInstance();
		$profiles = $profiledb->getList();
		if (is_array($profiles) == false) {
			return null;
		}
		
		$ret = array();
		foreach($profiles as $profile) {
			$s = array(
				'id' => $profile->id,
				'server' => $profile->server,
				'status' => $profile->status,
			);
			
			$users = $profile->getUsers();
			if (count($users) > 0) {
				$s['users'] = array();
				foreach($users as $user) {
					$s['users'][$user->getAttribute('login')] = $user->getAttribute('displayname');
				}
			}
			
			$ret[$s['id']] = $s;
		}
		
		return $ret;
	}
	
	public function user_profile_info($id_) {
		$this->check_authorized('viewSharedFolders');
		
		if (! Preferences::moduleIsEnabled('ProfileDB')) {
			return null;
		}
		
		$profiledb = ProfileDB::getInstance();
		$profile = $profiledb->import($id_);
		if (! is_object($profile)) {
			return null;
		}
		
		$s = array(
			'id' => $profile->id,
			'server' => $profile->server,
			'status' => $profile->status,
		);
		
		$users = $profile->getUsers();
		if (count($users) > 0) {
			$s['users'] = array();
			foreach($users as $user) {
				$s['users'][$user->getAttribute('login')] = $user->getAttribute('displayname');
			}
		}
		
		return $s;
	}
	
	public function user_profile_add($user_login_) {
		$this->check_authorized('manageServers');
		
		if (! Preferences::moduleIsEnabled('ProfileDB')) {
			return false;
		}
		
		$userDB = UserDB::getInstance();
		$user = $userDB->import($user_login_);
		if (! is_object($user)) {
			Logger::error('api', sprintf('Failed to import user "%s"', $user_login_));
			return false;
		}
		
		$profiledb = ProfileDB::getInstance();
		$fileserver = $profiledb->chooseFileServer();
		if (! is_object($fileserver)) {
			Logger::error('api', 'Unable to get a valid FileServer');
			return false;
		}
		
		$profile = new Profile(NULL, $fileserver->id, NetworkFolder::NF_STATUS_OK);
		if (! $profiledb->addToServer($profile, $fileserver)) {
			Logger::error('api', 'Unable to add the profile to the FileServer');
			return false;
		}
		
		$ret = $profile->addUser($user);
		if (! $ret) {
			return false;
		}
		
		$this->log_action('user_profile_add', array('user_login' => $user_login_, 'server_id' => $fileserver->id, 'server_name' => $fileserver->getDisplayName()));
		return true;
	}
	
	public function user_profile_remove($id_) {
		$this->check_authorized('manageServers');
		
		if (! Preferences::moduleIsEnabled('ProfileDB')) {
			return false;
		}
		
		$profiledb = ProfileDB::getInstance();
		$network_folder = $profiledb->import($id_);
		if (! is_object($network_folder)) {
			Logger::error('api', sprintf('Unable to load profile "%s"', $id_));
			return false;
		}
		
		$sessions = Abstract_Session::getByNetworkFolder($network_folder->id);
		if (count($sessions) > 0) {
			Logger::error('api', sprintf('Unable to remove user profile "%s" because there is at least one running session using it', $id_));
			return false;
		}
		
		$res = $profiledb->remove($network_folder->id);
		$server = Abstract_Server::load($network_folder->server);
		if ($profiledb->isInternal())
			$server->deleteNetworkFolder($network_folder->id, true);
		
		if (! $res) {
			return false;
		}
		
		$this->log_action('user_profile_remove', array('id' => $id_, 'server_id' => $server->id, 'server_name' => $server->getDisplayName()));
		return true;
	}
	
	public function network_folder_remove($id_) {
		$this->check_authorized('manageServers');
		
		$network_folder = Abstract_Network_Folder::load($id_);
		if (! is_object($network_folder)) {
			Logger::error('api', sprintf("Network folder '%s' do not exists", $id_));
			return false;
		}
		
		$server = Abstract_Server::load($network_folder->server);
		if ($server && $server->isOnline()) {
			$server->deleteNetworkFolder($network_folder->id, true);
		}
		
		Abstract_Network_Folder::delete($network_folder->id);
		$this->log_action('network_folder_remove', array('id' => $id_));
		return true;
	}
	
	public function sessions_count() {
		$this->check_authorized('viewStatus');
		
		$ret = array('total' => 0);
		foreach (Session::getAllStates() as $state) {
			$nb = Abstract_Session::countByStatus($state);
			if ($nb == 0) {
				continue;
			}
			
			$ret[$state] = $nb;
			$ret['total']+= $nb;
		}
		
		return $ret;
	}
	
	private static function generate_session_array($session_) {
		$s = array(
			'id' => $session_->id,
			'status' => $session_->getAttribute('status'),
			'mode' => $session_->getAttribute('mode'),
			'user_login' => $session_->getAttribute('user_login'),
			'user_displayname' => $session_->getAttribute('user_displayname'),
			'servers' => $session_->getAttribute('servers'),
		);
		
		if ($session_->hasAttribute('start_time')) {
			$s['start_time'] = $session_->getAttribute('start_time');
		}
		
		if ($session_->mode == Session::MODE_DESKTOP) {
			$s['desktop_server'] = $session_->server;
		}
		
		return $s;
	}
	
	public function sessions_list($offset_) {
		$this->check_authorized('viewStatus');
		
		$ret = array();
		
		$search_limit = $this->prefs->get('general', 'max_items_per_page');
		
		$sessions = Abstract_Session::load_partial($search_limit, $offset_); 
		
		foreach($sessions as $session) {
			$s = self::generate_session_array($session);
			$ret[$s['id']] = $s;
		}
		
		return $ret;
	}
	
	public function sessions_list_by_server($server_, $offset_) {
		$this->check_authorized('viewStatus');
		
		$ret = array();
		
		$search_limit = $this->prefs->get('general', 'max_items_per_page');
		
		$sessions = Abstract_Session::getByServer($server_, $search_limit, $offset_);
		
		foreach($sessions as $session) {
			$s = self::generate_session_array($session);
			$ret[$s['id']] = $s;
		}
		
		return $ret;
	}
	
	public function session_info($id_) {
		$this->check_authorized('viewStatus');
		
		$session = Abstract_Session::load($id_);
		if (! $session) {
			return null;
		}
		
		//FIX ME?
		$session->getStatus();
		
		$s = self::generate_session_array($session);
		
		if (Preferences::moduleIsEnabled('ApplicationDB')) {
			$applicationDB = ApplicationDB::getInstance();
			
			$applications = array();
			$running_apps = $session->getRunningApplications();
			foreach ($running_apps as $instance_id => $instance) {
				$myapp = $applicationDB->import($instance['application']);
				if (! is_object($myapp)) {
					continue;
				}
				
				$applications[$myapp->getAttribute('id')] = $myapp->getAttribute('name');
			}
			
			if (count($applications) > 0) {
				$s['instances'] = $applications;
			}
			
			$available_apps = $session->getPublishedApplications();
			if (count($available_apps) > 0) {
				$s['applications'] = array();
				foreach ($available_apps as $app_id => $myapp) {
					$s['applications'][$myapp->getAttribute('id')] = $myapp->getAttribute('name');
				}
			}
		}
		
		
		return $s;
	}
	
	public function session_kill($id_) {
		$this->check_authorized('manageSession');
		
		$session = Abstract_Session::load($id_);
		if (! $session) {
			Logger::error('api', sprintf('Unknown session "%s"', $id_));
			return false;
		}
		
		$ret = $session->orderDeletion(true, Session::SESSION_END_STATUS_ADMINKILL);
		if (! $ret) {
			Abstract_Session::delete($session->id);
			Logger::error('api', sprintf("Unable to delete session '%s'", $session->id));
			return false;
		}
		
		$this->log_action('session_kill', array('id' => $id_, 'user' => $user_login));
		return true;
	}
	
	public function log_preview() {
		$this->check_authorized('viewStatus');
		
		$logfiles = glob(SESSIONMANAGER_LOGS.'/*.log');
		$logfiles = array_reverse($logfiles);
		
		$ret = array();
		foreach ($logfiles as $logfile) {
			$obj = new FileTailer($logfile);
			$lines = $obj->tail(20);
			$ret[basename($logfile)] = $lines;
		}
		
		$servers = Abstract_Server::load_all();
		$ret['servers'] = array();
		foreach ($servers as $server) {
			$buf = new Server_Logs($server);
			$buf->process();
			
			$lines = $buf->getLog(20);
			if ($lines !== false)
				$ret['servers'][$server->id] = explode("\n", $lines);
		}
		
		return $ret;
	}
	
	
	public function log_download($log_) {
		$this->check_authorized('viewStatus');
		
		$filename = SESSIONMANAGER_LOGS.'/'.$log_.'.log';
		if (file_exists($filename)) {
			$fp = @fopen($filename, 'r');
			if ($fp === false) {
				return null;
			}
			
			$buf = '';
			while ($str = fgets($fp, 4096))
				$buf.= $str;
			
			fclose($fp);
			return $buf;
		}
		
		$server = Abstract_Server::load($log_);
		if (! $server) {
			return null;
		}
		
		$l = new Server_Logs($server);
		$l->process();
		
		$buf = '';
		while ($str = $l->getContent()) {
			$buf.= $str;
		}
		
		return $buf;
	}
	
	private static function generate_sessionreport_array($session_) {
		$s = array(
			'id' => $session_->getId(),
			'user' => $session_->getUser(),
			'start' => $session_->getStartTime(),
			'end' => $session_->getStopTime(),
			'stop_reason' => $session_->getStopWhy(),
			'server' => $session_->getServer(),
			'data' => $session_->getData(),
		);
		
		if ($s['stop_reason'] == '' || is_null($s['stop_reason']))
			$s['stop_reason'] = 'unknown';
		
		return $s;
	}
	
	private static function generate_sessionsreport_array($sessions_) {
		$ret = array();
		foreach($sessions_ as $session) {
			if (is_null($session->getStopTime())) { // todo: or stop_stamp=NULL
				if (! Abstract_Session::exists($session->getId())) {
					// Before the v2.5, most of sessions report was empty ...
					// Logger::warning('api', 'Invalid reporting item session '.$p['id']);
					continue;
				}
			}
			
			$s = self::generate_sessionreport_array($session);
			$ret[$s['id']] = $s;
		}
		
		return $ret;
	}
	
	public function sessions_reports_list($start_, $stop_) {
		$this->check_authorized('viewStatus');
		
		$sessions = Abstract_ReportSession::load_by_start_time_range($start_, $stop_);
		return self::generate_sessionsreport_array($sessions);
	}
	
	public function sessions_reports_list2($start_, $stop_, $server_) {
		// Should use this function only ??
		$this->check_authorized('viewStatus');
		
		$sessions = Abstract_ReportSession::load_by_start_and_stop_time_range($start_, $stop_, $server_);
		return self::generate_sessionsreport_array($sessions);
	}
	
	public function sessions_reports_list3($from_, $to_, $user_login_, $limit_) {
		$this->check_authorized('viewStatus');
		
		$sessions = Abstract_ReportSession::load_partial($from_, $to_, $user_login_, $limit_);
		return self::generate_sessionsreport_array($sessions);
	}
	
	public function session_report_info($id_) {
		$this->check_authorized('viewStatus');
		
		$report = Abstract_ReportSession::load($id_);
		if (! $report) {
			return null;
		}
		
		return self::generate_sessionreport_array($report);
	}
	
	public function session_report_remove($id_) {
		$this->check_authorized('manageReporting');
		
		if (! Abstract_ReportSession::exists($id_)) {
			Logger::error('api', sprintf('Unknown archived session "%s"', $id_));
			return false;
		}
		
		$ret = Abstract_ReportSession::delete($id_);
		if ($ret !== true) {
			Logger::error('api', sprintf('Unable to delete archived session "%s"', $id_));
			return false;
		}
		
		$this->log_action('session_report_remove', array('id' => $id_));
		return true;
	}
	
	public function servers_reports_list($start_, $stop_) {
		$this->check_authorized('viewStatus');
		
		$ret = array();
		
		$reports = Abstract_ReportServer::load_partial($start_, $stop_);
		foreach($reports as $report) {
			$r = array(
				'time' => $report->getTime(),
				'id' => $report->getId(),
				'fqdn' => $report->getFQDN(),
				'external_name' => $report->getExternalName(),
				'time' => $report->getTime(),
				'ram' => $report->getRAM(),
				'cpu' => $report->getCPU(),
				'data' => $report->getData(),
			);
			
			$ret[] = $r;
		}
		
		return $ret;
	}
	
	private static function checkup_liaison($type_, $element_, $group_) {
		switch ($type_) {
			case 'ApplicationServer':
				$applicationDB = ApplicationDB::getInstance();
				$buf = $applicationDB->import($element_);
				if (! is_object($buf))
					return 'Application "'.$element_.'" does not exist';
				
				$buf = Abstract_Server::load($group_);
				if (! $buf)
					return 'Server "'.$group_.'" does not exist';
				break;
			
			case 'AppsGroup':
				$applicationDB = ApplicationDB::getInstance();
				$buf = $applicationDB->import($element_);
				if (! is_object($buf))
					return 'Application "'.$element_.'" does not exist';
				
				$applicationsGroupDB = ApplicationsGroupDB::getInstance();
				$buf = $applicationsGroupDB->import($group_);
				if (! is_object($buf))
					return 'ApplicationsGroup "'.$group_.'" does not exist';
				break;
			
			case 'ApplicationMimeType':
				$applicationDB = ApplicationDB::getInstance();
				$buf = $applicationDB->import($element_);
				if (! is_object($buf))
					return 'Application "'.$element_.'" does not exist';
				break;
			
			case 'ServerSession':
				$buf = Abstract_Server::load($element_);
				if (! $buf)
					return 'Server "'.$element_.'" does not exist';
				
				$buf = Abstract_Session::load($group_);
				if (! $buf)
					return 'Session "'.$group_.'" does not exist';
				break;
			
			case 'UserProfile':
				$profiledb = ProfileDB::getInstance();
				$userDB = UserDB::getInstance();
				$buf = $userDB->import($element_);
				if (! is_object($buf))
					return 'User "'.$element_.'" does not exist';
				
				$buf = $profiledb->import($group_);
				if (! $buf)
					return 'Profile "'.$group_.'" does not exist';
				break;
			
			case 'UsersGroup':
				$userDB = UserDB::getInstance();
				$buf = $userDB->import($element_);
				if (! is_object($buf))
					return 'User "'.$element_.'" does not exist';
				
				$userGroupDB = UserGroupDB::getInstance();
				$buf = $userGroupDB->import($group_);
				if (! is_object($buf))
					return 'UserGroup "'.$group_.'" does not exist';
				break;
			
			case 'UsersGroupApplicationsGroup':
				$userGroupDB = UserGroupDB::getInstance();
				$buf = $userGroupDB->import($element_);
				if (! is_object($buf))
					return 'UserGroup "'.$element_.'" does not exist';
				
				$applicationsGroupDB = ApplicationsGroupDB::getInstance();
				$buf = $applicationsGroupDB->import($group_);
				if (! is_object($buf))
					return 'ApplicationsGroup "'.$group_.'" does not exist';
				break;
			
			case 'UsersGroupCached':
				$userDB = UserDB::getInstance();
				$buf = $userDB->import($element_);
				if (! is_object($buf))
					return 'User "'.$element_.'" does not exist';
				
				$userGroupDB = UserGroupDB::getInstance();
				$buf = $userGroupDB->import($group_);
				if (! is_object($buf))
					return 'UserGroup "'.$group_.'" does not exist';
				break;
		}
		
		return true;
	}
	
	private static function get_liaisons_types() {
		$liaisons_types = array('ApplicationServer', 'AppsGroup', 'ApplicationMimeType', 'ServerSession', 'UsersGroup', 'UsersGroupApplicationsGroup');
		if (Preferences::moduleIsEnabled('ProfileDB')) {
			$liaisons_types []= 'UserProfile';
		}
		if (Preferences::moduleIsEnabled('UsersGroupDBDynamic') || Preferences::moduleIsEnabled('UsersGroupDBDynamicCached')) {
			$liaisons_types []= 'UsersGroupCached';
		}
		
		return $liaisons_types;
	}
	
	public function checkup() {
		$this->check_authorized('manageConfiguration');
		
		$ret = array();
		
		try {
			@include_once('libchart/classes/libchart.php');
		} catch (Exception $e) {}
		
		$ret['php'] = array(
			'cURL'		=>	(function_exists('curl_init')),
			'Imagick'	=>	(class_exists('Imagick')),
			'LDAP'		=>	(function_exists('ldap_connect')),
			'libchart'	=>	(class_exists('LineChart')),
			'MySQL'		=>	(function_exists('mysql_connect')),
			'XML'		=>	(class_exists('DomDocument'))
		);
		
		$liaisons_types = self::get_liaisons_types();
		
		$ret['liaisons'] = array();
		foreach ($liaisons_types as $liaisons_type) {
			$liaisons = Abstract_Liaison::load($liaisons_type, NULL, NULL);
			if (is_null($liaisons))
				continue;
			
			$l = array('id' => $liaisons_type);
			
			$everything_ok = true;
			foreach ($liaisons as $liaison) {
				$r = self::checkup_liaison($liaisons_type, $liaison->element, $liaison->group);
				if ($r === true)
					continue;
				
				$everything_ok = false;
				if (! array_key_exists('errors', $l)) {
					$l['errors'] = array();
				}
				
				$err = array(
					'element' => $liaison->element,
					'group' => $liaison->group,
					'text' => $r,
				);
				
				$l['errors'][] = $err;
			}
			
			$l['status'] = $everything_ok;
			$ret['liaisons'][$l['id']] = $l;
		}
		
		$ret['conf'] = array('default_users_group' => array('status' => true));
		$userGroupDB = UserGroupDB::getInstance();
		
		$default_usergroup_id = $this->prefs->get('general', 'user_default_group');
		if ($default_usergroup_id != '') {
			$group = $userGroupDB->import($default_usergroup_id);
			if (! is_object($group)) {
				$ret['conf']['default_users_group']['status']  = false;
				$ret['conf']['default_users_group']['text']  = 'Usergroup "'.$default_usergroup_id.'" does not exist';
			}
		}
		
		return $ret;
	}
	
	public function cleanup_liaisons() {
		$this->check_authorized('manageConfiguration');
		
		foreach (self::get_liaisons_types() as $liaisons_type) {
			$liaisons = Abstract_Liaison::load($liaisons_type, NULL, NULL);
			if (is_null($liaisons)) {
				continue;
			}
			
			foreach ($liaisons as $k => $liaison) {
				if (self::checkup_liaison($liaisons_type, $liaison->element, $liaison->group) === true) {
					continue;
				}
				
				Abstract_Liaison::delete($liaisons_type, $liaison->element, $liaison->group);
			}
		}
		
		$this->log_action('cleanup_liaisons');
		return true;
	}

	public function cleanup_preferences() {
		$this->check_authorized('manageConfiguration');
		
		$userGroupDB = UserGroupDB::getInstance();
		$prefs = new Preferences_admin();
		
		$default_usergroup_id = $prefs->get('general', 'user_default_group');
		if ($default_usergroup_id != '') {
			$group = $userGroupDB->import($default_usergroup_id);
			if (! is_object($group)) {
				// unset the default usergroup
				$mods_enable = $prefs->set('general', 'user_default_group', '');
				$prefs->backup();
			}
		}
		
		$this->log_action('cleanup_preferences');
		return true;
	}
	
	private static function generate_news_array($news_) {
		return array(
			'id' => $news_->id,
			'title' => $news_->title,
			'content' => $news_->content,
			'timestamp' => $news_->timestamp,
		);
	}
	
	public function news_list() {
		$this->check_authorized('manageNews');
		
		$news = Abstract_News::load_all();
		
		$ret = array();
		foreach($news as $new) {
			$n = self::generate_news_array($new);
			
			$ret[$n['id']] = $n;
		}
		
		return $ret;
	}
	
	public function news_info($id_) {
		$this->check_authorized('manageNews');
		
		$news = Abstract_News::load($id_);
		if (! $news) {
			return null;
		}
		
		return self::generate_news_array($news);
	}
	
	public function news_modify($id_, $title_, $content_) {
		$this->check_authorized('manageNews');
		
		$news = Abstract_News::load($id_);
		if (! $news) {
			return false;
		}
		
		$news->title = $title_;
		$news->content = $content_;
		Abstract_News::save($news);
		
		$this->log_action('news_modify', array('id' => $id_, 'title' => $title_, 'content' => $content_));
		return true;
	}
	
	public function news_add($title_, $content_) {
		$this->check_authorized('manageNews');
		
		$news = new News('');
		$news->title = $title_;
		$news->content = $content_;
		$news->timestamp = time();
		$ret = Abstract_News::save($news);
		if ($ret !== true) {
			return false;
		}
		
		$this->log_action('news_add', array('id' => $news->id, 'title' => $title_, 'content' => $content_));
		return $news->id;
	}
	
	public function news_remove($id_) {
		$this->check_authorized('manageNews');
		
		$buf = Abstract_News::delete($id_);
		if (! $buf) {
			return false;
		}
		
		$this->log_action('news_remove', array('id' => $id_));
		return true;
	}
	
	public function session_simulate($user_login_) {
		$this->check_authorized('viewSummary');
		
		$userDB = UserDB::getInstance();
		$user = $userDB->import($user_login_);
		if (! $user) {
			return null;
		}
		
		$userGroupDB = UserGroupDB::getInstance();
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		try {
			$sessionmanagement = SessionManagement::getInstance();
		}
		catch (Exception $err) {
			die_error('Unable to instanciate SessionManagement: '.$err->getMessage(), __FILE__, __LINE__);
		}
		
		$info = array(); // Should only request SessionManagement instance to catch all these information ...
		$info['settings'] = $user->getSessionSettings('session_settings_defaults');
		
		$info['user_grps'] = array();
		$users_grps = $user->usersGroups();
		foreach ($users_grps as $group_id => $group) {
			$info['user_grps'][$group_id]= $group->name;
		}
		
		$info['apps_grps'] = array();
		$apps_grps = $user->appsGroups();
		foreach ($apps_grps as $agrp_id) {
			$agrp = $applicationsGroupDB->import($agrp_id);
			if (! is_object($agrp))
				continue;
			
			$info['apps_grps'][$agrp_id]= $agrp->name;
		}
		
		$info['apps'] = array();
		$applications = $user->applications();
		foreach ($applications as $application) {
			$a = array(
				'id' => $application->getAttribute('id'),
				'name' => $application->getAttribute('name'),
				'type' => $application->getAttribute('type'),
			);
			
			$info['apps'][$a['id']] = $a;
		}
		
		$info['shared_folders'] = array();
		if (array_key_exists('enable_sharedfolders', $info['settings']) && $info['settings']['enable_sharedfolders'] == 1) {
			$shared_folders = $user->getSharedFolders();
			foreach ($shared_folders as $shared_folder) {
				$info['shared_folders'][$shared_folder->id] = $shared_folder->name;
			}
		}
		
		$info['profiles'] = array();
		if (array_key_exists('enable_profiles', $info['settings']) && $info['settings']['enable_profiles'] == 1) {
			$profiles = $user->getProfiles();
			foreach ($profiles as $profile) {
				$info['profiles'][$profile->id] = $profile->id;
			}
		}
		
		$remote_desktop_settings = $user->getSessionSettings('remote_desktop_settings');
		$remote_desktop_enabled = ($remote_desktop_settings['enabled'] == 1);
		$remote_applications_settings = $user->getSessionSettings('remote_applications_settings');
		$remote_applications_enabled = ($remote_applications_settings['enabled'] == 1);
	
		$sessionmanagement2 = clone($sessionmanagement);
		$sessionmanagement2->user = $user;
		$info['can_start_session_desktop'] = $remote_desktop_enabled && 
			$sessionmanagement2->getDesktopServer() && 
			$sessionmanagement2->buildServersList(true);
		
		$sessionmanagement2 = clone($sessionmanagement);
		$sessionmanagement2->user = $user;
		$info['can_start_session_applications'] = $remote_applications_enabled &&
			$sessionmanagement2->buildServersList(true);
		
		if ($info['can_start_session_desktop'] || $info['can_start_session_applications']) {
			$sessionmanagement2 = clone($sessionmanagement);
			$sessionmanagement2->user = $user;
			$servers = $sessionmanagement2->chooseApplicationServers();
			
			$info['servers'] = array();
			foreach ($servers as $server) {
				$s = array(
					'id' => $server->id,
					'name' => $server->getDisplayName(),
					'type' => $server->getAttribute('type'),
				);
			}
			
			$info['servers'][$s['id']] = $s;
		}
		
		return $info;
	}
	
	private static function generate_admin_action_array($action_) {
		return array(
			'when' => $action_->when,
			'who' => $action_->who,
			'what' => $action_->what,
			'where' => $action_->where,
			'infos' => $action_->infos,
		);
	}
	
	public function admin_actions_list($offset_=0) {
		$this->check_authorized('viewStatus');
		
		$reports = Abstract_AdminAction::load_limited($this->prefs->get('general', 'max_items_per_page'), $offset_);
		
		$ret = array();
		foreach($reports as $report) {
			$n = self::generate_admin_action_array($report);
			
			$ret[]= $n;
		}
		
		return $ret;
	}
}

if (defined('SESSIONMANAGER_ADMIN_DEBUG') && SESSIONMANAGER_ADMIN_DEBUG === true && ! isset($_SESSION['admin_ovd_user'])) {
	// turn off the wsdl cache
	ini_set('soap.wsdl_cache_enabled', 0);
}

$server = new SoapServer('api.wsdl');
$server->setClass('OvdAdminSoap');

try {
	$server->handle();
} catch (Exception $e) {
	Logger::error('api', 'Soap server error: '.$e);
	throw($e);
}
