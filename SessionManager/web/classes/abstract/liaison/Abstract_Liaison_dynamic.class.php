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

class Abstract_Liaison_dynamic {
	public static function load($type_, $element_=NULL, $group_=NULL) {
		Logger::debug('admin',"Abstract_Liaison_dynamic::load($type_,$element_,$group_)");
		
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
			Logger::error('admin',"Abstract_Liaison_dynamic::load error liaison != UsersGroup not implemented");
			return NULL;
		}
		return NULL;
	}
	public static function save($type_, $element_, $group_) {
		Logger::debug('admin', "Abstract_Liaison_dynamic::save ($type_,$element_,$group_)");
		// not implemented
		return true;
	}
	public static function delete($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_dynamic::delete ($type_,$element_,$group_)");
		// not implemented
		return true;
	}
	public static function loadElements($type_, $group_) {
		Logger::debug('main',"Abstract_Liaison_dynamic::loadElements ($type_,$group_)");
		$group_ = 'dynamic_'.$group_;
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general', 'module_enable');
		
		if (! in_array('UserDB', $mods_enable))
			die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
		
		$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
		
		$userGroupDB = UserGroupDB::getInstance();
		$userDB = new $mod_user_name();
		
		$group = $userGroupDB->import($group_);
		if (! is_object($group)) {
			Logger::error('main', "Abstract_Liaison_dynamic::loadElements load group ($group_) failed");
			return NULL;
		}
		
		$users_list = $userDB->getList();
		if (!is_array($users_list)) {
			Logger::error('main', 'Abstract_Liaison_dynamic::loadElements get users list failed');
			return NULL;
		}
		
		$elements = array();
		
		foreach ($users_list as $user) {
			if ( $group->containUser($user)) {
				$l = new Liaison($user->getAttribute('login'), $group_);
				$elements[$l->element] = $l;
			}
		}
		return $elements;
	}
	public static function loadGroups($type_, $element_) {
		Logger::debug('admin',"Abstract_Liaison_dynamic::loadGroups ($type_,$element_)");
		
		$userGroupDB = UserGroupDB::getInstance();
		$userGroupDB_dynamic = new UserGroupDBDynamic();
		$groups = array();
		$usergroup_list_static = $userGroupDB->getList();
		$usergroup_list_dynamic = $userGroupDB_dynamic->getList();
		$usergroup_list = array_unique(array_merge($usergroup_list_dynamic, $usergroup_list_static));
		foreach ($usergroup_list as $group) {
			if (in_array($element_, $group->usersLogin())){
				$l = new Liaison($element_,$group->getUniqueID());
				$groups[$l->group] = $l;
			}
		}
		return $groups;
	}
	
	public static function loadAll($type_) {
		Logger::debug('admin',"Abstract_Liaison_dynamic::loadAll ($type_)");
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general', 'module_enable');
		
		if (! in_array('UserDB', $mods_enable))
			die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
		
		$userGroupDB = UserGroupDB::getInstance();
		$group_list = $userGroupDB->getList();
		
		$result = array();
		foreach ($group_list as $a_group) {
			$user_list = $a_group->usersLogin();
			foreach ($user_list as $user_login) {
				$result[] = new Liaison($user_login, $a_group->id);
			}
		}
		return $result;
	}
	public static function loadUnique($type_, $element_, $group_) {
		Logger::debug('admin',"Abstract_Liaison_dynamic::loadUnique ($type_,$element_,$group_)");
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		
		$mods_enable = $prefs->get('general', 'module_enable');
		
		if (! in_array('UserDB', $mods_enable))
			die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
		
		$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
		
		$userGroupDB = UserGroupDB::getInstance();
		$userDB = new $mod_user_name();
		
		$group = $userGroupDB->import($group_);
		if (! is_object($group)) {
			Logger::error('main', "Abstract_Liaison_dynamic::loadUnique load group ($group_) failed");
			return NULL;
		}
		
		$user = $userDB->import($element_);
		if (! is_object($user)) {
			Logger::error('main', "Abstract_Liaison_dynamic::loadUnique load $element ($element_) failed");
			return NULL;
		}
		
		if (! $group->containUser($user)) {
			return NULL;
		}
		else {
			return new Liaison($user->getAttribute('login'), $group_);
		}
	}
	
	public static function init($prefs_) {
		return true;
	}
}
