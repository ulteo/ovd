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
require_once(dirname(__FILE__).'/../admin/includes/core-minimal.inc.php');

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

exit(0);
