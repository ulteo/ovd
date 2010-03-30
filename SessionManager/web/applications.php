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
require_once(dirname(__FILE__).'/includes/core-minimal.inc.php');

if (! isset($_SESSION['login'])) {
	$ret = do_login();
	if (! $ret) {
		Logger::error('main', '(applications) Authentication failed');
		die();
	}
}

if (! isset($_SESSION['login'])) {
	Logger::error('main', '(applications) Authentication failed');
	die();
}

$user_login = $_SESSION['login'];

$userDB = UserDB::getInstance();

$user = $userDB->import($user_login);
if (! is_object($user)) {
	Logger::error('main', '(applications) User importation failed');
	die();
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');

$applications_node = $dom->createElement('applications');

foreach ($user->applications() as $application) {
	if ($application->getAttribute('static'))
		continue;

	$application_node = $dom->createElement('application');
	$application_node->setAttribute('id', $application->getAttribute('id'));
	$application_node->setAttribute('name', $application->getAttribute('name'));
	$mimes = explode(';', $application->getAttribute('mimetypes'));
	foreach ($mimes as $mime) {
		if ($mime == '')
			continue;

		$mime_node = $dom->createElement('mime');
		$mime_node->setAttribute('type', $mime);
		$application_node->appendChild($mime_node);
	}
	$applications_node->appendChild($application_node);
}

$dom->appendChild($applications_node);

echo $dom->saveXML();
