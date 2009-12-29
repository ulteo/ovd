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
	$fd = @fopen('/proc/stat', 'r');
	if (!$fd)
		return false;
	fscanf($fd, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
	fclose($fd);
	$load = $ab + $ac + $ad;        // cpu.user + cpu.sys
	$total = $ab + $ac + $ad + $ae; // cpu.total

	sleep(1);

	$fd = @fopen('/proc/stat', 'r');
	if (!$fd)
		return false;
	fscanf($fd, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
	fclose($fd);
	$load2 = $ab + $ac + $ad;        // cpu.user + cpu.sys
	$total2 = $ab + $ac + $ad + $ae; // cpu.total

	if ($total-$total2 == 0)
		return 0;

	return (($load-$load2)/($total-$total2));
}

function get_ram_load() {
	$fd = @fopen('/proc/meminfo', 'r');
	if (!$fd)
		return false;

	while ($buf = fgets($fd, 4096)) {
		if (preg_match('/^MemTotal:\s+(.*)\s*kB/i', $buf, $ar_buf))
			$results['ram']['total'] = $ar_buf[1];
		else if (preg_match('/^MemFree:\s+(.*)\s*kB/i', $buf, $ar_buf))
			$results['ram']['t_free'] = $ar_buf[1];
		else if (preg_match('/^Cached:\s+(.*)\s*kB/i', $buf, $ar_buf))
			$results['ram']['cached'] = $ar_buf[1];
		else if (preg_match('/^Buffers:\s+(.*)\s*kB/i', $buf, $ar_buf))
			$results['ram']['buffers'] = $ar_buf[1];
	}
	fclose($fd);

	$results['ram']['t_free'] = $results['ram']['t_free'] + ($results['ram']['buffers'] + $results['ram']['cached']);
	$results['ram']['t_used'] = $results['ram']['total'] - $results['ram']['t_free'];
	$results['ram']['percent'] = round(($results['ram']['t_used'] * 100) / $results['ram']['total']);

	return $results['ram'];
}

$cpu_model = trim(`grep "model name" /proc/cpuinfo |head -n 1 |sed -e 's/.*: //'`);
$cpu_nb = trim(`grep processor /proc/cpuinfo |tail -n 1 |awk '{ print $3 }'`)+1;
$cpu_load = get_cpu_load();
$ram = get_ram_load();

$dom = new DomDocument('1.0', 'utf-8');
$monitoring_node = $dom->createElement('monitoring');
$dom->appendChild($monitoring_node);

$cpu_node = $dom->createElement('cpu');
$cpu_node->setAttribute('nb_cores', $cpu_nb);
$cpu_node->setAttribute('load', $cpu_load);
$cpu_textnode = $dom->createTextNode($cpu_model);
$cpu_node->appendChild($cpu_textnode);
$monitoring_node->appendChild($cpu_node);

$ram_node = $dom->createElement('ram');
$ram_node->setAttribute('total', $ram['total']);
$ram_node->setAttribute('used', $ram['t_used']);
$monitoring_node->appendChild($ram_node);

$xml = $dom->saveXML();

header('Content-Type: text/xml; charset=utf-8');

echo $xml;
