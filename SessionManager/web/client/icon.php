<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	return $dom->saveXML();
}

if (! array_key_exists('id', $_GET)) {
	echo return_error(1, 'Usage: missing "id" $_GET parameter');
	die();
}

if (! array_key_exists('session_id', $_SESSION)) {
	echo return_error(1, 'Usage: missing "session_id" $_SESSION parameter');
	die();
}

$session = Abstract_Session::load($_SESSION['session_id']);
if (! $session) {
	echo return_error(1, 'No such session "'.$_SESSION['session_id'].'"');
	die();
}

/*if (! in_array($_GET['id'], $session->applications)) {
	echo return_error(1, 'Unauthorized application');
	die();
}*/

$applicationDB = ApplicationDB::getInstance();

$app = $applicationDB->import($_GET['id']);
if (! is_object($app)) {
	echo return_error(1, 'No such application "'.$_GET['id'].'"');
	die();
}

$path = $app->getIconPath();
if (! file_exists($path)) {
	echo return_error(1, 'No icon available for application "'.$_GET['id'].'"');
	die();
}

header('Content-Type: image/png');
echo @file_get_contents($path);
