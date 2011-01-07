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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

function return_error($errno_, $errstr_) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	Logger::error('main', "(client/logout) return_error($errno_, $errstr_)");
	return $dom->saveXML();
}

function parse_logout_XML($xml_) {
	if (! $xml_ || strlen($xml_) == 0)
		return false;

	$dom = new DomDocument('1.0', 'utf-8');

	$buf = @$dom->loadXML($xml_);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$node = $dom->getElementsByTagname('logout')->item(0);
	if (is_null($node))
		return false;

	if (! $node->hasAttribute('mode'))
		return false;

	$logout_mode = $node->getAttribute('mode');

	return $logout_mode;
}

if (! array_key_exists('session_id', $_SESSION)) {
	echo return_error(1, 'Usage: missing "session_id" $_SESSION parameter');
	die();
}

$ret = parse_logout_XML(@file_get_contents('php://input'));
if (! $ret)
	return_error(2, 'Client does not send a valid XML');

$session = Abstract_Session::load($_SESSION['session_id']);
if (is_object($session)) {
	if ($ret == 'suspend')
		$session->setStatus(Session::SESSION_STATUS_INACTIVE, Session::SESSION_END_STATUS_LOGOUT);
	else
		$session->setStatus(Session::SESSION_STATUS_WAIT_DESTROY, Session::SESSION_END_STATUS_LOGOUT);
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$logout_node = $dom->createElement('logout');
$logout_node->setAttribute('mode', $ret);
$dom->appendChild($logout_node);

$xml = $dom->saveXML();

echo $xml;
die();
