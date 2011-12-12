<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011 
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

if (! is_array($_POST) || count($_POST) == 0) {
	header('Location: index.php');
	die();
}

require_once(dirname(__FILE__).'/includes/core.inc.php');

function return_error_alt($errno_, $errstr_, $errmore_=NULL) {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('error_id', $errstr_);
	if (! is_null($errmore_) && $errmore_ != '')
		$node->setAttribute('more', $errmore_);
	$dom->appendChild($node);
	return $dom->saveXML();
}

function return_popup($location_='about:blank') {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('popup');
	$node->setAttribute('location', $location_);
	$dom->appendChild($node);
	return $dom->saveXML();
}



$_SESSION['ovd-client']['interface'] = array();
$_SESSION['ovd-client']['interface']['debug'] = $_POST['debug'];

header('Content-Type: text/xml; charset=utf-8');

if (! defined('SESSIONMANAGER_HOST') && ! array_key_exists('sessionmanager_host', $_POST)) {
	echo return_error_alt(0, 'no_sessionmanager_host');
	die();
}

$dom = new DomDocument('1.0', 'utf-8');

$session_node = $dom->createElement('session');
$session_node->setAttribute('mode', $_POST['mode']);
$session_node->setAttribute('language', $_POST['language']);
$session_node->setAttribute('timezone', $_POST['timezone']);
$user_node = $dom->createElement('user');
if (array_key_exists('login', $_POST))
	$user_node->setAttribute('login', $_POST['login']);
if (array_key_exists('password', $_POST))
	$user_node->setAttribute('password', $_POST['password']);
$session_node->appendChild($user_node);

if ($_POST['mode'] == 'desktop' && array_key_exists('start_app', $_SESSION['ovd-client'])) {
	$start_node = $dom->createElement('start');
	
	foreach($_SESSION['ovd-client']['start_app'] as $order) {
		$instance_node = $dom->createElement('application');
		$instance_node->setAttribute('id', $order['id']);
		
		if (array_key_exists('file', $order)) {
			$instance_node->setAttribute('file_type', $order['file']['type']);
			$instance_node->setAttribute('file_location', $order['file']['share']);
			$instance_node->setAttribute('file_path', $order['file']['path']);
		}
		
		$start_node->appendChild($instance_node);
	}
	
	$session_node->appendChild($start_node);
}

$dom->appendChild($session_node);

if (defined('SESSIONMANAGER_HOST')) {
	$_SESSION['ovd-client']['server'] = SESSIONMANAGER_HOST;
	$_SESSION['ovd-client']['sessionmanager_host'] = SESSIONMANAGER_HOST;
} else {
	$_SESSION['ovd-client']['server'] = $_POST['sessionmanager_host'];
	$_SESSION['ovd-client']['sessionmanager_host'] = $_POST['sessionmanager_host'];
}

$_SESSION['ovd-client']['gateway'] = false;
$_SESSION['ovd-client']['gateway_first'] = false;
$headers = apache_request_headers();
if (is_array($headers) && array_key_exists('OVD-Gateway', $headers)) {
	$_SESSION['ovd-client']['gateway'] = true;
	$_SESSION['ovd-client']['gateway_first'] = true;

	if (defined('GATEWAY_FORCE_PORT'))
		$port = GATEWAY_FORCE_PORT;
	else
		$port = $_POST['requested_port'];
	
	$_SESSION['ovd-client']['server'] = $_SERVER['REMOTE_ADDR'].':'.$port;
	$_SESSION['ovd-client']['sessionmanager_host'] = $_POST['requested_host'].':'.$_POST['requested_port'];
}

$_SESSION['ovd-client']['sessionmanager_url'] = 'https://'.$_SESSION['ovd-client']['server'].'/ovd/client';
$sessionmanager_url = $_SESSION['ovd-client']['sessionmanager_url'];

if (array_key_exists('sessionmanager', $_SESSION['ovd-client'])) {
	$sm = $_SESSION['ovd-client']['sessionmanager'];
	if ($sm->get_base_url() != $sessionmanager_url)
		$sm->set_base_url($sessionmanager_url);
}
else {
	$sm = new SessionManager($sessionmanager_url);
	$_SESSION['ovd-client']['sessionmanager'] = $sm;
}

if (array_key_exists('from_SM_start_XML', $_SESSION['ovd-client'])) {
	$xml = $_SESSION['ovd-client']['from_SM_start_XML'];
	unset($_SESSION['ovd-client']['from_SM_start_XML']);
} else {
	$xml = $sm->query_post_xml('start.php', $dom->saveXML());
	if (! $xml) {
		echo return_error_alt(0, 'unable_to_reach_sm', $sessionmanager_url.'/start.php');
		die();
	}
}

