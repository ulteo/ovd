<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author: Gauvain Pocentek <gauvain@ulteo.com>
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

Logger::debug('main', 'Starting webservices/htaccess.php');

if (! isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager');
}

foreach (array('type', 'key', 'value') as $key) {
	if (!isset($_GET[$key])) {
		Logger::error('main', "Missing parameter: $key");
		header('HTTP/1.1 400 Bad Request');
		die("ERROR - NO \$_GET['$key']");
	}
}

$type = $_GET['type'];
$key = $_GET['key'];
$value = $_GET['value'];

$know_types = array(
	'php_value'
);

if (!in_array($type, $know_types)) {
	Logger::error('main', "Unknown type: $type");
	header('HTTP/1.1 400 Bad Request');
	die("ERROR - Unknown type: $type");
}

if ($type == 'php_value') {
	if (Htaccess::set_php_value($key, $value) === false) {
		Logger::error('main', 'Unable to write the htaccess file');
		header('HTTP/1.1 400 Bad Request');
		die('ERROR - Unable to write the htaccess file');
	}
	print 'OK';
}

