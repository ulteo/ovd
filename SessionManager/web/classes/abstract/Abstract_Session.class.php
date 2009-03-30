<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

class Abstract_Session {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Session::init');

		$table_structure = array();
		$table_structure['id'] =  'varchar(255) NOT NULL';
		$table_structure['server'] =  'varchar(255) NOT NULL';
		$table_structure['status'] =  'int(8) NOT NULL';
		$table_structure['settings'] =  'text NOT NULL';
		$table_structure['user_login'] =  'varchar(255) NOT NULL';
		$table_structure['user_displayname'] =  'varchar(255) NOT NULL';
		$table_structure['applications'] =  'text NOT NULL';
		$table_structure['start_time'] =  'varchar(255) NOT NULL';
		$table_structure['timestamp'] =  'int(10) NOT NULL';
		$primary_key = array('id');
		
		$mysql_conf = $prefs_->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);
		$table = $mysql_conf['prefix'].'sessions';
		
		
		$ret = $SQL->buildTable($table, $table_structure, $primary_key);

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'sessions\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'sessions\' created');
		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_Session::load for \''.$id_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT @1,@2,@3,@4,@5,@6,@7 FROM @8 WHERE @9 = %10 LIMIT 1', 'server', 'status', 'settings', 'user_login', 'user_displayname', 'applications', 'start_time', $mysql_conf['prefix'].'sessions', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;

		$buf = new Session($id);
		$buf->server = (string)$server;
		$buf->status = (int)$status;
		$buf->settings = unserialize($settings);
		$buf->user_login = (string)$user_login;
		$buf->user_displayname = (string)$user_displayname;
		$buf->applications = unserialize($applications);
		$buf->start_time = (string)$start_time;

		return $buf;
	}

	public static function save($session_) {
		Logger::debug('main', 'Starting Abstract_Session::save for \''.$session_->id.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $session_->id;

		if (! Abstract_Session::load($id))
			if (! Abstract_Session::create($session_))
				return false;

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7,@8=%9,@10=%11,@12=%13,@14=%15,@16=%17 WHERE @18 = %19 LIMIT 1', $mysql_conf['prefix'].'sessions', 'server', $session_->server, 'status', $session_->status, 'settings', serialize($session_->settings), 'user_login', $session_->user_login, 'user_displayname', $session_->user_displayname, 'applications', serialize($session_->applications), 'start_time', $session_->start_time, 'timestamp', time(), 'id', $id);

		return true;
	}

	private static function create($session_) {
		Logger::debug('main', 'Starting Abstract_Session::create for \''.$session_->id.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $session_->id;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'sessions', 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0)
			return false;

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $mysql_conf['prefix'].'sessions', 'id', $id);

		Abstract_Liaison::save('ServerSession', $session_->server, $session_->id);

		return true;
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_Session::delete for \''.$id_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'sessions', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'sessions', 'id', $id);

		$invites_liaisons = Abstract_Liaison::load('SessionInvite', $id_, NULL);
		foreach ($invites_liaisons as $invites_liaison) {
			Abstract_Invite::delete($invites_liaison->group);
		}
		Abstract_Liaison::delete('SessionInvite', $id_, NULL);

		Abstract_Liaison::delete('ServerSession', NULL, $id_);

		return true;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_Session::load_all');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$SQL->DoQuery('SELECT @1 FROM @2', 'id', $mysql_conf['prefix'].'sessions');
		$rows = $SQL->FetchAllResults();

		$sessions = array();
		foreach ($rows as $row) {
			$id = $row['id'];

			$session = Abstract_Session::load($id);
			if (! $session)
				continue;

			$sessions[] = $session;
		}

		return $sessions;
	}

	public static function uptodate($session_) {
		Logger::debug('main', 'Starting Abstract_Session::uptodate for \''.$session_->id.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4 LIMIT 1', 'timestamp', $mysql_conf['prefix'].'sessions', 'id', $session_->id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		if ((int)$row['timestamp'] < time()-60)
			return false;

		return true;
	}
}
