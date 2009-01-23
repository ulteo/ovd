<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
//if (stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml'))
//	header('Content-Type: application/xhtml+xml; charset=utf-8');
//else
	header('Content-Type: text/html; charset=utf-8');

mb_internal_encoding('UTF-8');

require_once(dirname(__FILE__).'/core-minimal.inc.php');

$folders = array('servers', 'sessions', 'tokens', 'locks', 'tasks', 'reporting', 'tmp', 'cache');
foreach ($folders as $folder) {
	$buf = strtoupper($folder).'_DIR';
	define($buf, SESSIONMANAGER_SPOOL.'/'.$folder);

	if (! check_folder(constant($buf))) {
		Logger::critical('main', constant($buf).' does not exist and cannot be created !');
		die_error(constant($buf).' does not exist and cannot be created !',__FILE__,__LINE__);
	}
}

$prefs = Preferences::getInstance();
if (is_object($prefs)) {
	define('HAS_PREFERENCES', true);
	$mysql_conf = $prefs->get('general', 'mysql');
	if (is_array($mysql_conf)) {
		define('APPSGROUP_TABLE', $mysql_conf['prefix'].'gapplication');
		define('LIAISON_APPS_GROUP_TABLE', $mysql_conf['prefix'].'apps_group_link');
		define('USERSGROUP_TABLE', $mysql_conf['prefix'].'usergroup');
		define('LIAISON_USERS_GROUP_TABLE', $mysql_conf['prefix'].'users_group_link');
		define('USERSGROUP_APPLICATIONSGROUP_LIAISON_TABLE', $mysql_conf['prefix'].'ug_ag_link');
		define('LIAISON_APPLICATION_SERVER_TABLE', $mysql_conf['prefix'].'application_server_link');
		define('SOURCES_LIST_TABLE', $mysql_conf['prefix'].'sources_list');
		define('LIAISON_SERVERSESSION_TABLE', $mysql_conf['prefix'].'server_session_link');
		MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);
	}
}
