<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

Logger::debug('main', 'Starting webservices/session_end_status.php');

if (! isset($_GET['session'])) {
	Logger::error('main', '(webservices/session_end_status) Missing parameter : session');
	die('ERROR - NO $_GET[\'session\']');
}

if (! isset($_GET['status'])) {
	Logger::error('main', '(webservices/session_end_status) Missing parameter : status');
	die('ERROR - NO $_GET[\'status\']');
}

if (! isset($_GET['fqdn'])) {
	Logger::error('main', '(webservices/session_end_status) Missing parameter : fqdn');
	die('ERROR - NO $_GET[\'fqdn\']');
}

$buf = Abstract_Server::load($_GET['fqdn']);
if (! $buf || ! $buf->isAuthorized()) {
	Logger::error('main', '(webservices/session_end_status) Server not authorized : \''.$_GET['fqdn'].'\' == \''.@gethostbyname($_GET['fqdn']).'\' ?');
	die('Server not authorized');
}

Logger::debug('main', '(webservices/session_end_status) Security check OK');

$session = Abstract_Session::load($_GET['session']);

if (! $session)
	die('ERROR - No such session');

Logger::debug('main', '(webservices/session_end_status) Session \''.$session->id.'\' on server \''.$session->server.'\' have end status \''.$_GET['status'].'\'');

$report = Abstract_Report::loadSession($_GET['session']);
if (! $report)
	die('ERROR - No such report');

$report->stop_why = $_GET['status'];

$ret = Abstract_Report::updateSession($report);
