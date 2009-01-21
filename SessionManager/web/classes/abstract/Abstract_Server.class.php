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

class Abstract_Server extends Abstract_DB {
	public function load($fqdn_) {
// 		Logger::debug('main', 'Starting Abstract_Server::load for \''.$fqdn_.'\'');

		$fqdn = $fqdn_;
		$folder = SERVERS_DIR.'/'.$fqdn;

		if (! is_readable($folder))
			return false;

		$status = @file_get_contents($folder.'/status');
		$registered = @file_get_contents($folder.'/registered');
		$locked = @file_get_contents($folder.'/locked');

		$type = @file_get_contents($folder.'/type');
		$version = @file_get_contents($folder.'/version');
		$external_name = @file_get_contents($folder.'/external_name');
		$web_port = @file_get_contents($folder.'/web_port');
		$max_sessions = @file_get_contents($folder.'/max_sessions');
		$cpu_model = @file_get_contents($folder.'/cpu_model');
		$cpu_nb_cores = @file_get_contents($folder.'/cpu_nb_cores');
		$cpu_load = @file_get_contents($folder.'/cpu_load');
		$ram_total = @file_get_contents($folder.'/ram_total');
		$ram_used = @file_get_contents($folder.'/ram_used');

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

		if (! file_exists($folder))
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

		@mkdir($folder, 0750);

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
			@unlink($remove_file);
		unset($remove_files);

		if (! @rmdir($folder))
			return false;

		return true;
	}
}
