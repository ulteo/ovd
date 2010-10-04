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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

function return_error($errno_, $errstr_) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	Logger::error('main', "(webservices/application_icon) return_error($errno_, $errstr_)");
	return $dom->saveXML();
}

$server = Abstract_Server::load($_SERVER['REMOTE_ADDR']);
if (! $server) {
	echo return_error(1, 'Server does not exist');
	die();
}

if (! $server->isAuthorized()) {
	echo return_error(2, 'Server is not authorized');
	die();
}

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$node = $dom->createElement('applications');

$applications = $server->getApplications();
if (is_array($applications)) {
	foreach ($applications as $app) {
		if (! $app->getAttribute('static'))
			continue;

		if ($app->getAttribute('type') != $server->getAttribute('type'))
			continue;

		$app_node = $dom->createElement('application');
		$app_node->setAttribute('id', $app->getAttribute('id'));
		$app_node->setAttribute('name', $app->getAttribute('name'));
		$app_node->setAttribute('description', $app->getAttribute('description'));
		$app_node->setAttribute('command', $app->getAttribute('executable_path'));
		$app_node->setAttribute('revision', $app->getAttribute('revision'));
		$mimes = explode(';', $app->getAttribute('mimetypes'));
		foreach ($mimes as $mime) {
			$mime_node = $dom->createElement('mime');
			$mime_node->setAttribute('type', $mime);
			$app_node->appendChild($mime_node);
		}
		$node->appendChild($app_node);
	}
}

$dom->appendChild($node);

echo $dom->saveXML();
exit(0);
