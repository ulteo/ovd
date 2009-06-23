<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
require_once(dirname(__FILE__).'/core.inc.php');

function redirect($url_=NULL) {
	if (is_null($url_)) {
		if (! isset($_SERVER['HTTP_REFERER'])) {
			global $base_url;
			$url_ = $base_url;
		} else
			$url_ = $_SERVER['HTTP_REFERER'];
	}

	header('Location: '.$url_);
	die();
}

function get_from_file($file_) {
	if (!is_readable($file_)) {
		Logger::error('main', 'Unable to read from : '.$file_);
		return false;
		//die('Unable to read from : '.$file_);
	}

	$buf = trim(@file_get_contents($file_));

	if ($buf == '') {
		Logger::error('main', 'File is empty : '.$file_);
		return false;
		//die('File is empty : '.$file_);
	}

	//Logger::debug('main', 'Reading '.$buf.' from '.$file_);

	return $buf;
}

function put_to_file($file_, $data_) {
	if ($data_ == '') {
		Logger::warning('main', 'Empty string for : '.$file_);
		return false;
		//die('Empty string for : '.$file_);
	} else {
		if (! @file_put_contents($file_, $data_)) {
			Logger::error('main', 'Unable to write to : '.$file_);
			return false;
			//die('Unable to write to : '.$file_);
		}
	}

	//Logger::debug('main', 'Writing '.$data_.' to '.$file_);

	return true;
}

function query_url($url_) {
	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 10);
	$string = curl_exec($socket);
	$buf = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	curl_close($socket);

	if ($buf != 200)
		return false;

	return $string;
}

function ret200($msg_=false) {
	header('HTTP/1.1 200 OK');
}

function ret400($msg_=false) {
	header('HTTP/1.1 400 Bad Request');
}

function ret403($msg_=false) {
	header('HTTP/1.1 403 Forbidden');
}

function ret404($msg_=false) {
	header('HTTP/1.1 404 Not Found');
}

function die2($httpcode, $message) {
  $http = array(200 => 'OK',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		500 => 'Internal Server Error');

  Logger::debug('main', 'die2 ('.$httpcode.'): '.$message);

  if (isset($http[$httpcode]))
    $httpmessage = $http[$httpcode];
  else
    $httpmessage = 'OK';

  header('HTTP/1.1 '.$httpcode.' '.$httpmessage);
  //die();
  die('die2 ('.$httpcode.'): '.$message);
}

function getSessionManagerHost() {
  return parse_url(SESSIONMANAGER_URL, PHP_URL_HOST);
}

function isSessionManagerRequest() {
  $address = $_SERVER['REMOTE_ADDR'];
  $name = getSessionManagerHost();

  if (preg_match('/[0-9]{1,3}(\.[0-9]{1,3}){3}/', $name))
    return ($name == $address);

  $reverse = @gethostbyaddr($address);
  if ($reverse == $name)
    return true;

  Logger::error('main', 'isSessionManagerRequest() - IP: '.$address.' / Name: '.$name.' / Reverse: '.$reverse);

  return false;
}

function is_writable2($filename) {
  return ( (is_file($filename) && is_writable($filename)) ||  is_writable(dirname($filename)) );
}

function get_classes_startwith($start_name) {
	$classes_name = get_declared_classes();
	
	$ret = array();
	foreach ($classes_name as $name)
		if (substr($name, 0, strlen($start_name)) == $start_name)
			$ret[] = $name;
	
	return $ret;
}

function gen_string($nc, $st='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
	$len = strlen($st)-1;

	$ret = '';
	while ($nc-- > 0)
		$ret .= $st{mt_rand(0, $len)};

	return $ret;
}
