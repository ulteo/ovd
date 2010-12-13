<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

class NetworkFolder {
	const NF_TYPE_PROFILE = "profile";
	const NF_TYPE_NETWORKFOLDER = "network_folder";

	const NF_STATUS_NOT_EXISTS = 1;
	const NF_STATUS_ACTIVE = 2;
	const NF_STATUS_INACTIVE = 3;

	public $id = NULL;
	public $server = NULL; // FQDN/ID of the server
	public $status = '';

	public function __construct() {
	}
	
	public function __toString() {
		return get_class($this).'(id \''.$this->id.'\' server \''.$this->server.'\' status \''.$this->status.'\' )';
	}
	
	public function prettyName() {
		return get_class($this);
	}

	public function isUsed() {
		if ($this->status == 2)
			return true;

		return false;
	}

	public function textStatus($status_=NetworkFolder::NF_STATUS_NOT_EXISTS) {
		switch ($status_) {
			case NetworkFolder::NF_STATUS_NOT_EXISTS:
				return _('Unknown');
				break;
			case NetworkFolder::NF_STATUS_ACTIVE:
				return _('Active');
				break;
			case NetworkFolder::NF_STATUS_INACTIVE:
				return _('Inactive');
				break;
		}

		return _('Unknown');
	}

	public function colorStatus($status_=NetworkFolder::NF_STATUS_NOT_EXISTS) {
		switch ($status_) {
			case NetworkFolder::NF_STATUS_NOT_EXISTS:
				return 'error';
				break;
			case NetworkFolder::NF_STATUS_ACTIVE:
				return 'ok';
				break;
			case NetworkFolder::NF_STATUS_INACTIVE:
				return 'warn';
				break;
		}

		return 'error';
	}
}
