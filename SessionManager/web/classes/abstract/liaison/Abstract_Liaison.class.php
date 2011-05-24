<?php
/**
 * Copyright (C) 2009-2011 Ulteo SAS
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
	public static function callMethod($method_name_, $type_, $element_=NULL, $group_=NULL) {
		Logger::debug('main', "Abstract_Liaison::callMethod ('$method_name_', '$type_', '$element_', '$group_')");
		if ($type_ != 'UsersGroup') {
			$method_to_call = array('Abstract_Liaison_sql', $method_name_);
			$class_to_use = 'Abstract_Liaison_sql';
		}
		else {
			$prefs = Preferences::getInstance();
			if (! $prefs) {
				Logger::error('main', 'Abstract_Liaison::load get Preferences failed');
				return NULL;
			}
			$mods_enable = $prefs->get('general','module_enable');
			if (! in_array('UserGroupDB',$mods_enable)) {
				Logger::error('main', 'Abstract_Liaison::load UserGroupDB module must be enabled');
				return NULL;
			}
			
			$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
			$liaison_type = call_user_func(array($mod_usergroup_name, 'liaisonType'));
			
			$method_to_call = array('Abstract_Liaison_'.$liaison_type, $method_name_);
			$class_to_use = 'Abstract_Liaison_'.$liaison_type;
		}
		
		if (!method_exists($class_to_use, $method_name_)) {
			Logger::error('main', "Abstract_Liaison::callMethod method '$method_to_call' does not exist");
			return NULL;
		}
		return call_user_func($method_to_call, $type_,  $element_, $group_);
	}
	public static function load($type_, $element_=NULL, $group_=NULL) {
		return self::callMethod('load', $type_, $element_, $group_);
	}
	public static function delete($type_, $element_, $group_) {
		return self::callMethod('delete', $type_, $element_, $group_);
	}
	public static function save($type_, $element_, $group_) {
		return self::callMethod('save', $type_, $element_, $group_);
	}
	
	public static function init($prefs) {
		$mods_enable = $prefs->get('general','module_enable');
		if (in_array('UserGroupDB', $mods_enable)) {
			if (! is_null($prefs->get('UserGroupDB','enable'))) {
				$mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
				$liaison_type = call_user_func(array($mod_usergroup_name, 'liaisonType'));
				call_user_func(array('Abstract_Liaison_'.$liaison_type, 'init'), $prefs);
			}
			else {
				Logger::info('main', 'Abstract_Liaison::init no module UserGroupDB enable');
			}
		}
		Abstract_Liaison_sql::init($prefs);
		return true;
	}
}

