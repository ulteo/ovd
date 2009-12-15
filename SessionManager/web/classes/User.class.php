<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

		if (!is_null($user_default_group) && $user_default_group !== '-1' && $user_default_group !== '') {
			$g = $userGroupDB->import($user_default_group);// safe because even if  group = -1, the import failed safely
			if (is_object($g))
				$result[$user_default_group]= $g;
		}

		foreach ($rows as $lug){
			$g = $userGroupDB->import($lug->group);
			if (is_object($g))
				$result[$lug->group]= $g;
			else {
				Logger::error('main', 'USER::usersGroups user group (\''.$lug->group.'\') not ok');
			}
		}
		return $result;
	}

	public function appsGroups(){
		Logger::debug('main','USER::appsGroups');
		$apps_group_list = array();
		$users_grps = $this->UsersGroups();
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

	public function getAvailableServers($type){
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);

		$default_settings = $prefs->get('general', 'session_settings_defaults');
		$launch_without_apps = (int)$default_settings['launch_without_apps'];

		$user_profile_mode = $prefs->get('UserDB', 'enable');
		$prefs_ad = $prefs->get('UserDB', 'activedirectory');

		// get the list of server who the user can launch his applications
		Logger::debug('main','USER::getAvailableServers (type='.$type.')');
		$servers = array();
		$apps = $this->applications($type, false);
		$apps_static = $this->applications($type, true);
		$apps_id = array();
		$apps_type = array();
		foreach($apps as $app){
			$apps_id[$app->getAttribute('id')] = $app->getAttribute('id');
			$apps_type[$app->getAttribute('id')] = $app->getAttribute('type');
		}
		
		$available_servers = Servers::getAvailableType($type);
		foreach($available_servers as $server) {
			if ($user_profile_mode == 'activedirectory' && $type == 'windows' && $server->getAttribute('windows_domain') != $prefs_ad['domain']) {
				Logger::warning('main', 'USER::getAvailableServers Server \''.$server->fqdn.'\' is NOT linked to Active Directory domain \''.$prefs_ad['domain'].'\'');
				continue;
			}

			if (count($apps_id)>0 || $launch_without_apps == 1) {
				$elements2 = array();
				$buf2 = Abstract_Liaison::load('ApplicationServer', NULL,$server->fqdn);
				foreach($buf2 as $buf_liaison) {
					$elements2 []= $buf_liaison->element;
				}

				if ( count(array_diff($apps_id, $elements2)) == 0 ){
					$servers[$server->fqdn]= $server;
				}
			}
			else if (count($apps_static) > 0) {
				$servers[$server->fqdn]= $server;
			}
		}
		return $servers;
	}

	public function getAvailableServer($type){
		// get a server who the user can launch his applications
		Logger::debug('main', "USER::getAvailableServer($type)");
		$list_servers = $this->getAvailableServers($type);

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('main', 'USER::getAvailableServer get Preferences failed');
			return NULL;
		}
		$application_server_settings = $prefs->get('general', 'application_server_settings');
		if (!isset($application_server_settings['load_balancing'])) {
			Logger::error('main' , 'USER::getAvailableServer $application_server_settings[\'load_balancing\'] not set');
			return NULL;
		}
		$criterions = $application_server_settings['load_balancing'];
		if (is_null($criterions)) {
			Logger::error('main' , 'USER::getAvailableServer criterions is null');
			return NULL;
		}

		$server_val = array();
		foreach($list_servers as $server) {
			$val = 0;
			foreach ($criterions as $criterion_name  => $criterion_value ) {
				$name_class1 = 'DecisionCriterion_'.$criterion_name;
				$d1 = new $name_class1($server);
				$r1 = $d1->get();
				$val += $r1* $criterion_value;
			}
			$server_val[$server->fqdn] = $val;
		}

		while (count($server_val)>0) {
			$max_value = -1;
			$max_fqdn = 0;
			foreach ($server_val as $fqdn1 => $val1) {
				if ( $max_value < $val1) {
					$max_value = $val1;
					$max_fqdn = $fqdn1;
				}
			}
			$buf = $list_servers[$max_fqdn];
			unset($server_val[$max_fqdn]);
			$buf->getStatus();
			if ($buf->isOnline())
				return $buf;
		}
		Logger::error('main' , "USER::getAvailableServer($type) no server found for user '".$this->getAttribute('login')."'");
		return NULL;
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
		$result = $prefs->elements['general']['policy']['default_policy']->content_available;
		foreach ($result as $k => $v) {
				if (in_array($k, $default_policy))
						$result[$k] = true;
				else 
						$result[$k] = false;
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
}
