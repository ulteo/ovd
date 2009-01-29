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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

Logger::debug('main', '(webservices/server_status) Starting webservices/server_status.php');

if (! isset($_GET['status'])) {
	Logger::error('main', '(webservices/server_status) Missing parameter : status');
	die('ERROR - NO $_GET[\'status\']');
}

if (! isset($_GET['fqdn'])) {
	Logger::error('main', '(webservices/server_status) Missing parameter : fqdn');
	die('ERROR - NO $_GET[\'fqdn\']');
}

Logger::debug('main', '(webservices/server_status) Security check OK');

$buf = Abstract_Server::load($_GET['fqdn']);

if (! $buf) {
	$buf = new Server($_GET['fqdn']);

	$buf->registered = false;
	$buf->locked = true;
	$buf->external_name = $buf->fqdn;
	if (isset($_GET['web_port']))
		$web_port = $_GET['web_port'];
	else
		$web_port = 80;
	$buf->web_port = $web_port;
	$buf->max_sessions = 20;

	if (! $buf->isAuthorized()) {
		Logger::error('main', '(webservices/server_status) Server not authorized : '.$_GET['fqdn'].' == '.@gethostbyname($_GET['fqdn']).' ?');
		die('Server not authorized');
	}

	$buf_type = $buf->getType();
	if (! $buf_type) {
		Logger::error('main', '(webservices/server_status) Server does not send a valid type');
		die('Server does not send a valid type');
	}
	$buf_version = $buf->getVersion();
	if (! $buf_version) {
		Logger::error('main', '(webservices/server_status) Server does not send a valid version');
		die('Server does not send a valid version');
	}
	$buf_monitoring = $buf->getMonitoring();
	if (! $buf_monitoring) {
		Logger::error('main', '(webservices/server_status) Server does not send a valid monitoring');
		die('Server does not send a valid monitoring');
	}
}

$buf->setStatus($_GET['status']);

Abstract_Server::save($buf);
