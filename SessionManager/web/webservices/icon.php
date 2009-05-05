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

Logger::debug('main', 'Starting webservices/icon.php');

if (! isset($_GET['id'])) {
	Logger::error('main', '(webservices/icon) Missing parameter : id');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - NO $_GET[\'id\']');
}

if (! isset($_GET['fqdn'])) {
	Logger::error('main', '(webservices/icon) Missing parameter : fqdn');
	die('ERROR - NO $_GET[\'fqdn\']');
}

$buf = Abstract_Server::load($_GET['fqdn']);
if (! $buf || ! $buf->isAuthorized()) {
	Logger::error('main', '(webservices/icon) Server not authorized : '.$_GET['fqdn'].' == '.@gethostbyname($_GET['fqdn']).' ?');
	die('Server not authorized');
}

Logger::debug('main', '(webservices/icon) Security check OK');

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$mods_enable = $prefs->get('general', 'module_enable');
if (! in_array('ApplicationDB', $mods_enable)) {
	Logger::error('main', '(webservices/icon) Module ApplicationDB must be enabled');
	header('HTTP/1.1 400 Bad Request');
	die();
}

$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB', 'enable');
$applicationDB = new $mod_app_name();

$app = $applicationDB->import($_GET['id']);

if (! is_object($app)) {
	Logger::error('main', '(webservices/icon) error import app failed for \''.$_GET['id'].'\'');
	header('HTTP/1.1 400 Bad Request');
}


$path = $app->getIconPath();
if (file_exists($path)) {
	header('Content-Type: image/png');
	echo @file_get_contents($path);
}

header('HTTP/1.1 404 Not Found');
