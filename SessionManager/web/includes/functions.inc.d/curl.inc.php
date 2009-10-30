<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
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

function query_url_request($url_, $log_returned_data_=true, $data_in_file_=false) {
	Logger::debug('main', "query_url_request($url_,$log_returned_data_, $data_in_file_)");
	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, DEFAULT_REQUEST_TIMEOUT);
	curl_setopt($socket, CURLOPT_TIMEOUT, (DEFAULT_REQUEST_TIMEOUT+5));
	if ( $data_in_file_ === true) {
		$data_file = tempnam(sys_get_temp_dir(), "curl_");
		$fp = fopen($data_file, 'w');
		curl_setopt($socket, CURLOPT_FILE, $fp);
	}
	$data = curl_exec($socket);
	$code = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	$content_type=curl_getinfo($socket, CURLINFO_CONTENT_TYPE);
	if ( $data_in_file_ === true) {
		$data = $data_file;
		fclose($fp);
	}

	curl_close($socket);
	
	if ($code != 200)
		Logger::debug('main', "query_url_request($url_) returncode: '$code'");
	
	if (str_startswith($content_type, 'text/') && $log_returned_data_ === true)
		Logger::debug('main', "query_url_request($url_) returntext: '$data'");
	
	return array('data' => $data, 'code' => $code, 'content_type' => $content_type);
}

function query_url_no_error($url_, $log_returned_data_=true) {
	$ret = query_url_request($url_, $log_returned_data_);
	return $ret['data'];
}

function query_url_return_errorcode($url_, $log_returned_data_=true) {
	$ret = query_url_request($url_, $log_returned_data_);
	return array($ret['code'], $ret['data']);
}

function query_url($url_, $log_returned_data_=true) {
	$ret = query_url_request($url_, $log_returned_data_);
	if ($ret['code'] != 200) {
		Logger::error('main', "query_url($url_) returncode: '".$ret['code']."'");
		return false;
	}
	return $ret['data'];
}
