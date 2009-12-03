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
require_once(dirname(__FILE__).'/includes/core.inc.php');

if (!isset($_SERVER['HTTP_REFERER']) && !isset($_GET['token']))
	redirect(SESSIONMANAGER_URL);

if (!isset($_SERVER['HTTP_REFERER']) && isset($_GET['token']))
	$_SERVER['HTTP_REFERER'] = SESSIONMANAGER_URL;

$buf1 = @parse_url($_SERVER['HTTP_REFERER']);
$buf2 = @parse_url(SESSIONMANAGER_URL);
$sessionmanager_url = $buf1['scheme'].'://'.$buf1['host'].$buf2['path'];

if (!isset($_GET['token']) || $_GET['token'] == '')
	redirect($sessionmanager_url);
$token = $_GET['token'];

if (!isset($_SESSION['current_token']) || $_SESSION['current_token'] != $token) {
	session_destroy();
	session_start();
}
$_SESSION['current_token'] = $token;

$_SESSION['sessionmanager_url'] = $sessionmanager_url;

$xml = query_url(SESSIONMANAGER_URL.'/webservices/session_token.php?fqdn='.SERVERNAME.'&token='.$token);

$dom = new DomDocument('1.0', 'utf-8');
$buf = @$dom->loadXML($xml);
if (! $buf) {
	Logger::error('main', '(index) Invalid XML (token: '.$token.')');
	redirect('error/');
}

if (! $dom->hasChildNodes()) {
	Logger::error('main', '(index) Invalid XML (token: '.$token.')');
	redirect('error/');
}

$session_node = $dom->getElementsByTagname('session')->item(0);
if (is_null($session_node)) {
	Logger::error('main', '(index) Missing element \'session\' (token: '.$token.')');
	redirect('error/');
}

if ($session_node->hasAttribute('id'))
	$_SESSION['session'] = $session_node->getAttribute('id');
if ($session_node->hasAttribute('mode'))
	$_SESSION['mode'] = $session_node->getAttribute('mode');
if ($session_node->hasAttribute('type'))
	$_SESSION['type'] = $session_node->getAttribute('type');
$_SESSION['owner'] = false;
if ($_SESSION['type'] == 'start' || $_SESSION['type'] == 'resume')
	$_SESSION['owner'] = true;

$parameters = array();
foreach ($session_node->childNodes as $node) {
	if (!$node->hasAttribute('value'))
		continue;

	$parameters[$node->nodeName] = $node->getAttribute('value');
}

$settings = array('client', 'user_login', 'user_displayname', 'locale', 'quality'); //user_id

foreach ($settings as $setting) {
	if (!isset($parameters[$setting])) {
		Logger::error('main', '(index) Missing parameter \''.$setting.'\' (token: '.$token.')');
		redirect('error/');
	}
}

$_SESSION['parameters'] = $parameters;
$_SESSION['parameters']['session_mode'] = $_SESSION['mode'];

$_SESSION['popup'] = $_SESSION['parameters']['popup'];
$_SESSION['debug'] = (isset($_SESSION['parameters']['debug']))?1:0;

if ($_SESSION['owner'])
	$_SESSION['parameters']['view_only'] = 'No';

$module_fs_node = $session_node->getElementsByTagname('module_fs')->item(0);
if (is_null($module_fs_node)) {
	Logger::error('main', '(index) Missing element \'module_fs\' (token: '.$token.')');
	redirect('error/');
}

$_SESSION['parameters']['module_fs'] = array();
$_SESSION['parameters']['module_fs']['type'] = $module_fs_node->getAttribute('type');

$param_nodes = $module_fs_node->getElementsByTagname('param');
foreach ($param_nodes as $param_node)
	if ($param_node->hasAttribute('key') && $param_node->hasAttribute('value'))
		$_SESSION['parameters']['module_fs'][$param_node->getAttribute('key')] = $param_node->getAttribute('value');

$menu_node = $session_node->getElementsByTagname('menu')->item(0);
if (is_null($menu_node)) {
	Logger::error('main', '(index) Missing element \'menu\' (token: '.$token.')');
	redirect('error/');
}

$application_nodes = $menu_node->getElementsByTagname('application');
$_SESSION['parameters']['applications'] = array();
foreach ($application_nodes as $application_node) {
	$_SESSION['parameters']['applications'][$application_node->getAttribute('id')] = '';
	if ($application_node->hasAttribute('id'))
		$_SESSION['parameters']['applications'][$application_node->getAttribute('id')].= $application_node->getAttribute('id');
	if ($application_node->hasAttribute('mode')) {
		$_SESSION['parameters']['applications'][$application_node->getAttribute('id')].= '|'.$application_node->getAttribute('mode');

		if ($application_node->getAttribute('mode') == 'local') {
			if ($application_node->hasAttribute('desktopfile'))
				$_SESSION['parameters']['applications'][$application_node->getAttribute('id')].= '|'.$application_node->getAttribute('desktopfile');
		} elseif ($application_node->getAttribute('mode') == 'virtual') {
			if ($application_node->hasAttribute('reload') && $application_node->getAttribute('reload') == 1)
				$_SESSION['parameters']['applications'][$application_node->getAttribute('id')].= '|reload';
			else
				$_SESSION['parameters']['applications'][$application_node->getAttribute('id')].= '|cache';
		}
	}
}

$_SESSION['print_timestamp'] = time();

if ($_SESSION['type'] == 'invite') {
	$session_dir = SESSION_PATH.'/'.$_SESSION['session'];

	$buf = $session_dir.'/infos/share/'.$token;
	@mkdir($buf);
	@file_put_contents($buf.'/email', $_SESSION['parameters']['invite_email']);
	@file_put_contents($buf.'/mode', ($_SESSION['parameters']['view_only'] == 'No')?'active':'passive');
	if (isset($_SESSION['parameters']['access_id']) && $_SESSION['parameters']['access_id'] != '')
		@file_put_contents($buf.'/access_id', $_SESSION['parameters']['access_id']);

	$buf_access_id = @file_get_contents($buf.'/access_id');

	$_SESSION['tokens'][$_GET['token']] = array(
		'session_id'	=>	$_SESSION['session'],
		'access_id'		=>	$buf_access_id
	);
}

if ($_SESSION['type'] == 'start')
	@touch(SESSION2CREATE_PATH.'/'.$_SESSION['session']);

if (isset($_SESSION['parameters']['client']) && $_SESSION['parameters']['client'] == 'browser')
	redirect($_SESSION['mode'].'/?token='.$_GET['token']);

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$session_node = $dom->createElement('session');
$session_node->setAttribute('mode', $_SESSION['mode']);
$session_node->setAttribute('shareable', ((isset($_SESSION['parameters']['shareable']))?'true':'false'));
$session_node->setAttribute('persistent', ((isset($_SESSION['parameters']['persistent']))?'true':'false'));
$aps_node = $dom->createElement('aps');
$aps_node->setAttribute('protocol', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')?'https':'http'));
$aps_node->setAttribute('server', $_SERVER['SERVER_NAME']);
$aps_node->setAttribute('port', $_SERVER['SERVER_PORT']);
$aps_node->setAttribute('location', dirname($_SERVER['REQUEST_URI']));
$session_node->appendChild($aps_node);
$dom->appendChild($session_node);

$xml = $dom->saveXML();

echo $xml;
