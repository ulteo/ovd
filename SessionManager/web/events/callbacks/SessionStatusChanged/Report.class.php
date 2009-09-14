<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com>
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

/* This is an exemple for now, real implementation needs to be done */

require_once(dirname(__FILE__).'/../../../includes/core.inc.php');

class SessionStatusChangedReport extends EventCallback {
    public function run () {
		switch ($this->ev->status) {
			/* session starts */
			case 2:
				$token = $this->ev->id;
				$sql_sessions = get_from_cache ('reports', 'sessids');
				if (! is_array ($sql_sessions))
					$sql_session = array ();
				if (! array_key_exists($token, $sql_sessions)) {
					$sessitem = new SessionReportItem($token);
					if ($sessitem->getId() >= 0) {
						$sql_sessions[$token] = $sessitem;
					}
					set_cache($sql_sessions, 'reports', 'sessids');
				}
				break;

			/* session ended */
			case 4:
				$token = $this->ev->id;
				$sql_sessions = get_from_cache ('reports', 'sessids');
				if (! is_array($sql_sessions) ||
					! array_key_exists($token, $sql_sessions))
						return true;

				$sessitem = $sql_sessions[$token];
				$sessitem->end();
				unset($sql_sessions[$token]);

				return true;

			default:
				return true;
		}
    }

	public function getDescription() {
		return "";
	}

	public function isInternal() {
		return true;
	}
}

