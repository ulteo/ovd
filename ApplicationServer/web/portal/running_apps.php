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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

$session = $_SESSION['session'];

if (!isset($session) || $session == '') {
	Logger::critical('main', '(portal/running_apps) No SESSION');
	die('CRITICAL ERROR'); // That's odd !
}

$apps = explode(',', $_GET['apps']);
foreach ($apps as $k => $app) {
	if ($app == 'undefined' || $app == '')
		continue;

	list($id, $access_id, $status) = explode('-', $app);

	if ($id === '' || $id == 'undefined')
		unset($apps[$k]);
}

echo '<table border="0" cellspacing="1" cellpadding="3">';
foreach ($apps as $k => $app) {
	if ($app == 'undefined' || $app == '')
		continue;

	list($id, $access_id, $status) = explode('-', $app);

	$application = query_url(SESSIONMANAGER_URL.'/webservices/application.php?id='.$id.'&fqdn='.SERVERNAME);

	$dom = new DomDocument();
	@$dom->loadXML($application);

	if (! $dom->hasChildNodes())
		continue;

	$application_node = $dom->getElementsByTagname('application')->item(0);
	if (is_null($application_node))
		continue;

	if ($application_node->hasAttribute('name'))
		$name = $application_node->getAttribute('name');

	echo '<tr>';
	echo '<td><img src="../icon.php?id='.$id.'" alt="'.$name.'" title="'.$name.'" /></td>';
	echo '<td><strong>'.$name.'</strong><br />';
	if ($_SESSION['owner'] && isset($_SESSION['parameters']['shareable'])) {
		echo ' ';
		if ($status == 2)
			echo '<a href="javascript:;" onclick="return shareApplication(\''.$access_id.'\');">'._('share').'</a>';
	}

	if (($_SESSION['owner'] && isset($_SESSION['parameters']['shareable'])) && (isset($_SESSION['parameters']['persistent']) && $_SESSION['parameters']['persistent']) && ($status == 2))
		echo ' - ';

	if (isset($_SESSION['parameters']['persistent'])) {
		echo ' ';
		if ($status == 2)
			echo '<a href="javascript:;" onclick="return suspendApplication(\''.$access_id.'\');">'._('suspend').'</a>';
		elseif ($status == 10)
			echo '<a href="javascript:;" onclick="return resumeApplication(\''.$access_id.'\');">'._('resume').'</a>';
	}
	echo '</td>';
	echo '</tr>';
}
echo '</table>';
