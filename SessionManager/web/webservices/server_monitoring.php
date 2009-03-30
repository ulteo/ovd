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
if (! $server || ! $server->isAuthorized())
	die('Server not authorized');

Logger::debug('main', '(webservices/server_monitoring) Security check OK');

if (! $_FILES['xml']) {
	Logger::error('main', '(webservices/server_monitoring) No XML sent : '.$_POST['fqdn']);
	die('No XML sent');
}

$xml = trim(@file_get_contents($_FILES['xml']['tmp_name']));

$dom = new DomDocument();
$dom->loadXML($xml);

$server_keys = array();

$cpu_node = $dom->getElementsByTagname('cpu')->item(0);
$server_keys['cpu_model'] = $cpu_node->firstChild->nodeValue;
$server_keys['cpu_nb_cores'] = $cpu_node->getAttribute('nb_cores');
$server_keys['cpu_load'] = $cpu_node->getAttribute('load');

$ram_node = $dom->getElementsByTagname('ram')->item(0);
$server_keys['ram_total'] = $ram_node->getAttribute('total');
$server_keys['ram_used'] = $ram_node->getAttribute('used');

foreach ($server_keys as $k => $v)
	$server->setAttribute($k, trim($v));
Abstract_Server::save($server);

if ($server->getAttribute('type') != 'windows') {
	/* session + server history */
	$sql_sessions = get_from_cache ('reports', 'sessids');
	if (! is_array($sql_sessions))
	$sql_sessions = array();

	$sessions = $dom->getElementsByTagname('session');
	$tmp = array();
	foreach ($sessions as $session_node) {
		$token = $session_node->getAttribute('id');
		$tmp[] = $token;

		if (array_key_exists($token, $sql_sessions))
			$sql_sessions[$token]->update($session_node);
	}

	/* cleanup sessions that disappeared */
	foreach ($sql_sessions as $token => $session) {
		if (! in_array($token, $tmp))
			unset($sql_sessions[$token]);
	}

	unset($tmp);
	set_cache($sql_sessions, 'reports', 'sessids');
}

$sr = new ServerReportItem($_POST['fqdn'], $xml);
$sr->save();
