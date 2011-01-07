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

class Abstract_Liaison_unix {
	public static function load($type_, $element_=NULL, $group_=NULL) {
		Logger::debug('main', "Abstract_Liaison_unix::load($type_,$element_,$group_)");
		
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
			Logger::error('main', "Abstract_Liaison_unix::load error liaison != UsersGroup not implemented");
			return NULL;
		}
		return NULL;
		
	}
	public static function save($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_unix::save");
		return false;
	}
	public static function delete($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_unix::delete");
		return false;
	}
	public static function loadElements($type_, $group_) {
		Logger::debug('main', "Abstract_Liaison_unix::loadElements ($type_,$group_)");
		$elements = array();
		
		$userDB = UserDB::getInstance();
		$userGroupDB = UserGroupDB::getInstance();
		
		$group = $userGroupDB->import($group_);
		if (! is_object($group)) {
			Logger::error('main', "Abstract_Liaison_unix::loadElements load group ($group_) failed");
			return NULL;
		}
		
		if (isset($group->extras)) {
			if (is_array($group->extras) && array_key_exists('member', $group->extras)) {
				$members = $group->extras['member'];
				foreach ($members as $member) {
					$u = $userDB->import($member);
					if (is_object($u) == false) {
						Logger::error('main', "Abstract_Liaison_unix::loadElements ($type_,$group_) failed to import ".$member);
						continue;
					}
					$l = new Liaison($u->getAttribute('login'), $group->getUniqueID());
					$elements[$l->element]= clone($l);
				}
			}
		}
		return $elements;
	}
	
	public static function loadGroups($type_, $element_) {
		Logger::debug('main', "Abstract_Liaison_unix::loadGroups ($type_,$element_)");
		
		$groups = array();
		
		$userGroupDB = UserGroupDB::getInstance();
		$userDB = UserDB::getInstance();
		$element_user = $userDB->import($element_);
		if (! is_object($element_user)) {
			Logger::error('main', "Abstract_Liaison_unix::loadGroups load element ($element_) failed");
			return NULL;
		}
		
		$userGroupDB = UserGroupDB::getInstance();
		$groups_list = $userGroupDB->getList();
		
		foreach ($groups_list as $group) {
			$liaisons = self::loadElements($type_, $group->getUniqueID());
			if (is_array($liaisons)) {
				foreach ($liaisons as $liaison) {
					$l = new Liaison($element_user->getAttribute('login'), $group->getUniqueID());
					$groups[$l->group] = $l;
				}
			}
		}
		return $groups;
	}
	
	public static function loadAll($type_) {
		Logger::debug('main',"Abstract_Liaison_unix::loadAll ($type_)");
		return NULL;
	}
	public static function loadUnique($type_, $element_, $group_) {
		Logger::debug('main',"Abstract_Liaison_unix::loadUnique ($type_,$element_,$group_)");
		return NULL;
	}
	
	public static function init($prefs_) {
		return true;
	}
}
