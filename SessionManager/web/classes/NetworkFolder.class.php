<?php
/**
 * Copyright (C) 2010-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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

	const NF_STATUS_OK = 0;
	const NF_STATUS_NOT_EXISTS = 1;

	public $id = NULL;
	public $server = NULL; // FQDN/ID of the server
	public $status = '';
	
	public function __construct($id, $server, $status) {
		$this->id = $id;
		$this->server = $server;
		$this->status = $status;
	}
	
	public function __toString() {
		return get_class($this).'(id \''.$this->id.'\' server \''.$this->server.'\' status \''.$this->status.'\' )';
	}
}
