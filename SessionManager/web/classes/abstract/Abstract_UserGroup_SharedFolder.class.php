<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

class Abstract_UserGroup_SharedFolder {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_SharedFolder::init');

		$mysql_conf = $prefs_->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database'], $mysql_conf['prefix']);

		$usergroup_sharedfolders_table_structure = array(
			'id'			=>	'int(8) NOT NULL auto_increment',
			'name'			=>	'varchar(255) NOT NULL',
			'url'			=>	'varchar(255) NOT NULL',
			'usergroup_id'	=>	'varchar(255) NOT NULL'
		);

		$ret = $SQL->buildTable($mysql_conf['prefix'].'usergroup_sharedfolders', $usergroup_sharedfolders_table_structure, array('id'));

		//begin PHP-PEAR HTTP_WebDAV_Server
		$usergroup_sharedfolders_locks_table_structure = array(
			'token'			=>	'varchar(255) NOT NULL',
			'path'			=>	'varchar(200) NOT NULL',
			'expires'		=>	'int(11) NOT NULL default "0"',
			'owner'			=>	'varchar(200) default NULL',
			'recursive'		=>	'int(11) default "0"',
			'writelock'		=>	'int(11) default "0"',
			'exclusivelock'	=>	'int(11) NOT NULL default "0"'
		);

		$ret = $SQL->buildTable($mysql_conf['prefix'].'dav_locks', $usergroup_sharedfolders_locks_table_structure, array('token'));

		$usergroup_sharedfolders_properties_table_structure = array(
			'path'	=>	'varchar(255) NOT NULL',
			'name'	=>	'varchar(120) NOT NULL',
			'ns'	=>	'varchar(120) NOT NULL default "DAV:"',
			'value'	=>	'text'
		);

		$ret = $SQL->buildTable($mysql_conf['prefix'].'dav_properties', $usergroup_sharedfolders_properties_table_structure, array('path'));
		//end PHP-PEAR HTTP_WebDAV_Server

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'usergroup_sharedfolders\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'usergroup_sharedfolders\' created');
		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_SharedFolder::load for \''.$id_.'\'');

		$SQL = MySQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT @1,@2,@3 FROM @4 WHERE @5 = %6 LIMIT 1', 'name', 'url', 'usergroup_id', $SQL->prefix.'usergroup_sharedfolders', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;

		$buf = new UserGroup_SharedFolder($id);
		$buf->name = (string)$name;
		$buf->url = (string)$url;
		$buf->usergroup_id = (string)$usergroup_id;

		return $buf;
	}

	public static function save($usergroup_sharedfolder_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_SharedFolder::save for \''.$usergroup_sharedfolder_->id.'\'');

		$SQL = MySQL::getInstance();

		$sharedfolder_id = Abstract_UserGroup_SharedFolder::exists($usergroup_sharedfolder_->name, $usergroup_sharedfolder_->url, $usergroup_sharedfolder_->usergroup_id);
		if (! $sharedfolder_id) {
			$buf = Abstract_UserGroup_SharedFolder::create($usergroup_sharedfolder_);

			if ($buf === false) {
				Logger::error('main', 'Abstract_UserGroup_SharedFolder::save failed to create sharedfolder');
				return false;
			}

			$usergroup_sharedfolder_->id = $buf;
		} else {
			Logger::debug('main', 'Abstract_UserGroup_SharedFolder::save rule('.$usergroup_rule_->attribute.','.$usergroup_rule_->type.','.$usergroup_rule_->value.','.$usergroup_rule_->usergroup_id.') already exists');

			$usergroup_sharedfolder_->id = $sharedfolder_id;

			return true;
		}

		if (is_null($usergroup_sharedfolder_->id)) {
			Logger::error('main', 'Abstract_UserGroup_SharedFolder::save sharedfolder\'s id is null');
			return false;
		}

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7 WHERE @8 = %9 LIMIT 1', $SQL->prefix.'usergroup_sharedfolders', 'name', $usergroup_sharedfolder_->name, 'url', $usergroup_sharedfolder_->url, 'usergroup_id', $usergroup_sharedfolder_->usergroup_id, 'id', $usergroup_sharedfolder_->id);

		return true;
	}

	private static function create($usergroup_sharedfolder_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_SharedFolder::create for \''.$usergroup_sharedfolder_->id.'\'');

		$SQL = MySQL::getInstance();

		$id = $usergroup_sharedfolder_->id;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'usergroup_sharedfolders', 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0) {
			Logger::error('main', 'Abstract_UserGroup_SharedFolder::create sharedfolder id \''.$id.'\' already exists');
			return false;
		}

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $SQL->prefix.'usergroup_sharedfolders', 'id', '');

		$usergroup_sharedfolder_->id = $SQL->InsertId();

		@mkdir(SHAREDFOLDERS_DIR.'/'.$usergroup_sharedfolder_->usergroup_id.'/'.$usergroup_sharedfolder_->id, 0770, true);

		return $usergroup_sharedfolder_->id;
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_SharedFolder::delete for \''.$id_.'\'');

		$SQL = MySQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'usergroup_sharedfolders', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'usergroup_sharedfolders', 'id', $id);

		//rmdir SHAREDFOLDERS_DIR.'/'.$buf->usergroup_id.'/'.$buf->id

		return true;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_UserGroup_SharedFolder::load_all');

		$SQL = MySQL::getInstance();

		$SQL->DoQuery('SELECT @1 FROM @2', 'id', $SQL->prefix.'usergroup_sharedfolders');
		$rows = $SQL->FetchAllResults();

		$usergroup_sharedfolders = array();
		foreach ($rows as $row) {
			$id = $row['id'];

			$usergroup_sharedfolder = Abstract_UserGroup_SharedFolder::load($id);
			if (! $usergroup_sharedfolder)
				continue;

			$usergroup_sharedfolders[] = $usergroup_sharedfolder;
		}

		return $usergroup_sharedfolders;
	}

	public static function exists($name_, $url_, $usergroup_id_) {
		Logger::debug('main', 'Starting Abstract_UserGroup_SharedFolder::exists with name \''.$name_.'\' url \''.$url_.'\' usergroup_id \''.$usergroup_id_.'\'');

		$SQL = MySQL::getInstance();

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4 AND @5 = %6 AND @7 = %8 LIMIT 1', 'id', $SQL->prefix.'usergroup_sharedfolders', 'name', $name_, 'url', $url_, 'usergroup_id', $usergroup_id_);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();
		return $row['id'];
	}
}
