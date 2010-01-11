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

class ApplicationsGroupDB_sql extends ApplicationsGroupDB {
	protected $table;
	public static $prefixless_tablename = 'gapplication';
	
	public function __construct() {
		$prefs = Preferences::getInstance();
		if ($prefs) {
			$sql_conf = $prefs->get('general', 'sql');
			if (is_array($sql_conf)) {
				$this->table =  $sql_conf['prefix'].ApplicationsGroupDB_sql::$prefixless_tablename;
			}
			else
				$this->table = NULL;
		}
	}
	
	public function __toString() {
		return get_class($this).'(table \''.$this->table.'\')';
	}
	
	public function isWriteable() {
		return true;
	}
	
	public function canShowList() {
		return true;
	}
	
	public function isOK($group_) {
		if (is_object($group_)) {
			if ((!isset($group_->id)) || (!isset($group_->name)) || ($group_->name == '') || (!isset($group_->description)) || (!isset($group_->published)))
				return false;
			else
				return true;
		}
		else {
			Logger::info('main', 'ApplicationsGroupDB::sql::isOK('.serialize($group_).') is not an object');
			return false;
		}
	}
	
	public function import($id_) {
		Logger::debug('main', "ApplicationsGroupDB::sql::import (id = $id_)");
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM @5 WHERE @1 = %6', 'id', 'name', 'description', 'published', $this->table, $id_);
			
		if ($sql2->NumRows($res) == 1) {
			$row = $sql2->FetchResult($res);
			$group = $this->generateFromRow($row);
			if ($this->isOK($group))
				return $group;
		}
		
		Logger::error('main' ,"ApplicationsGroupDB::sql::import import group '$id_' failed");
		return NULL;
	}
	
	public function getList($sort_=false) {
		Logger::debug('main','ApplicationsGroupDB::sql::getList');
		if (is_null($this->table)) {
			Logger::error('main', 'ApplicationsGroupDB::sql::getList table is null');
			return NULL;
		}
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM @5', 'id', 'name', 'description', 'published', $this->table);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row) {
				$group = $this->generateFromRow($row);
				if ($this->isOK($group)) {
					$result[$group->id]= $group;
				}
				else {
					Logger::info('main', 'ApplicationsGroupDB::sql::getList group \''.$row['id'].'\' not ok');
				}
			}
			if ($sort_) {
				usort($result, "appsgroup_cmp");
			}
			
			return $result;
		}
		else {
			Logger::error('main', 'ApplicationsGroupDB::sql::getList failed (sql query failed)');
			// not the right argument
			return NULL;
		}
	}
	
	public function isDynamic() {
		return false;
	}
	
	public static function configuration() {
		return array();
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		// dirty
		$ret = self::prefsIsValid2($prefs_, $log);
		if ( $ret != true) {
			$ret = admin_ApplicationsGroupDB_sql::init($prefs_);
		}
		return $ret;
	}
	
	public static function prefsIsValid2($prefs_, &$log=array()) {
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			
			return false;
		}
		$table =  $sql_conf['prefix'].ApplicationsGroupDB_sql::$prefixless_tablename;
		$sql2 = SQL::newInstance($sql_conf);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2', $sql_conf['database'], $table);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows($ret);
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main', 'ApplicationsGroupDB::sql::prefsIsValid table \''.$table.'\' does not exist');
				return false;
			}
		}
		else {
			Logger::error('main', 'ApplicationsGroupDB::sql::prefsIsValid table \''.$table.'\' does not exist(2)');
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
	
	protected function generateFromRow($row_){
		$group = new AppsGroup($row_['id'], $row_['name'], $row_['description'], (bool)$row_['published']);
		return $group;
	}
}

