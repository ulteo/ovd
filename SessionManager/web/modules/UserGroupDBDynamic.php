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

class UserGroupDBDynamic extends Module {
	protected static $instance=NULL;
	public static function getInstance() {
		if (is_null(self::$instance)) {
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed', __FILE__, __LINE__);
			 
			$mods_enable = $prefs->get('general', 'module_enable');
			if (!in_array('UserGroupDBDynamic', $mods_enable)){
				die_error(_('UserGroupDBDynamic module must be enabled'), __FILE__, __LINE__);
			}
			$mod_app_name = 'UserGroupDBDynamic_'.$prefs->get('UserGroupDBDynamic', 'enable');
			self::$instance = new $mod_app_name();
		}
		return self::$instance;
	}
	
	public function import($id_) {
		return NULL;
	}
	
	public static function multiSelectModule() {
		return false;
	}
	
	public function getList() {
		return NULL;
	}
	
	public function isWriteable() {
		return false;
	}
	
	public function canShowList() {
		return false;
	}
	
	public static function enabledByDefault() {
		return false;
	}
	
	public function add($usergroup_){
		return false;
	}
	
	public function remove($usergroup_){
		return false;
	}
	
	public function update($usergroup_){
		return false;
	}
	
	public static function enable() {
		return true;
	}
	
	public static function configuration() {
		return array();
	}
	
	public static function init($prefs_) {
		return false;
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		return false;
	}
	
	public static function prettyName() {
		return 'UserGroupDBDynamic';
	}
	
	public function getGroupsContains($contains_, $attributes_=array('name', 'description'), $limit_=0) {
		return array(array(), false);
	}
}
