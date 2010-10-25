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

require_once(dirname(__FILE__).'/includes/core.inc.php');

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

function generateAjaxplorerActionsXML($application_nodes_) {
	$dom = new DomDocument('1.0', 'utf-8');

	$driver_node = $dom->createElement('driver');
	$driver_node->setAttribute('name', 'fs');
	$driver_node->setAttribute('className', 'class.fsDriver.php');
	$dom->appendChild($driver_node);

	$actions_node = $dom->createElement('actions');
	$driver_node->appendChild($actions_node);

	$actions = array();
	foreach ($application_nodes_ as $application_node) {
		$app_id = $application_node->getAttribute('id');
		$app_name = $application_node->getAttribute('name');

	$clientcallback_cdata = <<<EOF
var repository;
var path;
if (window.actionArguments && window.actionArguments.length > 0) {
	repository = 0;
	path = window.actionArguments[0];
} else {
	userSelection = ajaxplorer.getFilesList().getUserSelection();
	if (userSelection && userSelection.isUnique()) {
		repository = ajaxplorer.repositoryId;
		path = userSelection.getUniqueFileName();
	}
}

new Ajax.Request(
	'../start_app.php',
	{
		method: 'post',
		parameters: {
			id: $app_id,
			repository: repository,
			path: path
		}
	}
);
EOF;

		$mimes = array();
		foreach ($application_node->getElementsByTagName('mime') as $mime_node)
			$mimes[] = $mime_node->getAttribute('type');

		$actions['ulteo'.$application_node->getAttribute('id')] = array(
			'id'				=>	$app_id,
			'text'				=>	$app_name,
			'mimes'				=>	$mimes,
			'clientcallback'	=>	$clientcallback_cdata
		);
	}

	foreach ($actions as $k => $v) {
		$action_node = $dom->createElement('action');
		$action_node->setAttribute('name', $k);
		$action_node->setAttribute('fileDefault', 'true');
		$actions_node->appendChild($action_node);

		$gui_node = $dom->createElement('gui');
		$gui_node->setAttribute('text', $v['text']);
		$gui_node->setAttribute('title', $v['text']);
		$gui_node->setAttribute('src', '/ovd/icon.php?id='.$v['id']);
		$gui_node->setAttribute('hasAccessKey', 'false');
		$action_node->appendChild($gui_node);

			$context_node = $dom->createElement('context');
			$context_node->setAttribute('selection', 'true');
			$context_node->setAttribute('dir', 'false');
			$context_node->setAttribute('recycle', 'false');
			$context_node->setAttribute('actionBar', 'false');
			$context_node->setAttribute('actionBarGroup', 'get');
			$context_node->setAttribute('contextMenu', 'true');
			$context_node->setAttribute('infoPanel', 'true');
			$context_node->setAttribute('inZip', 'false');
			$context_node->setAttribute('ulteoMimes', implode(',', $v['mimes']));
			$gui_node->appendChild($context_node);

			$selectioncontext_node = $dom->createElement('selectionContext');
			$selectioncontext_node->setAttribute('dir', 'false');
			$selectioncontext_node->setAttribute('file', 'true');
			$selectioncontext_node->setAttribute('recycle', 'false');
			$selectioncontext_node->setAttribute('unique', 'true');
			$gui_node->appendChild($selectioncontext_node);

		$rightscontext_node = $dom->createElement('rightsContext');
		$rightscontext_node->setAttribute('noUser', 'true');
		$rightscontext_node->setAttribute('userLogged', 'only');
		$rightscontext_node->setAttribute('read', 'true');
		$rightscontext_node->setAttribute('write', 'false');
		$rightscontext_node->setAttribute('adminOnly', 'false');
		$action_node->appendChild($rightscontext_node);

		$processing_node = $dom->createElement('processing');
		$action_node->appendChild($processing_node);

			$clientcallback_node = $dom->createElement('clientCallback');
			$clientcallback_node->setAttribute('prepareModal', 'true');

			$clientcallback_cdata_node = $dom->createCDATASection($v['clientcallback']);
			$clientcallback_node->appendChild($clientcallback_cdata_node);

			$processing_node->appendChild($clientcallback_node);

			$servercallback_node = $dom->createElement('serverCallback');
			$servercallback_node->setAttribute('methodName', 'switchAction');
			$processing_node->appendChild($servercallback_node);
	}

	$xml = $dom->saveXML();

	return $xml;
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
$session_node->setAttribute('language', $_POST['language']);
$session_node->setAttribute('timezone', $_POST['timezone']);
$user_node = $dom->createElement('user');
$user_node->setAttribute('login', $_POST['login']);
$user_node->setAttribute('password', $_POST['password']);
$session_node->appendChild($user_node);
$dom->appendChild($session_node);

if (! defined('SESSIONMANAGER_HOST')) {
	$_SESSION['ovd-client']['sessionmanager_url'] = 'https://'.$_POST['sessionmanager_host'].'/ovd/client/';
	$sessionmanager_url = $_SESSION['ovd-client']['sessionmanager_url'];
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
if ($_SESSION['session_mode'] == 'desktop')
	$_SESSION['desktop_fullscreen'] = $_POST['desktop_fullscreen'];
$_SESSION['timeout'] = $session_node->getAttribute('timeout');

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
$sharedfolders_node = $session_node->getElementsByTagName('sharedfolders')->item(0);
if (is_object($profile_node) || is_object($sharedfolders_node)) {
	if (is_dir(dirname(__FILE__).'/ajaxplorer/'))
		$_SESSION['explorer'] = true;

	$_SESSION['ajxp'] = array();
	$_SESSION['ajxp']['applications'] = '';
	$_SESSION['ajxp']['repositories'] = array();
	$_SESSION['ajxp']['folders'] = array();
}

if ($_SESSION['explorer'] === true) {
	$_SESSION['ajxp']['applications'] = generateAjaxplorerActionsXML($session_node->getElementsByTagName('application'));

	if (is_object($profile_node)) {
		$_SESSION['ajxp']['repositories'][] = array(
			'DISPLAY'					=>	_('Profile'),
			'DRIVER'					=>	'fs',
			'DRIVER_OPTIONS'			=>	array(
				'PATH'					=>	'webdav://'.$profile_node->getAttribute('login').':'.$profile_node->getAttribute('password').'@'.$profile_node->getAttribute('server').':1113/ovd/fs/'.$profile_node->getAttribute('dir').'/',
				'CREATE'				=>	false,
				'RECYCLE_BIN'			=>	'',
				'CHMOD_VALUE'			=>	'0660',
				'DEFAULT_RIGHTS'		=>	'',
				'PAGINATION_THRESHOLD'	=>	500,
				'PAGINATION_NUMBER'		=>	200
			),
		);

		$_SESSION['ajxp']['folders'][] = $profile_node->getAttribute('dir');
	}

	if (is_object($sharedfolders_node)) {
		$sharedfolder_nodes = $sharedfolders_node->getElementsByTagName('sharedfolder');
		foreach ($sharedfolder_nodes as $sharedfolder_node) {
			if (! is_object($sharedfolder_node))
				continue;

			$_SESSION['ajxp']['repositories'][] = array(
				'DISPLAY'					=>	$sharedfolder_node->getAttribute('name'),
				'DRIVER'					=>	'fs',
				'DRIVER_OPTIONS'			=>	array(
					'PATH'					=>	'webdav://'.$sharedfolder_node->getAttribute('login').':'.$sharedfolder_node->getAttribute('password').'@'.$sharedfolder_node->getAttribute('server').':1113/ovd/fs/'.$sharedfolder_node->getAttribute('dir').'/',
					'CREATE'				=>	false,
					'RECYCLE_BIN'			=>	'',
					'CHMOD_VALUE'			=>	'0660',
					'DEFAULT_RIGHTS'		=>	'',
					'PAGINATION_THRESHOLD'	=>	500,
					'PAGINATION_NUMBER'		=>	200
				),
			);

			$_SESSION['ajxp']['folders'][] = $sharedfolder_node->getAttribute('dir');
		}
	}
}

if (array_key_exists('explorer', $_SESSION) && $_SESSION['explorer'] === true) {
	$explorer_node = $dom->createElement('explorer');
	$explorer_node->setAttribute('enabled', (($_SESSION['explorer'] === true)?1:0));
	$session_node->appendChild($explorer_node);
}

$xml = $dom->saveXML();
$_SESSION['xml'] = $xml;

setcookie('ovd-client[sessionmanager_host]', $_POST['sessionmanager_host'], (time()+(60*60*24*7)));
setcookie('ovd-client[user_login]', $_POST['login'], (time()+(60*60*24*7)));
setcookie('ovd-client[use_local_credentials]', 0, (time()+(60*60*24*7)));
setcookie('ovd-client[session_mode]', $_POST['mode'], (time()+(60*60*24*7)));
setcookie('ovd-client[session_language]', $_POST['language'], (time()+(60*60*24*7)));
setcookie('ovd-client[session_keymap]', $_POST['keymap'], (time()+(60*60*24*7)));
setcookie('ovd-client[desktop_fullscreen]', $_POST['desktop_fullscreen'], (time()+(60*60*24*7)));
setcookie('ovd-client[use_popup]', $_POST['use_popup'], (time()+(60*60*24*7)));
setcookie('ovd-client[debug]', $_POST['debug'], (time()+(60*60*24*7)));

echo $_SESSION['xml'];
die();