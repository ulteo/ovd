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

require_once(dirname(__FILE__).'/../includes/core.inc.php');

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');

$session = Abstract_Session::load($_GET['session']);
if (! $session)
	die();

$server = Abstract_Server::load($session->server);
if (! $server)
	die();

$servers_node = $dom->createElement('servers');

foreach (array($server) as $server) {
	$server_node = $dom->createElement('server');
	$server_node->setAttribute('fqdn', $server->getAttribute('external_name'));
	$servers_node->appendChild($server_node);
}

$dom->appendChild($servers_node);

$xml = $dom->saveXML();

echo $xml;
