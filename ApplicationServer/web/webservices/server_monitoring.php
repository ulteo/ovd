<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

Logger::debug('main', 'Starting webservices/server_monitoring.php');

if (! isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager');
}

function get_cpu_load() {
	$buf = file_get_contents('/proc/stat');
	$buf = explode("\n", $buf, 2);
	$buf = $buf[0];

	$infos = explode(' ', $buf);
	array_shift($infos);
	array_shift($infos);
	array_pop($infos);
	$data_str_old = $infos;

	sleep(1);

	$buf = file_get_contents('/proc/stat');
	$buf = explode("\n", $buf, 2);
	$buf = $buf[0];

	$infos = explode(' ', $buf);
	array_shift($infos);
	array_shift($infos);
	array_pop($infos);
	$data_str = $infos;

	$data = array();
	foreach($data_str as $elem)
		$data[]= intval($elem);

	$data_old = array();
	foreach($data_str_old as $elem)
		$data_old[]= intval($elem);

	$used = $data[0] + $data[1] + $data[2];  // user + nice + system
	$total =  $used + $data[3]; // used + idle
	$used_old = $data_old[0] + $data_old[1] + $data_old[2]; // user + nice + system
	$total_old =  $used_old + $data_old[3]; // used + idle

	if ($total == $total_old)
		return 0;

	return ($used - $used_old)  / ($total - $total_old);
}

$cpu_model = trim(`grep "model name" /proc/cpuinfo |head -n 1 |sed -e 's/.*: //'`);
$cpu_nb = trim(`grep processor /proc/cpuinfo |tail -n 1 |awk '{ print $3 }'`)+1;
$cpu_load = get_cpu_load();
$ram=trim(`grep MemTotal: /proc/meminfo |tr -s ' '|cut -d ' ' -f2`);
$ram_Buffers=trim(`grep Buffers: /proc/meminfo |tr -s ' '|cut -d ' ' -f2`);
$ram_Cached=trim(`grep Cached: /proc/meminfo |tr -s ' '|cut -d ' ' -f2`);
$ram_used = $ram - $ram_Buffers - $ram_Cached;


$dom = new DomDocument();
$monitoring_node = $dom->createElement('monitoring');
$dom->appendChild($monitoring_node);

$cpu_node = $dom->createElement('cpu');
$cpu_node->setAttribute('nb_cores', $cpu_nb);
$cpu_node->setAttribute('load', $cpu_load);
$cpu_textnode = $dom->createTextNode($cpu_model);
$cpu_node->appendChild($cpu_textnode);
$monitoring_node->appendChild($cpu_node);

$ram_node = $dom->createElement('ram');
$ram_node->setAttribute('total', $ram);
$ram_node->setAttribute('used', $ram_used);
$monitoring_node->appendChild($ram_node);

$xml = $dom->saveXML();

header('Content-Type: text/xml; charset=utf-8');

echo $xml;
