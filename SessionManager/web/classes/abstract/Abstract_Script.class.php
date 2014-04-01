<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS  <julien@ulteo.com> 2013
 * Author Vincent ROULLIER <vincent.roullier@ulteo.com> 2013
 * Author David LECHEVALIER <david@ulteo.com> 2013
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

class Abstract_Script {
	public static $table = 'scripts';
	
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Script::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$script_table_structure = array(
			'id'   => 'int(8) NOT NULL auto_increment',
			'name' => 'varchar(64)',
			'type' => 'varchar(10)',
			'os'   => 'varchar(10)',
			'data' => 'text'
		);

		$ret = $SQL->buildTable(self::$table, $script_table_structure, array('id'));
		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$sql_conf['prefix'].self::$table.'\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$sql_conf['prefix'].self::$table.'\' created');
		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_Script::load for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM #1 WHERE @2 = %3 LIMIT 1', self::$table, 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Script::load($id_) failed: NumRows == 0");
			return false;
		}

		$row = $SQL->FetchResult();

		$buf = self::generateFromRow($row);

		return $buf;
	}

	public static function save($script_) {
		Logger::debug('main', 'Starting Abstract_Script::save for \''.$script_->id.'\'');

		$SQL = SQL::getInstance();

		$id = $script_->id;

		if (! Abstract_Script::load($id)) {
			Logger::debug('main', "Abstract_Script::save($script_) unable to load script, we must create it");

			$id = Abstract_Script::create($script_);
			if (! $id) {
				Logger::error('main', "Abstract_Script::save($script_) Abstract_Script::create failed");
				return false;
			}
		}

		if ($script_->name == '')
			$script_->name = "Untitled ".$id;

		$SQL->DoQuery('UPDATE #1 SET @2=%3,@4=%5,@6=%7,@8=%9 WHERE @10 = %11 LIMIT 1', self::$table, 'name', $script_->name, 'os', $script_->os, "type", $script_->type, 'data', $script_->data,'id', $id);

		return true;
	}

	private static function create($script_) {
		Logger::debug('main', 'Starting Abstract_Script::create for \''.$script_->id.'\'');

		$SQL = SQL::getInstance();

		$id = $script_->id;

		$SQL->DoQuery('SELECT 1 FROM #1 WHERE @2 = %3 LIMIT 1', self::$table, 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0) {
			Logger::error('main', "Abstract_Script::create($script_) script already exist (NumRows == $total)");
			return false;
		}

		$SQL->DoQuery('INSERT INTO #1 (@2) VALUES (%3)', self::$table, 'id', $id);

		return $SQL->InsertId();
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_Script::delete for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM #1 WHERE @2 = %3 LIMIT 1', self::$table, 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Script::delete($id_) script does not exist (NumRows == 0)");
			return false;
		}

		$SQL->DoQuery('DELETE FROM #1 WHERE @2 = %3 LIMIT 1', self::$table, 'id', $id);

		return true;
	}

	private static function generateFromRow($row_) {
		foreach ($row_ as $k => $v)
			$$k = $v;

		$buf = new Script((int)$id);
		$buf->name = (string)$name;
		$buf->data = (string)$data;
		$buf->type = (string)$type;
		$buf->os = (string)$os;

		return $buf;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_Script::load_all');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM #1 ORDER BY @2 DESC', self::$table, 'name');
		$rows = $SQL->FetchAllResults();

		$scripts = array();
		foreach ($rows as $row) {
			$script = self::generateFromRow($row);
			if (! is_object($script))
				continue;

			$scripts[] = $script;
		}

		return $scripts;
	}
}
