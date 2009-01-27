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

define('SERVERS_DIR', SESSIONMANAGER_SPOOL.'/servers');
if (! check_folder(SERVERS_DIR)) {
	Logger::critical('main', SERVERS_DIR.' does not exist and cannot be created !');
	die_error(SERVERS_DIR.' does not exist and cannot be created !', __FILE__, __LINE__);
}

class Abstract_Server extends Abstract_DB {
	public function load($fqdn_) {
// 		Logger::debug('main', 'Starting Abstract_Server::load for \''.$fqdn_.'\'');

		$fqdn = $fqdn_;
		$folder = SERVERS_DIR.'/'.$fqdn;

		if (! is_readable($folder))
			return false;

		$attributes = array('status', 'registered', 'locked', 'type', 'version', 'external_name', 'web_port', 'max_sessions', 'cpu_model', 'cpu_nb_cores', 'cpu_load', 'ram_total', 'ram_used');
		foreach ($attributes as $attribute)
			if (($$attribute = @file_get_contents($folder.'/'.$attribute)) === false)
				return false;
		unset($attribute);
		unset($attributes);

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

		$fqdn = $server_->fqdn;
		$folder = SERVERS_DIR.'/'.$fqdn;

		if (! Abstract_Server::load($fqdn))
			if (! Abstract_Server::create($server_))
				return false;

		if (! is_writeable($folder))
			return false;

		@file_put_contents($folder.'/status', (string)$server_->status);
		@file_put_contents($folder.'/registered', (int)$server_->registered);
		@file_put_contents($folder.'/locked', (int)$server_->locked);
		@file_put_contents($folder.'/type', (string)$server_->type);
		@file_put_contents($folder.'/version', (string)$server_->version);
		@file_put_contents($folder.'/external_name', (string)$server_->external_name);
		@file_put_contents($folder.'/web_port', (int)$server_->web_port);
		@file_put_contents($folder.'/max_sessions', (int)$server_->max_sessions);
		@file_put_contents($folder.'/cpu_model', (string)$server_->cpu_model);
		@file_put_contents($folder.'/cpu_nb_cores', (int)$server_->cpu_nb_cores);
		@file_put_contents($folder.'/cpu_load', (string)$server_->cpu_load);
		@file_put_contents($folder.'/ram_total', (int)$server_->ram_total);
		@file_put_contents($folder.'/ram_used', (int)$server_->ram_used);

		return true;
	}

	private function create($server_) {
// 		Logger::debug('main', 'Starting Abstract_Server::create for \''.$server_->fqdn.'\'');

		$fqdn = $server_->fqdn;
		$folder = SERVERS_DIR.'/'.$fqdn;

		if (! is_writeable(SERVERS_DIR))
			return false;

		if (! @mkdir($folder, 0750))
			return false;

		return true;
	}

	public function delete($fqdn_) {
// 		Logger::debug('main', 'Starting Abstract_Server::delete for \''.$fqdn_.'\'');

		$fqdn = $fqdn_;
		$folder = SERVERS_DIR.'/'.$fqdn;

		if (! file_exists($folder))
			return false;

		$remove_files = glob($folder.'/*');
		foreach ($remove_files as $remove_file)
			if (! @unlink($remove_file))
				return false;
		unset($remove_file);
		unset($remove_files);

		if (! @rmdir($folder))
			return false;

		return true;
	}

	public function load_all() {
// 		Logger::debug('main', 'Starting Abstract_Server::load_all');

		$all_servers = glob(SERVERS_DIR.'/*', GLOB_ONLYDIR);

		$servers = array();
		foreach ($all_servers as $all_server) {
			$fqdn = basename($all_server);

			$server = Abstract_Server::load($fqdn);
			if (! $server)
				continue;

			$servers[] = $server;
		}
		unset($all_server);
		unset($all_servers);

		return $servers;
	}

	public function uptodate($server_) {
// 		Logger::debug('main', 'Starting Abstract_Server::uptodate for \''.$server_->fqdn.'\'');

		$fqdn = $server_->fqdn;
		$folder = SERVERS_DIR.'/'.$fqdn;

		$buf = @filemtime($folder.'/status');

		if ($buf > (time()-30))
			return true;

		return false;
	}
}
