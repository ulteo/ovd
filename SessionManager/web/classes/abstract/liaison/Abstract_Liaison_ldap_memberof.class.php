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
		
		$userDB = UserDB::getInstance();
		
		$elements = array();
		
		$users = $userDB->import_from_filter('(memberOf='.$group_.')');
		foreach ($users as $user_login => $user) {
			$l = new Liaison($user_login, $group_);
			$elements[$l->element] = $l;
		}
		return $elements;
	}
	
	public static function loadGroups($type_, $element_) {
		Logger::debug('main', "Abstract_Liaison_ldap_memberof::loadGroups ($type_,$element_)");
		
		$userGroupDB = UserGroupDB::getInstance('static');
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

			$memberof = $userGroupDB->imports($memberof);
			foreach ($memberof as $id_group => $g) {
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
