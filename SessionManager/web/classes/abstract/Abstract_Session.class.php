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

class Abstract_Session {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Session::init');

		$sql_conf = $prefs_->get('general', 'sql');
		$SQL = SQL::newInstance($sql_conf);

		$sessions_table_structure = array(
			'id'				=>	'varchar(255)',
			'server'			=>	'varchar(255)',
			'mode'				=>	'varchar(32)',
			'type'				=>	'varchar(32)',
			'status'			=>	'varchar(32)',
			'settings'			=>	'text',
			'user_login'		=>	'varchar(255)',
			'user_displayname'	=>	'varchar(255)',
			'servers'			=>	'text',
			'applications'		=>	'text',
			'start_time'		=>	'varchar(255)',
			'timestamp'			=>	'int(10)'
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

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3 LIMIT 1', $SQL->prefix.'sessions', 'id', $id_);
		$total = $SQL->NumRows();

		if ($total == 0) {
			Logger::error('main', "Abstract_Session::load($id_) failed: NumRows == 0");
			return false;
		}

		$row = $SQL->FetchResult();

		$buf = self::generateFromRow($row);

		return $buf;
	}

	public static function save($session_) {
		Logger::debug('main', 'Starting Abstract_Session::save for \''.$session_->id.'\'');
		
		$SQL = SQL::getInstance();

		$id = $session_->id;

		if (! Abstract_Session::exists($id)) {
			Logger::debug('main', "Abstract_Session::save($session_) session does NOT exist, we must create it");

			if (! Abstract_Session::create($session_)) {
				Logger::error('main', "Abstract_Session::save($session_) failed to create session");
				return false;
			}
		}

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7,@8=%9,@10=%11,@12=%13,@14=%15,@16=%17,@18=%19,@20=%21,@22=%23 WHERE @24 = %25 LIMIT 1', $SQL->prefix.'sessions', 'server', $session_->server, 'mode', $session_->mode, 'type', $session_->type, 'status', $session_->status, 'settings', serialize($session_->settings), 'user_login', $session_->user_login, 'user_displayname', $session_->user_displayname, 'servers', serialize($session_->servers), 'applications', serialize($session_->applications), 'start_time', $session_->start_time, 'timestamp', time(), 'id', $id);

		return true;
	}

	private static function create($session_) {
		Logger::debug('main', 'Starting Abstract_Session::create for \''.$session_->id.'\'');

		if (Abstract_Session::exists($session_->id)) {
			Logger::error('main', 'Abstract_Session::create(\''.$session_->id.'\') session already exists');
			return false;
		}

		$SQL = SQL::getInstance();
		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $SQL->prefix.'sessions', 'id', $session_->id);

		foreach ($session_->servers[Server::SERVER_ROLE_APS] as $fqdn => $data)
			Abstract_Liaison::save('ServerSession', $fqdn, $session_->id);

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

		Abstract_Liaison::delete('ServerSession', NULL, $id_);

		$tokens = Abstract_Token::load_by_session($id_);
		foreach ($tokens as $token)
			Abstract_Token::delete($token->id);

		return true;
	}

	private static function generateFromRow($row_) {
		foreach ($row_ as $k => $v)
			$$k = $v;

		$buf = new Session((string)$id);
		$buf->server = (string)$server;
		$buf->mode = (string)$mode;
		$buf->type = (string)$type;
		$buf->status = (string)$status;
		$buf->settings = unserialize($settings);
		$buf->user_login = (string)$user_login;
		$buf->user_displayname = (string)$user_displayname;
		$buf->servers = unserialize($servers);
		$buf->applications = unserialize($applications);
		$buf->start_time = (string)$start_time;
		$buf->timestamp = (int)$timestamp;

		return $buf;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_Session::load_all');
		
		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1', $SQL->prefix.'sessions');
		$rows = $SQL->FetchAllResults();

		$sessions = array();
		foreach ($rows as $row) {
			$session = self::generateFromRow($row);
			if (! is_object($session))
				continue;

			$sessions[] = $session;
		}

		return $sessions;
	}

	public static function load_partial($offset_=NULL, $start_=NULL) {
		Logger::debug('main', 'Starting Abstract_Session::load_partial('.$offset_.', '.$start_.')');

		$SQL = SQL::getInstance();

		if (! is_null($offset_))
			$SQL->DoQuery('SELECT * FROM @1 ORDER BY @2 DESC LIMIT '.((! is_null($start_))?$start_.',':'').$offset_, $SQL->prefix.'sessions', 'timestamp');
		else
			$SQL->DoQuery('SELECT * FROM @1 ORDER BY @2 DESC', $SQL->prefix.'sessions', 'timestamp');
		$rows = $SQL->FetchAllResults();

		$sessions = array();
		foreach ($rows as $row) {
			$session = self::generateFromRow($row);
			if (! is_object($session))
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

		if ((int)$row['timestamp'] < time()-DEFAULT_CACHE_DURATION)
			return false;

		return true;
	}

	public static function countByStatus($status_=NULL) {
		Logger::debug('main', "Starting Abstract_Session::countByStatus($status_)");

		$SQL = SQL::getInstance();

		if (! is_null($status_))
			$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3', $SQL->prefix.'sessions', 'status', $status_);
		else
			$SQL->DoQuery('SELECT 1 FROM @1', $SQL->prefix.'sessions');

		return $SQL->NumRows();
	}

	public static function countByServer($fqdn_) {
		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3', $SQL->prefix.'sessions', 'server', $fqdn_);

		return $SQL->NumRows();
	}

	public static function getByServer($fqdn_, $offset_=NULL, $start_=NULL) {
		$SQL = SQL::getInstance();

		if (! is_null($offset_))
			$SQL->DoQuery('SELECT * FROM @1 WHERE @2 LIKE %3 ORDER BY @4 DESC LIMIT '.((! is_null($start_))?$start_.',':'').$offset_, $SQL->prefix.'sessions', 'servers', '%'.$fqdn_.'%', 'timestamp');
		else
			$SQL->DoQuery('SELECT * FROM @1 WHERE @2 LIKE %3 ORDER BY @4 DESC', $SQL->prefix.'sessions', 'servers', '%'.$fqdn_.'%', 'timestamp');
		$rows = $SQL->FetchAllResults();

		$sessions = array();
		foreach ($rows as $row) {
			$session = self::generateFromRow($row);
			if (! is_object($session))
				continue;

			$sessions[] = $session;
		}

		return $sessions;
	}

	public static function getByUser($user_login_) {
		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 = %3', $SQL->prefix.'sessions', 'user_login', $user_login_);
		$rows = $SQL->FetchAllResults();

		$sessions = array();
		foreach ($rows as $row) {
			$session = self::generateFromRow($row);
			if (! is_object($session))
				continue;

			$sessions[] = $session;
		}

		return $sessions;
	}

	public static function getByFSUser($fs_user_login_) {
		$SQL = SQL::getInstance();

		$SQL->DoQuery('SELECT * FROM @1 WHERE @2 LIKE %3', $SQL->prefix.'sessions', 'settings', '%fs_access_login%'.$fs_user_login_.'%');
		$rows = $SQL->FetchAllResults();

		$sessions = array();
		foreach ($rows as $row) {
			$session = self::generateFromRow($row);
			if (! is_object($session))
				continue;

			$sessions[] = $session;
		}

		return $sessions;
	}
}
