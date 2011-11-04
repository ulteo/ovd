<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
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

require_once(dirname(__FILE__).'/includes/core.inc.php');

function return_error($errno_, $errstr_) {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	return $dom->saveXML();
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$buf = @$dom->loadXML($_SESSION['ovd-client']['xml']);
if (! $buf) {
	echo return_error(0, 'Invalid XML');
	die();
}

if (! $dom->hasChildNodes()) {
	echo return_error(0, 'Invalid XML');
	die();
}

$server_nodes = $dom->getElementsByTagName('server');
foreach ($server_nodes as $server_node) {
	$port = 3389;

	if (array_key_exists('gateway', $_SESSION['ovd-client']) && $_SESSION['ovd-client']['gateway'] === true && array_key_exists('gateway_first', $_SESSION['ovd-client'])) {
		if ($_SESSION['ovd-client']['gateway_first'] === true)
			$url = 'http://'.$_POST['requested_host'].':'.$_POST['requested_port'];
		elseif ($_SESSION['ovd-client']['gateway_first'] === false)
			$url = 'http://'.$_SESSION['ovd-client']['sessionmanager_host'];

		$host = parse_url($url, PHP_URL_HOST);
		$port = parse_url($url, PHP_URL_PORT);
		if (is_null($port))
			$port = 443;

		$server_node->setAttribute('fqdn', $host);
	}
	
	$server_node->setAttribute('port', $port);
}

$xml = $dom->saveXML();

echo $xml;
die();
