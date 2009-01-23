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

		$attributes = array('server', 'status', 'user_login', 'user_displayname');
		foreach ($attributes as $attribute)
			if (($$attribute = @file_get_contents($folder.'/'.$attribute)) === false)
				return false;
		unset($attribute);
		unset($attributes);

		$buf = new Session($id);
		$buf->server = (string)$server;
		$buf->status = (int)$status;
		$buf->user_login = (string)$user_login;
		$buf->user_displayname = (string)$user_displayname;

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
		@file_put_contents($folder.'/status', (int)$session_->status);
		@file_put_contents($folder.'/user_login', (string)$session_->user_login);
		@file_put_contents($folder.'/user_displayname', (string)$session_->user_displayname);

		return true;
	}

	private function create($session_) {
// 		Logger::debug('main', 'Starting Abstract_Session::create for \''.$session_->id.'\'');

		$id = $session_->id;
		$folder = SESSIONS_DIR.'/'.$id;

		if (! is_writeable(SESSIONS_DIR))
			return false;

		$l = new ServerSessionLiaison($session_->server, $session_->id);
		if (! $l->insertDB())
			return false;

		if (! @mkdir($folder, 0750))
			return false;

		return true;
	}

	public function delete($id_) {
// 		Logger::debug('main', 'Starting Abstract_Session::delete for \''.$id_.'\'');

		$id = $id_;
		$folder = SESSIONS_DIR.'/'.$id;

		if (! file_exists($folder))
			return false;

		$session = Abstract_Session::load($id_);
		if (! $session)
			return false;

		$l = new ServerSessionLiaison($session->server, $session->id);
		if (! $l->removeDB())
			return false;

		$remove_files = glob($folder.'/*');
		foreach ($remove_files as $remove_file)
			@unlink($remove_file);
		unset($remove_file);
		unset($remove_files);

		if (! @rmdir($folder))
			return false;

		return true;
	}

	public function load_all() {
// 		Logger::debug('main', 'Starting Abstract_Session::load_all');

		$all_sessions = glob(SESSIONS_DIR.'/*', GLOB_ONLYDIR);

		$sessions = array();
		foreach ($all_sessions as $all_session) {
			$id = basename($all_session);

			$session = Abstract_Session::load($id);
			if (! $session)
				continue;

			$sessions[] = $session;
		}
		unset($all_session);
		unset($all_sessions);

		return $sessions;
	}

	public function uptodate($session_) {
// 		Logger::debug('main', 'Starting Abstract_Session::uptodate for \''.$session_->id.'\'');

		$id = $session_->id;
		$folder = SESSIONS_DIR.'/'.$id;

		$buf = @filemtime($folder.'/status');

		if ($buf > (time()-30))
			return true;

		return false;
	}
}
