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
	
	public static function init($prefs_) {
		Logger::debug('main', 'Abstract_Preferences::init');
		
		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);
		
		$preferences_table_structure = array(
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
	
	public static function load() {
		$ret = array();
		$sql = SQL::getInstance();
		$res = $sql->DoQuery('SELECT @1,@2 FROM #3', 'key', 'value', self::$table);
		
		if ($res !== true) {
			Logger::error('main', "Abstract_Preferences::load sql request failed");
		}
		else {
			$rows = $sql->FetchAllResults();
			foreach ($rows as $row) {
				$k = $row['key'];
				$v = json_unserialize($row['value']);
				
				$ret[$k] = $v;
			}
		}
		
		return $ret;
	}
	
	public static function save($data_) {
		$sql = SQL::getInstance();
		
		// First: delete all
		$sql->DoQuery('DELETE FROM #1', self::$table);
		
		// Then: push in one request
		$values = array();
		foreach($data_ as $k => $v) {
			array_push($values, '('.$sql->Quote($k).','.$sql->Quote(json_serialize($v)).')');
		}
		
		$res = $sql->DoQuery('INSERT INTO #1 (@2, @3) VALUES '.implode(',', $values), self::$table, 'key', 'value');
		if ($res !== true) {
			Logger::error('main', "Abstract_Preferences::save Unable to save preferences");
			return false;
		}
		
		return true;
	}
}
