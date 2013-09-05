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


class Abstract_AdminAction {
	const table = 'actions';
	
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_AdminAction::init');
		
		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);
		
		$tokens_table_structure = array(
			'when'			=>	'timestamp NOT NULL default CURRENT_TIMESTAMP',
			'what'			=>	'varchar(255) NOT NULL',
			'who'			=>	'varchar(255) NOT NULL',
			'where'			=>	'varchar(255) NOT NULL',
			'infos'			=>	'text'
		);
		
		$ret = $SQL->buildTable(self::table, $tokens_table_structure, array(), array(), 'ARCHIVE');
		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.self::table.'\'');
			return false;
		}
		
		Logger::debug('main', 'MySQL table \''.self::table.'\' created');
		return true;
	}
	
	public static function save($action_) {
		$SQL = SQL::getInstance();
		$SQL->DoQuery('INSERT INTO #1 (@2,@3,@4,@5) VALUES (%6,%7,%8,%9)', self::table,
			'who', 'what', 'where', 'infos',
			$action_->who, $action_->what, $action_->where, self::export_infos($action_->infos)
		);
		
		return true;
	}
	
	public static function load_limited($number_, $offset_=0) {
		Logger::debug('main', 'Starting Abstract_AdminAction::load_limited');
		
		$SQL = SQL::getInstance();
		$SQL->DoQuery('SELECT * FROM #1 ORDER BY @2 DESC LIMIT '.intval($number_).' OFFSET '.intval($offset_), self::table, 'when');
		$rows = $SQL->FetchAllResults();
		
		$actions = array();
		foreach ($rows as $row) {
			$action = self::generateFromRow($row);
			if (! is_object($action))
				continue;
			
			$actions[] = $action;
		}
		
		return $actions;
	}
	
	private static function generateFromRow($row_) {
		$a = new AdminAction((string)$row_['when'], $row_['who'], $row_['what'], $row_['where']);
		$a->infos = self::import_infos($row_['infos']);
		return $a;
	}
	
	private static function import_infos($infos_) {
		$r = json_unserialize($infos_);
		if ($r == 0) {
			return array();
		}
		
		return $r;
	}
	
	private static function export_infos($infos_) {
		return json_serialize($infos_);
	}
}
