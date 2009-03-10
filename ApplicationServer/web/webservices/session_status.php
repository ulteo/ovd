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

if (! isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager or Application Server');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager or Application Server');
}

if (! isset($_REQUEST['session'])) {
	Logger::error('main', 'Missing argument "session"');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Missing argument "session"');
}
$session = $_REQUEST['session'];

if (file_exists(SESSION2CREATE_PATH.'/'.$session)) {
	Logger::info('main', 'Session is being created : '.$session);
	die('-1');
}

if (!is_readable(SESSION_PATH.'/'.$session.'/infos/status')) {
	Logger::error('main', 'No such session : '.$session);
	die('4');
}

$vncpass = get_from_file(SESSION_PATH.'/'.$session.'/clients/hexavncpasswd');
$sshuser = get_from_file(SESSION_PATH.'/'.$session.'/clients/sshuser');
$sshpass = get_from_file(SESSION_PATH.'/'.$session.'/clients/hexasshpasswd');
$rfbport = get_from_file(SESSION_PATH.'/'.$session.'/clients/rfbport');
$status = get_from_file(SESSION_PATH.'/'.$session.'/infos/status');

if ($status === false)
	$status = -2;

die($status);
