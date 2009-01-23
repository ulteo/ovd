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

// 	public $session = NULL;
// 	public $server = NULL;
// 	public $folder = NULL;
// 	public $settings = array();

class Session {
	public $id = NULL;

	public $server = NULL;
	public $status = NULL;
	public $user_login = NULL;
	public $user_displayname = NULL;

	public function __construct($id_) {
// 		Logger::debug('main', 'Starting Session::__construct for \''.$id_.'\'');

		$this->id = $id_;
	}

	public function __toString() {
		return 'Session(\''.$this->id.'\',\''.$this->server.'\',\''.$this->status.'\',\''.$this->user_login.'\',\''.$this->user_displayname.'\')';
	}

	public function hasAttribute($attrib_) {
// 		Logger::debug('main', 'Starting Session::hasAttribute for \''.$this->id.'\' attribute '.$attrib_);

		if (! isset($this->$attrib_) || is_null($this->$attrib_))
			return false;

		return true;
	}

	public function getAttribute($attrib_) {
// 		Logger::debug('main', 'Starting Session::getAttribute for \''.$this->id.'\' attribute '.$attrib_);

		if (! $this->hasAttribute($attrib_))
			return false;

		return $this->$attrib_;
	}

	public function setAttribute($attrib_, $value_) {
// 		Logger::debug('main', 'Starting Session::setAttribute for \''.$this->id.'\' attribute '.$attrib_.' value '.$value_);

		$this->$attrib_ = $value_;

		return true;
	}

	public function getStatus() {
// 		Logger::debug('main', 'Starting Session::getStatus for \''.$this->id.'\'');

		$server = Abstract_Server::load($this->server);

		$ret = query_url('http://'.$server->fqdn.':'.$server->web_port.'/webservices/session_status.php?session='.$this->id);

		if (! $ret) {
			$server->isUnreachable();
			return false;
		}

		if (is_numeric($ret)) {
			$this->setStatus($ret);

			return $ret;
		}

		return false;
	}

	public function setStatus($status_) {
// 		Logger::debug('main', 'Starting Session::setStatus for \''.$this->id.'\'');

		switch ($status_) {
			default:
				Logger::warning('main', 'Status set to "'.$status_.'" for \''.$this->id.'\'');
				$this->setAttribute('status', $status_);
				break;
		}

		Abstract_Session::save($this);

		return true;
	}

	public function stringStatus() {
// 		Logger::debug('main', 'Starting Session::stringStatus for \''.$this->id.'\'');

		$states = array(
			-1	=>	array(
						'color'	=>	'warn',
						'message'	=>	_('To create')
					),
			0	=>	array(
						'color'	=>	'warn',
						'message'	=>	_('Created')
					),
			1	=>	array(
						'color'	=>	'warn',
						'message'	=>	_('To start')
					),
			22	=>	array(
						'color'	=>	'warn',
						'message'	=>	_('Initializing')
					),
			2	=>	array(
						'color'	=>	'ok',
						'message'	=>	_('Active')
					),
			9	=>	array(
						'color'	=>	'warn',
						'message'	=>	_('Suspending')
					),
			10	=>	array(
						'color'	=>	'ok',
						'message'	=>	_('Suspended')
					),
			11	=>	array(
						'color'	=>	'warn',
						'message'	=>	_('Resuming')
					),
			3	=>	array(
						'color'	=>	'error',
						'message'	=>	_('To destroy')
					),
			4	=>	array(
						'color'	=>	'error',
						'message'	=>	_('Destroyed')
					)
		);

		$buf = $this->getAttribute('status');

		return '<span class="msg_'.$states[$buf]['color'].'">'.$states[$buf]['message'].'</span>';
	}

	public function isAlive() {
// 		Logger::debug('main', 'Starting Session::isAlive for \''.$this->id.'\'');

		$ret = $this->getStatus();

		if ($ret == 1 || $ret == 22 || $ret == 2 || $ret == 3)
			return true;

		return false;
	}

