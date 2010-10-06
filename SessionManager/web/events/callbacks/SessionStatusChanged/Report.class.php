<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com> 2009
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
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

class SessionStatusChangedReport extends EventCallback {
    public function run () {
		Logger::critical('main', 'SessionStatusChangedReport::run '.serialize($this->ev));
		switch ($this->ev->status) {
			/* session starts */
			case Session::SESSION_STATUS_READY:
				Logger::critical('main', 'SessionStatusChangedReport::run inited 00');
				$token = $this->ev->id;
				Logger::critical('main', 'SessionStatusChangedReport::run inited 04');
				$sql_sessions = get_from_cache ('reports', 'sessids');
				Logger::critical('main', 'SessionStatusChangedReport::run inited 07');
				if (! is_array ($sql_sessions))
					$sql_sessions = array ();
				Logger::critical('main', 'SessionStatusChangedReport::run inited 10');
				if (! array_key_exists($token, $sql_sessions)) {
					Logger::critical('main', 'SessionStatusChangedReport::run inited 20 array_key_exists($token, $sql_sessions) -> faux');
					$buf532 = Abstract_ReportSession::exists($token);
					Logger::critical('main', 'SessionStatusChangedReport::run inited 30');
					Logger::critical('main', 'SessionStatusChangedReport::run $buf532 '.serialize($buf532));
					if ($buf532 == true) {
						Logger::critical('main', 'SessionStatusChangedReport::run inited 40 $buf532 is true');
						$sessitem = Abstract_ReportSession::load($token);
					}
					else {
						Logger::critical('main', 'SessionStatusChangedReport::run inited 50');
						if (! Abstract_Session::exists($token)) {
							Logger::critical('main', 'SessionStatusChangedReport::run inited 60 Abstract_Session::exists($token) -> faux');
							Logger::error('main', "SessionStatusChangedReport::run failed to load session '$token'");
							return false;
						}
						Logger::critical('main', 'SessionStatusChangedReport::run inited 80');
						$sessitem = new SessionReportItem($token);
						$ret = Abstract_ReportSession::create($sessitem);
						Logger::critical('main', 'SessionStatusChangedReport::run inited 87');
						if (! $ret) {
							Logger::critical('main', 'SessionStatusChangedReport::run inited 90 Abstract_ReportSession::create -> problem');
							Logger::error('main', "SessionStatusChangedReport::run failed to save SessionReportItem($token)");
							return false;
						}
						Logger::critical('main', 'SessionStatusChangedReport::run inited 95');
					}
					Logger::critical('main', 'SessionStatusChangedReport::run inited 100');
					if ($sessitem->getId() >= 0) {
						$sql_sessions[$token] = $sessitem;
					}
					Logger::critical('main', 'SessionStatusChangedReport::run inited 110');
					set_cache($sql_sessions, 'reports', 'sessids');
					Logger::critical('main', 'SessionStatusChangedReport::run inited 120');
				}
				break;

			/* session ended */
			case Session::SESSION_STATUS_DESTROYED:
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

