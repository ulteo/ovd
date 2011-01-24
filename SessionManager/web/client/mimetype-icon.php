<?php
/**
 * Copyright (C) 2011 Ulteo SAS
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
	Logger::error('main', "(client/mimetype-icon) return_error($errno_, $errstr_)");
	return $dom->saveXML();
}

if (! array_key_exists('id', $_GET)) {
	echo return_error(1, 'Usage: missing "id" $_GET parameter');
	die();
}

$prefs = Preferences::getInstance();

$web_interface_settings = $prefs->get('general', 'web_interface_settings');
if (array_key_exists('public_webservices_access', $web_interface_settings) && $web_interface_settings['public_webservices_access'] == 1) {
	// ok
}

elseif (array_key_exists('session_id', $_SESSION)) {
	$session = Abstract_Session::load($_SESSION['session_id']);
	if (! $session) {
		echo return_error(3, 'No such session "'.$_SESSION['session_id'].'"');
		die();
	}

	/*if (! in_array($_GET['id'], $session->applications)) {
		echo return_error(4, 'Unauthorized application');
		die();
	}*/
}

else {
	Logger::debug('main', '(client/applications) No Session id nor public_webservices_access');
	echo return_error(7, 'No Session id nor public_webservices_access');
	die();
}

$applicationDB = ApplicationDB::getInstance();
$applications = $applicationDB->getApplicationsWithMimetype($_GET['id']);

$apps = array();
foreach ($applications as $application) {
	if (! $application->haveIcon())
		continue;

	$score = count($application->groups());
	if ($application->getAttribute('type') == 'windows')
		$score += 10;

	$apps[$score] = $application;
}

header('Content-Type: image/png');

$first = new Imagick(realpath(dirname(__FILE__).'/../admin/media/image/empty.png'));

if (! is_array($apps) || count($apps) == 0) {
	echo $first;
	die();
}

arsort($apps);
$application = array_shift($apps);

$second = new Imagick(realpath($application->getIconPath()));
$second->scaleImage(16, 16);

$first->setImageColorspace($second->getImageColorspace());
$first->compositeImage($second, $second->getImageCompose(), 6, 10);

echo $first;
