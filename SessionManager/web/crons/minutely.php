<?php
/**
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2012
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
require_once(dirname(__FILE__).'/../admin/includes/core-minimal.inc.php');

if (Preferences::fileExists() === false)
	exit(1);

//BEGIN Sessions expiration
$sessions = Abstract_Session::load_all();
foreach ($sessions as $session) {
	if (! Abstract_Session::exists($session->id)) // avoid operation on an already deleted Session (parallel processing)
		continue;

	if ($session->start_time != 0 && array_key_exists('timeout', $session->settings) && $session->settings['timeout'] > 0) {
		if ($session->start_time+$session->settings['timeout'] < time()) {
			Logger::info('main', '(minutely cron) Session \''.$session->id.'\' has expired, ending...');
			$session->orderDeletion(true, Session::SESSION_END_STATUS_TIMEOUT);
		}
	}

	if (in_array($session->status, array(Session::SESSION_STATUS_CREATING, Session::SESSION_STATUS_CREATED, Session::SESSION_STATUS_INIT, Session::SESSION_STATUS_READY))) {
		if ($session->start_time < time()-DEFAULT_UNUSED_SESSION_DURATION) {
			Logger::info('main', '(minutely cron) Session \''.$session->id.'\' was never used, ending...');
			$session->orderDeletion(true, Session::SESSION_END_STATUS_UNUSED);
		}
	}

	if (in_array($session->status, array(Session::SESSION_STATUS_ACTIVE))) {
		if (! $session->isAlive()) {
			Logger::info('main', '(minutely cron) Session \''.$session->id.'\' does not exist anymore, purging...');
			$session->orderDeletion(false, Session::SESSION_END_STATUS_ERROR);
		}
	}

	if ($session->status == Session::SESSION_STATUS_DESTROYED) {
		if (! Abstract_Session::uptodate($session)) {
			Logger::info('main', '(minutely cron) Session \''.$session->id.'\' does not exist anymore, purging...');
			$session->orderDeletion(false, Session::SESSION_END_STATUS_ERROR);
			Abstract_Session::delete($session->id);
		}
	}
	
	if (in_array($session->status, array(Session::SESSION_STATUS_WAIT_DESTROY, Session::SESSION_STATUS_DESTROYING))) {
		if (array_key_exists('stop_time', $session->settings) && ($session->settings['stop_time'] + DESTROYING_DURATION) < time()) {
			Logger::info('main', '(minutely cron) Session \''.$session->id.'\' do not respond, purging...');
			$session->orderDeletion(false, Session::SESSION_END_STATUS_ERROR);
			Abstract_Session::delete($session->id);
		}
	}
}
//END Sessions expiration

exit(0);
