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
		
		$liaison_type = 'sql';
		$method_to_call = array('Abstract_Liaison_'.$liaison_type, $method_name_);
		$class_to_use = 'Abstract_Liaison_'.$liaison_type;
		
		if (!method_exists($class_to_use, $method_name_)) {
			Logger::error('main', "Abstract_Liaison::callMethod method '".serialize($method_to_call)."' does not exist");
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
		$liaison_owners = Preferences::liaisonsOwner();
		if (is_array($liaison_owners)) {
			foreach ($liaison_owners as $type => $owner) {
				$class_to_use = 'Abstract_Liaison_'.$owner;
				$method_to_call = array($class_to_use, 'init');
				if (method_exists($class_to_use, 'init')) {
					call_user_func($method_to_call, $prefs);
				}
			}
		}
		Abstract_Liaison_sql::init($prefs);
		return true;
	}
}

