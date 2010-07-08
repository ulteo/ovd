<?php
/**
 * Copyright (C) 2008-2009 Ulteo SAS
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
	public static $role_aps = 'aps';
	public static $role_fs = 'fs';

	public static function getAll() {
// 		Logger::debug('main', 'Starting Servers::getAll');

		$buf = Abstract_Server::load_all();

		if (! is_array($buf))
			return array();

		return $buf;
	}

	public static function getAvailable() {
// 		Logger::debug('main', 'Starting Servers::getAvailable');

		$servers = Servers::getAll();

		foreach ($servers as $k => $server) {
			if (! $server->getAttribute('registered'))
				unset($servers[$k]);

			if ($server->getAttribute('locked'))
				unset($servers[$k]);

			if (! $server->isOnline())
				unset($servers[$k]);
		}

		return $servers;
	}

	public static function getOnline() {
// 		Logger::debug('main', 'Starting Servers::getOnline');

		$servers = Servers::getAll();

		foreach ($servers as $k => $server) {
			if (! $server->getAttribute('registered'))
				unset($servers[$k]);

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

	public static function getOffline() {
// 		Logger::debug('main', 'Starting Servers::getOffline');

		$servers = Servers::getAll();

		foreach ($servers as $k => $server) {
			if ($server->getAttribute('status') != 'down')
				unset($servers[$k]);
		}

		return $servers;
	}

	public static function getBroken() {
// 		Logger::debug('main', 'Starting Servers::getBroken');

		$servers = Servers::getAll();

		foreach ($servers as $k => $server) {
			if ($server->getAttribute('status') != 'broken')
				unset($servers[$k]);
		}

		return $servers;
	}

	public static function getAvailableType($type_) {
// 		Logger::debug('main', 'Starting Servers::getAvailableType');

		$servers = Servers::getAvailable();

		foreach ($servers as $k => $server) {
			if ($server->getAttribute('type') != $type_)
				unset($servers[$k]);
		}

		return $servers;
	}

	public static function getAvailableByRole($role_) {
// 		Logger::debug('main', 'Starting Servers::getAvailableByRole');

		$servers = Servers::getAvailable();

		foreach ($servers as $k => $server) {
			if (! array_key_exists($role_, $server->roles))
				unset($servers[$k]);
		}

		return $servers;
	}
}
