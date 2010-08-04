<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
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

function parse_monitoring_XML($xml_) {
	if (! $xml_ || strlen($xml_) == 0)
		return false;

	$dom = new DomDocument('1.0', 'utf-8');

	$buf = @$dom->loadXML($xml_);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$sessions_node = $dom->getElementsByTagname('sessions')->item(0);
	if (is_null($sessions_node))
		return false;

	if (! $sessions_node->hasAttribute('name'))
		return false;

	$ret = array(
		'server'	=>	$sessions_node->getAttribute('name'),
		'sessions'	=>	array()
	);

	$session_nodes = $dom->getElementsByTagname('session');
	foreach ($session_nodes as $session_node) {
		$ret['sessions'][] = array(
			'id'		=>	$session_node->getAttribute('id'),
			'status'	=>	$session_node->getAttribute('status')
		);
	}

	return $ret;
}

$ret = parse_monitoring_XML(@file_get_contents('php://input'));
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

$server = Abstract_Server::load($ret['server']);
if (! $server) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');

	$node = $dom->createElement('error');
	$node->setAttribute('id', 1);
	$node->setAttribute('message', 'Server does not send a valid XML');
	$dom->appendChild($node);

	echo $dom->saveXML();
	exit(1);
}

if (! $server->isAuthorized()) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');

	$node = $dom->createElement('error');
	$node->setAttribute('id', 2);
	$node->setAttribute('message', 'Server not authorized');
	$dom->appendChild($node);

	echo $dom->saveXML();
	exit(2);
}

foreach ($ret['sessions'] as $session) {
	$buf = Abstract_Session::load($session['id']);
	if (! $buf)
		continue;

	$buf->setStatus($session['status']);

	Abstract_Session::save($buf);
}
