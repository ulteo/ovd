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

class Abstract_Liaison {
	public function load($type_, $element_=NULL, $group_=NULL) {
		Logger::debug('main', "Abstract_Liaison::load ('$type_', '$element_', '$group_')");
		if ($type_ != 'UsersGroup')
			return Abstract_Liaison_sql::load($type_,  $element_, $group_);
		else {
			$prefs = Preferences::getInstance();
			if (! $prefs) {
				Logger::error('main', 'Abstract_Liaison::load get Preferences failed');
				return NULL;
			}
			$mods_enable = $prefs->get('general','module_enable');
			if (! in_array('UserGroupDB',$mods_enable)) {
				Logger::error('main', 'Abstract_Liaison::load Module UserGroupDB must be enabled');
				return NULL;
			}
			
			$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
			$userGroupDB = new $mod_usergroup_name();
			switch ($userGroupDB->liaisonType()) {
				case 'sql':
					return Abstract_Liaison_sql::load($type_,  $element_, $group_);
				break;
				case 'ldap':
					return Abstract_Liaison_ldap::load($type_,  $element_, $group_);
				break;
			}
			return NULL;
		}
	}
	public function delete($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison::delete ('$type_', '$element_', '$group_')");
		if ($type_ != 'UsersGroup')
			return Abstract_Liaison_sql::delete($type_,  $element_, $group_);
		else {
			$prefs = Preferences::getInstance();
			if (! $prefs) {
				Logger::error('main', 'Abstract_Liaison::delete get Preferences failed');
				return NULL;
			}
			$mods_enable = $prefs->get('general','module_enable');
			if (! in_array('UserGroupDB',$mods_enable)) {
				Logger::error('main', 'Abstract_Liaison::delete Module UserGroupDB must be enabled');
				return NULL;
			}
			
			$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
			$userGroupDB = new $mod_usergroup_name();
			switch ($userGroupDB->liaisonType()) {
				case 'sql':
					return Abstract_Liaison_sql::delete($type_,  $element_, $group_);
				break;
				case 'ldap':
					return Abstract_Liaison_ldap::delete($type_,  $element_, $group_);
				break;
			}
			return NULL;
		}
	}
	public function save($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison::save ('$type_', '$element_', '$group_')");
		if ($type_ != 'UsersGroup')
			return Abstract_Liaison_sql::save($type_,  $element_, $group_);
		else {
			$prefs = Preferences::getInstance();
			if (! $prefs) {
				Logger::error('main', 'Abstract_Liaison::save get Preferences failed');
				return NULL;
			}
			$mods_enable = $prefs->get('general','module_enable');
			if (! in_array('UserGroupDB',$mods_enable)) {
				Logger::error('main', 'Abstract_Liaison::load Module UserGroupDB must be enabled');
				return NULL;
			}
			
			$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
			$userGroupDB = new $mod_usergroup_name();
			switch ($userGroupDB->liaisonType()) {
				case 'sql':
					return Abstract_Liaison_sql::save($type_,  $element_, $group_);
				break;
				case 'ldap':
					return Abstract_Liaison_ldap::save($type_,  $element_, $group_);
				break;
			}
			return NULL;
		}
	}
	
	public static function init() {
		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('main', 'Abstract_Liaison::save get Preferences failed');
			return NULL;
		}
		$mods_enable = $prefs->get('general','module_enable');
		if (! in_array('UserGroupDB',$mods_enable)) {
			Logger::error('main', 'Abstract_Liaison::load Module UserGroupDB must be enabled');
			return NULL;
		}
		$userGroupDB = new $mod_usergroup_name();
		if ($userGroupDB->liaisonType() == 'ldap') {
			Abstract_Liaison_ldap::init();
		}
		Abstract_Liaison_sql::init();
		return true;
	}
}

