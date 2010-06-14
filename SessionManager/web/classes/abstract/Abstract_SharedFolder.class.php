<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
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

class Abstract_SharedFolder {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$sharedfolders_table_structure = array(
			'id'			=>	'int(8) NOT NULL auto_increment',
			'name'			=>	'varchar(255) NOT NULL'
		);

		$ret = $SQL->buildTable($sql_conf['prefix'].'sharedfolders', $sharedfolders_table_structure, array('id'));

		$sharedfolders_acl_table_structure = array(
			'sharedfolder_id'	=>	'int(8) NOT NULL',
			'usergroup_id'		=>	'varchar(255) NOT NULL',
		);

		$ret = $SQL->buildTable($sql_conf['prefix'].'sharedfolders_acl', $sharedfolders_acl_table_structure, array('sharedfolder_id', 'usergroup_id'));

		//begin PHP-PEAR HTTP_WebDAV_Server
		$sharedfolders_locks_table_structure = array(
			'token'			=>	'varchar(255) NOT NULL',
			'path'			=>	'varchar(200) NOT NULL',
			'expires'		=>	'int(11) NOT NULL default "0"',
			'owner'			=>	'varchar(200) default NULL',
			'recursive'		=>	'int(11) default "0"',
			'writelock'		=>	'int(11) default "0"',
			'exclusivelock'	=>	'int(11) NOT NULL default "0"'
		);

		$ret = $SQL->buildTable($sql_conf['prefix'].'dav_locks', $sharedfolders_locks_table_structure, array('token'));

		$sharedfolders_properties_table_structure = array(
			'path'	=>	'varchar(255) NOT NULL',
			'name'	=>	'varchar(120) NOT NULL',
			'ns'	=>	'varchar(120) NOT NULL default "DAV:"',
			'value'	=>	'text'
		);

