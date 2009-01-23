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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class Servers {
	public function getAll() {
// 		Logger::debug('main', 'Starting Servers::getAll');

		//FIX ME ?
		$all_servers = glob(SERVERS_DIR.'/*', GLOB_ONLYDIR);

		$buf = array();
		foreach ($all_servers as $all_server) {
			$fqdn = basename($all_server);

			$server = Abstract_Server::load($fqdn);

			$buf[] = $server;
		}

		return $buf;
	}

	public function getAvailable() {
// 		Logger::debug('main', 'Starting Servers::getAvailable');

		$servers = Servers::getAll();

		foreach ($servers as $k => $server) {
			if (! $server->getAttribute('registered'))
				unset($servers[$k]);

			if ($server->getAttribute('locked'))
				unset($servers[$k]);

			$server->getStatus();
			if (! $server->isOnline())
				unset($servers[$k]);
		}

		return $servers;
	}

	public static function getOnline() {
// 		Logger::debug('main', 'Starting Servers::getOnline');

		$servers = Servers::getAll();

		foreach ($servers as $k => $server) {
			$server->getStatus();
			if (! $server->isOnline())
				unset($servers[$k]);
		}

		return $servers;
	}

	public static function getRegistered() {
// 		Logger::debug('main', 'Starting Servers::getRegistered');

		$servers = Servers::getAll();

		foreach ($servers as $k => $server) {
			if (! $server->getAttribute('registered'))
				unset($servers[$k]);
		}

		return $servers;
	}

	public static function getUnregistered() {
// 		Logger::debug('main', 'Starting Servers::getUnregistered');

		$servers = Servers::getAll();

		foreach ($servers as $k => $server) {
			if ($server->getAttribute('registered'))
				unset($servers[$k]);
		}

		return $servers;
	}

	public function getAvailableType($type_) {
// 		Logger::debug('main', 'Starting Servers::getAvailableType');

		$servers = Servers::getAvailable();

		foreach ($servers as $k => $server) {
			if ($server->getAttribute('type') != $type_)
				unset($servers[$k]);
		}

		return $servers;
	}
}
