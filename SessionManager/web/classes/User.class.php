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

	protected function delete() {
		Logger::debug('main','USER::delete');
		unset($this->attributes);
	}

	public function usersGroups(){
		Logger::debug('main','USER::UsersGroups');
		$result = array();
		// add the default user group is enable
		$prefs = Preferences::getInstance();
		if ($prefs) {
			$user_default_group = $prefs->get('general', 'user_default_group');
			
			$mods_enable = $prefs->get('general','module_enable');
			if (! in_array('UserDB',$mods_enable))
				die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
			
			$mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
			$userGroupDB = new $mod_usergroup_name();
		}
		
		$rows = Abstract_Liaison::load('UsersGroup', $this->attributes['login'], NULL);
		if (is_null($rows)) {
			Logger::error('main', 'User::usersGroups load('.$this->attributes['login'].') is null');
			return $result;
		}
		$rows = Abstract_Liaison::load('UsersGroup', $this->attributes['login'], NULL);
		
		if ($user_default_group !== -1) {
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

		// get the list of server who the user can launch his applications
		Logger::error('main','USER::getAvailableServers (type='.$type.')');
		$servers = array();
		$apps = $this->applications($type);
		$apps_id = array();
		$apps_type = array();
		foreach($apps as $app){
			$apps_id[$app->getAttribute('id')] = $app->getAttribute('id');
			$apps_type[$app->getAttribute('id')] = $app->getAttribute('type');
		}
		
		if (count($apps_id)>0 || $launch_without_apps == 1){
			$available_servers = Servers::getAvailableType($type);
			foreach($available_servers as $server){
				$elements2 = array();
				$buf2 = Abstract_Liaison::load('ApplicationServer', NULL,$server->fqdn);
				foreach($buf2 as $buf_liaison) {
					$elements2 []= $buf_liaison->element;
				}
				
				if ( count(array_diff($apps_id, $elements2)) == 0 ){
					$servers[$server->fqdn]= $server;
				}
			}
		}
		return $servers;
	}

	public function getAvailableServer($type){
		// get a server who the user can launch his applications
		Logger::debug('main','USER::getAvailableServer');
		$list_servers = $this->getAvailableServers($type);

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('main', 'get Preferences failed');
			return NULL;
		}
		$application_server_settings = $prefs->get('general', 'application_server_settings');
		if (!isset($application_server_settings['load_balancing'])) {
			Logger::error('main' , 'USER::getAvailableServer $application_server_settings[\'load_balancing\'] not set');
			return NULL;
		}
		$criterions = $application_server_settings['load_balancing'];
		if (is_null($criterions))
			return NULL;

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
		return NULL;
	}

	public function applications($type=NULL){
		Logger::debug('main','USER::applications');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('main', 'get Preferences failed');
			return NULL;
		}

		$mods_enable = $prefs->get('general', 'module_enable');
		if (!in_array('ApplicationDB', $mods_enable)) {
			Logger::error('Module ApplicationDB must be enabled');
			return NULL;
		}

		$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB','enable');
		$applicationDB = new $mod_app_name();

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
					if ( $app->getAttribute('type') == $type)
						$my_applications []= $app;
				}
				else
					$my_applications []= $app;
			}
		}
		return $my_applications;
	}

	public function desktopfiles(){
		Logger::debug('main','USER::desktopfiles');
		$apps = $this->applications();
		$list = array();
		if (is_array($apps)){
			foreach ($apps as $a){
				if ($a->hasAttribute('desktopfile'))
					if ((!is_null($a->getAttribute('desktopfile'))) && ($a->getAttribute('desktopfile') != ''))
						$list []= $a->getAttribute('desktopfile');
			}
		}
		return $list;
	}

	public function getAttributesList(){
		return array_keys($this->attributes);
	}
	
	public function __toString() {
		$ret = 'User(';
		foreach ($this->attributes as $k=>$attr)
				$ret .= "'$k':'$attr', ";
		$ret .= ')';
		return $ret;
	}
}