		$ret = $SQL->buildTable($sql_conf['prefix'].'dav_properties', $sharedfolders_properties_table_structure, array('path'));
		//end PHP-PEAR HTTP_WebDAV_Server

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$sql_conf['prefix'].'sharedfolders\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$sql_conf['prefix'].'sharedfolders\' created');
		return true;
	}

	public static function exists($name_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::exists with name \''.$name_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4 LIMIT 1', 'id', $SQL->prefix.'sharedfolders', 'name', $name_);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();
		return $row['id'];
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::load for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sharedfolders', 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_SharedFolder::load($id_) failed: NumRows == 0");
			return false;
		}

		$row = $SQL->FetchResult();

		$buf = self::generateFromRow($row);

		return $buf;
	}

	public static function save($sharedfolder_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::save for \''.$sharedfolder_->id.'\'');

		$SQL = SQL::getInstance();

		$sharedfolder_id = Abstract_SharedFolder::exists($sharedfolder_->name);
		if (! $sharedfolder_id) {
			$buf = Abstract_SharedFolder::create($sharedfolder_);

			if ($buf === false) {
				Logger::error('main', 'Abstract_SharedFolder::save failed to create sharedfolder');
				return false;
			}

			$sharedfolder_->id = $buf;
		} else {
			Logger::debug('main', 'Abstract_SharedFolder::save sharedfolder already exists');

			$sharedfolder_->id = $sharedfolder_id;

			return true;
		}

		if (is_null($sharedfolder_->id)) {
			Logger::error('main', 'Abstract_SharedFolder::save sharedfolder\'s id is null');
			return false;
		}

		$SQL->DoQuery('UPDATE @1 SET @2=%3 WHERE @4 = %5 LIMIT 1', $SQL->prefix.'sharedfolders', 'name', $sharedfolder_->name, 'id', $sharedfolder_->id);

		return true;
	}

	public static function modify($sharedfolder_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::modify for \''.$sharedfolder_->id.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('UPDATE @1 SET @2=%3 WHERE @4 = %5 LIMIT 1', $SQL->prefix.'sharedfolders', 'name', $sharedfolder_->name, 'id', $sharedfolder_->id);

		return true;
	}

	private static function create($sharedfolder_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::create for \''.$sharedfolder_->name.'\'');

		$SQL = SQL::getInstance();

		$id = $sharedfolder_->id;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sharedfolders', 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0) {
			Logger::error('main', 'Abstract_SharedFolder::create sharedfolder id \''.$id.'\' already exists');
			return false;
		}

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $SQL->prefix.'sharedfolders', 'id', '');

		$sharedfolder_->id = $SQL->InsertId();

		@mkdir(SHAREDFOLDERS_DIR.'/'.$sharedfolder_->id, 0770, true);

		return $sharedfolder_->id;
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::delete for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sharedfolders', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_SharedFolder::delete($id_) ShareFolder does not exist (NumRows == 0)");
			return false;
		}

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sharedfolders', 'id', $id);
		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3', $SQL->prefix.'sharedfolders_acl', 'sharedfolder_id', $id);

		@rmdirr(SHAREDFOLDERS_DIR.'/'.$id);

		return true;
	}

	private static function generateFromRow($row_) {
		foreach ($row_ as $k => $v)
			$$k = $v;

		$buf = new SharedFolder((int)$id);
		$buf->name = (string)$name;

		$SQL = MySQL::getInstance();

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4', 'usergroup_id', $SQL->prefix.'sharedfolders_acl', 'sharedfolder_id', $buf->id);
		$rows = $SQL->FetchAllResults();

		foreach ($rows as $row)
			$buf->acls[$row['usergroup_id']] = 'rw';

		return $buf;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_SharedFolder::load_all');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1', $SQL->prefix.'sharedfolders');
		$rows = $SQL->FetchAllResults();

		$sharedfolders = array();
		foreach ($rows as $row) {
			$sharedfolder = self::generateFromRow($row);
			if (! is_object($sharedfolder))
				continue;

			$sharedfolders[$sharedfolder->id] = $sharedfolder;
		}

		return $sharedfolders;
	}

	public static function add_acl($sharedfolder, $usergroup_id_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::add_acl with sharedfolder \''.$sharedfolder->id.'\' and usergroup \''.$usergroup_id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('INSERT INTO @1 (@2,@3) VALUES(%4,%5)', $SQL->prefix.'sharedfolders_acl', 'sharedfolder_id', 'usergroup_id', $sharedfolder->id, $usergroup_id_);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		return true;
	}

	public static function del_acl($sharedfolder, $usergroup_id_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::del_acl with sharedfolder \''.$sharedfolder->id.'\' and usergroup \''.$usergroup_id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 AND @4 = %5 LIMIT 1', $SQL->prefix.'sharedfolders_acl', 'sharedfolder_id', $sharedfolder->id, 'usergroup_id', $usergroup_id_);
// 		$total = $SQL->NumRows();

// 		if ($total == 0)
// 			return false;

		return true;
	}

	public static function del_usergroup_acl($usergroup_id_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::del_usergroup_acl with usergroup \''.$usergroup_id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sharedfolders_acl', 'usergroup_id', $usergroup_id_);
// 		$total = $SQL->NumRows();

// 		if ($total == 0)
// 			return false;

		return true;
	}

	public static function load_by_usergroup_id($usergroup_id_) {
		Logger::debug('main', 'Starting Abstract_SharedFolder::load_by_usergroup_id for usergroup \''.$usergroup_id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT @1,@2 FROM @3,@4 WHERE @3.@5 = @4.@6 AND @4.@7 = %8', 'id', 'name', $SQL->prefix.'sharedfolders', $SQL->prefix.'sharedfolders_acl', 'id', 'sharedfolder_id', 'usergroup_id', $usergroup_id_);
		$rows = $SQL->FetchAllResults();

		$sharedfolders = array();
		foreach ($rows as $row) {
			$buf = new SharedFolder($row['id']);
			$buf->name = (string)$row['name'];

			$SQL->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4', 'usergroup_id', $SQL->prefix.'sharedfolders_acl', 'sharedfolder_id', $row['id']);
			$rows2 = $SQL->FetchAllResults();

			foreach ($rows2 as $row2)
				$buf->acls[$row2['usergroup_id']] = 'rw';

			$sharedfolders[$buf->id] = $buf;
		}

		return $sharedfolders;
	}
}
