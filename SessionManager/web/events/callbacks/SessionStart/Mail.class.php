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

class SessionStartMail extends EventCallback {
    public function run () {
		$needs_alert = false;

		$data = get_from_cache('events', 'SessionStart');

		/* if the session didn't start we store the information; admin will
		 * receive the alert only once. */
		if ($data == NULL)
			$data = array();

		$user = $this->ev->user->getAttribute('login');
		if (isset($data[$user]) && $this->ev->ok)
			unset($data[$user]);

		if (! isset($data[$user]) && (! $this->ev->ok)) {
			$needs_alert = true;
			$data[$user] = false;
		}

		set_cache($data, 'events', 'SessionStart');

		if ($needs_alert) {
			Logger::debug('main', 'SessionStartMail: sending alert');

			$subject = sprintf(_('OVD Session alert: %s couldn\'t log in'),
			                   $user);

			if (isset($this->ev->error))
				$message = _("The following error happened:\n").$this->ev->error;
			else
				$message = _('No error given');

			send_alert_mail($subject, $message);
		}

		return true;
    }

	public function getDescription() {
		return _("Send an email on login problem");
	}

	public function isInternal() {
		return false;
	}
}

