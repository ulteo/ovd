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
		if (str_startswith($element_, 'static_'))
			$element_ = substr($element_, strlen('static_'));
		if (str_startswith($group_, 'static_'))
			$group_ = substr($group_, strlen('static_'));
		
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
		
		$userDB = UserDB::getInstance();
		
		$elements = array();
		
		$userGroupDB_activedirectory = new UserGroupDB_activedirectory();
		$field = $userGroupDB_activedirectory->customize_field('memberOf');
		$users = $userDB->import_from_filter('('.$field.'='.$group_.')');
		
		foreach ($users as $user_login => $user) {
			$l = new Liaison($user_login, $group_);
			$elements[$l->element] = $l;
		}
		return $elements;
	}
	
	public static function loadGroups($type_, $element_) {
		Logger::debug('main', "Abstract_Liaison_activedirectory::loadGroups ($type_,$element_)");
		
		$userDB = UserDB::getInstance();
		$element_user = $userDB->import($element_);
		if (! is_object($element_user)) {
			Logger::error('main', "Abstract_Liaison_activedirectory::loadGroups load element ($element_) failed");
			return NULL;
		}

		$userGroupDB = UserGroupDB::getInstance('static');
		$groups = $userGroupDB->get_by_user_members($element_user->getAttribute('dn'));
		$liaisons = array();
		foreach ($groups as $group_id => $group) {
			$l = new Liaison($element_, $group_id);
			$liaisons[$l->group] = $l;
		}
		
		return $liaisons;
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
