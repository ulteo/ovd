<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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

class Abstract_ServersGroup {
	const table = 'servers_groups';
	protected static $cache = array();
	
	public static function init($prefs_) {
		Logger::debug('main', 'Abstract_ServersGroup::init');
		$structure = array(
			'id' => 'int(8) NOT NULL auto_increment',
			'name' => 'varchar(150) NOT NULL',
			'description' => 'varchar(150) NOT NULL',
			'published' => 'tinyint(1) NOT NULL');
		
		$SQL = SQL::getInstance();
		$ret = $SQL->buildTable(self::table, $structure, array('id'));
		if ( $ret === false) {
			Logger::error('main', 'Abstract_ServersGroup::init table '.self::table.' fail to created');
			return false;
		}
		
		Logger::debug('main', 'Abstract_ServersGroup::init table '.self::table.' created');
		return true;
	}
	
	
	public static function load($id_) {
		if (array_key_exists($id_, self::$cache)) {
			return self::$cache[$id_];
		}
		
		$group = self::import_nocache($id_);
		if (is_object($group)) {
			self::$cache[$group->id] = $group;
		}
		
		return $group;
	}
	
	protected static function import_nocache($id_) {
		Logger::debug('main', "Abstract_ServersGroup::import_nocache (id = $id_)");
		$SQL = SQL::getInstance();
		$res = $SQL->DoQuery('SELECT @1, @2, @3, @4 FROM #5 WHERE @1 = %6', 'id', 'name', 'description', 'published', self::table, $id_);
		if ($SQL->NumRows($res) != 1) {
			Logger::error('main' ,"Abstract_ServersGroup::import_nocache group '$id_' failed");
			return NULL;
		}
		
		$row = $SQL->FetchResult($res);
		$group = self::generateFromRow($row);
		if (! self::isOK($group)) {
			Logger::error('main' ,"Abstract_ServersGroup::import_nocache group '$id_' is not a valid group");
			return NULL;
		}
		
		return $group;
	}
	
	public static function getList() {
		Logger::debug('main','Abstract_ServersGroup::getList');
		
		$SQL = SQL::getInstance();
		$res = $SQL->DoQuery('SELECT @1, @2, @3, @4 FROM #5', 'id', 'name', 'description', 'published', self::table);
		if ($res === false){
			Logger::error('main', 'Abstract_ServersGroup::getList failed (sql query failed)');
			// not the right argument
			return NULL;
		}
		
		$result = array();
		$rows = $SQL->FetchAllResults($res);
		foreach ($rows as $row) {
			$group = self::generateFromRow($row);
			if (! self::isOK($group)) {
				Logger::info('main', 'Abstract_ServersGroup::getList group \''.$row['id'].'\' not ok');
				continue;
			}
			
			self::$cache[$group->id] = $group;
			$result[$group->id]= $group;
		}
		
		return $result;
	}
	
	public static function exists($id_) {
		Logger::debug('main', 'Starting Abstract_ServersGroup::exists for \''.$id_.'\'');
		
		$SQL = SQL::getInstance();
		$SQL->DoQuery('SELECT 1 FROM #1 WHERE @2 = %3 LIMIT 1', self::table, 'id', $id_);
		$total = $SQL->NumRows();
		if ($total == 0) {
			return false;
		}
		
		return true;
	}
	
	public static function create($group_) {
		Logger::debug('main', "Abstract_ServersGroup::add($group_)");
		if (array_key_exists($group_->id, self::$cache)) {
			unset(self::$cache[$group_->id]);
		}
		
		$SQL = SQL::getInstance();
		$res = $SQL->DoQuery('INSERT INTO #1 (@2,@3,@4) VALUES (%5,%6,%7)',self::table, 
			'name', 'description', 'published',
			$group_->name, $group_->description, $group_->published);
		if ($res === false) {
			return false;
		}
		
		$group_->id = $SQL->InsertId();
		return is_object(self::load($group_->id));
	}
	
	public function delete($group_) {
		Logger::debug('main', "Abstract_ServersGroup::delete($group_)");
		if (! is_object($group_)) {
			Logger::error('main', "Abstract_ServersGroup::delete($group_) the parameter is not a object");
			return false;
		}
		
		if (array_key_exists($group_->id, self::$cache)) {
			unset(self::$cache[$group_->id]);
		}
		
		// first we delete liaison
		Abstract_Liaison::delete('UsersGroupServersGroup', NULL, $group_->id);
		Abstract_Liaison::delete('ServersGroup', NULL, $group_->id);
		
		// second we delete the group
		$SQL = SQL::getInstance();
		$res = $SQL->DoQuery('DELETE FROM #1 WHERE @2 = %3', self::table, 'id', $group_->id);
		return ($res !== false);
	}
	
	public function update($group_) {
		Logger::debug('main',"Abstract_ServersGroup::update($group_)");
		if (! is_object($group_)) {
			Logger::error('main', "Abstract_ServersGroup::update($group_) the parameter is not an object");
			return false;
		}
		
		if (array_key_exists($group_->id, self::$cache)) {
			unset(self::$cache[$group_->id]);
		}
		
		$SQL = SQL::getInstance();
		$res = $SQL->DoQuery('UPDATE #1  SET @2 = %3 , @4 = %5 , @6 = %7  WHERE @8 = %9', self::table,
			'published', $group_->published,
			'name', $group_->name,
			'description', $group_->description,
			'id', $group_->id);
		return ($res !== false);
	}
	
	protected static function isOK($group_) {
		if (! is_object($group_)) {
			Logger::info('main', 'Abstract_ServersGroup::isOK('.serialize($group_).') is not an object');
			return false;
		}
		
		if ((!isset($group_->id)) || (!isset($group_->name)) || ($group_->name == '') || (!isset($group_->description)) || (!isset($group_->published))) {
			return false;
		}
		
		return true;
	}
	
	protected static function generateFromRow($row_){
		$group = new ServersGroup($row_['id'], $row_['name'], $row_['description'], (bool)$row_['published']);
		return $group;
	}
}
