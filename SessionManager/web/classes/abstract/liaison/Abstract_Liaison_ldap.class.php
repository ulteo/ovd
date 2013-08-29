<?php
/**
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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

class Abstract_Liaison_ldap {
	public static function load($type_, $element_=NULL, $group_=NULL) {
		Logger::debug('main', "Abstract_Liaison_ldap::load($type_,$element_,$group_)");
		if (str_startswith($element_, 'static_'))
			$element_ = substr($element_, strlen('static_'));
		if (str_startswith($group_, 'static_'))
			$group_ = substr($group_, strlen('static_'));
		
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
			Logger::error('main', "Abstract_Liaison_ldap::load error liaison != UsersGroup not implemented");
			return NULL;
		}
		return NULL;
		
	}
	public static function save($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_ldap::save");
		return false;
	}
	public static function delete($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_ldap::delete");
		return false;
	}
	public static function loadElements($type_, $group_) {
		Logger::debug('main', "Abstract_Liaison_ldap::loadElements ($type_,$group_)");
		$userGroupDB = UserGroupDB::getInstance('static');
		$group = $userGroupDB->import($group_);
		if (! is_object($group)) {
			Logger::error('main', "Abstract_Liaison_ldap::loadElements load group ($group_) failed");
			return NULL;
		}
		
		$userDB = UserDB::getInstance();
		$prefs = $userGroupDB->get_prefs();
		if (in_array('group_membership', $prefs['group_match_user'])) {
			if ($prefs['group_membership_type'] == 'dn') {
				$value = $group_;
			}
			else {
				$value = $group->name;
			}
			
			$filter = $prefs['group_membership_field'].'='.$value;
			$users = $userDB->import_from_filter($filter);
		}
		else {
			$field = $prefs['user_member_field'];
			$configLDAP = $userGroupDB->makeLDAPconfig();
			$ldap = new LDAP($configLDAP);
			$sr = $ldap->searchDN($group_, array($field));
			if ($sr === false) {
				Logger::error('main', "Abstract_Liaison_ldap::loadElements search failed for ($id_)");
				return NULL;
			}
			
			$infos = $ldap->get_entries($sr);
			if (!is_array($infos) || $infos === array())
				return NULL;
			
			$keys = array_keys($infos);
			$dn = $keys[0];
			$info = $infos[$dn];
			if (! array_key_exists($field, $info)) {
				return null;
			}
			
			if (is_array($info[$field])) {
				if (isset($info[$field]['count'])) {
					unset($info[$field]['count']);
				}
				
				$members = $info[$field];
			}
			else {
				$members = array($info[$field]);
			}
			
			if ($prefs['user_member_type'] == 'dn') {
				$filter = array();
				foreach($members as $dn) {
					$expl = explode_with_escape(',', $dn, 2);
					$rdn = $expl[0];
					array_push($filter, $rdn);
				}
				
				$filter = LDAP::join_filters($filter, '|');
				$users = $userDB->import_from_filter($filter);
			}
			else {
				$users = $userDB->imports($members);
			}
		}
		
		if (is_null($users)) {
			return NULL;
		}
		
		$elements = array();
		foreach ($users as $user_login => $u) {
			$l = new Liaison($u->getAttribute('login'), $group_);
			$elements[$l->element] = $l;
		}
		
		return $elements;
	}
	
	public static function loadGroups($type_, $element_) {
		Logger::debug('main', "Abstract_Liaison_ldap::loadGroups ($type_,$element_)");
		$userDB = UserDB::getInstance();
		$user = $userDB->import($element_);
		if (! is_object($user)) {
			Logger::error('main', "Abstract_Liaison_ldap::loadGroups load user ($element_) failed");
			return NULL;
		}
		
		$userGroupDB = UserGroupDB::getInstance('static');
		$prefs = $userGroupDB->get_prefs();
		if (in_array('user_member', $prefs['group_match_user'])) {
			if ($prefs['user_member_type'] == 'dn') {
				$value = $user->getAttribute('dn');
			}
			else {
				$value = $user->getAttribute('login');
			}
			
			$filter = $prefs['user_member_field'].'='.$value;
			$groups = $userGroupDB->import_from_filter($filter);
		}
		else {
			$field = $prefs['group_membership_field'];
			
			$configLDAP = $userDB->config;
			$ldap = new LDAP($configLDAP);
			$sr = $ldap->searchDN($user->getAttribute('dn'), array($field));
			if ($sr === false) {
				Logger::error('main','Abstract_Liaison_ldap::loadGroups ldap failed (mostly timeout on server)');
				return NULL;
			}
			
			$infos = $ldap->get_entries($sr);
			if (!is_array($infos) || $infos === array())
				return NULL;
			
			$keys = array_keys($infos);
			$dn = $keys[0];
			$info = $infos[$dn];
			if (is_array($info[$field])) {
				if (isset($info[$field]['count'])) {
					unset($info[$field]['count']);
				}
				
				$memberof = $info[$field];
			}
			else {
				$memberof = array($info[$field]);
			}
			
			if ($prefs['group_membership_type'] == 'dn') {
				$groups = $userGroupDB->imports($memberof);
			}
			else {
				$filter = array();
				foreach($memberof as $name) {
					array_push($filter, '('.$prefs['match']['name'].'='.$name.')');
				}
				
				$filter = LDAP::join_filters($filter, '|');
				$groups = $userGroupDB->import_from_filter($filter);
			}
		}
		
		if (is_null($groups)) {
			return NULL;
		}
		
		$liaisons = array();
		foreach ($groups as $group_dn => $group) {
			$l = new Liaison($element_, $group->getUniqueID());
			$liaisons[$l->group] = $l;
		}
		
		return $liaisons;
	}
	
	public static function loadAll($type_) {
		Logger::debug('main',"Abstract_Liaison_ldap::loadAll ($type_)");
		return NULL;
	}
	public static function loadUnique($type_, $element_, $group_) {
		Logger::debug('main',"Abstract_Liaison_ldap::loadUnique ($type_,$element_,$group_)");
		return NULL;
	}
	
	public static function init($prefs_) {
		return true;
	}
}
