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

abstract class UserGroupDB extends Module {
	public function isOK($usergroup_) {
		if (is_object($usergroup_)) {
			if ((!isset($usergroup_->id)) || (!isset($usergroup_->name)) || ($usergroup_->name == '') || (!isset($usergroup_->published)))
				return false;
			else
				return true;
		}
		else
			return false;
	}
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
			'published' => 'tinyint(1) NOT NULL',
			'type' => "VARCHAR( 50 ) NULL DEFAULT 'static'");
		
		$ret = $sql2->buildTable($usersgroup_table, $usersgroup_table_structure, array('id'));
		
		if ( $ret === false) {
			Logger::error('admin','ADMIN_USERGROUPDB::sql::init table '.$usersgroup_table.' fail to created');
			return false;
		}
		
		return true;
	}
	protected function import_sql($id_, $must_be_dynamic=false){
		Logger::debug('admin',"USERGROUPDB::MYSQL::fromDB (id = $id_)");
		
		$prefs = Preferences::getInstance();
		if ($prefs) {
			$mysql_conf = $prefs->get('general', 'mysql');
			if (!is_array($mysql_conf))
				return NULL;
			else
				$table =  $mysql_conf['prefix'].'usergroup';
		}
		
		$sql2 = MySQL::getInstance();
		if ( $must_be_dynamic)
			$res = $sql2->DoQuery('SELECT @1, @2, @3, @4, @7 FROM @5 WHERE @1 = %6 AND @7 = %8', 'id', 'name', 'description', 'published', $table, $id_, 'type', 'dynamic');
		else
			$res = $sql2->DoQuery('SELECT @1, @2, @3, @4, @7 FROM @5 WHERE @1 = %6', 'id', 'name', 'description', 'published', $table, $id_, 'type');
			
		if ($sql2->NumRows($res) == 1) {
			$row = $sql2->FetchResult($res);
			if (!isset($row['type'])) {
				$ug = new UsersGroup($row['id'], $row['name'], $row['description'], (bool)$row['published']);
			}
			else if ($row['type'] == 'dynamic') {
				$ug = new UsersGroup_dynamic($row['id'], $row['name'], $row['description'], (bool)$row['published']);
			}
			else {
				$ug = new UsersGroup($row['id'], $row['name'], $row['description'], (bool)$row['published']);
			}
			if ($this->isOK($ug))
				return $ug;
		}
		else {
			Logger::error('main' ,"USERGROUPDB::MYSQL::fromDB import group '$id_' failed");
			return NULL;
		}
	}
	protected function getListDynamic() {
		Logger::debug('main','UserGroupDB::getListDynamic');
		$prefs = Preferences::getInstance();
		if (!$prefs) {
			return array();
		}
		$mysql_conf = $prefs->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			return array();
		}
		$table =  $mysql_conf['prefix'].'usergroup';
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4, @6 FROM @5 WHERE @6 = %7', 'id', 'name', 'description', 'published', $this->table, 'type', 'dynamic');
		if ($res === false) {
			Logger::error('main', 'USERGROUPDB::MYSQL::getList failed (sql query failed)');
			// not the right argument
			return array();
		}
		$result = array();
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row){
			$ug = new UsersGroup_dynamic($row['id'], $row['name'], $row['description'], (bool)$row['published']);
			if ($this->isOK($ug))
				$result[$ug->id]= $ug;
			else {
				Logger::info('main', 'USERGROUPDB::MYSQL::getList group \''.$row['id'].'\' not ok');
			}
		}
		return $result;;
	}
}
