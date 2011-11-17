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

class Abstract_Liaison_ldap_memberof {
	public static function load($type_, $element_=NULL, $group_=NULL) {
		Logger::debug('main', "Abstract_Liaison_ldap_memberof::load($type_,$element_,$group_)");
		
		if ($type_ == 'UsersGroup') {
			if (is_null($element_) && is_null($group_))
				return self::loadAll($type_);
			else if (is_null($element_))
				return self::loadElements($type_, $group_);
			else if (is_null($group_))
				return self::loadGroups($type_, $element_);
			else
				return self::loadUnique($type_, $element_, $group_);
		}
		else
		{
			Logger::error('main', "Abstract_Liaison_ldap_memberof::load error liaison != UsersGroup not implemented");
			return NULL;
		}
		return NULL;
		
	}
	public static function save($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_ldap_memberof::save");
		return false;
	}
	public static function delete($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_ldap_memberof::delete");
		return false;
	}
	public static function loadElements($type_, $group_) {
		Logger::debug('main', "Abstract_Liaison_ldap_memberof::loadElements ($type_,$group_)");
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$userGroupDB = UserGroupDB::getInstance();
		
		$group = $userGroupDB->import($group_);
		if (! is_object($group)) {
			Logger::error('main', "Abstract_Liaison_ldap_memberof::loadElements load group ($group_) failed");
			return NULL;
		}
		
		$userGroupDB_ldap_memberof = new UserGroupDB_ldap_memberof();
		$use_child_group = false;
		
		$$userGroupDB_ldap_memberof_preferences = $userGroupDB_ldap_memberof->preferences;
		if (array_key_exists('use_child_group', $$userGroupDB_ldap_memberof_preferences)) {
			if ($$userGroupDB_ldap_memberof_preferences['use_child_group'] == 1 || $$userGroupDB_ldap_memberof_preferences['use_child_group'] == '1') {
				$use_child_group = true;
			}
		}
		
		$elements = array();
		
		if (is_base64url($group->id))
			$id_ = base64url_decode($group->id);
		else
			$id_ = $group->id;
		
		$userDBldap = new UserDB_ldap();
		$userDBldap2 = UserDB::getInstance();
		if ( get_class($userDBldap) == get_class($userDBldap2)) {
			$userDBldap = $userDBldap2; // for cache
		}
		
		$config_ldap = $prefs->get('UserDB','ldap');
		
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
			Logger::error('main',"Abstract_Liaison_ldap_memberof::loadElements search failed for ($id_)");
			return NULL;
		}
		$infos = $ldap->get_entries($sr);
		if ($infos === array()) {
			return $elements;
		}
		$keys = array_keys($infos);
		$dn = $keys[0];
		$info = $infos[$dn];
		foreach ($config_ldap['match'] as $attribut => $match_ldap){
			if (isset($info[$match_ldap])) {
				unset($info[$match_ldap]['count']);
				$buf[$attribut] = $info[$match_ldap];
			}
		}
		if (isset($buf['member']) && is_array($buf['member'])) {
			foreach ($buf['member'] as $member) {
				$u = $userDBldap->importFromDN($member);
				if (is_object($u) == false) {
					Logger::error('main', "Abstract_Liaison_ldap_memberof::loadElements ($type_,$group_) failed to import ".$member);
					continue;
				}
				else {
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
		Logger::debug('main', "Abstract_Liaison_ldap_memberof::loadGroups ($type_,$element_)");
		
		$userGroupDB = UserGroupDB::getInstance();
		$userDB = UserDB::getInstance();
		$element_user = $userDB->import($element_);
		if (! is_object($element_user)) {
			Logger::error('main', "Abstract_Liaison_ldap_memberof::loadGroups load element ($element_) failed");
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
					$l = new Liaison($element_,$g->getUniqueID());
					$groups[$l->group] = $l;
				}
			}
			return $groups;
		}
		Logger::error('main', "Abstract_Liaison_ldap_memberof::loadGroups ($type_,$element_) end of function");
		return NULL;
	}
	
	public static function loadAll($type_) {
		Logger::debug('main',"Abstract_Liaison_ldap_memberof::loadAll ($type_)");
		return NULL;
	}
	public static function loadUnique($type_, $element_, $group_) {
		Logger::debug('main',"Abstract_Liaison_ldap_memberof::loadUnique ($type_,$element_,$group_)");
		return NULL;
	}
	
	public static function init($prefs_) {
		return true;
	}
}
