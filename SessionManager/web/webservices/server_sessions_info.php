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

function return_error($errno_, $errstr_) {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	Logger::error('main', "(webservices/server_sessions_info) return_error($errno_, $errstr_)");
	return $dom->saveXML();
}

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
		$ret['sessions'][$session_node->getAttribute('id')] = array(
			'id'		=>	$session_node->getAttribute('id'),
			'status'	=>	$session_node->getAttribute('status'),
			'instances'	=>	array()
		);

		$childnodes = $session_node->childNodes;
		foreach ($childnodes as $childnode) {
			if ($childnode->nodeName != 'instance')
				continue;

			$ret['sessions'][$session_node->getAttribute('id')]['instances'][$childnode->getAttribute('id')] = $childnode->getAttribute('application');
		}
	}

	return $ret;
}

$ret = parse_monitoring_XML(@file_get_contents('php://input'));
if (! $ret) {
	echo return_error(1, 'Server does not send a valid XML');
	die();
}

$server = Abstract_Server::load($ret['server']);
if (! $server) {
	echo return_error(2, 'Server does not exist');
	die();
}

if (! $server->isAuthorized()) {
	echo return_error(3, 'Server is not authorized');
	die();
}

foreach ($ret['sessions'] as $session) {
	$buf = Abstract_Session::load($session['id']);
	if (! $buf)
		continue;

	$buf->setStatus($session['status']);
	$buf->setRunningApplications($ret['server'], $session['instances']);

	Abstract_Session::save($buf);
}
