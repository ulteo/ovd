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

		$l = new ServerSessionLiaison();
		$all_sessions = $l->all();
		$buf = array();
		foreach ($all_sessions as $liaison) {
			$session = Abstract_Session::load($liaison['group']);
			if ( $session !== false)
				$buf[] = $session;
		}

		return $buf;
	}

	public function getByServer($fqdn_) {
// 		Logger::debug('main', 'Starting Sessions::getByServer');
		$sessions = array();
		$l = new ServerSessionLiaison($fqdn_,NULL);
		$sessions_id = $l->groups();
		
		foreach ($sessions_id as $session_id) {
			$session = Abstract_Session::load($session_id);
			if ( $session !== false)
				$sessions []= $session;
			
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

		return $sessions;
	}
}
