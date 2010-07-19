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

class Session {
	const SESSION_STATUS_UNKNOWN = "unknown";
	const SESSION_STATUS_ERROR = "error";
	const SESSION_STATUS_INIT = "init";
	const SESSION_STATUS_INITED = "ready";
	const SESSION_STATUS_ACTIVE = "logged";
	const SESSION_STATUS_INACTIVE = "disconnected";
	const SESSION_STATUS_WAIT_DESTROY = "wait_destroy";
	const SESSION_STATUS_DESTROYED = "destroyed";

	const MODE_DESKTOP = "desktop";
	const MODE_APPLICATIONS = "applications";

	public $id = NULL;

	public $server = NULL;
	public $mode = NULL;
	public $type = NULL;
	public $status = NULL;
	public $settings = NULL;
	public $user_login = NULL;
	public $user_displayname = NULL;
	public $start_time = 0;
	public $servers = array();
	public $applications = array();

	public function __construct($id_) {
// 		Logger::debug('main', 'Starting Session::__construct for \''.$id_.'\'');

		$this->id = $id_;
	}

	public function __toString() {
		/* print array of apps? */
		return 'Session(\''.$this->id.'\', \''.$this->server.'\', \''.$this->mode.'\', \''.$this->type.'\', \''.$this->status.'\', \''.$this->user_login.'\', \''.$this->user_displayname.'\', \''.$this->start_time.'\')';
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

	public function uptodateAttribute($attrib_) {
// 		Logger::debug('main', 'Starting Session::uptodateAttribute for \''.$this->id.'\' attribute '.$attrib_);

		$buf = Abstract_Session::uptodate($this);

		return $buf;
	}

	public function getStatus() {
		Logger::debug('main', 'Starting Session::getStatus for \''.$this->id.'\'');

		$server = Abstract_Server::load($this->server);
		if (! $server) {
			Logger::error('main', 'Session::getStatus failed to load server (server='.$this->server.')');
			return false;
		}

		$ret = $server->getSessionStatus($this->id);
		if (! $ret) {
			Logger::error('main', 'Session::getStatus('.$this->id.') - ApS answer is incorrect');
			return false;
		}

		if ($ret != $this->getAttribute('status'))
			$this->setStatus($ret);

		return $ret;
	}

	public function setStatus($status_) {
		Logger::debug('main', 'Starting Session::setStatus for \''.$this->id.'\'');

		if ($status_ == Session::SESSION_STATUS_INITED) {
			Logger::info('main', 'Session start : \''.$this->id.'\'');

			$this->setAttribute('start_time', time());
		} elseif ($status_ == Session::SESSION_STATUS_INACTIVE) {
			if (! array_key_exists('persistent', $this->settings) || $this->settings['persistent'] == 0)
				return $this->setStatus(Session::SESSION_STATUS_WAIT_DESTROY);
		} elseif ($status_ == Session::SESSION_STATUS_WAIT_DESTROY || $status_ == Session::SESSION_STATUS_DESTROYED || $status_ == Session::SESSION_STATUS_ERROR || $status_ == Session::SESSION_STATUS_UNKNOWN) {
			Logger::info('main', 'Session end : \''.$this->id.'\'');

			$plugins = new Plugins();
			$plugins->doLoad();

			$plugins->doRemovesession(array(
				'fqdn'		=>	$this->server,
				'session'	=>	$this->id
			));

			if (! $this->orderDeletion((($status_ == Session::SESSION_STATUS_WAIT_DESTROY)?true:false)))
				Logger::error('main', 'Unable to order session deletion for session \''.$this->id.'\'');

			Abstract_Session::delete($this->id);

			return false;
		}

		Logger::debug('main', 'Status set to "'.$status_.'" ('.$this->textStatus($status_).') for session \''.$this->id.'\'');
		$this->setAttribute('status', $status_);

		$ev = new SessionStatusChanged(array(
			'id'		=>	$this->id,
			'status'	=>	$status_
		));

		$ev->emit();

		Abstract_Session::save($this);

		return true;
	}

	public function textStatus($status_=Session::SESSION_STATUS_UNKNOWN) {
		switch ($status_) {
			case Session::SESSION_STATUS_UNKNOWN:
				return _('Unknown');
				break;
			case Session::SESSION_STATUS_ERROR:
				return _('Error');
				break;
			case Session::SESSION_STATUS_INIT:
				return _('Initializing');
				break;
			case Session::SESSION_STATUS_INITED:
				return _('Ready');
				break;
			case Session::SESSION_STATUS_ACTIVE:
				return _('Logged');
				break;
			case Session::SESSION_STATUS_INACTIVE:
				return _('Disconnected');
				break;
			case Session::SESSION_STATUS_WAIT_DESTROY:
				return _('To destroy');
				break;
			case Session::SESSION_STATUS_DESTROYED:
				return _('Destroyed');
				break;
		}

		return _('Unknown');
	}

	public function colorStatus($status_=Session::SESSION_STATUS_UNKNOWN) {
		switch ($status_) {
			case Session::SESSION_STATUS_UNKNOWN:
				return 'error';
				break;
			case Session::SESSION_STATUS_ERROR:
				return 'error';
				break;
			case Session::SESSION_STATUS_INIT:
				return 'warn';
				break;
			case Session::SESSION_STATUS_INITED:
				return 'ok';
				break;
			case Session::SESSION_STATUS_ACTIVE:
				return 'ok';
				break;
			case Session::SESSION_STATUS_INACTIVE:
				return 'warn';
				break;
			case Session::SESSION_STATUS_WAIT_DESTROY:
				return 'warn';
				break;
			case Session::SESSION_STATUS_DESTROYED:
				return 'error';
				break;
		}

		return 'error';
	}

	public function stringStatus() {
		$buf = $this->getAttribute('status');

		return '<span class="msg_'.$this->colorStatus($buf).'">'.$this->textStatus($buf).'</span>';
	}

	public function isAlive() {
		Logger::debug('main', 'Starting Session::isAlive for \''.$this->id.'\'');

		if (! $this->hasAttribute('status') || ! $this->uptodateAttribute('status'))
			$this->getStatus();

		if (($buf = $this->getAttribute('status')) === false)
			return false;

		if ($buf == 0 || $buf == 1 || $buf == 22 || $buf == 2)
			return true;

		return false;
	}

	public function isSuspended() {
		Logger::debug('main', 'Starting Session::isSuspended for \''.$this->id.'\'');

		if (! $this->hasAttribute('status') || ! $this->uptodateAttribute('status'))
			$this->getStatus();

		if (($buf = $this->getAttribute('status')) === false)
			return false;

		if ($buf == 9 || $buf == 10)
			return true;

		return false;
	}

	public function orderDeletion($request_aps_=true) {
		Logger::debug('main', 'Starting Session::orderDeletion for \''.$this->id.'\'');

		foreach ($this->servers as $server) {
			$session_server = Abstract_Server::load($server);
			if (! $session_server) {
				Logger::error('main', 'Session::orderDeletion Unable to load server \''.$server.'\'');
				return false;
			}

			if ($request_aps_) {
				$buf = $session_server->orderSessionDeletion($this->id);

				if (! $buf)
					Logger::warning('main', 'Session::orderDeletion Session \''.$this->id.'\' already destroyed on server \''.$server.'\'');
			}
		}

		Abstract_Session::delete($this->id);

		return true;
	}
}
