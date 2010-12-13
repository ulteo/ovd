<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
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

abstract class SharedFolderDB extends Module  {
	protected static $instance=NULL;
	public static function getInstance() {
		if (is_null(self::$instance)) {
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed', __FILE__, __LINE__);
			
			$mods_enable = $prefs->get('general', 'module_enable');
			if (!in_array('SharedFolderDB', $mods_enable)){
				die_error(_('SharedFolderDB module must be enabled'), __FILE__, __LINE__);
			}
			$mod_app_name = 'SharedFolderDB_'.$prefs->get('SharedFolderDB', 'enable');
			self::$instance = new $mod_app_name();
		}
		return self::$instance;
	}
	
	public function exists($id_) {
		return NULL;
	}
	
	public function import($id_) {
		return false;
	}
	
	public function importFromServer($server_fdqn_) {
		return false;
	}
	
	public function importFromName($a_name_) {
		return false;
	}
	
	public function importFromUsergroup($usergroup_id_) {
		return array();
	}
	
	public function getList($order_=false) {
		return false;
	}
	
	public function count() {
		return false;
	}
	
	public function countOnServer($fqdn_) {
		return false;
	}
	
	public static function update($sharedfolder_) {
		return false;
	}
	
	public function remove($sharedfolder_id_) {
		return false;
	}
	
	public function addToServer($sharedfolder_, $a_server) {
		return false;
	}
	
	public function deleteFromServer($sharedfolder_, $a_server) {
		return false;
	}
	
	public function addUserGroupToSharedFolder($usergroup_, $sharedfolder_) {
		return false;
	}
	
	public function delUserGroupToSharedFolder($usergroup_, $sharedfolder_) {
		return false;
	}
	
	public function chooseFileServer() {
		return false;
	}
}
