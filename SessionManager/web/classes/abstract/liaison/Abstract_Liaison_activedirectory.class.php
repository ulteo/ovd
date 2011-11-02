<?php
/**
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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
		Logger::debug('main', "Abstract_Liaison_activedirectory::load($type_,$element_,$group_)");
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
			Logger::error('main', "Abstract_Liaison_activedirectory::load error liaison != UsersGroup not implemented");
			return NULL;
		}
		
	}
	public static function save($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_activedirectory::save");
		return false;
	}
	public static function delete($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_activedirectory::delete");
		return false;
	}
	public static function loadElements($type_, $group_) {
		Logger::debug('main', "Abstract_Liaison_activedirectory::loadElements ($type_,$group_)");
		
		$userGroupDB = UserGroupDB::getInstance();
		$userGroupDB_activedirectory = new UserGroupDB_activedirectory();
		
		$use_child_group = false;
		$userGroupDB_activedirectory_preferences = $userGroupDB_activedirectory->preferences;
		if (array_key_exists('use_child_group', $userGroupDB_activedirectory_preferences)) {
			if ($userGroupDB_activedirectory_preferences['use_child_group'] == 1 || $userGroupDB_activedirectory_preferences['use_child_group'] == '1') {
				$use_child_group = true;
			}
		}
		
		$group = $userGroupDB->import($group_);
		if (! is_object($group)) {
			Logger::error('main', "Abstract_Liaison_activedirectory::loadElements load group ($group_) failed");
			return NULL;
		}
		
		if ($group->type != 'static') {
			return NULL;
		}
		
		$elements = array();
		$id_ = $group->id;
		
		$userDBAD2 = new UserDB_activedirectory();
		$userDBAD = UserDB::getInstance();
		if ( get_class($userDBAD) == get_class($userDBAD2)) {
			$userDBAD = $userDBAD2; // for cache
		}
		$config_ldap = $userDBAD->makeLDAPconfig();
		
		if (isset($group->extras) && is_array($group->extras) && isset($group->extras['member'])) {
			$buf = $group->extras;
		}
		else {
			$config_ldap['match'] =  array('description' => 'description','name' => 'name', 'member' => 'member');
			if (str_endswith(strtolower($id_),strtolower($config_ldap['suffix'])) === true) {
				$id2 = substr($id_,0, -1*strlen($config_ldap['suffix']) -1);
			}
			else
			{
				$id2 = $id_;
			}
			$expl = explode(',',$id2,2);
			if (count($expl) < 2) {
				Logger::error('main', "Abstract_Liaison_activedirectory::loadElements($type_,$group_) count(expl) != 2 (count=".count($expl).")(id2=".$id2.")");
				return NULL;
			}
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
			$keys = array_keys($infos);
			$dn = $keys[0];
			$info = $infos[$dn];
			foreach ($config_ldap['match'] as $attribut => $match_ldap){
				if (isset($info[$match_ldap])) {
					unset($info[$match_ldap]['count']);
					$buf[$attribut] = $info[$match_ldap];
				}
			}
		}
		if (isset($buf['member']) && is_array($buf['member'])) {
			foreach ($buf['member'] as $member) {
				$u = $userDBAD->importFromDN($member);
				if (is_object($u)) {
					if ($u->hasAttribute('objectclass')) {
						if (in_array('user', $u->getAttribute('objectclass'))) {
							$l = new Liaison($u->getAttribute('login'), $group_);
							$elements[$l->element] = $l;
						}
						else if (in_array('group', $u->getAttribute('objectclass')) && $use_child_group == true) {
							 $ret1 = self::loadElements($type_, 'static_'.$member);
							 if (is_array($ret1)) {
								foreach ($ret1 as $element1 => $liaison1) {
									$elements[$element1] = $liaison1;
								}
							 }
						}
					}
					else {
						$l = new Liaison($u->getAttribute('login'), $group_);
						$elements[$l->element] = $l;
					}
				}
			}
		}
		return $elements;
	}
	
	public static function loadGroups($type_, $element_) {
		Logger::debug('main', "Abstract_Liaison_activedirectory::loadGroups ($type_,$element_)");
		$userGroupDB = UserGroupDB::getInstance();
		$userDB = UserDB::getInstance();
		
		$use_child_group = false;
		$userGroupDB_activedirectory = new UserGroupDB_activedirectory();
		$userGroupDB_activedirectory_preferences = $userGroupDB_activedirectory->preferences;
		if (array_key_exists('use_child_group', $userGroupDB_activedirectory_preferences)) {
			if (in_array($userGroupDB_activedirectory_preferences['use_child_group'], array(1, '1')))
				$use_child_group = true;
		}
		
		$element_user = $userDB->import($element_);
		if (! is_object($element_user)) {
			Logger::error('main', "Abstract_Liaison_activedirectory::loadGroups load element ($element_) failed");
			return NULL;
		}
		if ($element_user->hasAttribute('memberof')) {
			$groups = array();
			$memberof = $element_user->getAttribute('memberof');
			if (is_string($memberof))
				$memberof = array($memberof);
			foreach ($memberof as $id_group) {
				$g = $userGroupDB->import('static_'.$id_group);
				if (is_object($g)) {
					$l = new Liaison($element_,$g->getUniqueID());
					$groups[$l->group] = $l;
					
					if ($use_child_group === true) {
						$gs = self::loadParentsGroups($id_group);
						foreach ($gs as $g2) {
							$l = new Liaison($element_, $g2->getUniqueID());
							$groups[$l->group] = $l;
						}
					}
				}
			}
			return $groups;
		}
		// no group found
		return array();
	}
	
	
	public static function loadParentsGroups($group_) {
		Logger::debug('main',"Abstract_Liaison_activedirectory::loadParentsGroups ($group_)");
		
		$userDBAD2 = new UserDB_activedirectory();
		$userDBAD = UserDB::getInstance();
		if ( get_class($userDBAD) == get_class($userDBAD2)) {
			$userDBAD = $userDBAD2; // for cache
		}
		
		$userGroupDB = UserGroupDB::getInstance();
		$groups = array();
		
		$u = $userDBAD->importFromDN($group_);
		if (is_null($u))
			return $groups;
		
		if (! $u->hasAttribute('memberof'))
			return $groups;
		
		$memberof = $u->getAttribute('memberof');
		if (is_string($memberof))
			$memberof = array($memberof);
		
		foreach ($memberof as $id_group) {
			$g = $userGroupDB->import('static_'.$id_group);
			if (! is_object($g))
				continue;
			
			$groups[]= $g;
			
			$parent_groups = self::loadParentsGroups($id_group);
			$groups = array_merge($groups, $parent_groups);
		}
		
		return $groups;
	}
	
	
	public static function loadAll($type_) {
		Logger::debug('main',"Abstract_Liaison_activedirectory::loadAll ($type_)");
		return NULL;
	}
	public static function loadUnique($type_, $element_, $group_) {
		Logger::debug('main',"Abstract_Liaison_activedirectory::loadUnique ($type_,$element_,$group_)");
		return NULL;
	}
	
	public static function init($prefs_) {
		return true;
	}
}
