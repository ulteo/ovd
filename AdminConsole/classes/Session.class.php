<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
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

class Session extends AbstractObject {
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

	public $id;
	public $status;
	
	public function __construct($attributes_) {
		parent::__construct($attributes_);
		if (! $this->is_valid()) {
			return;
		}
		
		$this->id = $attributes_['id'];
		$this->status = $attributes_['status'];
	}
	
	protected function required_attributes() {
		return array('id', 'status', 'mode', 'user_login', 'user_displayname', 'servers');
	}
	
	public static function textStatus($status_=Session::SESSION_STATUS_UNKNOWN) {
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

	public static function colorStatus($status_=Session::SESSION_STATUS_UNKNOWN) {
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

	/**
	 * Tells if the current session is killable.
	 * @return {boolean} True : the session is killable | False : otherwise
	 */
	public function isKillable() {
		return
			$this->status == Session::SESSION_STATUS_CREATING ||
			$this->status == Session::SESSION_STATUS_CREATED ||
			$this->status == Session::SESSION_STATUS_INIT ||
			$this->status == Session::SESSION_STATUS_INACTIVE ||
			$this->isDisconnectable();
	}

	/**
	 * Tells if the current session can be disconnected.
	 * @return {boolean} True : the session is disconnectable | False : otherwise
	 */
	public function isDisconnectable() {
		return
			$this->status == Session::SESSION_STATUS_ACTIVE ||
			$this->status == Session::SESSION_STATUS_READY;
	}
}
