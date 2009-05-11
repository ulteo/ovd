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
class UserGroupDB_sql extends UserGroupDB {
	protected $table;
	public function __construct(){
		$prefs = Preferences::getInstance();
		if ($prefs) {
			$mysql_conf = $prefs->get('general', 'mysql');
			if (is_array($mysql_conf)) {
				$this->table =  $mysql_conf['prefix'].'usergroup';
			}
			else
				$this->table = NULL;
		}
	}
	
	public function import($id_){
		Logger::debug('admin',"USERGROUPDB::MYSQL::fromDB (id = $id_)");
		if (is_null($this->table)) {
			Logger::error('main', 'USERGROUPDB::MYSQL::fromDB table is null');
			return NULL;
		}
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM @5 WHERE @1= %6', 'id', 'name', 'description', 'published', $this->table, $id_);
		if ($sql2->NumRows($res) == 1) {
			$row = $sql2->FetchResult($res);
			$ug = new UsersGroup($row['id'], $row['name'], $row['description'], (bool)$row['published']);
			if ($this->isOK($ug))
				return $ug;
		}
		else {
			Logger::error('main' ,"USERGROUPDB::MYSQL::fromDB import group '$id_' failed");
			return NULL;
		}
	}
	
	public function isWriteable(){
		return true;
	}
	
	public function canShowList(){
		return true;
	}
	
	public function getList() {
		Logger::debug('main','UserGroupDB_sql::getList');
		if (is_null($this->table)) {
			Logger::error('main', 'USERGROUPDB::MYSQL::getList table is null');
			return NULL;
		}
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM @5', 'id', 'name', 'description', 'published', $this->table);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$ug = new UsersGroup($row['id'], $row['name'], $row['description'], (bool)$row['published']);
				if ($this->isOK($ug))
					$result[$ug->id]= $ug;
				else {
					Logger::info('main', 'USERGROUPDB::MYSQL::getList group \''.$row['id'].'\' not ok');
				}
			}
			return $result;
		}
		else {
			Logger::error('main', 'USERGROUPDB::MYSQL::getList failed (sql query failed)');
			// not the right argument
			return NULL;
		}
	}
	
	public function isOK($usergroup_){
		if (is_object($usergroup_)) {
			if ((!isset($usergroup_->id)) || (!isset($usergroup_->name)) || (!isset($usergroup_->published)))
				return false;
			else
				return true;
		}
		else
			return false;
	}
	
	private function generateUserFromRow($row){
		$u = new User();
		foreach ($row as $key => $value){
			$u->setAttribute($key,$value);
		}
		return $u;
	}
	
	public static function configuration() {
		return array();
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		// dirty
		$ret = self::prefsIsValid2($prefs_, $log);
		if ( $ret != true) {
			$ret = admin_UserGroupDB_sql::init($prefs_);
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
	
	public static function prettyName() {
		return _('MySQL');
	}
	
	public static function isDefault() {
		return true;
	}
	
	public static function liaisonType() {
		return 'sql';
	}
}
