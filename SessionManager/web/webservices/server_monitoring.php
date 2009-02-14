<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

Logger::debug('main', '(webservices/server_monitoring) Starting webservices/server_monitoring.php');

if (! isset($_POST['fqdn'])) {
	Logger::error('main', '(webservices/server_monitoring) Missing parameter : fqdn');
	die('ERROR - NO $_POST[\'fqdn\']');
}

$server = Abstract_Server::load($_POST['fqdn']);
if (! $server || ! $server->isAuthorized()) {
	Logger::error('main', '(webservices/server_monitoring) Server not authorized : '.$_POST['fqdn'].' == '.@gethostbyname($_POST['fqdn']).' ?');
	die('Server not authorized');
}

Logger::debug('main', '(webservices/server_monitoring) Security check OK');

if (! $_FILES['xml']) {
	Logger::error('main', '(webservices/server_monitoring) No XML sent : '.$_POST['fqdn']);
	die('No XML sent');
}

$xml = trim(@file_get_contents($_FILES['xml']['tmp_name']));

$dom = new DomDocument();
$dom->loadXML($xml);

$server_keys = array();

/* how many times each application is currently used */
$applications_count = array();

$cpu_node = $dom->getElementsByTagname('cpu')->item(0);
$server_keys['cpu_model'] = $cpu_node->firstChild->nodeValue;
$server_keys['cpu_nb_cores'] = $cpu_node->getAttribute('nb_cores');
$server_keys['cpu_load'] = $cpu_node->getAttribute('load');

$ram_node = $dom->getElementsByTagname('ram')->item(0);
$server_keys['ram_total'] = $ram_node->getAttribute('total');
$server_keys['ram_used'] = $ram_node->getAttribute('used');

/* store an array of $desktop -> $id */
$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$mods_enable = $prefs->get('general','module_enable');
if (! in_array('ApplicationDB',$mods_enable))
	die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
$applicationDB = new $mod_app_name();
$all_applications = $applicationDB->getList();

$applications = array();
foreach ($all_applications as $app) {
	$applications[$app->getAttribute('desktopfile')] = $app->getAttribute('id');
}

/* get running apps per session */
$sessions_node = $dom->getElementsByTagName('session');
foreach ($sessions_node as $session_node) {
	$id = $session_node->getAttribute('id');
	$session = Abstract_Session::load($id);

	if (($session === false) || (! $session->isAlive()))
		continue;

	/* reset the applications infos for this session */
	unset($session->applications);
	$session->applications = array();

	foreach ($session_node->childNodes as $user_node) {
		if ($user_node->nodeType != XML_ELEMENT_NODE ||
			$user_node->tagName != 'user')
				continue;

		foreach ($user_node->childNodes as $pid_node) {
			if ($pid_node->nodeType != XML_ELEMENT_NODE ||
				$pid_node->tagName != 'pid')
					continue;

			$pid = $pid_node->getAttribute('id');
			$desktop = $pid_node->getAttribute('desktop');

			$app_id = $applications[$desktop];
			if (! in_array($app_id, $session->applications)) {
				$session->applications[] = $app_id;

				if (! isset($applications_count[$app_id]))
					$applications_count[$app_id] = 0;
				$applications_count[$app_id] +=1;
			}
		}
	}

	Abstract_Session::save($session);
}

foreach ($server_keys as $k => $v)
	$server->setAttribute($k, trim($v));
Abstract_Server::save($server);
