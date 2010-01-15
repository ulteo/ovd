<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

Logger::debug('main', 'Starting webservices/icon.php');

if (! isSessionManagerRequest()) {
	Logger::error('main', 'Request not coming from Session Manager');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Request not coming from Session Manager');
}

if (!isset($_GET['path'])) {
	Logger::error('main', 'Missing parameter : path');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - NO $_GET[\'path\']');
}

if ( get_classes_startwith('Imagick') == array()) {
	Logger::error('main', 'No Imagick support found');
	header('HTTP/1.1 500 Internal Server Error');
	die();
}

$path = base64_decode($_GET['path']);
if (! $path) {
	Logger::error('main', 'Parameter path must be base64');
	header('HTTP/1.1 400 Bad Request');
	die('ERROR - Parameter path must be base64');
}

if ($path[0] == DIRECTORY_SEPARATOR) { // is it an absolute path ?
	$icon_files = array($path);
}
else {
	$icon_files = shell_exec('find '.CHROOT.'/usr/share/pixmaps '.CHROOT.'/usr/share/icons -iname \'*'.$path.'*\'');
	$icon_files = explode("\n", $icon_files);
}

foreach ($icon_files as $k => $v) {
	if ($v == '') {
		unset($icon_files[$k]);
		continue;
	}

	$icon_files[$k] = chroot_realpath($icon_files[$k]);
}

if (count($icon_files) == 0) {
	header('HTTP/1.1 404 Not Found');
	die();
}

$tab1 = array();
$tab2 = array();
foreach ($icon_files as $image) {
	if (!is_file($image) || !is_readable($image))
		continue;

	$buf_img = new Imagick($image);
	$buf = array();
// 	$buf[0] = $buf_img->getImageWidth();
	$buf[1] = $buf_img->getImageHeight();

	if ($buf[1] >= 32)
		$tab1[$buf[1]] = $image;
	else
		$tab2[$buf[1]] = $image;
}

if (count($tab1) > 0) {
	krsort($tab1);
	$image = array_pop($tab1);
} elseif (count($tab2) > 0) {
	ksort($tab2);
	$image = array_pop($tab2);
} else {
	header('HTTP/1.1 404 Not Found');
	die();
}

try {
	$buf = new Imagick($image);
	$buf->setImageFormat('png');
	$buf->scaleImage(0, 32);
}
catch (Exception $e) {
	Logger::error('main', '(webservices/icon.php) exception while scaling '.$image.' (path=\''.$path.'\'): '.$e->getMessage());
	header('HTTP/1.1 404 Not Found');
	die();
}

header('Content-Type: image/png');
echo $buf;