if (is_array($xml) && count($xml) == 2) {
	if ($xml[0] == 302) {
		$protocol = parse_url($xml[1], PHP_URL_SCHEME);
		$host = parse_url($xml[1], PHP_URL_HOST);
		$port = parse_url($xml[1], PHP_URL_PORT);
		$path = parse_url($xml[1], PHP_URL_PATH);

		if (substr($path, 0, 5) == '/cas/') {
			$protocol = 'https';

			$CAS_server_url = $protocol.'://'.$host;
			if ($port != 443)
				$CAS_server_url .= ':'.$port;
			$CAS_server_url .= '/cas';

			echo return_popup('auth/cas/login.php?CAS_server_url='.urlencode($CAS_server_url));
			die();
		}
	}

	echo return_error_alt(0, 'internal_error');
	die();
}

$dom = new DomDocument('1.0', 'utf-8');
$buf = @$dom->loadXML($xml);
if (! $buf) {
	echo return_error_alt(0, 'internal_error');
	die();
}

if (! $dom->hasChildNodes()) {
	echo return_error_alt(0, 'internal_error');
	die();
}

$response_node = $dom->getElementsByTagName('response')->item(0);
if (! is_null($response_node)) {
	echo return_error_alt(-1, $response_node->getAttribute('code'));
	die();
}

$session_node = $dom->getElementsByTagName('session');
if (count($session_node) != 1) {
	echo return_error_alt(1, 'internal_error');
	die();
}
$session_node = $session_node->item(0);
if (! is_object($session_node)) {
	echo return_error_alt(1, 'internal_error');
	die();
}
$session_node->setAttribute('sessionmanager', $_SESSION['ovd-client']['sessionmanager_host']);

$server_nodes = $session_node->getElementsByTagName('server');
foreach ($server_nodes as $server_node) {
	$port = 3389;
	
	if ($_SESSION['ovd-client']['gateway'] === true) {
		if ($_SESSION['ovd-client']['gateway_first'] === true) {
			$host = $_POST['requested_host'];
			$port = $_POST['requested_port'];
		}
		else {
			$url = 'http://'.$_SESSION['ovd-client']['sessionmanager_host'];
			$host = parse_url($url, PHP_URL_HOST);
			$port = parse_url($url, PHP_URL_PORT);
			if (is_null($port))
				$port = 443;
		}
		$server_node->setAttribute('fqdn', $host);
	}
	
	$server_node->setAttribute('port', $port);
}


$_SESSION['ovd-client']['session_id'] = $session_node->getAttribute('id');
$session_mode = $session_node->getAttribute('mode');
if ($session_mode == 'desktop')
	$_SESSION['ovd-client']['desktop_fullscreen'] = ((array_key_exists('desktop_fullscreen', $_POST))?$_POST['desktop_fullscreen']:0);
$_SESSION['ovd-client']['timeout'] = $session_node->getAttribute('timeout');

if ($session_node->hasAttribute('mode_gateway') && $session_node->getAttribute('mode_gateway') == 'on') {
	$_SESSION['ovd-client']['gateway'] = true;
}

$user_node = $session_node->getElementsByTagName('user');
if (count($user_node) != 1) {
	echo return_error_alt(2, 'internal_error');
	die();
}
$user_node = $user_node->item(0);
if (! is_object($user_node)) {
	echo return_error_alt(2, 'internal_error');
	die();
}

$server_nodes = $session_node->getElementsByTagName('server');
if (count($server_nodes) < 1) {
	echo return_error_alt(3, 'internal_error');
	die();
}

$aj = new Ajaxplorer($session_node);
$_SESSION['ovd-client']['explorer'] = ($aj->can_run() && $aj->is_required());
if ($_SESSION['ovd-client']['explorer'] === true) {
	$_SESSION['ovd-client']['ajxp'] = $aj->build_data();
	
	$explorer_node = $dom->createElement('explorer');
	$explorer_node->setAttribute('enabled', (($_SESSION['ovd-client']['explorer'] === true)?1:0));
	$session_node->appendChild($explorer_node);
}

if (array_key_exists('sessionmanager_host', $_POST))
	setcookie('ovd-client[sessionmanager_host]', $_POST['sessionmanager_host'], (time()+(60*60*24*7)));
if (array_key_exists('login', $_POST))
	setcookie('ovd-client[user_login]', $_POST['login'], (time()+(60*60*24*7)));
setcookie('ovd-client[use_local_credentials]', 0, (time()+(60*60*24*7)));
setcookie('ovd-client[session_mode]', $_POST['mode'], (time()+(60*60*24*7)));
setcookie('ovd-client[session_language]', $_POST['language'], (time()+(60*60*24*7)));
setcookie('ovd-client[session_keymap]', $_POST['keymap'], (time()+(60*60*24*7)));
if (array_key_exists('desktop_fullscreen', $_POST))
	setcookie('ovd-client[desktop_fullscreen]', $_POST['desktop_fullscreen'], (time()+(60*60*24*7)));
setcookie('ovd-client[debug]', $_POST['debug'], (time()+(60*60*24*7)));

echo $dom->saveXML();
die();
