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

class Lock {
	public $user_login = NULL;
	public $session = NULL;
	public $server = NULL;

	public function __construct($user_login_) {
		Logger::debug('main', 'Starting Lock::__construct for user '.$user_login_);

		$this->user_login = $user_login_;

		if (is_readable(LOCKS_DIR.'/'.$this->user_login)) {
			$buf = trim(@file_get_contents(LOCKS_DIR.'/'.$this->user_login));

			if ($buf != '') {
				$buf = explode(':', $buf);
				$this->session = $buf[0];
				$this->server = $buf[1];
			}
		}
	}

	public function add_lock($session_, $server_)  {
		Logger::debug('main', 'Starting Lock::add_lock for user '.$this->user_login.' with session '.$session_);

		if (file_exists(LOCKS_DIR.'/'.$this->user_login))
			Logger::warning('main', 'Lock already exists : '.LOCKS_DIR.'/'.$this->user_login);
		//else {
			if (! @file_put_contents(LOCKS_DIR.'/'.$this->user_login, $session_.':'.$server_))
				Logger::error('main', 'Lock NOT created : '.LOCKS_DIR.'/'.$this->user_login);
			else
				Logger::info('main', 'Lock created : '.LOCKS_DIR.'/'.$this->user_login);
		//}
	}

	public function remove_lock() {
		Logger::debug('main', 'Starting Lock::remove_lock for user '.$this->user_login);

		if (!file_exists(LOCKS_DIR.'/'.$this->user_login))
			Logger::warning('main', 'Lock does not exist : '.LOCKS_DIR.'/'.$this->user_login);
		else {
			@unlink(LOCKS_DIR.'/'.$this->user_login);

			if (!file_exists(LOCKS_DIR.'/'.$this->user_login))
				Logger::info('main', 'Lock removed : '.LOCKS_DIR.'/'.$this->user_login);
			else
				Logger::error('main', 'Lock NOT removed : '.LOCKS_DIR.'/'.$this->user_login);
		}
	}

	public function have_lock() {
		Logger::debug('main', 'Starting Lock::have_lock for user '.$this->user_login);

		if (!is_null($this->session)) {
			Logger::info('main', 'Lock existing');
			return true;
		}

		Logger::error('main', 'Lock NOT existing');
		return false;
	}
}
