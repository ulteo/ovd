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

class Abstract_Liaison_ldap_posix {
	public static function load($type_, $element_=NULL, $group_=NULL) {
		Logger::debug('admin',"Abstract_Liaison_ldap_posix::load($type_,$element_,$group_)");
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
			Logger::error('admin',"Abstract_Liaison_ldap_posix::load error liaison != UsersGroup not implemented");
			return NULL;
		}
		return NULL;
		
	}
	public static function save($type_, $element_, $group_) {
		Logger::debug('admin',"Abstract_Liaison_ldap_posix::save");
		return true;
	}
	public static function delete($type_, $element_, $group_) {
		Logger::debug('admin',"Abstract_Liaison_ldap_posix::delete");
		return true;
	}
	public static function loadElements($type_, $group_) {
		Logger::debug('admin',"Abstract_Liaison_ldap_posix::loadElements ($type_,$group_)");
		
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
		
		$configLDAP = $prefs->get('UserDB','ldap');
		
		$conf = $prefs->get('UserGroupDB', $prefs->get('UserGroupDB','enable'));
		if (! is_array($conf)) {
			Logger::error('main', "Abstract_Liaison_ldap_posix::loadElements  UserGroupDB::$mod_usergroup_name have not configuration");
			die_error("Abstract_Liaison_ldap_posix::loadElements UserGroupDB::$mod_usergroup_name have not configuration",__FILE__,__LINE__);
		}
		
		if (isset($conf['group_dn'])) {
			$configLDAP['userbranch'] = $conf['group_dn'];
		}
		else {
			Logger::error('main', "Abstract_Liaison_ldap_posix::loadElements  UserGroupDB::$mod_usergroup_name have not correct configuration");
			die_error("Abstract_Liaison_ldap_posix::loadElements UserGroupDB::$mod_usergroup_name have not correct configuration",__FILE__,__LINE__);
		}
		
		$ldap = new LDAP($configLDAP);
		$sr = $ldap->search('cn='.$group_, NULL);
		$infos = $ldap->get_entries($sr);
		if (!is_array($infos))
			return NULL;
		$info = $infos[0];
		if ( isset($info['dn']) && $info['cn']) {
			if (is_string($info['dn']) && isset($info['cn']) && is_array($info['cn']) && isset($info['cn'][0]) ) {
				$u = new UsersGroup($info['cn'][0], $info['cn'][0], '', true);
				if ($userGroupDB->isOK($u)) {
					$elements = array();
					if (isset($info['memberuid'])) {
						unset($info['memberuid']['count']);
						foreach ($info['memberuid'] as $memberuid) {
							$u = $userDB->import($memberuid);
							if (is_object($u)) {
								$l = new Liaison($u->getAttribute('login'), $group_);
								$elements[$l->element] = $l;
							}
						}
					}
					return $elements;
				}
			}
		}
		return NULL;
	}
	
	public static function loadGroups($type_, $element_) {
		Logger::debug('admin',"Abstract_Liaison_ldap_posix::loadGroups ($type_,$element_)");
		
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
		
		$groups = array();
		$groups_all = $userGroupDB->getList();
		if (! is_array($groups_all)) {
			Logger::error('main', 'Abstract_Liaison_ldap::loadGroups userGroupDB->getList failed');
			return NULL;
		}
		foreach ($groups_all as $a_group) {
			if (in_array($element_, $a_group->usersLogin())) {
				$l = new Liaison($element_,$a_group->id);
				$groups[$l->group] = $l;
			}
		}
		return $groups;
	}
	
	public static function loadAll($type_) {
		Logger::debug('main',"Abstract_Liaison_ldap_posix::loadAll ($type_)");
		echo "Abstract_Liaison_ldap_posix::loadAll($type_)<br>";
		return NULL;
	}
	public static function loadUnique($type_, $element_, $group_) {
		Logger::debug('main',"Abstract_Liaison_ldap_posix::loadUnique ($type_,$element_,$group_)");
		echo "Abstract_Liaison_ldap_posix::loadUnique($type_,$element_,$group_)<br>";
		return NULL;
	}
	
	public static function init($prefs_) {
		return true;
	}
}