	public function isSuspended() {
// 		Logger::debug('main', 'Starting Session::isSuspended for \''.$this->id.'\'');

		$ret = $this->getStatus();

		if ($ret == 10)
			return true;

		return false;
	}

	public function orderDeletion() {
// 		Logger::debug('main', 'Starting Session::orderDeletion for \''.$this->id.'\'');

		$server = Abstract_Server::load($this->server);

		$buf = query_url('http://'.$server->fqdn.':'.$server->web_port.'/webservices/kill_session.php?session='.$this->id);

		if (! $buf)
			return false;

		$this->setStatus(3);

		return true;
	}

	// ? unclean?
	public function create_token($mode_, $content_=array()) {
		Logger::debug('main', 'Starting SESSION::create_token for session '.$this->id.' on server '.$this->server);

$this->folder = SESSIONS_DIR.'/'.$this->id;

		$token = gen_string(5);

		if (!file_exists(TOKENS_DIR.'/'.$token)) {
			if (@file_put_contents(TOKENS_DIR.'/'.$token, $mode_.':'.$this->id)) {
				Logger::info('main', 'Token created : '.TOKENS_DIR.'/'.$token);

				if ($mode_ == 'start') {
					$data = serialize($content_);

					if (@file_put_contents($this->folder.'/settings', $data))
						Logger::info('main', 'Session "start" token created : '.$this->folder.'/settings');
					else
						Logger::error('main', 'Session "start" token NOT created : '.$this->folder.'/settings');
				} elseif ($mode_ == 'invite') {
					$data = serialize($content_);

					if (@file_put_contents($this->folder.'/'.$token, $data))
						Logger::info('main', 'Session "invite" token created : '.$this->folder.'/'.$token);
					else
						Logger::error('main', 'Session "invite" token NOT created : '.$this->folder.'/'.$token);
				}

				return $token;
			} else
				Logger::error('main', 'Token NOT created : '.TOKENS_DIR.'/'.$token);
		} else {
			Logger::error('main', 'Token already exists : '.TOKENS_DIR.'/'.$token);
			//$this->create_token($mode_, $data_);
		}

		return false;
	}

	public function use_token($token_) {
		Logger::debug('main', 'Starting SESSION::use_token for token '.$token_);

		if (! @unlink(TOKENS_DIR.'/'.$token_)) {
			Logger::error('main', 'Unable to remove token : '.TOKENS_DIR.'/'.$token_);
			return false;
		}
		Logger::info('main', 'Token removed : '.TOKENS_DIR.'/'.$token_);

		return true;
	}

	public function getStartTime() {
		Logger::debug('main', 'Starting SESSION::getStartTime for session '.$this->id);

$this->folder = SESSIONS_DIR.'/'.$this->id;

		if (! file_exists($this->folder.'/used')) {
			Logger::error('main', 'Unable to get start time');
			return _('Unknown');
		}
		Logger::info('main', 'Got start time');

		return date('d/m/Y H:i:s', filemtime($this->folder.'/used'));
	}

	public function addInvite($email_, $view_only_) {
		Logger::debug('main', 'Starting SESSION::addInvite for session '.$this->session.' on server '.$this->server);

		$buf = $email_;
		if ($view_only_ == true)
			$buf .= ' (passive mode)';
		else
			$buf .= ' (active mode)';

		if (@file_put_contents($this->folder.'/invited', $buf."\n", FILE_APPEND)) {
			Logger::info('main', 'Session invited file created : '.$this->folder.'/invited');

			return true;
		}
		Logger::error('main', 'Session invited file NOT created : '.$this->folder.'/invited');

		return false;
	}

	public function invitedEmails() {
		Logger::debug('main', 'Starting SESSION::invitedEmails for session '.$this->session.' on server '.$this->server);

		if (!file_exists($this->folder.'/invited'))
			return false;

		$buf = @file_get_contents($this->folder.'/invited');

		if (! $buf)
			return false;

		$ret = explode("\n", $buf);

		foreach ($ret as $k => $v)
			if ($v === '')
				unset($ret[$k]);

		return $ret;
	}
}
