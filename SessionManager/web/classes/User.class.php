<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2014
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
class User {
	private $attributes;

	public function __construct(){
		$this->attributes = array();
	}

	public function setAttribute($myAttribute_,$value_){
		$this->attributes[$myAttribute_] = $value_;
	}

	public function hasAttribute($myAttribute_){
		return isset($this->attributes[$myAttribute_]);
	}

	public function getAttribute($myAttribute_){
		if (isset($this->attributes[$myAttribute_]))
			return $this->attributes[$myAttribute_];
		else
			return NULL;
	}

	public function getLocale() {
		if ( $this->hasAttribute('countrycode')) {
			$language = $this->getAttribute('countrycode'); // only works for ISO-3166
		}
		else {
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed',__FILE__,__LINE__);
			
			$default_settings = $prefs->get('general', 'session_settings_defaults');
			$language = $default_settings['language'];
		}
		$locale = locale2unix($language);
		return $locale;
	}
	
	protected function get_my_usersgroups_from_list($users_group_id_) {
		$result = array();
		
		$UserGroupDB = UserGroupDB::getInstance();
		$users_groups_mine = $UserGroupDB->get_groups_including_user_from_list($users_group_id_, $this);
		foreach($users_groups_mine as $group) {
			if (! $group->published) {
				continue;
			}
			
			array_push($result, $group->getUniqueID());
		}
		
		return $result;
	}
	
	public function appsGroups(){
		Logger::debug('main','USER::appsGroups');
		$apps_group_list = array();

		$ApplicationsGroupDB = ApplicationsGroupDB::getInstance();
		$UserGroupDB = UserGroupDB::getInstance();

		$publications = Abstract_Liaison::load('UsersGroupApplicationsGroup', NULL, NULL);
		$users_group_id = array();

		foreach($publications as $publication) {
			if (in_array($publication->element, $users_group_id)) {
				continue;
			}
			
			$users_group_id[]= $publication->element;
		}
		
		// from this group, which are these I am into
		$users_groups_mine_ids = $this->get_my_usersgroups_from_list($users_group_id);
		
		foreach($publications as $publication) {
			if (! in_array($publication->element, $users_groups_mine_ids)) {
				continue;
			}
			
			if (in_array($publication->group, $apps_group_list)) {
				continue;
			}
			
			$g = $ApplicationsGroupDB->import($publication->group);
			if (! is_object($g)) {
				continue;
			}
			
			if (! $g->published) {
				continue;
			}
			
			array_push($apps_group_list, $publication->group);
		}
		return array_unique($apps_group_list);
	}

	public function applications($type=NULL, $with_static_=true){
		Logger::debug('main', "USER::applications(type=$type, with_static=$with_static_)");

		$applicationDB = ApplicationDB::getInstance();

		$my_applications_id = array();
		$my_applications = array();
		$appgroups_id = $this->appsGroups();
		foreach ($appgroups_id as $agrp_id){
			$els = Abstract_Liaison::load('AppsGroup', NULL,$agrp_id);
			if (is_array($els))
				foreach ($els as $e)
					array_push($my_applications_id,$e->element);
		}

		$my_applications_id =  array_unique($my_applications_id);
		foreach ($my_applications_id as $id){
			$app = $applicationDB->import($id);
			if (is_object($app)) {
				if ( $type != NULL) {
					if ( $app->getAttribute('type') == $type) {
						if ( $app->getAttribute('static') ) {
							if ( $with_static_) {
								$my_applications []= $app;
							}
						}
						else {
							$my_applications []= $app;
						}
					}
				}
				else {
					if ( $app->getAttribute('static') ) {
						if ( $with_static_) {
							$my_applications []= $app;
						}
					}
					else {
						$my_applications []= $app;
					}

				}
			}
		}
		return $my_applications;
	}
	
	public function scripts() {
		Logger::debug('main', 'USER::scripts()');
		$scripts_tmp = Abstract_Script::load_all();
		$my_scripts_id = array();
		$my_scripts = array();
		
		$publications = Abstract_Liaison::load('Scripts', NULL, NULL);
		foreach($publications as $publication) {
			if (in_array($publication->element, $my_scripts_id)) {
				continue;
			}
			
			$my_scripts_id[]= $publication->group;
		}
		
		// from this group, which are these I am into
		$users_groups_mine_ids = $this->get_my_usersgroups_from_list($my_scripts_id);
		
		foreach($publications as $publication) {
			if (! in_array($publication->group, $users_groups_mine_ids)) {
				continue;
			}
			
			foreach ($scripts_tmp as $script) {
				if ($script->getAttribute('id') == $publication->element) {
					array_push($my_scripts, $script);
				}
			}
		}
		
		return array_unique($my_scripts);
	}
	
