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

class Abstract_Server {
	public static function init($prefs_) {
		Logger::debug('main', 'Starting Abstract_Server::init');

		$mysql_conf = $prefs_->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$ret = $SQL->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@2 varchar(255) NOT NULL,
		@3 varchar(255) NOT NULL,
		@4 int(8) NOT NULL,
		@5 int(8) NOT NULL,
		@6 varchar(255) NOT NULL,
		@7 varchar(255) NOT NULL,
		@8 varchar(255) NOT NULL,
		@9 int(5) NOT NULL,
		@10 int(8) NOT NULL,
		@11 varchar(255) NOT NULL,
		@12 int(8) NOT NULL,
		@13 int(8) NOT NULL,
		@14 int(16) NOT NULL,
		@15 int(16) NOT NULL,
		@16 int(10) NOT NULL,
		PRIMARY KEY  (@2)
		)', $mysql_conf['prefix'].'servers', 'fqdn', 'status', 'registered', 'locked', 'type', 'version', 'external_name', 'web_port', 'max_sessions', 'cpu_model', 'cpu_nb_cores', 'cpu_load', 'ram_total', 'ram_used', 'timestamp');

		if (! $ret) {
			Logger::error('main', 'Unable to create MySQL table \''.$mysql_conf['prefix'].'servers\'');
			return false;
		}

		Logger::debug('main', 'MySQL table \''.$mysql_conf['prefix'].'servers\' created');

		return true;
	}

	public static function load($fqdn_) {
		Logger::debug('main', 'Starting Abstract_Server::load for \''.$fqdn_.'\'');

		if (substr($fqdn_, -1) == '.')
			$fqdn_ = substr($fqdn_, 0, (strlen($fqdn_)-1));

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$fqdn = $fqdn_;

		$SQL->DoQuery('SELECT @1,@2,@3,@4,@5,@6,@7,@8,@9,@10,@11,@12,@13 FROM @14 WHERE @15 = %16 LIMIT 1', 'status', 'registered', 'locked', 'type', 'version', 'external_name', 'web_port', 'max_sessions', 'cpu_model', 'cpu_nb_cores', 'cpu_load', 'ram_total', 'ram_used', $mysql_conf['prefix'].'servers', 'fqdn', $fqdn);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		foreach ($row as $k => $v)
			$$k = $v;

		$buf = new Server($fqdn);
		$buf->status = (string)$status;
		$buf->registered = (bool)$registered;
		$buf->locked = (bool)$locked;
		$buf->type = (string)$type;
		$buf->version = (string)$version;
		$buf->external_name = (string)$external_name;
		$buf->web_port = (int)$web_port;
		$buf->max_sessions = (int)$max_sessions;
		$buf->cpu_model = (string)$cpu_model;
		$buf->cpu_nb_cores = (int)$cpu_nb_cores;
		$buf->cpu_load = (float)($cpu_load/100);
		$buf->ram_total = (int)$ram_total;
		$buf->ram_used = (int)$ram_used;

		return $buf;
	}

	public static function save($server_) {
		Logger::debug('main', 'Starting Abstract_Server::save for \''.$server_->fqdn.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$fqdn = $server_->fqdn;

		if (! Abstract_Server::load($fqdn))
			if (! Abstract_Server::create($server_))
				return false;

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7,@8=%9,@10=%11,@12=%13,@14=%15,@16=%17,@18=%19,@20=%21,@22=%23,@24=%25,@26=%27,@28=%29 WHERE @30 = %31 LIMIT 1', $mysql_conf['prefix'].'servers', 'status', $server_->status, 'registered', (int)$server_->registered, 'locked', (int)$server_->locked, 'type', $server_->type, 'version', $server_->version, 'external_name', $server_->external_name, 'web_port', $server_->web_port, 'max_sessions', $server_->max_sessions, 'cpu_model', $server_->cpu_model,
		'cpu_nb_cores', $server_->cpu_nb_cores, 'cpu_load', (int)($server_->cpu_load*100), 'ram_total', $server_->ram_total, 'ram_used', $server_->ram_used, 'timestamp', time(), 'fqdn', $fqdn);

		return true;
	}

	private static function create($server_) {
		Logger::debug('main', 'Starting Abstract_Server::create for \''.$server_->fqdn.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$fqdn = $server_->fqdn;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'servers', 'fqdn', $fqdn);
		$total = $SQL->NumRows();

		if ($total != 0)
			return false;

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $mysql_conf['prefix'].'servers', 'fqdn', $fqdn);

		return true;
	}

	public static function modify($server_) {
		Logger::debug('main', 'Starting Abstract_Server::modify for \''.$server_->fqdn.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$fqdn = $server_->fqdn;

		if (! Abstract_Server::load($fqdn))
			return false;

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7,@8=%9,@10=%11,@12=%13,@14=%15,@16=%17,@18=%19,@20=%21,@22=%23,@24=%25,@26=%27,@28=%29 WHERE @30 = %31 LIMIT 1', $mysql_conf['prefix'].'servers', 'status', $server_->status, 'registered', (int)$server_->registered, 'locked', (int)$server_->locked, 'type', $server_->type, 'version', $server_->version, 'external_name', $server_->external_name, 'web_port', $server_->web_port, 'max_sessions', $server_->max_sessions, 'cpu_model', $server_->cpu_model,
		'cpu_nb_cores', $server_->cpu_nb_cores, 'cpu_load', (int)($server_->cpu_load*100), 'ram_total', $server_->ram_total, 'ram_used', $server_->ram_used, 'timestamp', time(), 'fqdn', $fqdn);

		return true;
	}

	public static function delete($fqdn_) {
		Logger::debug('main', 'Starting Abstract_Server::delete for \''.$fqdn_.'\'');

		if (substr($fqdn_, -1) == '.')
			$fqdn_ = substr($fqdn_, 0, (strlen($fqdn_)-1));

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$fqdn = $fqdn_;

		$SQL->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'servers', 'fqdn', $fqdn);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'servers', 'fqdn', $fqdn);

		$sessions_liaisons = Abstract_Liaison::load('ServerSession', $fqdn_, NULL);
		foreach ($sessions_liaisons as $sessions_liaison) {
			Abstract_Session::delete($sessions_liaison->group);
		}
		Abstract_Liaison::delete('ServerSession', $fqdn_, NULL);

		return true;
	}

	public static function load_all() {
		Logger::debug('main', 'Starting Abstract_Server::load_all');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$SQL->DoQuery('SELECT @1 FROM @2', 'fqdn', $mysql_conf['prefix'].'servers');
		$rows = $SQL->FetchAllResults();

		$servers = array();
		foreach ($rows as $row) {
			$fqdn = $row['fqdn'];

			$server = Abstract_Server::load($fqdn);
			if (! $server)
				continue;

			$servers[] = $server;
		}

		return $servers;
	}

	public static function uptodate($server_) {
		Logger::debug('main', 'Starting Abstract_Server::uptodate for \''.$server_->fqdn.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4 LIMIT 1', 'timestamp', $mysql_conf['prefix'].'servers', 'fqdn', $server_->fqdn);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$row = $SQL->FetchResult();

		if ((int)$row['timestamp'] < time()-60)
			return false;

		return true;
	}
}
