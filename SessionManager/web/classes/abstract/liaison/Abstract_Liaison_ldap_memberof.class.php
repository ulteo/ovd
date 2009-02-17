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
		Logger::debug('admin',"<b>Abstract_Liaison_ldap_memberof::load($type_,$element_,$group_)</b>");
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
			Logger::error('admin',"Abstract_Liaison_ldap_memberof::load error liaison != UsersGroup not implemented");
			return NULL;
		}
		return NULL;
		
	}
	public function save($type_, $element_, $group_) {
		Logger::debug('admin',"<b>Abstract_Liaison_ldap_memberof::save</b>");
		return false;
	}
	public function delete($type_, $element_, $group_) {
		Logger::debug('admin',"<b>Abstract_Liaison_ldap_memberof::delete</b>");
		return false;
	}
	public function loadElements($type_, $group_) {
		Logger::debug('admin',"<b>Abstract_Liaison_ldap_memberof::loadElements ($type_,$group_)</b>");
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
			Logger::error('admin',"Abstract_Liaison_ldap_memberof::loadElements load group ($goup_) failed");
			return NULL;
		}
		
		$elements = array();
		$id_ = $group_;
		
		$userDBldap = new UserDB_ldap();
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
				$u = $userDBldap->importFromDN($member);
				$l = new Liaison($u->getAttribute('login'), $group_);
				$elements[$l->element] = $l;
			}
		}
		return $elements;
	}
	
	public function loadGroups($type_, $element_) {
		Logger::debug('admin',"<b>Abstract_Liaison_ldap_memberof::loadGroups ($type_,$element_)</b>");
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
			Logger::error('admin',"Abstract_Liaison_ldap_memberof::loadGroups load element ($element_) failed");
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
	
	public function loadAll($type_) {
		Logger::debug('main',"<b>Abstract_Liaison_ldap_memberof::loadAll ($type_)</b>");
		echo "Abstract_Liaison_ldap_memberof::loadAll($type_)<br>";
		return NULL;
	}
	public function loadUnique($type_, $element_, $group_) {
		Logger::debug('main',"<b>Abstract_Liaison_ldap_memberof::loadUnique ($type_,$element_,$group_)</b>");
		echo "Abstract_Liaison_ldap_memberof::loadUnique($type_,$element_,$group_)<br>";
		return NULL;
	}
	
	public static function init($prefs_) {
		return true;
	}
}
