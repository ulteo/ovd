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

abstract class ApplicationDB extends Module {
	protected static $instance=NULL;
	public static function getInstance() {
		if (is_null(self::$instance)) {
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed',__FILE__,__LINE__);
			
			$mods_enable = $prefs->get('general','module_enable');
			if (!in_array('ApplicationDB',$mods_enable)){
				die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
			}
			$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB','enable');
			self::$instance = new $mod_app_name();
		}
		return self::$instance;
	}
	
	public function import($id_) {
		return false;
	}
	public function search($app_name, $app_description, $app_type, $app_path_exe) {
		return false;
	}
	public function getList($sort_=false, $type_=NULL) {
		return false;
	}
	public function isWriteable() {
		return false;
	}
	public function isOK($app_) {
		return false;
	}
	public function add($a) {
		return false;
	}
	public function remove($a) {
		return false;
	}
	public function update($a) {
		return false;
	}
	public static function init($prefs_) {
		return false;
	}
	public static function enable() {
		return false;
	}
	public function minimun_attributes() {
		return array();
	}
}
