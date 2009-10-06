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

function query_url_request($url_) {
	Logger::debug('main', "query_url_request($url_)");
	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, DEFAULT_REQUEST_TIMEOUT);
	curl_setopt($socket, CURLOPT_TIMEOUT, (DEFAULT_REQUEST_TIMEOUT+5));
	$data = curl_exec($socket);
	$code = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	$content_type=curl_getinfo($socket, CURLINFO_CONTENT_TYPE);
	curl_close($socket);
	
	if ($code != 200)
		Logger::debug('main', "query_url_request($url_) returncode: '$code'");
	
	if (str_startswith($content_type, 'text/'))
		Logger::debug('main', "query_url_request($url_) returntext: '$data'");
	
	return array('data' => $data, 'code' => $code, 'content_type' => $content_type);
}

function query_url_no_error($url_) {
	$ret = query_url_request($url_);
	return $ret['data'];
}

function query_url_return_errorcode($url_) {
	$ret = query_url_request($url_);
	return array($ret['code'], $ret['data']);
}

function query_url($url_) {
	$ret = query_url_request($url_);
	if ($ret['code'] != 200) {
		Logger::error('main', "query_url($url) returncode: '$code'");
		return false;
	}
	return array($ret['data']);
}
