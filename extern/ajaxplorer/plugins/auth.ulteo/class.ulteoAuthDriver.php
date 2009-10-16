<?php
/*
Ulteo License
*/
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
