<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2010
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
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
require_once(dirname(__FILE__).'/../includes/webservices.inc.php');

function parse_session_status_XML($xml_) {
	if (! $xml_ || strlen($xml_) == 0)
		return false;

	$dom = new DomDocument('1.0', 'utf-8');

	$buf = @$dom->loadXML($xml_);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$node = $dom->getElementsByTagname('session')->item(0);
	if (is_null($node))
		return false;

	if (! $node->hasAttribute('id'))
		return false;

	if (! $node->hasAttribute('status'))
		return false;

	$ret = array(
		'id'		=>	$node->getAttribute('id'),
		'server'	=>	$_SERVER['REMOTE_ADDR'],
		'status'	=>	$node->getAttribute('status'),
		'reason'	=>	NULL
	);

	if ($node->hasAttribute('reason'))
		$ret['reason'] = $node->getAttribute('reason');

	return $ret;
}

$ret = parse_session_status_XML(@file_get_contents('php://input'));
if (! $ret) {
	Logger::error('main', '(webservices/session/status) Server does not send a valid XML (error_code: 1)');
	webservices_return_error(1, 'Server does not send a valid XML');
}

$server = webservices_load_server($_SERVER['REMOTE_ADDR']);
if (is_null($server)) {
	Logger::error('main', '(webservices/session/status) Server does not exist (error_code: 2)');
	webservices_return_error(2, 'Server does not exist');
}

$session = Abstract_Session::load($ret['id']);
if (! $session) {
	Logger::error('main', '(webservices/session/status) Session does not exist (error_code: 2)');
	webservices_return_error(2, 'Session does not exist');
}

$session->setServerStatus($server->id, $ret['status'], $ret['reason']);

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$node = $dom->createElement('session');
$node->setAttribute('id', $session->id);
$node->setAttribute('status', $session->status);
$dom->appendChild($node);

echo $dom->saveXML();
exit(0);
