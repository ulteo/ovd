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
	public function __construct() {
	}

	public function getAll() {
		$all_servers = glob(SESSIONS_DIR.'/*', GLOB_ONLYDIR);

		$buf = array();
		foreach ($all_servers as $all_server) {
			$server = new Server(basename($all_server));

			if ($server->hasAttribute('unregistered'))
				continue;

			$buf[] = $server;
		}

		return $buf;
	}

	public function getAvailable() {
		$available_servers = glob(SESSIONS_DIR.'/*', GLOB_ONLYDIR);

		$buf = array();
		foreach ($available_servers as $available_server) {
			$server = new Server(basename($available_server));
			$server->getStatus();

			if ($server->hasAttribute('unregistered'))
				continue;

			if ($server->hasAttribute('locked'))
				continue;

			if (!$server->isOnline())
				continue;

			$buf[] = $server;
		}

		return $buf;
	}

	public static function getOnline() {
		$servers = Servers::getAll();

		foreach ($servers as $k => $server) {
			if (! $server->isOnline())
				unset($servers[$k]);
		}

		return $servers;
	}

	public function getUnregistered() {
		$unregistered_servers = glob(SESSIONS_DIR.'/*/unregistered');

		$buf = array();
		foreach ($unregistered_servers as $unregistered_server) {
			$server = new Server(basename(dirname($unregistered_server)));

			$buf[] = $server;
		}

		return $buf;
	}
	
	public function getAvailableType($type) {
		$all = Servers::getAvailable();
		$all2 = array();
		foreach ($all as $s) {
			if ($s->getAttribute('type') ===  $type)
				$all2[] = $s;
		}
		return $all2;
	}
}
