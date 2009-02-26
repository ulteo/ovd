<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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

Logger::debug('main', 'Starting webservices/app_desktopfile.php');

if (! isset($_GET['desktopfile'])) {
	Logger::error('main', '(webservices/app_desktopfile) Missing parameter : desktopfile');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - NO $_GET[\'desktopfile\']');
}

if (! isset($_GET['fqdn'])) {
	Logger::error('main', '(webservices/app_desktopfile) Missing parameter : fqdn');
	die('ERROR - NO $_GET[\'fqdn\']');
}

$buf = Abstract_Server::load($_GET['fqdn']);
if (! $buf || ! $buf->isAuthorized()) {
	Logger::error('main', '(webservices/session_token) Server not authorized : '.$_GET['fqdn'].' == '.@gethostbyname($_GET['fqdn']).' ?');
	die('Server not authorized');
}

Logger::debug('main', '(webservices/app_desktopfile) Security check OK');

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$mods_enable = $prefs->get('general', 'module_enable');
if (! in_array('ApplicationDB', $mods_enable)) {
	Logger::error('main', '(webservices/app_desktopfile) Module ApplicationDB must be enabled');
	header('HTTP/1.1 400 Bad Request');
	die();
}

$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB', 'enable');
$applicationDB = new $mod_app_name();

$apps = $applicationDB->getList();

if (! is_array($apps)) {
	Logger::error('main', '(webservices/app_desktopfile) error getList failed');
	header('HTTP/1.1 400 Bad Request');
}

foreach ($apps as $app) {
	if ($app->hasAttribute('desktopfile')) {
		if ($app->getAttribute('desktopfile') == stripslashes($_GET['desktopfile'])) {
			header('Content-Type: text/xml; charset=utf-8');
			echo $app->toXML();
			die();
		}
	}
}
Logger::error('main', '(webservices/app_desktopfile) error final');
header('HTTP/1.1 404 Not Found');
