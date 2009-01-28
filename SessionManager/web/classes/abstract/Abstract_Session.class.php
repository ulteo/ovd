<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
	public function init() {
// 		Logger::debug('main', 'Starting Abstract_Session::init');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$ret = $SQL->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@2 varchar(255) NOT NULL,
		@3 varchar(255) NOT NULL,
		@4 int(8) NOT NULL,
		@5 text NOT NULL,
		@6 varchar(255) NOT NULL,
		@7 varchar(255) NOT NULL,
		PRIMARY KEY  (@2)
		)', $mysql_conf['prefix'].'sessions', 'id', 'server', 'status', 'settings', 'user_login', 'user_displayname');

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'sessions\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'sessions\' created');
		return true;
	}

	public function load($id_) {
// 		Logger::debug('main', 'Starting Abstract_Session::load for \''.$id_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$id = $id_;

		$SQL->DoQuery('SELECT @1,@2,@3,@4,@5 FROM @6 WHERE @7 = %8 LIMIT 1', 'server', 'status', 'settings', 'user_login', 'user_displayname', $mysql_conf['prefix'].'sessions', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;
		unset($v);
		unset($k);
		unset($row);

		$buf = new Session($id);
		$buf->server = (string)$server;
		$buf->status = (int)$status;
		$buf->settings = unserialize($settings);
		$buf->user_login = (string)$user_login;
		$buf->user_displayname = (string)$user_displayname;

		return $buf;
	}

	public function save($session_) {
// 		Logger::debug('main', 'Starting Abstract_Session::save for \''.$session_->id.'\'');

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

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7,@8=%9,@10=%11 WHERE @12 = %13 LIMIT 1', $mysql_conf['prefix'].'sessions', 'server', $session_->server, 'status', $session_->status, 'settings', serialize($session_->settings), 'user_login', $session_->user_login, 'user_displayname', $session_->user_displayname, 'id', $id);

		return true;
	}

	private function create($session_) {
// 		Logger::debug('main', 'Starting Abstract_Session::create for \''.$session_->id.'\'');

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

		return true;
	}

	public function delete($id_) {
// 		Logger::debug('main', 'Starting Abstract_Session::delete for \''.$id_.'\'');

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

		return true;
	}

	public function load_all() {
// 		Logger::debug('main', 'Starting Abstract_Session::load_all');

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
		unset($row);
		unset($rows);

		return $sessions;
	}

	public function uptodate($session_) {
// 		Logger::debug('main', 'Starting Abstract_Session::uptodate for \''.$session_->id.'\'');

		return true;
	}
}
