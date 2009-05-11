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
require_once(dirname(__FILE__).'/../../../includes/core.inc.php');

class Abstract_Liaison_activedirectory {
	public static function load($type_, $element_=NULL, $group_=NULL) {
		Logger::debug('admin',"Abstract_Liaison_activedirectory::load($type_,$element_,$group_)");
		if ($type_ == 'UsersGroup') {
			if (is_null($element_) && is_null($group_))
				return Abstract_Liaison_activedirectory::loadAll($type_);
			else if (is_null($element_))
				return Abstract_Liaison_activedirectory::loadElements($type_, $group_);
			else if (is_null($group_))
				return Abstract_Liaison_activedirectory::loadGroups($type_, $element_);
			else
				return Abstract_Liaison_activedirectory::loadUnique($type_, $element_, $group_);
		}
		else
		{
			Logger::error('admin',"Abstract_Liaison_activedirectory::load error liaison != UsersGroup not implemented");
			return NULL;
		}
		
	}
	public static function save($type_, $element_, $group_) {
		Logger::debug('admin',"Abstract_Liaison_activedirectory::save");
		return false;
	}
	public static function delete($type_, $element_, $group_) {
		Logger::debug('admin',"Abstract_Liaison_activedirectory::delete");
		return false;
	}
	public static function loadElements($type_, $group_) {
		Logger::debug('admin',"Abstract_Liaison_activedirectory::loadElements ($type_,$group_)");
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable))
			die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);
		
		$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
		$userGroupDB = new $mod_usergroup_name();
		
		$group = $userGroupDB->import($group_);
		if (! is_object($group)) {
			Logger::error('admin',"Abstract_Liaison_activedirectory::loadElements load group ($goup_) failed");
			return NULL;
		}
		
		$elements = array();
		$id_ = $group_;
		
		$userDBAD = new UserDB_activedirectory();
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
		$config_ldap['userbranch'] = $expl[1];

		$buf = array();
		$buf['id'] = $id_;
		$ldap = new LDAP($config_ldap);
		$sr = $ldap->search($expl[0], array_keys($config_ldap['match']));
		if ($sr === false) {
			Logger::error('main',"Abstract_Liaison_activedirectory::loadElements search failed for ($id_)");
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		$info = $infos[0];
		foreach ($config_ldap['match'] as $attribut => $match_ldap){
			if (isset($info[$match_ldap])) {
				unset($info[$match_ldap]['count']);
				$buf[$attribut] = $info[$match_ldap];
			}
		}
		if (isset($buf['member']) && is_array($buf['member'])) {
			foreach ($buf['member'] as $member) {
				$ldap->searchDN($member);
				$u =$userDBAD->importFromDN($member);
				$l = new Liaison($u->getAttribute('login'), $group_);
				$elements[$l->element] = $l;
			}
		}
		return $elements;
	}
	
	public static function loadGroups($type_, $element_) {
		Logger::debug('admin',"Abstract_Liaison_activedirectory::loadGroups ($type_,$element_)");
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable))
			die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);
		
		if (! in_array('UserDB',$mods_enable))
			die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
		$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
		$userGroupDB = new $mod_usergroup_name();
		$mod_user_name = 'UserDB_'.$prefs->get('UserDB','enable');
		$userDB = new $mod_user_name();
		$element_user = $userDB->import($element_);
		if (! is_object($element_user)) {
			Logger::error('admin',"Abstract_Liaison_activedirectory::loadGroups load element ($element_) failed");
			return NULL;
		}
		if ($element_user->hasAttribute('memberof')) {
			$groups = array();
			$memberof = $element_user->getAttribute('memberof');
			if (is_string($memberof))
				$memberof = array($memberof);
			foreach ($memberof as $id_group) {
				$g = $userGroupDB->import($id_group);
				if (is_object($g)) {
					$l = new Liaison($element_,$g->id);
					$groups[$l->group] = $l;
				}
			}
			return $groups;
		}
		return NULL;
	}
	
	public static function loadAll($type_) {
		Logger::debug('main',"Abstract_Liaison_activedirectory::loadAll ($type_)");
		echo "Abstract_Liaison_activedirectory::loadAll($type_)<br>";
		return NULL;
	}
	public static function loadUnique($type_, $element_, $group_) {
		Logger::debug('main',"Abstract_Liaison_activedirectory::loadUnique ($type_,$element_,$group_)");
		echo "Abstract_Liaison_activedirectory::loadUnique($type_,$element_,$group_)<br>";
		return NULL;
	}
	
	public static static function init($prefs_) {
		return true;
	}
}
