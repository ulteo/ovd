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

function generateAjaxplorerActionsXML() {
	$dom = new DomDocument('1.0', 'utf-8');

	$driver_node = $dom->createElement('driver');
	$driver_node->setAttribute('name', 'fs');
	$driver_node->setAttribute('className', 'class.fsDriver.php');
	$dom->appendChild($driver_node);

	$actions_node = $dom->createElement('actions');
	$driver_node->appendChild($actions_node);

	$actions = array();
	foreach ($_SESSION['parameters']['applications'] as $app_line) {
		$buf = explode('|', $app_line);

		$app_id = $buf[0];
		$app_desktopfile = $buf[2];

		if ($app_desktopfile == 'cache')
			$app_desktopfile = '/var/spool/ulteo-ovd/virtual_apps/'.$app_id.'.desktop';

		$buf = new DesktopFile(CHROOT.'/'.$app_desktopfile, $_SESSION['parameters']['locale']);
		$buf->parse();

		if (count($buf->getMimeType()) == 0)
			continue;

		$application = query_url(SESSIONMANAGER_URL.'/webservices/application.php?id='.$app_id.'&fqdn='.SERVERNAME);

		$sm_dom = new DomDocument('1.0', 'utf-8');
		@$sm_dom->loadXML($application);

		if (! $sm_dom->hasChildNodes())
			continue;

		$application_node = $sm_dom->getElementsByTagname('application')->item(0);
		if (is_null($application_node))
			continue;

		$app_icon = query_url(SESSIONMANAGER_URL.'/webservices/icon.php?id='.$app_id.'&fqdn='.SERVERNAME);
		@file_put_contents(dirname(__FILE__).'/portal/ajaxplorer/client/images/crystal/actions/16/ulteo'.$app_id.'.png', $app_icon);
		@file_put_contents(dirname(__FILE__).'/portal/ajaxplorer/client/images/crystal/actions/22/ulteo'.$app_id.'.png', $app_icon);
		@file_put_contents(dirname(__FILE__).'/portal/ajaxplorer/client/images/crystal/actions/32/ulteo'.$app_id.'.png', $app_icon);

	$clientcallback_cdata = <<<EOF
var path;
if (window.actionArguments && window.actionArguments.length > 0) {
	path = window.actionArguments[0];
} else {
	userSelection = ajaxplorer.getFilesList().getUserSelection();
	if (userSelection && userSelection.isUnique())
		path = userSelection.getUniqueFileName();
}

var window_ = window.open('about:blank', 'Ulteo'+Math.round(Math.random()*100), 'toolbar=no,status=no,top=0,left=0,width='+screen.width+',height='+screen.height+',scrollbars=no,resizable=no,resizeable=no,fullscreen=no');

setTimeout(function() {
	window_.location.href = '../external_app.php?app_id=$app_id&doc='+path;
}, 1000);
EOF;

		$actions['ulteo'.$app_id] = array(
			'id'				=>	$app_id,
			'text'				=>	$buf->getName(),
			'mimes'				=>	$buf->getMimeType(),
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
		$gui_node->setAttribute('src', $k.'.png');
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

	put_to_file(SESSION_PATH.'/'.$_SESSION['session'].'/parameters/ajaxplorerActions.xml', $xml);

	return true;
}

$session = $_SESSION['session'];

if (!isset($session) || $session == '')
	die('CRITICAL ERROR'); // That's odd !

$_SESSION['width'] = @$_REQUEST['width'];
$_SESSION['height'] = @$_REQUEST['height'];

if ($_SESSION['type'] == 'start' && get_from_file(SESSION_PATH.'/'.$session.'/infos/status') == 0) {
	put_to_file(SESSION_PATH.'/'.$session.'/parameters/geometry', $_SESSION['width'].'x'.$_SESSION['height']);

	foreach ($_SESSION['parameters'] as $k => $v)
		put_to_file(SESSION_PATH.'/'.$session.'/parameters/'.$k, $v);

	@unlink(SESSION_PATH.'/'.$session.'/parameters/module_fs');
	@mkdir(SESSION_PATH.'/'.$session.'/parameters/module_fs', 0750);
	foreach ($_SESSION['parameters']['module_fs'] as $k => $v)
		put_to_file(SESSION_PATH.'/'.$session.'/parameters/module_fs/'.$k, $v);

	$buf = '';
	foreach ($_SESSION['parameters']['applications'] as $app)
		$buf .= $app."\n";
	put_to_file(SESSION_PATH.'/'.$session.'/parameters/applications', $buf);

	if ($_SESSION['mode'] == 'desktop')
		@touch(SESSION_PATH.'/'.$session.'/infos/keepmealive');

	if ($_SESSION['mode'] == 'portal')
		generateAjaxplorerActionsXML();

	put_to_file(SESSION_PATH.'/'.$session.'/infos/status', 1);
} elseif ($_SESSION['type'] == 'resume' && get_from_file(SESSION_PATH.'/'.$session.'/infos/status') == 10) {
	if ($_SESSION['mode'] == 'desktop')
		@touch(SESSION_PATH.'/'.$session.'/infos/keepmealive');

	if ($_SESSION['mode'] == 'portal')
		generateAjaxplorerActionsXML();

	put_to_file(SESSION_PATH.'/'.$session.'/infos/status', 11);
}

Logger::info('main', 'Session starting');
