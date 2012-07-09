<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
 * Author David LECHEVALIER <david@ulteo.com> 2012
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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
	const SESSION_STATUS_CREATING = "creating";
	const SESSION_STATUS_CREATED = "created";
	const SESSION_STATUS_INIT = "init";
	const SESSION_STATUS_READY = "ready";
	const SESSION_STATUS_ACTIVE = "logged";
	const SESSION_STATUS_INACTIVE = "disconnected";
	const SESSION_STATUS_WAIT_DESTROY = "wait_destroy";
	const SESSION_STATUS_DESTROYING = "destroying";
	const SESSION_STATUS_DESTROYED = "destroyed";

	const SESSION_END_STATUS_LOGOUT = "logout";
	const SESSION_END_STATUS_ADMINKILL = "adminkill";
	const SESSION_END_STATUS_TIMEOUT = "timeout";
	const SESSION_END_STATUS_UNUSED = "unused";
	const SESSION_END_STATUS_ERROR = "error";
	const SESSION_END_STATUS_SHUTDOWN = "shutdown";
	const SESSION_END_STATUS_SERVER_DOWN = "server_down";
	const SESSION_END_STATUS_SERVER_BROKEN = "server_broken";
	const SESSION_END_STATUS_SERVER_DELETED = "server_deleted";

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
	public $timestamp = 0;
	public $servers = array();
	public $applications = array();

	public function __construct($id_) {
// 		Logger::debug('main', 'Starting Session::__construct for \''.$id_.'\'');

		$this->id = $id_;
	}

	public function __toString() {
		/* print array of apps? */
		return 'Session(\''.$this->id.'\', \''.$this->server.'\', \''.$this->mode.'\', \''.$this->type.'\', \''.$this->status.'\', \''.$this->user_login.'\', \''.$this->user_displayname.'\', \''.$this->start_time.'\', \''.$this->timestamp.'\')';
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

	public function getAllStates() {
		return array(Session::SESSION_STATUS_UNKNOWN, Session::SESSION_STATUS_CREATING, Session::SESSION_STATUS_CREATED, Session::SESSION_STATUS_INIT, Session::SESSION_STATUS_READY, Session::SESSION_STATUS_ACTIVE, Session::SESSION_STATUS_INACTIVE, Session::SESSION_STATUS_WAIT_DESTROY, Session::SESSION_STATUS_DESTROYING, Session::SESSION_STATUS_DESTROYED, Session::SESSION_STATUS_ERROR);
	}

	public function getStatus() {
		if ($this->uptodateAttribute('status'))
			return $this->getAttribute('status');

		Logger::debug('main', 'Starting Session::getStatus for \''.$this->id.'\'');

		foreach ($this->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
			$server = Abstract_Server::load($fqdn);
			if (! $server) {
				Logger::error('main', 'Session::getStatus failed to load server (server='.$fqdn.')');
				$this->setServerStatus($fqdn, Session::SESSION_STATUS_ERROR);
				continue;
			}

			$ret = $server->getSessionStatus($this->id);
			if (! $ret) {
				Logger::error('main', 'Session::getStatus('.$this->id.') - ApS answer is incorrect');
				$this->setServerStatus($fqdn, Session::SESSION_STATUS_ERROR);
				continue;
			}

			$this->setServerStatus($fqdn, $ret);
		}

		return $this->getAttribute('status');
	}

	public function setServerStatus($server_, $status_, $reason_=NULL) {
		$states = array(
			Session::SESSION_STATUS_CREATING		=>	-1,
			Session::SESSION_STATUS_CREATED			=>	0,
			Session::SESSION_STATUS_INIT			=>	1,
			Session::SESSION_STATUS_READY			=>	2,
			Session::SESSION_STATUS_ACTIVE			=>	3,
			Session::SESSION_STATUS_INACTIVE		=>	3,
			Session::SESSION_STATUS_WAIT_DESTROY	=>	4,
			Session::SESSION_STATUS_DESTROYING		=>	5,
			Session::SESSION_STATUS_DESTROYED		=>	6,
			Session::SESSION_STATUS_ERROR			=>	6,
			Session::SESSION_STATUS_UNKNOWN			=>	6
		);

		if (! array_key_exists($server_, $this->servers[Server::SERVER_ROLE_APS]))
			return false; // no such Server

		$current_status = $this->servers[Server::SERVER_ROLE_APS][$server_]['status'];

		if ($status_ == $current_status) {
			Abstract_Session::save($this);
			return false; // status is already the same...
		}

		if (array_key_exists($status_, $states) && array_key_exists($current_status, $states)) {
			if ($states[$status_] < $states[$current_status])
				return false; // avoid switching Session to a previous status...
		}

		Logger::debug('main', 'Starting Session::setServerStatus for \''.$this->id.'\'');

		Logger::debug('main', 'Status set to "'.$status_.'" ('.$this->textStatus($status_).') for server \''.$server_.'\' on session \''.$this->id.'\'');
		$this->servers[Server::SERVER_ROLE_APS][$server_]['status'] = $status_;
		Abstract_Session::save($this);

		switch ($status_) {
			case Session::SESSION_STATUS_INIT:
				Logger::debug('main', 'Session::setServerStatus('.$server_.', '.$status_.') - Server "'.$server_.'" is now "'.$status_.'", switching Session status to "'.$status_.'"');
				$this->setStatus(Session::SESSION_STATUS_INIT);
				break;
			case Session::SESSION_STATUS_READY:
				$all_ready = true;
				foreach ($this->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
					if ($fqdn != $server_ && $data['status'] != Session::SESSION_STATUS_READY) {
						$all_ready = false;
						break;
					}
				}
				if ($all_ready) {
					Logger::debug('main', 'Session::setServerStatus('.$server_.', '.$status_.') - All servers are "'.$status_.'", switching Session status to "'.$status_.'"');
					$this->setStatus(Session::SESSION_STATUS_READY);
				}
				break;
			case Session::SESSION_STATUS_ACTIVE:
				Logger::debug('main', 'Session::setServerStatus('.$server_.', '.$status_.') - Server "'.$server_.'" is now "'.$status_.'", switching Session status to "'.$status_.'"');
				$this->setStatus(Session::SESSION_STATUS_ACTIVE);
				break;
			case Session::SESSION_STATUS_INACTIVE:
				Logger::debug('main', 'Session::setServerStatus('.$server_.', '.$status_.') - Server "'.$server_.'" is now "'.$status_.'", switching Session status to "'.$status_.'"');
				$this->setStatus(Session::SESSION_STATUS_INACTIVE);
				break;
			case Session::SESSION_STATUS_WAIT_DESTROY:
				Logger::debug('main', 'Session::setServerStatus('.$server_.', '.$status_.') - Server "'.$server_.'" is now "'.$status_.'", switching Session status to "'.$status_.'"');
				$this->setStatus(Session::SESSION_STATUS_WAIT_DESTROY, Session::SESSION_END_STATUS_LOGOUT);
				break;
			case Session::SESSION_STATUS_DESTROYING:
				Logger::debug('main', 'Session::setServerStatus('.$server_.', '.$status_.') - Server "'.$server_.'" is now "'.$status_.'", switching Session status to "'.$status_.'"');
				if (Abstract_ReportSession::exists($this->id)) {
					$session_report = Abstract_ReportSession::load($this->id);
					if (is_object($session_report) && ! is_null($session_report->stop_why))
						$reason_ = $session_report->stop_why;
				}
				$this->setStatus(Session::SESSION_STATUS_DESTROYING, $reason_);
				break;
			case Session::SESSION_STATUS_DESTROYED:
				$all_destroyed = true;
				foreach ($this->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
					if ($fqdn != $server_ && $data['status'] != Session::SESSION_STATUS_DESTROYED) {
						$all_destroyed = false;
						break;
					}
				}
				if ($all_destroyed) {
					Logger::debug('main', 'Session::setServerStatus('.$server_.', '.$status_.') - All servers are "'.$status_.'", switching Session status to "'.$status_.'"');
					if (Abstract_ReportSession::exists($this->id)) {
						$session_report = Abstract_ReportSession::load($this->id);
						if (is_object($session_report) && ! is_null($session_report->stop_why))
							$reason_ = $session_report->stop_why;
					}
					$this->setStatus(Session::SESSION_STATUS_DESTROYED, $reason_);
				}
				break;
			case Session::SESSION_STATUS_ERROR:
				Logger::debug('main', 'Session::setServerStatus('.$server_.', '.$status_.') - Server "'.$server_.'" is now "'.$status_.'", switching Session status to "'.$status_.'"');
				$this->setStatus(Session::SESSION_STATUS_WAIT_DESTROY, Session::SESSION_END_STATUS_ERROR);
				break;
			case Session::SESSION_STATUS_UNKNOWN:
				Logger::debug('main', 'Session::setServerStatus('.$server_.', '.$status_.') - Server "'.$server_.'" is now "'.$status_.'", switching Session status to "'.$status_.'"');
				$this->setStatus(Session::SESSION_STATUS_WAIT_DESTROY, Session::SESSION_END_STATUS_ERROR);
				break;
		}

		return true;
	}

	public function setStatus($status_, $reason_=NULL) {
		if ($status_ == $this->getAttribute('status'))
			return false; // status is already the same...

		$states = array(
			Session::SESSION_STATUS_CREATING		=>	-1,
			Session::SESSION_STATUS_CREATED			=>	0,
			Session::SESSION_STATUS_INIT			=>	1,
			Session::SESSION_STATUS_READY			=>	2,
			Session::SESSION_STATUS_ACTIVE			=>	3,
			Session::SESSION_STATUS_INACTIVE		=>	3,
			Session::SESSION_STATUS_WAIT_DESTROY	=>	4,
			Session::SESSION_STATUS_DESTROYING		=>	5,
			Session::SESSION_STATUS_DESTROYED		=>	6,
			Session::SESSION_STATUS_ERROR			=>	6,
			Session::SESSION_STATUS_UNKNOWN			=>	6
		);

		if (array_key_exists($status_, $states) && array_key_exists($this->getAttribute('status'), $states)) {
			if ($states[$status_] < $states[$this->getAttribute('status')] && ! $this->canSwitchToPreviousStatus($status_))
				return false; // avoid switching Session to a previous status...
		}

		Logger::debug('main', 'Starting Session::setStatus for \''.$this->id.'\'');

		$ev = new SessionStatusChanged(array(
			'id'		=>	$this->id,
			'status'	=>	$status_
		));

		if ($status_ == Session::SESSION_STATUS_READY) {
			Logger::info('main', 'Session start : \''.$this->id.'\'');

			$this->setAttribute('start_time', time());
		} elseif ($status_ == Session::SESSION_STATUS_INACTIVE) {
			if ($this->mode != Session::MODE_DESKTOP || ! array_key_exists('persistent', $this->settings) || $this->settings['persistent'] == 0)
				return $this->setStatus(Session::SESSION_STATUS_WAIT_DESTROY, Session::SESSION_END_STATUS_LOGOUT);
		} elseif ($status_ == Session::SESSION_STATUS_WAIT_DESTROY) {
			Logger::info('main', 'Session end : \''.$this->id.'\' (reason: \''.$reason_.'\')');
			
			if (! array_key_exists('stop_time', $this->settings))
				$this->settings["stop_time"] = time();
			
			if ($status_ == Session::SESSION_STATUS_WAIT_DESTROY && ! is_null($reason_)) {
				$report_session = Abstract_ReportSession::load($this->id);
				if (is_object($report_session)) {
					$report_session->stop_why = $reason_;
					Abstract_ReportSession::update($report_session);
				}
			}

			if (! $this->orderDeletion())
				Logger::error('main', 'Unable to order session deletion for session \''.$this->id.'\'');
			else {
				$ev->emit();

				return false;
			}
		} elseif ($status_ == Session::SESSION_STATUS_DESTROYED) {
			Logger::info('main', 'Session purge : \''.$this->id.'\' (reason: \''.$reason_.'\')');

			if (array_key_exists(Server::SERVER_ROLE_FS, $this->servers)) {
				foreach ($this->servers[Server::SERVER_ROLE_FS] as $fqdn => $data) {
					$session_server = Abstract_Server::load($fqdn);
					if (! $session_server) {
						Logger::error('main', 'Session::orderDeletion Unable to load server \''.$fqdn.'\'');
						return false;
					}

					if (is_array($session_server->roles)) {
						if (array_key_exists(Server::SERVER_ROLE_FS, $session_server->roles)) {
							$buf = $session_server->orderFSAccessDisable($this->settings['fs_access_login']);
							if (! $buf)
								Logger::warning('main', 'Session::orderDeletion User \''.$this->settings['fs_access_login'].'\' already logged out of server \''.$fqdn.'\'');
						}
					}
				}
			}

			if ($status_ == Session::SESSION_STATUS_DESTROYED && ! is_null($reason_)) {
				$report_session = Abstract_ReportSession::load($this->id);
				if (is_object($report_session)) {
					$report_session->stop_why = $reason_;
					Abstract_ReportSession::update($report_session);
				}
			}

			$ev->emit();

			Abstract_Session::delete($this->id);

			return false;
		}

		Logger::debug('main', 'Status set to "'.$status_.'" ('.$this->textStatus($status_).') for session \''.$this->id.'\'');
		$this->setAttribute('status', $status_);

		$ev->emit();

		Abstract_Session::save($this);

		return true;
	}

	private function canSwitchToPreviousStatus($status_) {
		return (array_key_exists('persistent', $this->settings) && 
			$this->settings['persistent'] == 1 && 
			$this->getAttribute('status') == Session::SESSION_STATUS_INACTIVE &&
			$status_ == Session::SESSION_STATUS_READY);
	}

	public function textStatus($status_=Session::SESSION_STATUS_UNKNOWN) {
		switch ($status_) {
			case Session::SESSION_STATUS_UNKNOWN:
				return _('Unknown');
				break;
			case Session::SESSION_STATUS_ERROR:
				return _('Error');
				break;
			case Session::SESSION_STATUS_CREATING:
				return _('Creating');
				break;
			case Session::SESSION_STATUS_CREATED:
				return _('Created');
				break;
			case Session::SESSION_STATUS_INIT:
				return _('Initializing');
				break;
			case Session::SESSION_STATUS_READY:
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
			case Session::SESSION_STATUS_DESTROYING:
				return _('Destroying');
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
			case Session::SESSION_STATUS_CREATING:
				return 'warn';
				break;
			case Session::SESSION_STATUS_CREATED:
				return 'warn';
				break;
			case Session::SESSION_STATUS_INIT:
				return 'warn';
				break;
			case Session::SESSION_STATUS_READY:
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
			case Session::SESSION_STATUS_DESTROYING:
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

	public static function textEndStatus($end_status_) {
		return $end_status_; // for now the text is not translated
	}

	public function isAlive() {
		Logger::debug('main', 'Starting Session::isAlive for \''.$this->id.'\'');

		if (! $this->hasAttribute('status') || ! $this->uptodateAttribute('status'))
			$this->getStatus();

		if (($buf = $this->getAttribute('status')) === false)
			return false;

		if (in_array($buf, array(Session::SESSION_STATUS_CREATING, Session::SESSION_STATUS_CREATED, Session::SESSION_STATUS_INIT, Session::SESSION_STATUS_READY, Session::SESSION_STATUS_ACTIVE)))
			return true;

		return false;
	}

	public function isSuspended() {
		Logger::debug('main', 'Starting Session::isSuspended for \''.$this->id.'\'');

		if (! $this->hasAttribute('status') || ! $this->uptodateAttribute('status'))
			$this->getStatus();

		if (($buf = $this->getAttribute('status')) === false)
			return false;

		if ($buf == Session::SESSION_STATUS_INACTIVE)
			return true;

		return false;
	}

	public function orderDeletion($request_servers_=true, $reason_=NULL) {
		Logger::debug('main', 'Starting Session::orderDeletion for \''.$this->id.'\'');

		$total = count($this->servers[Server::SERVER_ROLE_APS]);
		$destroyed = 0;

		if (! is_null($reason_)) {
			$report_session = Abstract_ReportSession::load($this->id);
			if (is_object($report_session)) {
				$report_session->stop_why = $reason_;
				Abstract_ReportSession::update($report_session);
			}
		}

		if ($request_servers_) {
			foreach ($this->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
				$session_server = Abstract_Server::load($fqdn);
				if (! $session_server) {
					Logger::error('main', 'Session::orderDeletion Unable to load server \''.$fqdn.'\'');
					return false;
				}

				if (is_array($session_server->roles)) {
					if (array_key_exists(Server::SERVER_ROLE_APS, $session_server->roles)) {
						$buf = $session_server->orderSessionDeletion($this->id);
						if (! $buf) {
							Logger::warning('main', 'Session::orderDeletion Session \''.$this->id.'\' already destroyed on server \''.$fqdn.'\'');
							$this->setServerStatus($fqdn, Session::SESSION_STATUS_DESTROYED);
							$destroyed++;
						} else
							$this->setServerStatus($fqdn, Session::SESSION_STATUS_DESTROYING);
					}
				}
			}
		} else
			$destroyed = $total;

		if ($destroyed == $total)
			$this->setStatus(Session::SESSION_STATUS_DESTROYED, $reason_);
		else
			$this->setStatus(Session::SESSION_STATUS_DESTROYING, $reason_);

		return true;
	}

	public function getRunningApplications() {
		$running_apps = array();
		foreach ($this->applications as $server => $running_app) {
			if (is_array($running_app)) {
				foreach ($running_app as $k => $v)
					$running_apps[$k] = $v;
			}
		}

		return $running_apps;
	}

	public function setRunningApplications($fqdn_, $running_apps_) {
		$this->applications[$fqdn_] = $running_apps_;

		return true;
	}
}
