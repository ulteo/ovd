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

class Abstract_Session {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Session::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf['host'], $sql_conf['user'], $sql_conf['password'], $sql_conf['database'], $sql_conf['prefix']);

		$sessions_table_structure = array(
			'id'				=>	'varchar(255) NOT NULL',
			'server'			=>	'varchar(255) NOT NULL',
			'mode'				=>	'varchar(32) NOT NULL',
			'type'				=>	'varchar(32) NOT NULL',
			'status'			=>	'int(8) NOT NULL',
			'settings'			=>	'text NOT NULL',
			'user_login'		=>	'varchar(255) NOT NULL',
			'user_displayname'	=>	'varchar(255) NOT NULL',
			'applications'		=>	'text NOT NULL',
			'start_time'		=>	'varchar(255) NOT NULL',
			'timestamp'			=>	'int(10) NOT NULL'
		);

		$ret = $SQL->buildTable($sql_conf['prefix'].'sessions', $sessions_table_structure, array('id'));

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$sql_conf['prefix'].'sessions\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$sql_conf['prefix'].'sessions\' created');
		return true;
	}

	public static function exists($id_) {
		Logger::debug('main', 'Starting Abstract_Session::exists for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sessions', 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		return true;
	}

	public static function load($id_) {
		Logger::debug('main', 'Starting Abstract_Session::load for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT @1,@2,@3,@4,@5,@6,@7,@8,@9 FROM @10 WHERE @11 = %12 LIMIT 1', 'server', 'mode', 'type', 'status', 'settings', 'user_login', 'user_displayname', 'applications', 'start_time', $SQL->prefix.'sessions', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Session::load($id_) session does not exist (NumRows == 0)");
			return false;
		}

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;

		$buf = new Session($id);
		$buf->server = (string)$server;
		$buf->mode = (string)$mode;
		$buf->type = (string)$type;
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
		
		$SQL = SQL::getInstance();

		$id = $session_->id;

		if (! Abstract_Session::exists($id)) {
			Logger::info('main', "Abstract_Session::save($session_) session does NOT exist, we must create it");

			if (! Abstract_Session::create($session_)) {
				Logger::error('main', "Abstract_Session::save($session_) failed to create session");
				return false;
			}
		}

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7,@8=%9,@10=%11,@12=%13,@14=%15,@16=%17,@18=%19,@20=%21 WHERE @22 = %23 LIMIT 1', $SQL->prefix.'sessions', 'server', $session_->server, 'mode', $session_->mode, 'type', $session_->type, 'status', $session_->status, 'settings', serialize($session_->settings), 'user_login', $session_->user_login, 'user_displayname', $session_->user_displayname, 'applications', serialize($session_->applications), 'start_time', $session_->start_time, 'timestamp', time(), 'id', $id);

		return true;
	}

	private static function create($session_) {
		Logger::debug('main', 'Starting Abstract_Session::create for \''.$session_->id.'\'');

		$SQL = SQL::getInstance();

		$id = $session_->id;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sessions', 'id', $id);
		$total = $SQL->NumRows();

		if ($total != 0) {
			Logger::error('main', "Abstract_Session::create($session_) session already exists (NumRows == $total)");
			return false;
		}

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $SQL->prefix.'sessions', 'id', $id);

		Abstract_Liaison::save('ServerSession', $session_->server, $session_->id);

		if (isset($session_->settings['windows_server']))
			Abstract_Liaison::save('ServerSession', $session_->settings['windows_server'], $session_->id);

		return true;
	}

	public static function delete($id_) {
		Logger::debug('main', 'Starting Abstract_Session::delete for \''.$id_.'\'');

		$SQL = SQL::getInstance();

		$id = $id_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sessions', 'id', $id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Session::delete($id_) session does not exist (NumRows == 0)");
			return false;
		}

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sessions', 'id', $id);

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
		
		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT @1 FROM @2', 'id', $SQL->prefix.'sessions');
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
		
		$SQL = SQL::getInstance();
		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4 LIMIT 1', 'timestamp', $SQL->prefix.'sessions', 'id', $session_->id);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Session::uptodate($session_) session does not exist (NumRows == 0)");
			return false;
		}

		$row = $SQL->FetchResult();

		if ((int)$row['timestamp'] < time()-60)
			return false;

		return true;
	}
}
