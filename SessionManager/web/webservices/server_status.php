<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008
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

function parse_server_status_XML($xml_) {
	if (! $xml_ || strlen($xml_) == 0)
		return false;

	$dom = new DomDocument('1.0', 'utf-8');

	$buf = @$dom->loadXML($xml_);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$node = $dom->getElementsByTagname('server')->item(0);
	if (is_null($node))
		return false;

	if (! $node->hasAttribute('name'))
		return false;

	if (! $node->hasAttribute('status'))
		return false;

	return array(
		'name'		=>	$node->getAttribute('name'),
		'status'	=>	$node->getAttribute('status')
	);
}

$ret = parse_server_status_XML(@file_get_contents('php://input'));
if (! $ret) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');

	$node = $dom->createElement('error');
	$node->setAttribute('id', 1);
	$node->setAttribute('message', 'Server does not send a valid XML');
	$dom->appendChild($node);

	echo $dom->saveXML();
	exit(1);
}

$server = Abstract_Server::load($ret['name']);
if (! $server) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');

	$node = $dom->createElement('error');
	$node->setAttribute('id', 2);
	$node->setAttribute('message', 'Server does not exist');
	$dom->appendChild($node);

	echo $dom->saveXML();
	exit(2);
}

if (! $server->isAuthorized()) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');

	$node = $dom->createElement('error');
	$node->setAttribute('id', 3);
	$node->setAttribute('message', 'Server is not authorized');
	$dom->appendChild($node);

	echo $dom->saveXML();
	exit(3);
}

$server->setStatus($ret['status']);
if ($server->isOnline()) {
	if (! $server->getConfiguration()) {
		header('Content-Type: text/xml; charset=utf-8');
		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('error');
		$node->setAttribute('id', 4);
		$node->setAttribute('message', 'Server does not send a valid configuration');
		$dom->appendChild($node);

		echo $dom->saveXML();
		exit(4);
	}
}
Abstract_Server::save($server);

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$node = $dom->createElement('server');
$node->setAttribute('name', $server->fqdn);
$node->setAttribute('status', $server->status);
$dom->appendChild($node);

echo $dom->saveXML();
exit(0);
