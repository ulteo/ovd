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

require_once(dirname(__FILE__).'/../../../includes/core.inc.php');

class SqlFailureMail extends EventCallback {
    public function run () {
		$needs_alert = false;

		$data = get_from_cache('events', 'SqlFailure');

		if ($data == NULL) {
			$data[$this->ev->host] = $this->ev->status;
			if ($this->ev->status < 0)
				$needs_alert = true;
		} else {
			if (isset($data[$this->ev->host]) &&
			  ($data[$this->ev->host] != $this->ev->status)) {
				$data[$this->ev->host] = $this->ev->status;
				$needs_alert = true;
			}
		}

		if ($needs_alert) {
			set_cache($data, 'events', 'SqlFailure');
			Logger::debug('main', 'SqlFailureMail: sending alert');
			if ($this->ev->status < 0)
				$subject = sprintf(_('OVD Alert: MySQL %s is offline'),
				                   $this->ev->host);
			else
				$subject = sprintf(_('OVD End Alert: MySQL %s is up again'),
				                   $this->ev->host);

			send_alert_mail($subject, '');
		}

		return true;
    }

	public function getDescription() {
		return _("Send an email");
	}

	public function isInternal() {
		return false;
	}
}

