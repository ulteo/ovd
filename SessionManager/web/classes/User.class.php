<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
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

	public function usersGroups(){
		Logger::debug('main','USER::UsersGroups');
		$result = array();
		// add the default user group is enable
		$prefs = Preferences::getInstance();
		if (!$prefs) {
			Logger::critical('main', 'USER::UsersGroups get prefs failed');
			die_error('get Preferences failed',__FILE__,__LINE__);
		}
		$user_default_group = $prefs->get('general', 'user_default_group');
		$userGroupDB = UserGroupDB::getInstance();

		$static = Abstract_Liaison::load('UsersGroup', $this->attributes['login'], NULL);
		if (is_null($static)) {
			Logger::error('main', 'User::usersGroups load('.$this->attributes['login'].') is null');
			return $result;
		}
		
		if ($userGroupDB->isDynamic()) {
			$dynamic = Abstract_Liaison_dynamic::load('UsersGroup', $this->attributes['login'], NULL);
			if (is_null($dynamic)) {
				$dynamic = array();
			}
		}
		else {
			$dynamic = array();
		}
		
		$rows = array_unique(array_merge($static, $dynamic));

		$groups_id = array();
		foreach ($rows as $lug){
			array_push($groups_id, $lug->group);
		}
		$result = $userGroupDB->imports($groups_id);
		
		if (!is_null($user_default_group) && $user_default_group !== '-1' && $user_default_group !== '') {
			$g = $userGroupDB->import($user_default_group);// safe because even if  group = -1, the import failed safely
			if (is_object($g))
				$result[$user_default_group]= $g;
		}
		
		return $result;
	}

	public function appsGroups(){
		Logger::debug('main','USER::appsGroups');
		$apps_group_list = array();
		$users_grps = $this->usersGroups();
		foreach ($users_grps as $ugrp){
			if ($ugrp->published){
				$app_group_s = $ugrp->appsGroups();
				if (is_array($app_group_s)) {
					foreach($app_group_s as $app_group){
						if ($app_group->published){
							array_push($apps_group_list,$app_group->id);
						}
					}
				}
			}
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

		$usergroups = $this->usersGroups();
		if (is_array($usergroups) === false) {
			Logger::error('main', 'User::getSharedFolders usersGroups failed for user (login='.$this->getAttribute('login').')');
			return array();
		}
		
		$sharedfolderdb = SharedFolderDB::getInstance();
		$publications = $sharedfolderdb->get_publications();
		
		$sharedfolders = array();
		foreach ($publications as $publication) {
			if (! array_key_exists($publication['group'], $usergroups)) {
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
		
		$groups = $this->usersGroups();
		foreach ($groups as $a_group) {
			$policy = $a_group->getPolicy();
			if (is_array($policy)) {
				foreach ($policy as $key => $value){
					if (array_key_exists($key, $result)) {
						if ($value == true)
							$result[$key] = $value;
					}
					else {
						$result[$key] = $value;
					}
				}
			}
		}
		return $result;
	}
	
	public function getSessionSettings($container_) {
		$prefs = Preferences::getInstance();
		$overriden = array();
		$default_settings = $prefs->get('general', $container_);
		$groups = $this->usersGroups();
		foreach ($groups as $a_group) {
			$prefs_of_a_group_unsort = Abstract_UserGroup_Preferences::loadByUserGroupId($a_group->getUniqueID(), 'general', $container_);
			foreach ($prefs_of_a_group_unsort as $key => $pref) {
				$element = $pref->toConfigElement();
				if (isset($overriden[$key]) && ($overriden[$key] == true) && ($element->content != $default_settings[$key])) {
					ErrorManager::report('User "'.$this->getAttribute('login').'" has at least two groups with the same overriden rule but with different values, the result will be unpredictable.');
				}
				$default_settings[$key] = $element->content;
				$overriden[$key] = true;
			}
		}
		
		$prefs_of_a_user_unsort = Abstract_User_Preferences::loadByUserLogin($this->getAttribute('login'), 'general', $container_);
		foreach ($prefs_of_a_user_unsort as $key => $pref) {
			$element = $pref->toConfigElement();
			if (isset($overriden[$key]) && ($overriden[$key] == true) && ($element->content != $default_settings[$key])) {
				Logger::debug("User '".$this->getAttribute('login')."' has at least overriden preferences but with different values, the result will be unpredictable.");
			}
			$default_settings[$key] = $element->content;
			$overriden[$key] = true;
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
}
