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

require_once(dirname(__FILE__).'/../includes/core.inc.php');

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');

$session = Abstract_Session::load($_GET['session']);
if (! $session)
	die();

$server = Abstract_Server::load($session->server);
if (! $server)
	die();

$userDB = UserDB::getInstance();

$user = $userDB->import($session->settings['user_login']);
if (! is_object($user))
	die();

$applications_node = $dom->createElement('applications');

foreach ($user->applications() as $application) {
	if ($application->getAttribute('static'))
		continue;

	if ($application->getAttribute('type') != $server->getAttribute('type'))
		continue;

	$application_node = $dom->createElement('application');
	$application_node->setAttribute('id', $application->getAttribute('id'));
	$application_node->setAttribute('name', $application->getAttribute('name'));
	$application_node->setAttribute('server', $server->getAttribute('external_name'));
	foreach (explode(';', $application->getAttribute('mimetypes')) as $mimetype) {
		if ($mimetype == '')
			continue;

		$mimetype_node = $dom->createElement('mime');
		$mimetype_node->setAttribute('type', $mimetype);
		$application_node->appendChild($mimetype_node);
	}
	$applications_node->appendChild($application_node);
}

$dom->appendChild($applications_node);

$xml = $dom->saveXML();

echo $xml;
