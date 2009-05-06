<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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

Logger::debug('main', 'Starting webservices/session_token.php');

if (! isset($_GET['fqdn'])) {
	Logger::error('main', '(webservices/session_token) Missing parameter : fqdn');
	die('ERROR - NO $_GET[\'fqdn\']');
}

$buf = Abstract_Server::load($_GET['fqdn']);
if (! $buf || ! $buf->isAuthorized()) {
	Logger::error('main', '(webservices/session_token) Server not authorized : '.$_GET['fqdn'].' == '.@gethostbyname($_GET['fqdn']).' ?');
	die('Server not authorized');
}

Logger::debug('main', '(webservices/session_token) Security check OK');

if (!isset($_GET['token']) || $_GET['token'] == '') {
	Logger::error('main', '(webservices/session_token) Missing parameter : token');
	die('ERROR - NO $_GET[\'token\']');
}

$token = Abstract_Token::load($_GET['token']);
if (! $token) {
	Logger::error('main', '(webservices/session_token) No such token : '.$_GET['token']);
	die('No such token file');
}

if ($token->getAttribute('type') == 'start' || $token->getAttribute('type') == 'resume') {
	$session_id = $token->getAttribute('link_to');
} elseif ($token->getAttribute('type') == 'invite') {
	$invite = Abstract_Invite::load($token->getAttribute('link_to'));

	$session_id = $invite->getAttribute('session');
}

$session = Abstract_Session::load($session_id);
if (! $session || ! $session->hasAttribute('settings')) {
	Logger::error('main', '(webservices/session_token) Invalid session: '.$session->id);
	die('Invalid session');
}

Abstract_Token::delete($token->id);

$session_type = 'linux';

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument();
$session_node = $dom->createElement('session');
$session_node->setAttribute('id', $session->id);
$session_node->setAttribute('mode', $token->type);
$session_node->setAttribute('type', $session_type);
$dom->appendChild($session_node);

$settings = $session->getAttribute('settings');
if ($token->type == 'invite')
	$settings = array_merge($settings, $invite->getAttribute('settings'));
foreach ($settings as $k => $v) {
	if ($k == 'home_dir_type' || $k == 'module_fs')
		continue;

	if ($k == 'user_login' || $k == 'user_displayname')
		$v = str_replace(' ', '', $v);

	if ($k == 'view_only') {
		if ($v == true)
			$v = 'Yes';
		elseif ($v == false)
			$v = 'No';
	}

	$item = $dom->createElement($k);
	$item->setAttribute('value', $v);
	$session_node->appendChild($item);
}

/*if ($buf[0] == 'invite') {
	$invite_settings = unserialize(@file_get_contents(SESSIONS_DIR.'/'.$session->server.'/'.$session->session.'/'.$token));

	foreach ($invite_settings as $k => $v) {
		$item = $dom->createElement($k);
		$item->setAttribute('value', $v);
		$session_node->appendChild($item);
	}
}*/

$module_fs_node = $dom->createElement('module_fs');
$module_fs_node->setAttribute('type', $settings['home_dir_type']);
$session_node->appendChild($module_fs_node);

if (isset($settings['module_fs']) && is_array($settings['module_fs'])) {
	foreach ($settings['module_fs'] as $k2 => $v2) {
		$item = $dom->createElement('param');
		$item->setAttribute('key', $k2);
		$item->setAttribute('value', $v2);
		$module_fs_node->appendChild($item);
	}
}

$menu_node = $dom->createElement('menu');
$session_node->appendChild($menu_node);

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$mods_enable = $prefs->get('general', 'module_enable');
if (!in_array('UserDB', $mods_enable)) {
// 	die_error('Module UserDB must be enabled',__FILE__,__LINE__);
	return_error();
	die();
}

$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
$userDB = new $mod_user_name();
$user = $userDB->import($settings['user_login']);

if (!is_null($user)) {
	$available_apps = $user->applications();
	foreach ($available_apps as $app) {
		$item = $dom->createElement('application');
		$item->setAttribute('id', $app->getAttribute('id'));

		if ($app->getAttribute('static') || $app->getAttribute('type') != $session_type) {
			$item->setAttribute('mode', 'virtual');

			if ($app->getAttribute('static')) {
				$buf = Abstract_Liaison::load('StaticApplicationServer', $app->getAttribute('id'), $session->server);
				if (is_null($buf))
					$item->setAttribute('reload', true);
			}
		} else {
			$item->setAttribute('mode', 'local');
			$item->setAttribute('desktopfile', $app->getAttribute('desktopfile'));
		}

		$menu_node->appendChild($item);
	}
}

$xml = $dom->saveXML();

echo $xml;
