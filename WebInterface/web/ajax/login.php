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

function return_error($errno_, $errstr_) {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	return $dom->saveXML();
}

function query_sm_start($url_, $xml_) {
	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($socket, CURLOPT_TIMEOUT, (10+5));

	curl_setopt($socket, CURLOPT_HEADER, 1);
	curl_setopt($socket, CURLOPT_NOBODY, 1);

	$ret = curl_exec($socket);
	curl_close($socket);

	preg_match('@Set-Cookie: (.*)=(.*);@', $ret, $matches);
	if (count($matches) != 3)
		return false;

	$_SESSION['sessionmanager'] = array();
	$_SESSION['sessionmanager']['session_var'] = $matches[1];
	$_SESSION['sessionmanager']['session_id'] = $matches[2];

	return query_sm_post_xml($url_, $xml_);
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

if (! defined('SESSIONMANAGER_HOST')) {
	$_SESSION['webinterface']['sessionmanager_url'] = 'https://'.$_POST['sessionmanager_host'].'/ovd/client/';
	$sessionmanager_url = $_SESSION['webinterface']['sessionmanager_url'];
}

$xml = query_sm_start($sessionmanager_url.'/start.php', $dom->saveXML());
if (! $xml) {
	echo return_error(0, 'Unable to reach the Session Manager');
	die();
}

$dom = new DomDocument('1.0', 'utf-8');
$buf = @$dom->loadXML($xml);
if (! $buf) {
	echo return_error(0, 'An internal error occured, please contact your administrator');
	die();
}

if (! $dom->hasChildNodes()) {
	echo return_error(0, 'An internal error occured, please contact your administrator');
	die();
}

$response_node = $dom->getElementsByTagName('response')->item(0);
if (! is_null($response_node)) {
	$response_code = $response_node->getAttribute('code');

	switch ($response_code) {
		case 'auth_failed':
			$ret = _('Authentication failed, please double-check your password and try again');
			break;
		case 'in_maintenance':
			$ret = _('The system is in maintenance mode, please contact your administrator for more information');
			break;
		case 'internal_error':
			$ret = _('An internal error occured, please contact your administrator');
			break;
		case 'invalid_user':
			$ret = _('You specified an invalid login, please double-check and try again');
			break;
		case 'service_not_available':
			$ret = _('The service is not available, please contact your administrator for more information');
			break;
		case 'unauthorized_session_mode':
			$ret = _('You are not authorized to launch a session in this mode');
			break;
		case 'user_with_active_session':
			$ret = _('You already have an active session');
			break;
		default:
			$ret = _('An error occured, please contact your administrator');
			break;
	}

	echo return_error(-1, $ret);
	die();
}

$session_node = $dom->getElementsByTagName('session');
if (count($session_node) != 1) {
	echo return_error(1, 'An internal error occured, please contact your administrator');
	die();
}
$session_node = $session_node->item(0);
if (! is_object($session_node)) {
	echo return_error(1, 'An internal error occured, please contact your administrator');
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
	echo return_error(2, 'An internal error occured, please contact your administrator');
	die();
}
$user_node = $user_node->item(0);
if (! is_object($user_node)) {
	echo return_error(2, 'An internal error occured, please contact your administrator');
	die();
}
$_SESSION['session_displayname'] = $user_node->getAttribute('displayName');

$server_nodes = $session_node->getElementsByTagName('server');
if (count($server_nodes) < 1) {
	echo return_error(3, 'An internal error occured, please contact your administrator');
	die();
}

$_SESSION['explorer'] = false;
$profile_node = $session_node->getElementsByTagName('profile')->item(0);
if (is_object($profile_node)) {
	$_SESSION['explorer'] = true;
	$_SESSION['profile'] = array();
	$_SESSION['profile']['server'] = $profile_node->getAttribute('server');
	$_SESSION['profile']['dir'] = $profile_node->getAttribute('dir');
	$_SESSION['profile']['login'] = $profile_node->getAttribute('login');
	$_SESSION['profile']['password'] = $profile_node->getAttribute('password');
}

$_SESSION['xml'] = $xml;

setcookie('webinterface[sessionmanager_host]', $_POST['sessionmanager_host'], (time()+(60*60*24*7)));
setcookie('webinterface[user_login]', $_POST['login'], (time()+(60*60*24*7)));
setcookie('webinterface[use_local_credentials]', 0, (time()+(60*60*24*7)));
setcookie('webinterface[session_mode]', $_POST['mode'], (time()+(60*60*24*7)));
setcookie('webinterface[session_language]', $_POST['language'], (time()+(60*60*24*7)));
setcookie('webinterface[session_keymap]', $_POST['keymap'], (time()+(60*60*24*7)));
setcookie('webinterface[use_popup]', $_POST['use_popup'], (time()+(60*60*24*7)));
setcookie('webinterface[debug]', $_POST['debug'], (time()+(60*60*24*7)));

echo $_SESSION['xml'];
die();
