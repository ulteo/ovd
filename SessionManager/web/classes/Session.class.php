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

		$this->setStatus($ret);

		return $ret;
	}

	public function setStatus($status_) {
		Logger::debug('main', 'Starting Session::setStatus for \''.$this->id.'\'');

		//$status_ == 'init'
		if ($status_ == 'ready') { // || $status_ == 'logged'
			Logger::info('main', 'Session start : \''.$this->id.'\'');

			$this->setAttribute('start_time', time());
		} elseif ($status_ == 'wait_destroy' || $status_ == 'destroyed' || $status_ == 'error' || $status_ == 'unknown') { // || $status_ == 'disconnected'
			Logger::info('main', 'Session end : \''.$this->id.'\'');

			$plugins = new Plugins();
			$plugins->doLoad();

			$plugins->doRemovesession(array(
				'fqdn'		=>	$this->server,
				'session'	=>	$this->id
			));

			if (! $this->orderDeletion((($status_ == 'wait_destroy')?true:false)))
				Logger::error('main', 'Unable to order session deletion for session \''.$this->id.'\'');

			Abstract_Session::delete($this);

			return false;
		}

		Logger::info('main', 'Status set to "'.$status_.'" ('.$this->textStatus($status_).') for session \''.$this->id.'\'');
		$this->setAttribute('status', $status_);

		$ev = new SessionStatusChanged(array(
			'id'		=>	$this->id,
			'status'	=>	$status_
		));

		$ev->emit();

		return true;
	}

	public function textStatus($status_='unknown') {
// 		Logger::debug('main', 'Starting Session::textStatus for \''.$this->id.'\'');

		$states = array(
			'init'			=>	_('Initializing'),
			'ready'			=>	_('Ready'),
			'logged'		=>	_('Logged'),
			'disconnected'	=>	_('Disconnected'),
			'wait_destroy'	=>	_('To destroy'),
			'destroyed'		=>	_('Destroyed'),
			'error'			=>	_('Error'),
			'unknown'		=>	_('Unknown')
		);

		return $states[$status_];
	}

	public function colorStatus($status_='unknown') {
// 		Logger::debug('main', 'Starting Session::colorStatus for \''.$this->id.'\'');

		$states = array(
			'init'			=>	'warn',
			'ready'			=>	'ok',
			'logged'		=>	'ok',
			'disconnected'	=>	'ok',
			'wait_destroy'	=>	'warn',
			'destroyed'		=>	'error',
			'error'			=>	'error',
			'unknown'		=>	'error'
		);

		return $states[$status_];
	}

	public function stringStatus() {
// 		Logger::debug('main', 'Starting Session::stringStatus for \''.$this->id.'\'');

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

		$server = Abstract_Server::load($this->server);
		if (! $server) {
			Logger::error('main', 'Session::orderDeletion Unable to load server \''.$this->server.'\'');
			return false;
		}

		if (isset($this->settings['windows_server']) && $request_aps_) {
			$windows_server = Abstract_Server::load($this->settings['windows_server']);
			if (! $windows_server) {
				Logger::error('main', 'Session::orderDeletion Unable to delete windows session \''.$this->id.'\' server \''.$this->settings['windows_server'].'\' not found');
				return false;
			}

			$buf = $windows_server->orderWindowsSessionDeletion($this->id);
			if (! $buf) {
				Logger::error('main', 'Session::orderDeletion Unable to delete windows session \''.$this->id.'\'');
				return false;
			}
		}

		if ($request_aps_) {
			$buf = $server->orderSessionDeletion($this->id);

			if ($buf) {
				$this->setStatus(3);
				return true;
			} else
				Logger::warning('main', 'Session::orderDeletion Session \''.$this->id.'\' already destroyed on ApS side');
		}

		Abstract_Session::delete($this->id);

		return true;
	}
}
