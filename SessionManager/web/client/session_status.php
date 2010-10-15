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
	Logger::error('main', "(client/session_status) return_error($errno_, $errstr_)");
	return $dom->saveXML();
}

if (! array_key_exists('session_id', $_SESSION)) {
	echo return_error(1, 'Usage: missing "session_id" $_SESSION parameter');
	die();
}

if (! Abstract_Session::exists($_SESSION['session_id'])) {
	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument('1.0', 'utf-8');
	$session_node = $dom->createElement('session');
	$session_node->setAttribute('id', $_SESSION['session_id']);
	$session_node->setAttribute('status', 'unknown');
	$dom->appendChild($session_node);

	$xml = $dom->saveXML();

	echo $xml;

	die();
}

$session = Abstract_Session::load($_SESSION['session_id']);
if (! $session) {
	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument('1.0', 'utf-8');
	$session_node = $dom->createElement('session');
	$session_node->setAttribute('id', $_SESSION['session_id']);
	$session_node->setAttribute('status', 'unknown');
	$dom->appendChild($session_node);

	$xml = $dom->saveXML();

	echo $xml;

	die();
}

$session_status = $session->getStatus();
if ($session_status == Session::SESSION_STATUS_CREATED)
	$session_status = Session::SESSION_STATUS_INIT;

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$session_node = $dom->createElement('session');
$session_node->setAttribute('id', $session->id);
$session_node->setAttribute('status', $session_status);
$dom->appendChild($session_node);

$xml = $dom->saveXML();

echo $xml;
die();
