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

if (!isset($_SERVER['HTTP_REFERER']) && !isset($_GET['token'])) {
	header('Location: '.SESSIONMANAGER_URL);
	die();
}

if (!isset($_SERVER['HTTP_REFERER']) && isset($_GET['token']))
	$_SERVER['HTTP_REFERER'] = SESSIONMANAGER_URL;

$buf1 = @parse_url($_SERVER['HTTP_REFERER']);
$buf2 = @parse_url(SESSIONMANAGER_URL);
$sessionmanager_url = $buf1['scheme'].'://'.$buf1['host'].$buf2['path'];

if (!isset($_GET['token']) || $_GET['token'] == '')
	header('Location: '.$sessionmanager_url);
$token = $_GET['token'];

if (!isset($_SESSION['current_token']) || $_SESSION['current_token'] != $token) {
	session_destroy();
	session_start();
}
$_SESSION['current_token'] = $token;

$_SESSION['sessionmanager_url'] = $sessionmanager_url;

$xml = query_url(SESSIONMANAGER_URL.'/webservices/session_token.php?fqdn='.SERVERNAME.'&token='.$token);

$dom = new DomDocument();
@$dom->loadXML($xml);

if (! $dom->hasChildNodes())
	die('Invalid XML');

$session_node = $dom->getElementsByTagname('session')->item(0);
if (is_null($session_node))
	die('Missing element \'session\'');

if ($session_node->hasAttribute('id'))
	$_SESSION['session'] = $session_node->getAttribute('id');
if ($session_node->hasAttribute('mode'))
	$_SESSION['mode'] = $session_node->getAttribute('mode');
$_SESSION['owner'] = false;
if (substr($_SESSION['mode'], 0, 5) == 'start' || substr($_SESSION['mode'], 0, 6) == 'resume')
	$_SESSION['owner'] = true;

$parameters = array();
foreach ($session_node->childNodes as $node) {
	if (!$node->hasAttribute('value'))
		continue;

	$parameters[$node->nodeName] = $node->getAttribute('value');
}

$settings = array('client', 'user_login', 'user_displayname', 'locale', 'quality'); //user_id

foreach ($settings as $setting)
	if (!isset($parameters[$setting]))
		die('Missing parameter \''.$setting.'\'');

$_SESSION['parameters'] = $parameters;

$_SESSION['debug'] = (isset($_SESSION['parameters']['debug']))?1:0;

$_SESSION['share_desktop'] = 'true';
if ($_SESSION['owner'])
	$_SESSION['parameters']['view_only'] = 'No';

$module_fs_node = $session_node->getElementsByTagname('module_fs')->item(0);
if (is_null($module_fs_node))
	die('Missing element \'module_fs\'');

$_SESSION['parameters']['module_fs'] = array();
$_SESSION['parameters']['module_fs']['type'] = $module_fs_node->getAttribute('type');

$param_nodes = $module_fs_node->getElementsByTagname('param');
foreach ($param_nodes as $param_node)
	if ($param_node->hasAttribute('key') && $param_node->hasAttribute('value'))
		$_SESSION['parameters']['module_fs'][$param_node->getAttribute('key')] = $param_node->getAttribute('value');

$menu_node = $session_node->getElementsByTagname('menu')->item(0);
if (is_null($menu_node))
	die('Missing element \'menu\'');

$application_nodes = $menu_node->getElementsByTagname('application');
$_SESSION['parameters']['desktopfiles'] = array();
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

if ($_SESSION['mode'] == 'invite_desktop') {
	$session_dir = SESSION_PATH.'/'.$_SESSION['session'];

	$buf = $session_dir.'/infos/share/'.$token;
	@mkdir($buf);
	@file_put_contents($buf.'/email', $_SESSION['parameters']['invite_email']);
	@file_put_contents($buf.'/mode', ($_SESSION['parameters']['view_only'] == 'No')?'active':'passive');
}

if (substr($_SESSION['mode'], 0, 5) == 'start')
	@touch(SESSION2CREATE_PATH.'/'.$_SESSION['session']);

if (isset($_SESSION['parameters']['client']) && $_SESSION['parameters']['client'] == 'browser') {
	if (substr($_SESSION['mode'], -7) == 'desktop')
		redirect('desktop/');
	elseif (substr($_SESSION['mode'], -6) == 'portal')
		redirect('portal/');

	die();
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument();
$aps_node = $dom->createElement('aps');
$aps_node->setAttribute('server', $_SERVER['SERVER_NAME']);
$dom->appendChild($aps_node);

$xml = $dom->saveXML();

echo $xml;
