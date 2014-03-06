<?php
/**
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2014
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

require_once(dirname(__FILE__).'/../../../includes/core.inc.php');

class SessionStatusChangedPendingSessionCreation extends EventCallback {
	public function run () {
		if ($this->ev->status != Session::SESSION_STATUS_DESTROYED)
			return true;
		
		$token = $this->ev->id;
		
		if (! Abstract_Session::exists($token)) {
			Logger::error('main', "SessionStatusChangedPendingSessionCreation:: Session '$token' does not exist");
			return false;
		}
		
		$session = Abstract_Session::load($token);
		return $this->checkPendingSession($session);
	}

	public function getDescription() {
		return "";
	}

	public function isInternal() {
		return true;
	}
	
	public function checkPendingSession($session_) {
		$sessions = Abstract_Session::getByUser($session_->user_login);
	       	foreach ($sessions as $i=> $session) {
			if ($session->id == $session_->id) {
				unset($sessions[$i]);
				continue;
			}
		}
		
		if (count($sessions) != 1)
			return true;

		$session = reset($sessions);
		if ($session->need_creation == 0)
			return true;
		
		// Start the creation
		try {
			$sessionManagement = SessionManagement::getInstance();
		}
		catch (Exception $err) {
			Logger::error('main', "SessionStatusChangedPendingSessionCreation:: Failed to get SessionManagement instance");
			return false;
		}
		
		if (! $sessionManagement->initialize()) {
			Logger::error('main', "SessionStatusChangedPendingSessionCreation:: SessionManagement initialization failed");
			return false;
		}
		
		$userDB = UserDB::getInstance();
		$user = $userDB->import($session->user_login);
		if (! is_object($user)) {
			Logger::error('main', 'SessionStatusChangedPendingSessionCreation:: Unable to import a valid user with login "'.$session->user_login.'"');
			return false;
		}
		
		$sessionManagement->user = $user;
	
		if (! $sessionManagement->prepareSession($session)) {
			Logger::error('main', "SessionStatusChangedPendingSessionCreation:: SessionManagement initialization failed");
			return false;
		}
	
		$session->need_creation = 0;
		Abstract_Session::save($session);
		
		return true;
	}
}

