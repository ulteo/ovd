<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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
require_once(dirname(__FILE__).'/../../../includes/core.inc.php');

class ServerStatusChangedTask extends EventCallback {
	public function run () {
		Logger::debug('main', 'ServerStatusChangedTask::run');
		$needs_cleanup = false;
		$data = get_from_cache('events', 'ServerStatusChanged');
		
		if ($data == NULL) {
			$data[$this->ev->fqdn] = $this->ev->status;
			if ($this->ev->status != ServerStatusChanged::$ONLINE)
				$needs_cleanup = true;
		} else {
			if ($this->ev->status != ServerStatusChanged::$ONLINE) {
				$needs_cleanup = true;
			}
		}
		
		if ($needs_cleanup) {
			Logger::debug('main', 'ServerStatusChangedTask::run cleanup task for '.$this->ev->fqdn);
			set_cache($data, 'events', 'ServerStatusChanged');
			$tm = new Tasks_Manager();
			$tm->load_from_server($this->ev->fqdn);
			foreach ($tm->tasks as $a_task) {
				$tm->remove($a_task->id);
			}
		}
		
		return true;
    }
	
	public function getDescription() {
		return _("Clean up tasks");
	}
	
	public function isInternal() {
		return true;
	}
}
