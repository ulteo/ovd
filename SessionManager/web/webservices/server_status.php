<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
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
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	Logger::error('main', "(webservices/server_status) return_error($errno_, $errstr_)");
	return $dom->saveXML();
}

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
	echo return_error(1, 'Server does not send a valid XML');
	die();
}

$server = Abstract_Server::load($ret['name']);
if (! $server) {
	echo return_error(2, 'Server does not exist');
	die();
}

if (! $server->isAuthorized()) {
	echo return_error(3, 'Server is not authorized');
	die();
}

$old_roles = $server->getAttribute('roles');

if ($ret['status'] == Server::SERVER_STATUS_ONLINE) {
	if (! $server->getConfiguration()) {
		echo return_error(4, 'Server does not send a valid configuration');
		die();
	}
}

// check if server's roles have been changes
$new_roles = $server->getAttribute('roles');
foreach ($old_roles as $a_role => $enable) {
	if (array_key_exists($a_role, $new_roles) == false) {
		// the server has not anymore the role
		Abstract_Server::removeRole($server->getAttribute('fqdn'), $a_role);
	}
}

$server->setStatus($ret['status']);
Abstract_Server::save($server);

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$node = $dom->createElement('server');
$node->setAttribute('name', $server->fqdn);
$node->setAttribute('status', $server->status);
$dom->appendChild($node);

echo $dom->saveXML();
exit(0);
