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

class Abstract_Session extends Abstract_DB {
	public function load($id_) {
// 		Logger::debug('main', 'Starting Abstract_Session::load for \''.$id_.'\'');

		$id = $id_;
		$folder = SESSIONS_DIR.'/'.$id;

		if (! is_readable($folder))
			return false;

		$attributes = array('server');
		foreach ($attributes as $attribute)
			if (($$attribute = @file_get_contents($folder.'/'.$attribute)) === false)
				return false;

		$buf = new Session($id);
		$buf->server = (string)$server;

		return $buf;
	}

	public function save($session_) {
// 		Logger::debug('main', 'Starting Abstract_Session::save for \''.$session_->id.'\'');

		$id = $session_->id;
		$folder = SESSIONS_DIR.'/'.$id;

		if (! file_exists($folder))
			if (! Abstract_Session::create($session_))
				return false;

		if (! is_writeable($folder))
			return false;

		@file_put_contents($folder.'/server', (string)$session_->server);

		return true;
	}

	private function create($session_) {
// 		Logger::debug('main', 'Starting Abstract_Session::create for \''.$session_->id.'\'');

		$id = $session_->id;
		$folder = SESSIONS_DIR.'/'.$id;

		if (! is_writeable(SESSIONS_DIR))
			return false;

		if (! @mkdir($folder, 0750))
			return false;

		return true;
/*
		$server = Abstract_Server::load($this->server);
		if ($server->getAttribute('locked')) {
			Logger::error('main', 'Server does not accept new sessions : '.$this->server);
			if ($die_ == true)
				die('Server does not accept new sessions : '.$this->server);
			return false;
		}
		if (!$server->hasAttribute('status') || $server->getAttribute('status') != 'ready') {
			Logger::error('main', 'Server does not accept new sessions : '.$this->server);
			if ($die_ == true)
				die('Server does not accept new sessions : '.$this->server);
			return false;
		}
		if ($server->getNbAvailableSessions() == 0) {
			Logger::error('main', 'Server does not accept new sessions : '.$this->server);
			if ($die_ == true)
				die('Server does not accept new sessions : '.$this->server);
			return false;
		}

		if (file_exists($this->folder)) {
			Logger::error('main', 'Session already exists : '.$this->folder);
			if ($die_ == true)
				die('Session already exists : '.$this->folder);
			else
				return false;
		}

		if (!file_exists(SESSIONS_DIR.'/'.$this->server)) {
			Logger::info('main', 'Server folder does not exist : '.SESSIONS_DIR.'/'.$this->server);

			if (! @mkdir(SESSIONS_DIR.'/'.$this->server, 0750)) {
				Logger::error('main', 'Server folder NOT created : '.SESSIONS_DIR.'/'.$this->server);
				if ($die_ == true)
					die('Server folder NOT created : '.SESSIONS_DIR.'/'.$this->server);
				else
					return false;
			}
			Logger::info('main', 'Server folder created : '.SESSIONS_DIR.'/'.$this->server);
		}

		if (! @mkdir($this->folder, 0750)) {
			Logger::error('main', 'Session folder NOT created : '.$this->folder);
			if ($die_ == true)
				die('Session folder NOT created : '.$this->folder);
			else
				return false;
		}
		Logger::info('main', 'Session folder created : '.$this->folder);

		if (! @touch($this->folder.'/available')){
			Logger::error('main', 'Session "available" file NOT created : '.$this->folder.'/available');
			if ($die_ == true)
				die('Session "available" file NOT created : '.$this->folder.'/available');
			else
				return false;
		}
		Logger::info('main', 'Session "available" file created : '.$this->folder.'/available');

		return true;
*/
	}

	public function delete($id_) {
// 		Logger::debug('main', 'Starting Abstract_Session::delete for \''.$id_.'\'');

		$id = $id_;
		$folder = SESSIONS_DIR.'/'.$id;

		if (! file_exists($folder))
			return false;

		$remove_files = glob($folder.'/*');
		foreach ($remove_files as $remove_file)
			@unlink($remove_file);
		unset($remove_files);

		if (! @rmdir($folder))
			return false;

		return true;
/*
		Logger::debug('main', 'Starting SESSION::remove_session for session '.$this->session.' on server '.$this->server);

		if (!file_exists($this->folder)) {
			Logger::error('main', 'Session does not exist : '.$this->folder);
			if ($die_ == true)
				die('Session does not exist : '.$this->folder);
			else
				return false;
		}

		$remove_files = glob($this->folder.'/*');
		foreach ($remove_files as $remove_file)
			@unlink($remove_file);
		unset($remove_files);

		if (! @rmdir($this->folder)) {
			Logger::error('main', 'Session folder NOT removed : '.$this->folder);
			if ($die_ == true)
				die('Session folder NOT removed : '.$this->folder);
			else
				return false;
		}
		Logger::info('main', 'Session folder removed : '.$this->folder);

		if (count(glob(SESSIONS_DIR.'/'.$this->server.'/*')) == 0) {
			Logger::info('main', 'Server folder is empty : '.SESSIONS_DIR.'/'.$this->server);

			if (! @rmdir(SESSIONS_DIR.'/'.$this->server)) {
				Logger::error('main', 'Server folder NOT removed : '.SESSIONS_DIR.'/'.$this->server);
				if ($die_ == true)
					die('Server folder NOT removed : '.SESSIONS_DIR.'/'.$this->server);
				else
					return false;
			}
			Logger::info('main', 'Server folder removed : '.SESSIONS_DIR.'/'.$this->server);
		}

		return true;
*/
	}
}
