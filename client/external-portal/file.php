<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
 
require_once('config.inc.php');
require_once('functions.inc.php');

if (! array_key_exists('path', $_REQUEST)) {
	header('401 bad request');
	die('bad request');
}
$path = GED_FOLDER.'/'.$_REQUEST['path'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (count($_FILES) <= 0) {
		header('401 bad request');
		die('bad request');
	}
	
	$upload = array_shift($_FILES);
	
	if(array_key_exists('error', $upload) && $upload['error'] != 0) {
		header('500 Internal server error');
		die('500 Internal server error');
	}
	
	$source_file = $upload['tmp_name'];
	if (! is_readable($source_file)) {
		header('500 Internal server error');
		die('500 Internal server error');
	}
	
	move_uploaded_file ($source_file, $path);
}
else {
	if (! is_readable($path)) {
		header('404 Not Found');
		die('404 Not Found');
	}
	
	$m = mime_content_type($path);
	header('Content-type: '.$m); 
	$content = file_get_contents($path);
	die($content);
}
