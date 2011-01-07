<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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

function return_error($errno_, $errstr_) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	Logger::error('main', "(client/icon) return_error($errno_, $errstr_)");
	return $dom->saveXML();
}

$prefs = Preferences::getInstance();
$web_interface_settings = $prefs->get('general', 'web_interface_settings');
if (! array_key_exists('public_webservices_access', $web_interface_settings) || $web_interface_settings['public_webservices_access'] == 0) {
	Logger::debug('main', '(client/applications) Public webservices access is disabled, aborting');
	echo return_error(1, 'Public webservices access is disabled');
	die();
}


if (! array_key_exists('user', $_REQUEST)) {
	Logger::error('main', '(client/applications) - no user given');
	echo return_error(1, 'No user given');
	die();
}
$user_login = $_REQUEST['user'];

$userDB = UserDB::getInstance();
$user = $userDB->import($user_login);
if (! is_object($user)) {
	Logger::error('main', '(client/applications) - Unknown user '.$_REQUEST['user']);
	echo return_error(3, 'Unknown user '.$_REQUEST['user']);
	die();
}

/*
ToDo: implement this kind of generic authentication

$sessionManagement = SessionManagement::getInstance();
if (! $sessionManagement->initialize()) {
	Logger::error('main', '(client/applications) SessionManagement initialization failed');
	throw_response(INTERNAL_ERROR);
}

if (! $sessionManagement->authenticate()) {
	Logger::error('main', '(client/applications) Authentication failed');
	throw_response(AUTH_FAILED);
}

if ($sessionManagement->user === false) {
	Logger::error('main', '(client/applications) - no user given');
	throw_response(25); // todo: change 
}
*/


$applications = $user->applications();


header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$user_node = $dom->createElement('user');
$user_node->setAttribute('login', $user->getAttribute('login'));

foreach ($applications as $application) {
	$application_node = $dom->createElement('application');
	$application_node->setAttribute('id', $application->getAttribute('id'));
	$application_node->setAttribute('name', $application->getAttribute('name'));
	$application_node->setAttribute('description', $application->getAttribute('description'));
	foreach ($application->getMimeTypes() as $mimetype) {
		$mimetype_node = $dom->createElement('mime');
		$mimetype_node->setAttribute('type', $mimetype);
		$application_node->appendChild($mimetype_node);
	}
	$user_node->appendChild($application_node);
}

$dom->appendChild($user_node);

echo $dom->saveXML();
exit(0);
