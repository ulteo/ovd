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

class Sessions {
	public function getAll() {
// 		Logger::debug('main', 'Starting Sessions::getAll');

		$buf = Abstract_Session::load_all();

		return $buf;
	}

	public function getByServer($fqdn_) {
// 		Logger::debug('main', 'Starting Sessions::getByServer');

		$sessions_liaisons = Abstract_Liaison::load('ServerSession', $fqdn_, NULL);

		$sessions = array();
		foreach ($sessions_liaisons as $sessions_liaison) {
			$session = Abstract_Session::load($sessions_liaison->group);
			if (! $session)
				continue;

			$sessions[] = $session;
		}

		return $sessions;
	}

	public function getByUser($user_login_) {
// 		Logger::debug('main', 'Starting Sessions::getByUser');

		$sessions = Sessions::getAll();

		foreach ($sessions as $k => $session) {
			if ($session->getAttribute('user_login') != $user_login_)
				unset($sessions[$k]);
		}
		unset($session);

		return $sessions;
	}
}
