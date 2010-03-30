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
require_once(dirname(__FILE__).'/includes/core.inc.php');

$server = $_SERVER['SERVER_NAME'];
$session = $_SESSION['ovd_session']['session'];

if (!isset($session) || $session == '') {
	Logger::critical('main', '(start_app) No SESSION');
	die('CRITICAL ERROR'); // That's odd !
}

if (!isset($_GET['app_id']) || $_GET['app_id'] == '') {
	Logger::critical('main', '(start_app) No app_id');
	die('CRITICAL ERROR'); // That's odd !
}

if (!isset($_GET['size']) || $_GET['size'] == '') {
	Logger::critical('main', '(start_app) No size');
	die('CRITICAL ERROR'); // That's odd !
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');

$buf = gen_string(5);

$app_args = '';
if (isset($_GET['app_args']) && $_GET['app_args'] != '')
	$app_args = $_GET['app_args']."\n";
put_to_file(SESSION_PATH.'/'.$session.'/sessions/'.$buf.'.txt', $_GET['app_id']."\n".$_GET['size']."\n".$app_args);

$error = false;

if ($error === false) {
	for ($i = 0; (! is_dir(SESSION_PATH.'/'.$session.'/sessions/'.$buf) || get_from_file(SESSION_PATH.'/'.$session.'/sessions/'.$buf.'/status') != 2); $i++) {
		if ($i >= 20) {
			$errno = 1;
			$errstr = 'Unable to start external application';
			break;
		}

		sleep(1);
	}
}

if (isset($errno) && isset($errstr)) {
	$error_node = $dom->createElement('error');
	$error_node->setAttribute('errno', $errno);
	$error_node->setAttribute('errstr', $errstr);
	$dom->appendChild($error_node);
} else {
	$access_node = $dom->createElement('access');
	$access_node->setAttribute('id', $buf);
	$dom->appendChild($access_node);
}

$xml = $dom->saveXML();

echo $xml;
