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

/**
 * Abstraction layer between the Preferences instances and the SQL backend.
 */
class Abstract_Preferences {
	private static $table = 'preferences';
	private static $TYPE_GENERAL	= 'general';
	private static $TYPE_GROUP		= 'group';
	private static $TYPE_USER		= 'user';
	
	public static function init($prefs_) {
		Logger::debug('main', 'Abstract_Preferences::init');
		
		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);
		
		$preferences_table_structure = array(
			'owner_type'=>	'varchar(255)',
			'owner'		=>	'varchar(255)',
			'key'		=>	'varchar(255)',
			'value'		=>	'text'
		);
		
		$ret = $SQL->buildTable(self::$table, $preferences_table_structure, array());
		if (! $ret) {
			Logger::error('main', "Unable to create MySQL table '".self::$table."'");
			return false;
		}
		
		Logger::debug('main', "MySQL table '".self::$table."' created");
		return true;
	}
	
	private static function load($type_ = null, $owners_ = null, $pattern_ = null) {
		$sql = SQL::getInstance();
		
		$where = array();
		if (! is_null($type_) && in_array($type_, array(self::$TYPE_GENERAL, self::$TYPE_GROUP, self::$TYPE_USER))) {
			array_push($where, $sql->QuoteField('owner_type').' = '.$sql->Quote($type_));
		}
		
		if (is_array($owners_) && count($owners_) > 0) {
			$owners2 = array();
			foreach($owners_ as $owner) {
				array_push($owners2, $sql->Quote($owner));
			}
			
			array_push($where, $sql->QuoteField('owner').' IN ('.implode(', ', $owners2).')');
		}
		
		if (! is_null($pattern_) && strlen($pattern_) > 0) {
			$search = str_replace('*', '%', $pattern_);
			if (strpos($search, '%')) {
				$cmp = 'LIKE';
			}
			else {
				$cmp = '=';
			}
			
			array_push($where, $sql->QuoteField('key').' '.$cmp.' '.$sql->Quote($search));
		}
		
		$query = 'SELECT * FROM #1';
		if (count($where) > 0) {
			$query.= ' WHERE '.implode(' AND ', $where);
		}
		
		$res = $sql->DoQuery($query, self::$table);
		if ($res !== true) {
			Logger::error('main', "Abstract_Preferences::load sql request failed");
			return array();
		}
		
		$ret = array();
		$rows = $sql->FetchAllResults();
		foreach ($rows as $row) {
			$k = $row['key'];
			$v = json_unserialize($row['value']);
			
			$ret[$k] = $v;
		}
		
		return $ret;
	}

	private static function delete($type_ = null, $owners_ = null, $pattern_ = null) {
		$sql = SQL::getInstance();
		
		$where = array();
		if (! is_null($type_) && in_array($type_, array(self::$TYPE_GENERAL, self::$TYPE_GROUP, self::$TYPE_USER))) {
			array_push($where, $sql->QuoteField('owner_type').' = '.$sql->Quote($type_));
		}
		
		if (is_array($owners_) && count($owners_) > 0) {
			$owners2 = array();
			foreach($owners_ as $owner) {
				array_push($owners2, $sql->Quote($owner));
			}
			
			array_push($where, $sql->QuoteField('owner').' IN ('.implode(', ', $owners2).')');
		}
		
		if (! is_null($pattern_) && strlen($pattern_) > 0) {
			$search = str_replace('*', '%', $pattern_);
			if (strpos($search, '%')) {
				$cmp = 'LIKE';
			}
			else {
				$cmp = '=';
			}
			
			array_push($where, $sql->QuoteField('key').' '.$cmp.' '.$sql->Quote($search));
		}
		
		$query = 'DELETE FROM #1';
		if (count($where) > 0) {
			$query.= ' WHERE '.implode(' AND ', $where);
		}
		
		$res = $sql->DoQuery($query, self::$table);
		return $res;
	}
	
	public static function load_general() {
		return self::load(self::$TYPE_GENERAL);
	}
	
	public static function save_general($data_) {
		$sql = SQL::getInstance();
		
		// First: delete all
		$sql->DoQuery('DELETE FROM #1 WHERE @2 = %3', self::$table, 'owner_type', self::$TYPE_GENERAL);
		
		// Then: push in one request
		$values = array();
		foreach($data_ as $k => $v) {
			array_push($values, '('.$sql->Quote(self::$TYPE_GENERAL).','.$sql->Quote($k).','.$sql->Quote(json_serialize($v)).')');
		}
		
		$res = $sql->DoQuery('INSERT INTO #1 (@2, @3, @4) VALUES '.implode(',', $values), self::$table, 'owner_type', 'key', 'value');
		if ($res !== true) {
			Logger::error('main', "Abstract_Preferences::save Unable to save preferences");
			return false;
		}
		
		return true;
	}
	
	private static function load_by_owner($type_, $pattern_) {
		$sql = SQL::getInstance();
		
		$query = 'SELECT * FROM #1 WHERE @2 = %3';
		if (! is_null($pattern_) && strlen($pattern_) > 0) {
			$search = str_replace('*', '%', $pattern_);
			$query.= ' AND '.$sql->QuoteField('key').' LIKE '.$sql->Quote($search);
		}
		
		$res = $sql->DoQuery($query, self::$table, 'owner_type', $type_);
		if ($res !== true) {
			Logger::error('main', "Abstract_Preferences::load_by_owner sql request failed");
			return array();
		}
		
		$ret = array();
		$rows = $sql->FetchAllResults();
		foreach ($rows as $row) {
			$owner = $row['owner'];
			if (! $owner || is_null($owner) || strlen($owner) == 0) {
				continue;
			}
			
			$k = $row['key'];
			$v = json_unserialize($row['value']);
			
			if (! array_key_exists($owner, $ret)) {
				$ret[$owner] = array();
			}
			
			$ret[$owner][$k] = $v;
		}
		
		return $ret;
	}
	
	public static function save_owner_item($type_, $owner_, $key_, $value_) {
		$sql = SQL::getInstance();
		
		if (! in_array($type_, array(self::$TYPE_GROUP, self::$TYPE_USER))) {
			return false;
		}
		
		$res = $sql->DoQuery('INSERT INTO #1 (@2, @3, @4, @5) VALUES (%6, %7, %8, %9)', self::$table,
			'owner_type', 'owner', 'key', 'value',
			$type_, $owner_, $key_, json_serialize($value_)
		);
		if ($res !== true) {
			Logger::error('main', "Abstract_Preferences::save_owner_item Unable to save preference");
			return false;
		}
		
		return true;
	}
	
	public static function load_group($group_, $pattern_=null) {
		return self::load(self::$TYPE_GROUP, array($group_), $pattern_);
	}
	
	private static function delete_groups($groups_ = null, $pattern_ = null) {
		return self::delete(self::$TYPE_GROUP, $groups_, $pattern_);
	}
	
	public static function delete_all_groups($pattern_ = null) {
		return self::delete_groups(null, $pattern_);
	}
	
	public static function delete_group($group_, $pattern_) {
		return self::delete_groups(array($group_), $pattern_);
	}
	
	public static function save_group_item($group_, $key_, $value_) {
		$sql = SQL::getInstance();
		
		self::delete_group($group_, $key_);
		
		// Then save
		return self::save_owner_item(self::$TYPE_GROUP, $group_, $key_, $value_);
	}
	
	public static function load_by_group($pattern_=null) {
		return self::load_by_owner(self::$TYPE_GROUP, $pattern_);
	}
	
	public static function load_user($user_, $pattern_=null) {
		return self::load(self::$TYPE_USER, array($user_), $pattern_);
	}
	
	private static function delete_users($users_ = null, $pattern_ = null) {
		return self::delete(self::$TYPE_USER, $users_, $pattern_);
	}
	
	public static function delete_all_users($pattern_ = null) {
		return self::delete_users(null, $pattern_);
	}
	
	public static function delete_user($user_, $pattern_) {
		return self::delete_users(array($user_), $pattern_);
	}
	
	public static function save_user_item($user_, $key_, $value_) {
		$sql = SQL::getInstance();
		
		// First: delete
		self::delete_user($user_, $key_);
		
		// Then save
		return self::save_owner_item(self::$TYPE_USER, $user_, $key_, $value_);
	}

	public static function load_by_user($pattern_=null) {
		return self::load_by_owner(self::$TYPE_USER, $pattern_);
	}
}
