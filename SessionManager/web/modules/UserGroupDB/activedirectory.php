<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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
class UserGroupDB_activedirectory {

	public function import($id_) {
		Logger::debug('main',"UserGroupDB::activedirectory::import (id = $id_)");
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable))
			die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);
		
		$mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
		$userGroupDB = new $mod_usergroup_name();
		
		$userDBAD = new UserDB_activedirectory();
		$config_ldap = $userDBAD->makeLDAPconfig();
		
		$config_ldap['match'] =  array('description' => 'description','name' => 'name');
		if (str_endswith(strtolower($id_),strtolower($config_ldap['suffix'])) === true) {
			$id2 = substr($id_,0, -1*strlen($config_ldap['suffix']) -1);
		}
		else
		{
			$id2 = $id_;
		}
		$expl = explode(',',$id2,2);
		if (count($expl) == 1) {
			$expl = array($id2, '');
		}
		$config_ldap['userbranch'] = $expl[1];

		$buf = $config_ldap['match'];
		$buf['id'] = $id_;
		$ldap = new LDAP($config_ldap);
		$sr = $ldap->search($expl[0], array_keys($config_ldap['match']));
		if ($sr === false) {
			Logger::error('main',"UserGroupDB::activedirectory::import search failed for ($id_)");
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		$info = $infos[0];
		foreach ($config_ldap['match'] as $attribut => $match_ldap){
			if (isset($info[$match_ldap][0])) {
				$buf[$attribut] = $info[$match_ldap][0];
			}
		}
		$ug = new UsersGroup($buf['id'], $buf['name'], $buf['description'], true);
		return $ug;
	}
	
	public function isWriteable(){
		return false;
	}
	
	public function canShowList(){
		return true;
	}
	
	public function getList() {
		Logger::debug('main','UserGroupDB::activedirectory::getList');
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable))
			die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);
		
		$mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
		$userGroupDB = new $mod_usergroup_name();
	
	
		$userDBAD = new UserDB_activedirectory();
		$config_ldap = $userDBAD->makeLDAPconfig();
		$groups = array();
		$users = $userDBAD->getList();
		foreach ($users as $u) {
			if ($u->hasAttribute('memberof')) {
				$memberof = $u->getAttribute('memberof');
				if (! is_array($memberof))
					$memberof = array($memberof);
				foreach ($memberof as $group_name) {
					$ug = $this->import($group_name);
					if (is_object($ug))
						$groups[$group_name] = $ug;
				}
			}
		}
		return $groups;
	}
	
	public function isOK($usergroup_){
		if (is_object($usergroup_)) {
			if ((!isset($usergroup_->id)) || (!isset($usergroup_->name)) || (!isset($usergroup_->published)))
				return false;
			else
				return true;
		}
		else
			return false;
	}
	
	public function configuration(){
		return array();
	}
	
	public static function prefsIsValid($prefs_) {
		// FIXME : liaison to ad
		return true;
	}
	
	public static function prettyName() {
		return _('activedirectory');
	}
	
	public static function isDefault() {
		return false;
	}
	
	public function groups($user_) {
		$groups = array();
		if (is_object($user_)) {
			if ($user_->hasAttribute('memberof')) {
				$memberof = $user_->getAttribute('memberof');
				if (! is_array($memberof))
					$memberof = array($memberof);
				foreach ($memberof as $group_name) {
					$ug = $this->import($group_name);
					if (is_object($ug))
						$groups[$ug->id] = $ug;
				}
			}
		}
		return $groups;
	}
	
	public static function liaisonType() {
		return 'activedirectory';
	}
}
