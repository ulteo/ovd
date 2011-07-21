<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
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
class UserGroupDB_activedirectory extends UserGroupDB_ldap_memberof {
	public function __construct() {
		parent::__construct();
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$a_pref = $prefs->get('UserGroupDB', 'activedirectory');
		if (is_array($a_pref)) {
			$this->preferences = $a_pref;
		}
		else { // ugly...
			$this->preferences = array();
		}
	}
	
	public function import($id_) {
		Logger::debug('main',"UserGroupDB::activedirectory::import (id = $id_)");
		// cache 
		if (isset($this->cache[$id_])) {
			return $this->cache[$id_];
		}
		// cache end
		
		if (isset($this->cache[$id_]))
			return $this->cache[$id_];
		
		$userGroupDB = UserGroupDB::getInstance();
		
		$userDBAD2 = new UserDB_activedirectory();
		$userDBAD = UserDB::getInstance();
		if ( get_class($userDBAD) == get_class($userDBAD2)) {
			$userDBAD = $userDBAD2; // for cache
		}
		$config_ldap = $userDBAD->makeLDAPconfig();
		
		$config_ldap['match'] =  array('description' => 'description','name' => 'name', 'member' => 'member');
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
		$buf['name'] = '';
		$buf['description'] = '';
		$ldap = new LDAP($config_ldap);
		$sr = $ldap->search($expl[0], array_values($config_ldap['match']));
		if ($sr === false) {
			Logger::error('main',"UserGroupDB::activedirectory::import search failed for ($id_)");
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		if (count($infos) == 0) {
			Logger::error('main',"UserGroupDB::activedirectory::import search failed for ($id_), no data found on the directory");
			return NULL;
		}
		$keys = array_keys($infos);
		$dn = $keys[0];
		$info = $infos[$dn];
		foreach ($config_ldap['match'] as $attribut => $match_ldap){
			if (isset($info[$match_ldap][0])) {
				$buf[$attribut] = $info[$match_ldap][0];
			}
			if (array_key_exists($match_ldap, $info) && is_array($info[$match_ldap])) {
				if (isset($info[$match_ldap]['count']))
					unset($info[$match_ldap]['count']);
				$extras[$attribut] = $info[$match_ldap];
			}
		}
		if ($buf['name'] == '') {
			Logger::error('main', "UserGroupDB::activedirectory::import($id_) error group name is empty");
			return NULL;
		}
		$ug = new UsersGroup($buf['id'], $buf['name'], $buf['description'], true);
		$ug->extras = $extras;
		$this->cache[$buf['id']] = $ug;
		return $ug;
	}
	
	public function getList($sort_=false) {
		Logger::debug('main','UserGroupDB::activedirectory::getList');
		$userDBAD = UserDB::getInstance();
		
		$config_ldap = $userDBAD->makeLDAPconfig();
		$config_ldap['match'] = array();
		if (array_key_exists('match', $this->preferences)) {
			$config_ldap['match'] = $this->preferences['match'];
		}

		$ldap = new LDAP($config_ldap);
		$sr = $ldap->search('(objectClass=group)', array_values($config_ldap['match']));
		if ($sr === false) {
			Logger::error('main',"UserGroupDB::activedirectory::getList search failed");
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		
		$groups = array();
		
		foreach ($infos as $dn => $info) {
			$buf = array();
			foreach ($config_ldap['match'] as $attribut => $match_ldap) {
				if (isset($info[$match_ldap][0])) {
					$buf[$attribut] = $info[$match_ldap][0];
				}
				if (isset($info[$match_ldap]) && is_array($info[$match_ldap])) {
					if (isset($info[$match_ldap]['count']))
						unset($info[$match_ldap]['count']);
					$extras[$attribut] = $info[$match_ldap];
				}
				else {
					$extras[$attribut] = array();
				}
			}
			if (!isset($buf['description']))
				$buf['description'] = '';
			
			if (!isset($buf['name']))
				$buf['name'] = $dn;
			
			$ug = new UsersGroup($dn, $buf['name'], $buf['description'], true);
			$ug->extras = $extras;
			$groups[$dn] = $ug;
		}
		if ($sort_) {
			usort($groups, "usergroup_cmp");
		}
		
		return $groups;
	}
	
	
	public static function prettyName() {
		return _('Active Directory');
	}
	
	public static function isDefault() {
		return false;
	}
	
	public static function liaisonType() {
		return 'activedirectory';
	}
	
	public function add($usergroup_){
		return false;
	}
	
	public function remove($usergroup_){
		if ($usergroup_->isDefault()) {
			// unset the default usergroup
			$prefs = new Preferences_admin();
			$mods_enable = $prefs->set('general', 'user_default_group', '');
			$prefs->backup();
		}
		return true;
	}
	
	public function update($usergroup_){
		return true;
	}
	
	public static function init($prefs_) {
		return true;
	}
	
	public static function enable() {
		return true;
	}
}
