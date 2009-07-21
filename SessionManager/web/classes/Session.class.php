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

		$ret = query_url_no_error('http://'.$server->fqdn.':'.$server->web_port.'/webservices/session_status.php?session='.$this->id);

		if (! is_numeric($ret) || $ret === '') {
			Logger::error('main', 'Session::getStatus('.$this->id.') - ApS answer is incorrect');
			return false;
		}

		$this->setStatus($ret);

		return $ret;
	}

	public function setStatus($status_) {
		Logger::debug('main', 'Starting Session::setStatus for \''.$this->id.'\'');

		Logger::info('main', 'Status set to "'.$status_.'" ('.$this->textStatus($status_).') for \''.$this->id.'\'');
		$this->setAttribute('status', $status_);

		$ev = new SessionStatusChanged(array(
			'id'		=>	$this->id,
			'status'	=>	$status_
		));

		$ev->emit();

		return true;
	}

	public function textStatus($status_=4) {
// 		Logger::debug('main', 'Starting Session::textStatus for \''.$this->id.'\'');

		$states = array(
			-1	=>	_('To create'),
			0	=>	_('Created'),
			1	=>	_('To start'),
			22	=>	_('Initializing'),
			2	=>	_('Active'),
			9	=>	_('Suspending'),
			10	=>	_('Suspended'),
			11	=>	_('Resuming'),
			3	=>	_('To destroy'),
			4	=>	_('Destroyed')
		);

		return $states[$status_];
	}

	public function colorStatus($status_=4) {
// 		Logger::debug('main', 'Starting Session::colorStatus for \''.$this->id.'\'');

		$states = array(
			-1	=>	'warn',
			0	=>	'warn',
			1	=>	'warn',
			22	=>	'warn',
			2	=>	'ok',
			9	=>	'warn',
			10	=>	'ok',
			11	=>	'warn',
			3	=>	'error',
			4	=>	'error'
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

	public function orderDeletion() {
		Logger::debug('main', 'Starting Session::orderDeletion for \''.$this->id.'\'');

		$server = Abstract_Server::load($this->server);

		$buf = query_url('http://'.$server->fqdn.':'.$server->web_port.'/webservices/kill_session.php?session='.$this->id);

		if (! $buf) {
			Abstract_Session::delete($this->id);
			return false;
		}

		$this->setStatus(3);

		return true;
	}
}
