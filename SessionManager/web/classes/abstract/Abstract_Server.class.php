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

class Abstract_Server {
	public function load($fqdn_) {
// 		Logger::debug('main', 'Starting Abstract_Server::load for \''.$fqdn_.'\'');

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
		unset($v);
		unset($k);
		unset($row);

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
		$buf->cpu_load = (float)$cpu_load;
		$buf->ram_total = (int)$ram_total;
		$buf->ram_used = (int)$ram_used;

		return $buf;
	}

	public function save($server_) {
// 		Logger::debug('main', 'Starting Abstract_Server::save for \''.$server_->fqdn.'\'');

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

		$SQL->DoQuery('UPDATE @1 SET @2=%3,@4=%5,@6=%7,@8=%9,@10=%11,@12=%13,@14=%15,@16=%17,@18=%19,@20=%21,@22=%23,@24=%25,@26=%27 WHERE @28 = %29 LIMIT 1', $mysql_conf['prefix'].'servers', 'status', (string)$server_->status, 'registered', (int)$server_->registered, 'locked', (int)$server_->locked, 'type', (string)$server_->type, 'version', (string)$server_->version, 'external_name', (string)$server_->external_name, 'web_port', (int)$server_->web_port, 'max_sessions', (int)$server_->max_sessions, 'cpu_model', (string)$server_->cpu_model,
		'cpu_nb_cores', (int)$server_->cpu_nb_cores, 'cpu_load', (string)$server_->cpu_load, 'ram_total', (int)$server_->ram_total, 'ram_used', (int)$server_->ram_used, 'fqdn', $fqdn);

		return true;
	}

	private function create($server_) {
// 		Logger::debug('main', 'Starting Abstract_Server::create for \''.$server_->fqdn.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$fqdn = $server_->fqdn;

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @1 = %3 LIMIT 1', 'fqdn', $mysql_conf['prefix'].'servers', $fqdn);
		$total = $SQL->NumRows();

		if ($total != 0)
			return false;

		$SQL->DoQuery('INSERT INTO @1 (@2) VALUES (%3)', $mysql_conf['prefix'].'servers', 'fqdn', $fqdn);

		return true;
	}

	public function delete($fqdn_) {
// 		Logger::debug('main', 'Starting Abstract_Server::delete for \''.$fqdn_.'\'');

		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::critical('get Preferences failed in '.__FILE__.' line '.__LINE__);
			return false;
		}

		$mysql_conf = $prefs->get('general', 'mysql');
		$SQL = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);

		$fqdn = $fqdn_;

		$SQL->DoQuery('SELECT @1 FROM @2 WHERE @1 = %3 LIMIT 1', 'fqdn', $mysql_conf['prefix'].'servers', $fqdn);
		$total = $SQL->NumRows();

		if ($total == 0)
			return false;

		$SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3 LIMIT 1', $mysql_conf['prefix'].'servers', 'fqdn', $fqdn);

		return true;
	}

	public function load_all() {
// 		Logger::debug('main', 'Starting Abstract_Server::load_all');

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
		unset($row);
		unset($rows);

		return $servers;
	}

	public function uptodate($server_) {
// 		Logger::debug('main', 'Starting Abstract_Server::uptodate for \''.$server_->fqdn.'\'');

		return true;
	}
}
