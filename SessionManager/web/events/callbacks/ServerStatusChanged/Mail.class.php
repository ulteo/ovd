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

class ServerStatusChangedMail extends EventCallback {
    public function run () {
		$needs_alert = false;

		$data = get_from_cache('events', 'ServerStatusChanged');

		if ($data == NULL) {
			$data[$this->ev->fqdn] = $this->ev->status;
			if ($this->ev->status == ServerStatusChanged::$OFFLINE)
				$needs_alert = true;
		} else {
			if (isset($data[$this->ev->fqdn]) &&
			  ($data[$this->ev->fqdn] != $this->ev->status)) {
				$data[$this->ev->fqdn] = $this->ev->status;
				$needs_alert = true;
			}
		}

		if ($needs_alert) {
			Logger::debug('main', 'ServerStatusChangedMail: sending alert');
			set_cache($data, 'events', 'ServerStatusChanged');
			if ($this->ev->status == ServerStatusChanged::$OFFLINE)
				$subject = sprintf(_('OVD Alert: %s is offline'), $this->ev->fqdn);
			else
				$subject = sprintf(_('OVD End Alert: %s is up again'), $this->ev->fqdn);

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

