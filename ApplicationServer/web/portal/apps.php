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

if (isset($_GET['action']) && $_GET['action'] == 'get_image') {
	header('Content-Type: image/png');
	die(query_url(SESSIONMANAGER_URL.'/webservices/icon.php?id='.$_GET['id'].'&fqdn='.SERVERNAME));
}

$session = $_SESSION['session'];

if (!isset($session) || $session == '') {
	Logger::critical('main', '(portal/apps) No SESSION');
	die('CRITICAL ERROR'); // That's odd !
}

$buf = @file_get_contents(SESSION_PATH.'/'.$session.'/parameters/menu');
$buf = explode("\n", $buf);

$ids = array();
foreach ($buf as $buf2) {
	if ($buf2 == '')
		continue;

	$buf3 = explode("|", $buf2);
	$ids[] = $buf3[0];
}

echo '<table border="0" cellspacing="1" cellpadding="3">';
foreach ($ids as $id) {
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

	$executable_node = $application_node->getElementsByTagname('executable')->item(0);
	if (is_null($executable_node))
		continue;

	if ($executable_node->hasAttribute('command'))
		$command = $executable_node->getAttribute('command');

	echo '<tr>';
	echo '<td><a href="javascript:;" onclick="return startExternalApp('.$id.', \''.str_replace("\\", "\\\\", $command).'\');"><img src="apps.php?action=get_image&id='.$id.'" alt="'.$name.'" title="'.$name.'" /></a></td>';
	echo '<td><a href="javascript:;" onclick="return startExternalApp('.$id.', \''.str_replace("\\", "\\\\", $command).'\');">'.$name.'</a></td>';
	echo '</tr>';
}
echo '</table>';
