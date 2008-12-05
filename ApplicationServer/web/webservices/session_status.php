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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

Logger::debug('main', 'Starting webservices/session_status.php');

if (isset($_SESSION['session']))
	$session = $_SESSION['session'];
elseif (isset($_REQUEST['session'])) {
	if (! isSessionManagerRequest()) {
		Logger::error('main', 'Request not coming from Session Manager or Application Server');
		header('HTTP/1.1 400 Bad Request');
		die('ERROR - Request not coming from Session Manager or Application Server');
	}

	$session = $_REQUEST['session'];
}

if (!isset($session)) {
// 	Logger::error('main', 'No $session');
	die('ERROR - No $session');
}

if (file_exists(SESSION2CREATE_PATH.'/'.$session)) {
	Logger::info('main', 'Session is being created : '.$session);
	die('-1');
}

if (!is_readable(SESSION_PATH.'/'.$session)) {
	Logger::error('main', 'No such session : '.$session);
	die('3');
}

$vncpass = get_from_file(SESSION_PATH.'/'.$session.'/hexavncpasswd');
$sshuser = get_from_file(SESSION_PATH.'/'.$session.'/sshuser');
$sshpass = get_from_file(SESSION_PATH.'/'.$session.'/hexasshpasswd');
$rfbport = get_from_file(SESSION_PATH.'/'.$session.'/rfbport');
$runasap = get_from_file(SESSION_PATH.'/'.$session.'/runasap');

if ($runasap === false)
	$runasap = -2;

die($runasap);
