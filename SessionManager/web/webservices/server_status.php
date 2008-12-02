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

Logger::debug('main', 'Starting webservices/server_status.php');

if (!isset($_GET['status'])) {
	Logger::error('main', 'Missing parameter : status');
	die('ERROR - NO $_GET[\'status\']');
}

if (!isset($_GET['fqdn'])) {
	Logger::error('main', 'Missing parameter : fqdn');
	die('ERROR - NO $_GET[\'fqdn\']');
}

if (!check_ip($_GET['fqdn'])) {
	Logger::error('main', 'Server not authorized : '.$_GET['fqdn'].' ? '.@gethostbyname($_GET['fqdn']));
	die('Server not authorized');
}

Logger::debug('main', 'Security check OK');

$server = new Server($_GET['fqdn']);
$server->setStatus($_GET['status']);
