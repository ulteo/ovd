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
		Logger::debug('admin','ADMIN_USERGROUPDB::sql::init');
		$mysql_conf = $prefs_->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('admin','ADMIN_USERGROUPDB::sql::init mysql conf not valid');
			return false;
		}
		$usersgroup_table = $mysql_conf['prefix'].'usergroup';
		$sql2 = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);
		
		$usersgroup_table_structure = array(
			'id' => 'int(8) NOT NULL auto_increment',
			'name' => 'varchar(150) NOT NULL',
			'description' => 'varchar(150) NOT NULL',
			'published' => 'tinyint(1) NOT NULL');
		
		$ret = $sql2->buildTable($usersgroup_table, $usersgroup_table_structure, array('id'));
		
		if ( $ret === false) {
			Logger::error('admin','ADMIN_USERGROUPDB::sql::init table '.$usersgroup_table.' fail to created');
			return false;
		}
		
		return true;
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		// dirty
		$ret = self::prefsIsValid2($prefs_, $log);
		if ( $ret != true) {
			$ret = self::init($prefs_);
		}
		return $ret;
	}
	
	public static function prefsIsValid2($prefs_, &$log=array()) {
		$mysql_conf = $prefs_->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			
			return false;
		}
		$table =  $mysql_conf['prefix'].'usergroup';
		$sql2 = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2', $mysql_conf['database'], $table);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows($ret);
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main','USERGROUPDB::MYSQL::prefsIsValid table \''.$table.'\' not exists');
				return false;
			}
		}
		else {
			Logger::error('main','USERGROUPDB::MYSQL::prefsIsValid table \''.$table.'\' not exists(2)');
			return false;
		}
	}
	
	public static function enable() {
		return true;
	}
	
}
