<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
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

class admin_ApplicationsGroupDB_sql extends ApplicationsGroupDB_sql {
	public function add($group_) {
		Logger::debug('main', "admin_ApplicationsGroupDB::sql::add($group_)");
		$sql2 = SQL::getInstance();
		// group already exists ?
		$res = $sql2->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 AND @4 = %5', $this->table, 'name', $group_->name, 'description', $group_->description);
			
		if ($sql2->NumRows($res) > 0) {
			Logger::error('main', 'admin_ApplicationsGroupDB::sql_sql::add usersgroup (name='.$group_->name.',description='.$group_->description.') already exists');
			popup_error(_('Applications group already exists'));
			return false;
		}
		
		$res = $sql2->DoQuery('INSERT INTO @1 (@2,@3,@4) VALUES (%5,%6,%7)',$this->table, 'name', 'description', 'published', $group_->name, $group_->description, $group_->published);
		if ($res !== false) {
			$group_->id = $sql2->InsertId();
			return is_object($this->import($sql2->InsertId()));
		}
		else
			return false;
	}
	
	public function remove($group_) {
		Logger::debug('main', "admin_ApplicationsGroupDB::sql::remove($group_)");
		if (! is_object($group_)) {
			Logger::error('main', "admin_ApplicationsGroupDB::sql::remove($group_) the parameter is not a object");
			return false;
		}
		// first we delete liaison
		$sql2 = SQL::getInstance();
		$liaisons = Abstract_Liaison::load('UsersGroupApplicationsGroup', NULL, $group_->id);
		foreach ($liaisons as $liaison) {
			Abstract_Liaison::delete('UsersGroupApplicationsGroup', $liaison->element, $liaison->group);
		}
		$liaisons = Abstract_Liaison::load('ApplicationServer', NULL, $group_->id);
		foreach ($liaisons as $liaison) {
			Abstract_Liaison::delete('ApplicationServer', $liaison->element, $liaison->group);
		}
		
		// second we delete the group
		$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2 = %3', $this->table, 'id', $group_->id);
		return ($res !== false);
	}
	
	public function update($group_) {
		Logger::debug('main',"admin_ApplicationsGroupDB::sql::update($group_)");
		if (! is_object($group_)) {
			Logger::error('main', "admin_ApplicationsGroupDB::sql::update($group_) the parameter is not an object");
			return false;
		}
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('UPDATE @1  SET @2 = %3 , @4 = %5 , @6 = %7  WHERE @8 = %9', $this->table, 'published', $group_->published, 'name', $group_->name, 'description', $group_->description, 'id', $group_->id);
		return ($res !== false);
	}
	
	public static function init($prefs_) {
		Logger::debug('main', 'admin_ApplicationsGroupDB::sql::init');
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			Logger::error('main','admin_ApplicationsGroupDB::sql::init sql conf is not valid');
			return false;
		}
		$sql2 = SQL::newInstance($sql_conf);
		
		$appsgroup_table = $sql_conf['prefix'].ApplicationsGroupDB_sql::$prefixless_tablename;
		$appsgroup_structure = array(
			'id' => 'int(8) NOT NULL auto_increment',
			'name' => 'varchar(150) NOT NULL',
			'description' => 'varchar(150) NOT NULL',
			'published' => 'tinyint(1) NOT NULL');
		
		$ret = $sql2->buildTable($appsgroup_table, $appsgroup_structure, array('id'));
		
		if ( $ret === false) {
			Logger::error('main', 'admin_ApplicationsGroupDB::sql::init table '.$appsgroup_table.' fail to created');
			return false;
		}
		else
			Logger::debug('main', 'admin_ApplicationsGroupDB::sql::init table '.$appsgroup_table.' created');
		
		return true;
	}
	
	public static function enable() {
		return true;
	}
}



