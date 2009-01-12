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

if (!check_ip($_POST['fqdn'])) {
	Logger::error('main', 'Server not authorized : '.$_POST['fqdn'].' ? '.@gethostbyname($_POST['fqdn']));
	die('Server not authorized');
}

Logger::debug('main', '(webservices/server_monitoring) Security check OK');

if (!$_FILES['xml']) {
	Logger::error('main', '(webservices/server_monitoring) No XML sent : '.$_POST['fqdn']);
	die('No XML sent');
}

$xml = trim(@file_get_contents($_FILES['xml']['tmp_name']));

$dom = new DomDocument();
$dom->loadXML($xml);

$keys = array();

$cpu_node = $dom->getElementsByTagname('cpu')->item(0);
$keys['cpu_model'] = $cpu_node->firstChild->nodeValue;
$keys['cpu_nb'] = $cpu_node->getAttribute('nb_cores');
$keys['cpu_load'] = $cpu_node->getAttribute('load');

$ram_node = $dom->getElementsByTagname('ram')->item(0);
$keys['ram'] = $ram_node->getAttribute('total');
$keys['ram_used'] = $ram_node->getAttribute('used');

$server = new Server($_POST['fqdn']);
foreach ($keys as $k => $v)
	$server->setAttribute($k, trim($v));
