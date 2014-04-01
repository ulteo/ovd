<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

if (Preferences::fileExists() === false)
	exit(1);

//BEGIN Updating logs cache
$servers = Abstract_Server::load_all();
foreach ($servers as $server) {
	$buf = new Server_Logs($server);
	$buf->process();
}
//END Updating logs cache

//BEGIN UserGroupDBDynamic_cached update
if (Preferences::moduleIsEnabled('UserGroupDBDynamicCached')) {
	$ugdbdc = UserGroupDBDynamicCached::getInstance();

	$groups = $ugdbdc->getList();
	if (! is_array($groups)) {
		Logger::error('main', '(hourly cron) UserGroupDBDynamic_cached->getList() failed');
	}
	else {
		foreach ($groups as $a_group) {
			$ret = $ugdbdc->updateCache($a_group);
			if ($ret !== true)
				Logger::error('main', '(hourly cron) UserGroupDBDynamic_cached->updateCache for group \''.$a_group->getUniqueID().'\' failed');
		}
	}
}
//END UserGroupDBDynamic_cached update

//BEGIN Sessions time restriction
$sessions = Abstract_Session::load_all();
foreach ($sessions as $session) {
	if (! Abstract_Session::exists($session->id)) {// avoid operation on an already deleted Session (parallel processing)
		continue;
	}
	
	if (! in_array($session->status, array(
			Session::SESSION_STATUS_CREATING,
			Session::SESSION_STATUS_CREATED,
			Session::SESSION_STATUS_INIT,
			Session::SESSION_STATUS_READY,
			Session::SESSION_STATUS_ACTIVE))) {
		continue;
	}
	
	$userDB = UserDB::getInstance();
	$user = $userDB->import($session->user_login);
	if (! is_object($user)) {
		// ???
		continue;
	}
	
	if ($user->can_use_session()) {
		continue;
	}
	
	Logger::info('main', '(hourly cron) User '.$user->getAttribute('login').' order session disconnect because of time restriction policy');
	if ($session->settings['persistent'] == 1) {
		$session->orderDisonnect();
	}
	else {
		$session->orderDeletion(true, Session::SESSION_END_STATUS_TIMEOUT);
	}
}
//END Sessions time restriction

exit(0);
