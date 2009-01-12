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

$ip = $_SERVER['REMOTE_ADDR'];
$name = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
$buf = @gethostbyname($name);
$use = $ip;
if ($buf == $ip)
	$use = $name;

$buf = Server::loadDB($use);
if ($buf) {
	return_error('a');
	die();
}

$buf = new Server($use);
// if (!$buf->isOnline()) {
// 	return_error('b');
// 	die();
// }
if ($buf->isRegistered()) {
	return_error('c');
	die();
}

return_ok('d');
echo $buf->generateKey();
$buf->saveDB();
die();
