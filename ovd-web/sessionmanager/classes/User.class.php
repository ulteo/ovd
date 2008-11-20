<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
		$l = new UsersGroupLiaison($this->attributes['login'],NULL);
		$rows = $l->groups();
		foreach ($rows as $row){
			$g = new UsersGroup();
			$g->fromDB($row['group']);
			if ($g->isOK())
				$result []= $g;
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

	public function getAvalaibleServers(){
		// get the list of server who the user can launch his applications
		Logger::error('main','USER::getAvalaibleServers');
		$servers = array();
		$apps = $this->applications();
		$apps_id = array();
		foreach($apps as $app){
			$apps_id[$app->getAttribute('id')] = $app->getAttribute('id');
		}
		if (count($apps_id)>0){
			$available_servers = Servers::getAvailable(); // servers who can we user/session
			foreach($available_servers as $server){
				$l = new ApplicationServerLiaison(NULL,$server->fqdn);
				if ( count(array_diff($apps_id,$l->elements())) == 0 ){
					$servers []= $server;
				}
			}
		}
		return $servers;
	}

	public function getAvalaibleServer(){
		// get a server who the user can launch his applications
		Logger::debug('main','USER::getAvalaibleServer');
		// TODO algo from prefs
		$list_servers = $this->getAvalaibleServers();
		if (is_array($list_servers)) {
			while (count($list_servers)>0) {
				$buf_key = array_rand($list_servers); // TODO : make an better algo
				$buf = $list_servers[$buf_key];
				unset($list_servers[$buf_key]);
				$buf->getStatus();
				if ($buf->isOnline())
					return $buf;
			}
		}
		return NULL;
	}

	public function applications(){
		Logger::debug('main','USER::applications');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('get Preferences failed');
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
			$lagrp = new AppsGroupLiaison(NULL,$agrp_id);
			$els = $lagrp->elements();
			if (is_array($els))
				foreach ($els as $e)
					array_push($my_applications_id,$e);
		}

		$my_applications_id =  array_unique($my_applications_id);
		foreach ($my_applications_id as $id){
			$app = $applicationDB->import($id);
			if (is_object($app))
				$my_applications []= $app;
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
}
