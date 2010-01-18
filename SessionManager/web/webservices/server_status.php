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

$exists = Abstract_Server::exists($_GET['fqdn']);

if (! $exists) {
	$buf = new Server($_GET['fqdn']);

	$buf->registered = false;
	$buf->locked = true;

	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error(_('get Preferences failed'), __FILE__, __LINE__);

	$buf_prefs = $prefs->get('general', 'application_server_settings');
	$auto_register_new_servers = $buf_prefs['auto_register_new_servers'];
	$auto_switch_new_servers_to_production = $buf_prefs['auto_switch_new_servers_to_production'];

	if ($auto_register_new_servers == 1)
		$buf->registered = true;

	if ($auto_switch_new_servers_to_production == 1)
		$buf->locked = false;

	$buf->external_name = $buf->fqdn;
	if (isset($_GET['web_port']))
		$web_port = $_GET['web_port'];
	else
		$web_port = 80;
	$buf->web_port = $web_port;
	$buf->max_sessions = 20;

	if (! $buf->isAuthorized()) {
		Logger::error('main', '(webservices/server_status) Server not authorized : \''.$_GET['fqdn'].'\' == \''.@gethostbyname($_GET['fqdn']).'\' ?');
		die('Server not authorized');
	}

	if (! $buf->isOnline()) {
		Logger::error('main', '(webservices/server_status) Server not "ready" : \''.$_GET['fqdn'].'\'');
		die('Server not "ready"');
	}

	if (! $buf->isOK()) {
		Logger::error('main', '(webservices/server_status) Server not OK : \''.$_GET['fqdn'].'\'');
		die('Server not OK');
	}
} else {
	$buf = Abstract_Server::load($_GET['fqdn']);
	if (! $buf->isAuthorized()) {
		Logger::error('main', '(webservices/server_status) Server not authorized : \''.$_GET['fqdn'].'\' == \''.@gethostbyname($_GET['fqdn']).'\' ?');
		die('Server not authorized');
	}
}

$buf->setStatus($_GET['status']);

Abstract_Server::save($buf);

try {
	$buf->updateApplications();
}
catch (Exception $e) {
	Logger::warning('main', '(webservices/server_status) updateApplications error for \''.$_GET['fqdn'].'\'');
}
