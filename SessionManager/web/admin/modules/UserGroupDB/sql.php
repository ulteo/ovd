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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class admin_UserGroupDB_sql extends UserGroupDB_sql {
	public function add($usergroup_){
		Logger::debug('admin','ADMIN_USERGROUPDB::add');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('INSERT INTO @1 (@2,@3,@4) VALUES (%5,%6,%7)',$this->table, 'name', 'description', 'published', $usergroup_->name, $usergroup_->description, $usergroup_->published);
		if ($res !== false) {
			$usergroup_->id = $sql2->InsertId();
			return is_object($this->import($sql2->InsertId()));
		}
		else
			return false;
	}
	
	public function remove($usergroup_){
		Logger::debug('admin','ADMIN_USERGROUPDB::remove');
		// first we delete liaisons
		$sql2 = MySQL::getInstance();
		$liaisons = Abstract_Liaison::load('UsersGroupApplicationsGroup', $usergroup_->id, NULL);
		foreach ($liaisons as $liaison) {
			Abstract_Liaison::delete('UsersGroupApplicationsGroup', $liaison->element, $liaison->group);
		}
		foreach ($liaisons as $liaison) {
			Abstract_Liaison::delete('UsersGroup', NULL, $usergroup_->id);
		}
		// second we delete the group
		$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2 = %3', $this->table, 'id', $usergroup_->id);
		return ($res !== false);
	}
	
	public function update($usergroup_){
		Logger::debug('admin','ADMIN_USERGROUPDB::update');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('UPDATE @1  SET @2 = %3 , @4 = %5 , @6 = %7  WHERE @8 = %9', $this->table, 'published', $usergroup_->published, 'name', $usergroup_->name, 'description', $usergroup_->description, 'id', $usergroup_->id);
		return ($res !== false);
	}
	
// 	public function configuration(){
// 		$c = new ConfigElement('mysql','host','host_des(mysql)','un host',NULL,1);
// 		$this->add('general',$c);
// 		$c = new ConfigElement('mysql','port','port_des(mysql)','un port',NULL,0);
// 		$this->add('general',$c);
// 		$c = new ConfigElement('mysql','login','login_des(mysql)','un login',NULL,1);
// 		$this->add('general',$c);
// 		$c = new ConfigElement('mysql','pass','pass_des(mysql)','un password',NULL,1);
// 		$this->add('general',$c);
// 		$p = new Preferences_admin();
// 	}

	public static function init($prefs_) {
		parent::init($prefs_);
		// do nothing more
		return true;
	}
	
	public static function enable() {
		return true;
	}
	
}
