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

if (!check_ip($_GET['fqdn'])) {
	Logger::error('main', 'Server not authorized : '.$_GET['fqdn'].' ? '.@gethostbyname($_GET['fqdn']));
	die('Server not authorized');
}

Logger::debug('main', 'Security check OK');

if (!isset($_GET['token']) || $_GET['token'] == '') {
	Logger::error('main', 'Missing parameter : token');
	die('ERROR - NO $_GET[\'token\']');
}

$token = $_GET['token'];

if (!is_readable(TOKENS_DIR.'/'.$token)) {
	Logger::error('main', 'No such token file : '.TOKENS_DIR.'/'.$token);
	die('No such token file');
}

$buf = trim(@file_get_contents(TOKENS_DIR.'/'.$token));

$buf = explode(':', $buf);

$session = new Session($buf[1], $_GET['fqdn']);

if (!is_readable(SESSIONS_DIR.'/'.$session->server.'/'.$session->session.'/settings')) {
	Logger::error('main', 'No such session token file : '.SESSIONS_DIR.'/'.$session->server.'/'.$session->session.'/settings');
	die('No such session token file');
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument();
$session_node = $dom->createElement('session');
$session_node->setAttribute('id', $session->session);
$session_node->setAttribute('mode', $buf[0]);
$dom->appendChild($session_node);

$settings = unserialize(@file_get_contents(SESSIONS_DIR.'/'.$session->server.'/'.$session->session.'/settings'));

foreach ($settings as $k => $v) {
	if ($k == 'home_dir_type' || $k == 'module_fs')
		continue;

	$item = $dom->createElement($k);
	$item->setAttribute('value', $v);
	$session_node->appendChild($item);
}

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
	$desktopfiles = $user->desktopfiles();
	foreach ($desktopfiles as $desktopfile) {
		$item = $dom->createElement('application');
		$item->setAttribute('desktopfile', $desktopfile);
		$menu_node->appendChild($item);
	}
}

$xml = $dom->saveXML();

echo $xml;

$session->use_token($token);
