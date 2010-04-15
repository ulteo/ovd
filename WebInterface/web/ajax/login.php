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

$_SESSION = array();

function return_error($errno_, $errstr_) {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	return $dom->saveXML();
}

function query_url_post_xml($url_, $xml_) {
	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($socket, CURLOPT_TIMEOUT, (10+5));

	curl_setopt($socket, CURLOPT_HEADER, 1);
	curl_setopt($socket, CURLOPT_NOBODY, 1);

	$ret = curl_exec($socket);

	preg_match('@Set-Cookie: (.*)=(.*);@', $ret, $matches);
	if (count($matches) != 3)
		return false;

	$_SESSION['sessionmanager'] = array();
	$_SESSION['sessionmanager']['session_var'] = $matches[1];
	$_SESSION['sessionmanager']['session_id'] = $matches[2];

	curl_setopt($socket, CURLOPT_HEADER, 0);
	curl_setopt($socket, CURLOPT_NOBODY, 0);

	curl_setopt($socket, CURLOPT_POSTFIELDS, $xml_);
	curl_setopt($socket, CURLOPT_HTTPHEADER, array('Connection: close', 'Content-Type: text/xml'));
	curl_setopt($socket, CURLOPT_COOKIE, $_SESSION['sessionmanager']['session_var'].'='.$_SESSION['sessionmanager']['session_id']);

	$ret = curl_exec($socket);

	curl_close($socket);

	return $ret;
}

$_SESSION['interface'] = array();
$_SESSION['interface']['debug'] = $_POST['debug'];

header('Content-Type: text/xml; charset=utf-8');

if (! array_key_exists('login', $_POST)) {
	echo return_error(1, 'Usage: missing "login" parameter');
	die();
}

if (! array_key_exists('password', $_POST)) {
	echo return_error(2, 'Usage: missing "password" parameter');
	die();
}

$dom = new DomDocument('1.0', 'utf-8');

$session_node = $dom->createElement('session');
$session_node->setAttribute('mode', $_POST['mode']);
if (array_key_exists('language', $_POST))
	$session_node->setAttribute('language', $_POST['language']);
$user_node = $dom->createElement('user');
$user_node->setAttribute('login', $_POST['login']);
$user_node->setAttribute('password', $_POST['password']);
$session_node->appendChild($user_node);
$dom->appendChild($session_node);

if (! defined('SESSIONMANAGER_URL')) {
	$_SESSION['webinterface']['sessionmanager_url'] = 'http://'.$_POST['sessionmanager_url'].'/sessionmanager/';
	$sessionmanager_url = $_SESSION['webinterface']['sessionmanager_url'];
}

$xml = query_url_post_xml($sessionmanager_url.'/startsession.php', $dom->saveXML());
if (! $xml) {
	echo return_error(0, 'Unable to reach the Session Manager');
	die();
}

$dom = new DomDocument('1.0', 'utf-8');
$buf = @$dom->loadXML($xml);
if (! $buf) {
	echo return_error(0, 'Invalid XML');
	die();
}

if (! $dom->hasChildNodes()) {
	echo return_error(0, 'Invalid XML');
	die();
}

$response_nodes = $dom->getElementsByTagName('response');
if (count($response_nodes) == 1) {
	$response_node = $response_nodes->item(0);

	$response_code = NULL;
	if (! is_null($response_node))
		$response_code = $response_node->getAttribute('code');

	$ret = _('Unknown error');
	switch ($response_code) {
		case 'invalid_user':
			$ret = _('You specified an invalid login, please double-check and try again');
			break;
		case 'unauthorized_session_mode':
			$ret = _('You are not authorized to launch a session with this mode');
			break;
		default:
			$ret = _('Unknown error');
			break;
	}

	echo return_error(-1, $ret);
	die();
}

$session_node = $dom->getElementsByTagName('session');
if (count($session_node) != 1) {
	echo return_error(1, 'Invalid XML: No session node');
	die();
}
$session_node = $session_node->item(0);
if (! is_object($session_node)) {
	echo return_error(1, 'Invalid XML: No session node');
	die();
}
$_SESSION['session_id'] = $session_node->getAttribute('id');
$_SESSION['session_mode'] = $session_node->getAttribute('mode');
$_SESSION['session_language'] = $_POST['language'];
$_SESSION['keyboard_layout'] = $_POST['keymap'];
$_SESSION['multimedia'] = $session_node->getAttribute('multimedia');
$_SESSION['redirect_client_printers'] = $session_node->getAttribute('redirect_client_printers');

$user_node = $session_node->getElementsByTagName('user');
if (count($user_node) != 1) {
	echo return_error(2, 'Invalid XML: No user node');
	die();
}
$user_node = $user_node->item(0);
if (! is_object($user_node)) {
	echo return_error(2, 'Invalid XML: No user node');
	die();
}
$_SESSION['session_displayname'] = $user_node->getAttribute('displayName');

$server_nodes = $session_node->getElementsByTagName('server');
if (count($server_nodes) < 1) {
	echo return_error(3, 'Invalid XML: No server node');
	die();
}

$_SESSION['xml'] = $xml;

echo $_SESSION['xml'];
die();