	public function getProfiles() {
		if (Preferences::moduleIsEnabled('ProfileDB') == false) {
			return array();
		}
		$profiledb = ProfileDB::getInstance();
		return $profiledb->importFromUser($this->getAttribute('login'));
	}
	
	public function getSharedFolders() {
		if (Preferences::moduleIsEnabled('SharedFolderDB') == false) {
			return array();
		}
		
		$session_settings_defaults = $this->getSessionSettings('session_settings_defaults');
		if (array_key_exists('enable_sharedfolders', $session_settings_defaults)) {
			$enable_sharedfolders = $session_settings_defaults['enable_sharedfolders'];
			if ($enable_sharedfolders == 0) {
				return array();
			}
		}
		
		$sharedfolderdb = SharedFolderDB::getInstance();
		$publications = $sharedfolderdb->get_publications();
		$users_group_id = array();
		foreach ($publications as $publication) {
			if (in_array($publication['group'], $users_group_id)) {
				continue;
			}
			
			array_push($users_group_id, $publication['group']);
		}
		
		// from this group, which are these I am into
		$users_groups_mine_ids = $this->get_my_usersgroups_from_list($users_group_id);
		
		$sharedfolders = array();
		foreach ($publications as $publication) {
			if (! in_array($publication['group'], $users_groups_mine_ids)) {
				continue;
			}
			
			if (array_key_exists($publication['share'], $sharedfolders)) {
				if ($publication['mode'] == 'rw' && $sharedfolders[$publication['share']]['mode'] != 'rw') {
					$sharedfolders[$publication['share']]['mode'] = $publication['mode'];
				}
				
				continue;
			}
			
			$share = $sharedfolderdb->import($publication['share']);
			if (is_null($share)) {
				continue;
			}
			
			$sharedfolders[$publication['share']] = array('share' => $share, 'mode' => $publication['mode']);
		}
		
		return $sharedfolders;
	}

	public function getAttributesList(){
		return array_keys($this->attributes);
	}

	public function __toString() {
		$ret = 'User(';
		foreach ($this->attributes as $k => $attr) {
				$ret .= "'$k':'";
				if ( is_array($attr)) {
					$ret .= ' [';
					foreach ($attr as $k => $v) {
						$ret .= "'$k':'$v', ";
					}
					$ret .= '] ';
				}
				else {
					$ret .= $attr;
				}
				$ret .= "', ";
		}
		$ret .= ')';
		return $ret;
	}
	
	public function getPolicy() {
		Logger::debug('main', 'User::getPolicy for '.$this->getAttribute('login'));
		$prefs = Preferences::getInstance();
		
		$policies = $prefs->get('general', 'policy');
		$default_policy = $policies['default_policy'];
		
		$elements = $prefs->getElements('general', 'policy');
		if (array_key_exists('default_policy', $elements) == false) {
			Logger::error('main', 'User::getPolicy, default_policy not found on general policy');
			return array();
		}
		
		$policy_items = $elements['default_policy']->content_available;
		$result = array();
		foreach ($policy_items as $policy_item) {
			if (in_array($policy_item, $default_policy))
				$result[$policy_item] = true;
			else
				$result[$policy_item] = false;
		}
		
		$acls = Abstract_Liaison::load('ACL', NULL, NULL);
		if (! is_array($acls)) {
			$acls = array();
		}
		
		$users_group_id = array();
		foreach ($acls as $acl_liaison) {
			if (in_array($acl_liaison->element, $users_group_id)) {
				continue;
			}
			
			array_push($users_group_id, $acl_liaison->element);
		}
		
		// from this group, which are these I am into
		$users_groups_mine_ids = $this->get_my_usersgroups_from_list($users_group_id);
		
		foreach ($acls as $acl_liaison) {
			if (! in_array($acl_liaison->element, $users_groups_mine_ids)) {
				continue;
			}
			
			if (! in_array($acl_liaison->group, $policy_items)) {
				continue;
			}
			
			$result[$acl_liaison->group] = true;
		}
		
		return $result;
	}
	
