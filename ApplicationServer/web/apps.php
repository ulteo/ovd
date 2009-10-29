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

$session = $_SESSION['session'];

if (!isset($session) || $session == '') {
	Logger::critical('main', '(portal/apps) No SESSION');
	die('CRITICAL ERROR'); // That's odd !
}

$ids = array();
foreach ($_SESSION['parameters']['applications'] as $buf) {
	if ($buf == '')
		continue;

	$buf = explode("|", $buf);
	$ids[] = $buf[0];
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$applications_node = $dom->createElement('applications');
$dom->appendChild($applications_node);

foreach ($ids as $id) {
	$application = query_url(SESSIONMANAGER_URL.'/webservices/application.php?id='.$id.'&fqdn='.SERVERNAME);

	$buf = new DomDocument('1.0', 'utf-8');
	$buf->loadXML($application);

	if (! $buf->hasChildNodes())
		continue;

	$application_node = $buf->getElementsByTagname('application')->item(0);
	if (is_null($application_node))
		continue;

	if ($application_node->hasAttribute('name'))
		$name = $application_node->getAttribute('name');

	$application_node = $dom->createElement('application');
	$application_node->setAttribute('id', $id);
	$application_node->setAttribute('name', $name);
	$applications_node->appendChild($application_node);
}

$xml = $dom->saveXML();

echo $xml;
