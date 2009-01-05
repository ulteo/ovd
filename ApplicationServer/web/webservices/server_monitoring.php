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

$cpu_model = trim(`grep "model name" /proc/cpuinfo |head -n 1 |sed -e 's/.*: //'`);
$cpu_nb = trim(`grep processor /proc/cpuinfo |tail -n 1 |awk '{ print $3 }'`)+1;
$cpu_load = trim(shell_exec('python '.CHROOT.'/usr/bin/cpu_load.py'));
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