	public function getSessionSettings($container_) {
		$prefs = Preferences::getInstance();
		$overriden = array();
		$default_settings = $prefs->get('general', $container_);
		
		// load rules (overriden settings)
		$preferences_by_group = Abstract_Preferences::load_by_group('general.'.$container_.'.*');
		$users_group_id = array();
		foreach ($preferences_by_group as $group => $useless) {
			if (in_array($group, $users_group_id)) {
				continue;
			}
			
			array_push($users_group_id, $group);
		}
		
		// from this group, which are these I am into
		$users_groups_mine_ids = $this->get_my_usersgroups_from_list($users_group_id);
		
		// Finnaly, overwrite default settings with users groups settings
		foreach ($users_groups_mine_ids as $group) {
			if (! array_key_exists($group, $preferences_by_group)) {
				// means potentially bug in backend !
				continue;
			}
			
			foreach ($preferences_by_group[$group] as $setting_key => $setting_value) {
				$key = substr($setting_key, strrpos('.', $setting_key));
				
				$element = $pref->toConfigElement();
				if (isset($overriden[$key]) && ($overriden[$key] == true) && ($setting_value != $default_settings[$key])) {
					ErrorManager::report('User "'.$this->getAttribute('login').'" has at least two groups with the same overriden rule but with different values, the result will be unpredictable.');
				}
				
				$default_settings[$key] = $setting_value;
				$overriden[$key] = true;
			}
		}
		
		$user_preferences = Abstract_Preferences::load_user($this->getAttribute('login'), 'general.'.$container_.'.*');
		foreach ($user_preferences as $setting_key => $setting_value) {
			$key = substr($setting_key, strrpos('.', $setting_key));
			if (isset($overriden[$key]) && ($overriden[$key] == true) && ($setting_value != $default_settings[$key])) {
				Logger::debug("User '".$this->getAttribute('login')."' has at least overriden preferences but with different values, the result will be unpredictable.");
			}
			$default_settings[$key] = $setting_value;
		}
		
		return $default_settings;
	}
	
	public function can_use_session($hour_ = null, $day_ = null) {
		if (is_null($hour_)) {
			$hour_ = date('G');
		}
		
		if (is_null($day_)) {
			$day_ = date('w');
		}
		
		$default_settings = $this->getSessionSettings('session_settings_defaults');
		$restriction = $default_settings['time_restriction'];
		
		$restriction_today = substr($restriction, $day_*3*2, 3*2);
		$restriction_today = base_convert($restriction_today, 16, 2);
		$diff = 3*8 - strlen($restriction_today);
		if ($diff > 0) {
			$restriction_today = str_repeat('0', $diff).$restriction_today;
		}
		
		return ($restriction_today[$hour_] == '1');
	}
	
	public function get_published_servers_for_user() {
		Logger::debug('main', "USER::get_my_published_servers");
		
		$server_groups = array();
		
		$liaisons = Abstract_Liaison::load('UsersGroupServersGroup', NULL, NULL);
		foreach($liaisons as $liaison) {
			if (in_array($liaison->element, $server_groups)) {
				continue;
			}
			
			$server_groups[]= $liaison->element;
		}
		
		// from this group, which are these I am into
		$users_groups_mine_ids = $this->get_my_usersgroups_from_list($server_groups);
		$servers = array();
		
		foreach($liaisons as $liaison) {
			if (! in_array($liaison->element, $users_groups_mine_ids)) {
				continue;
			}
			
			$liaisons2 = Abstract_Liaison::load('ServersGroup', NULL, $liaison->group);
			foreach($liaisons2 as $liaison2) {
				$servers []= $liaison2->element;
			}
		}
		
		return array_unique($servers);
	}
	
	public function get_all_published_servers() {
		Logger::debug('main', "USER::get_all_published_servers");
		
		$servers = array();
		
		$liaisons = Abstract_Liaison::load('UsersGroupServersGroup', NULL, NULL);
		foreach($liaisons as $liaison) {
			$liaisons2 = Abstract_Liaison::load('ServersGroup', NULL, $liaison->group);
			foreach($liaisons2 as $liaison2) {
				$servers []= $liaison2->element;
			}
		}
		
		return $servers;
	}
}
