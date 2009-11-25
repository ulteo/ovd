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
require_once(INSTALL_PATH.'/plugins/auth.serial/class.serialAuthDriver.php');

class ulteoAuthDriver extends serialAuthDriver  {
	public function autoCreateUser() {
		return true;
	}

	public function usersEditable() {
		return false;
	}

	public function passwordsEditable() {
		return false;
	}

	public function preLogUser($sessionId) {
		if (! isset($_SESSION['parameters']['user_login']))
			return;

		$user_login = $_SESSION['parameters']['user_login'];

		if (! isset($user_login))
			return;

		if (! $this->userExists($user_login)) {
			if (! $this->autoCreateUser())
				return;

			$this->createUser($user_login, '');
		}

		
		AuthService::logUser($user_login, '', true);
	}
}
