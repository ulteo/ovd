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

Logger::debug('main', 'Starting webservices/kill_session.php');

if (!isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager');
}

if (!isset($_GET['session'])) {
	Logger::error('main', 'Missing parameter : session');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - NO $_GET[\'session\']');
}
$session = $_GET['session'];

if (!is_readable(SESSION_PATH.'/'.$session)) {
	Logger::error('main', 'No such session : '.$session);
	header('HTTP/1.1 400 Bad Request');
	die('No such session : '.$session);
}

if (!is_writable(SESSION_PATH.'/'.$session.'/runasap')) {
	Logger::error('main', 'Unable to kill session : '.$session);
 	header('HTTP/1.1 400 Bad Request');
	die('Unable to kill session : '.$session);
}

@file_put_contents(SESSION_PATH.'/'.$session.'/runasap', '3');
