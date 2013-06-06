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

class Server {
	const SERVER_ROLE_APS = "aps";
	const SERVER_ROLE_FS = "fs";
	const SERVER_TYPE_LINUX = "linux";
	const SERVER_TYPE_WINDOWS = "windows";

	const SERVER_STATUS_PENDING = "pending";
	const SERVER_STATUS_ONLINE = "ready";
	const SERVER_STATUS_OFFLINE = "down";
	const SERVER_STATUS_BROKEN = "broken";
	
	const DEFAULT_RDP_PORT = 3389;

	public $fqdn = NULL;

	public $status = NULL;
	public $registered = NULL;
	public $locked = NULL;
	public $type = NULL;
	public $version = NULL;
	public $cpu_model = NULL;
	public $cpu_nb_cores = NULL;
	public $cpu_load = NULL;
	public $ram_total = NULL;
	public $ram_used = NULL;

	public $roles = array();
	public $roles_disabled = array();

	public function __construct($id_) {
		$this->id = $id_;
	}

	public function hasAttribute($attrib_) {
		if (! isset($this->$attrib_) || is_null($this->$attrib_))
			return false;

		return true;
	}

	public function getAttribute($attrib_) {
		if (! $this->hasAttribute($attrib_))
			return false;

		return $this->$attrib_;
	}

	public function setAttribute($attrib_, $value_) {
		$this->$attrib_ = $value_;

		return true;
	}

	public function isOnline() {
		if ($this->getAttribute('status') != 'ready') {
			return false;
		}

		return true;
	}

	public function stringStatus() {
		$string = '';

		if ($this->getAttribute('locked'))
			$string .= '<span class="msg_unknown">'._('Under maintenance').'</span> ';

		$buf = $this->getAttribute('status');
		if ($buf == 'pending')
			$string .= '<span class="msg_warn">'._('Pending').'</span>';
		elseif ($buf == 'ready')
			$string .= '<span class="msg_ok">'._('Online').'</span>';
		elseif ($buf == 'down')
			$string .= '<span class="msg_warn">'._('Offline').'</span>';
		elseif ($buf == 'broken')
			$string .= '<span class="msg_error">'._('Broken').'</span>';
		else
			$string .= '<span class="msg_other">'._('Unknown').'</span>';

		return $string;
	}

	public function stringType() {
		if ($this->hasAttribute('type'))
			return $this->getAttribute('type');

		return _('Unknown');
	}

	public function stringVersion() {
		if ($this->hasAttribute('version'))
			return $this->getAttribute('version');

		return _('Unknown');
	}

	public function getCpuUsage() {
		$cpu_load = $this->getAttribute('cpu_load');
		$cpu_nb_cores = $this->getAttribute('cpu_nb_cores');

		if ($cpu_nb_cores == 0)
			return false;

		return round(($cpu_load/$cpu_nb_cores)*100);
	}

	public function getRamUsage() {
		$ram_used = $this->getAttribute('ram_used');
		$ram_total = $this->getAttribute('ram_total');

		if ($ram_total == 0)
			return false;

		return round(($ram_used/$ram_total)*100);
	}

	public function getSessionUsage() {
		if (! $this->hasAttribute('max_sessions')) {
			return 0;
		}
		
		$max_sessions = $this->getAttribute('max_sessions');
		$used_sessions = $this->getAttribute('sessions_number');

		if ($max_sessions == 0)
			return false;

		return round(($used_sessions/$max_sessions)*100);
	}

	public function getDiskUsage() {
		$total_disk = (float)($this->getAttribute('disk_total'));
		$free_disk = (float)($this->getAttribute('disk_free'));

		if ($total_disk == 0)
			return false;

		return round((($total_disk-$free_disk)/$total_disk)*100);
	}
	
	public function getApSRDPPort() {
		if ($this->hasAttribute('rdp_port') && ! is_null($this->getAttribute('rdp_port')))
			return $this->getAttribute('rdp_port');
		
		return self::DEFAULT_RDP_PORT;
	}
	
	public function getDisplayName() {
		if ($this->hasAttribute('display_name') && ! is_null($this->getAttribute('display_name')))
			return $this->getAttribute('display_name');
		
		return $this->getExternalName();
	}
	
	public function getExternalName() {
		if ($this->hasAttribute('external_name'))
			return $this->getAttribute('external_name');
		
		return $this->fqdn;
	}
}
