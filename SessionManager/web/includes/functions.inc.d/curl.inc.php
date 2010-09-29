<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
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

	$hr = new HttpRequest($url_, 'GET');
	if ($data_in_file_ === true) {
		$data_file = tempnam(NULL, 'curl_');
		$fp = fopen($data_file, 'w');
		$hr->setToFile($fp);
	}
	$data = $hr->send();
	if ($data_in_file_ === true) {
		$data = $data_file;
		fclose($fp);
	}

	if ($data === false)
		Logger::error('main', "query_url_request($url_) error code: ".$hr->getResponseErrno(). " text: '".$hr->getResponseError()."'");
	else {
		if (str_startswith($hr->getResponseContentType(), 'text/') && $log_returned_data_ === true)
			Logger::debug('main', "query_url_request($url_) returntext: '$data'");
	}

	if ($hr->getResponseCode() != 200)
		Logger::debug('main', "query_url_request($url_) returncode: '".$hr->getResponseCode()."'");

	return array('data' => $data, 'code' => $hr->getResponseCode(), 'content_type' => $hr->getResponseContentType());
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

function query_url_post_xml($url_, $xml_, $log_returned_data_=true) {
	Logger::debug('main', "query_url_post_xml($url_, $xml_, $log_returned_data_)");

	$hr = new HttpRequest($url_, 'POST', array('contentType' => 'text/xml', 'postFields' => $xml_));
	$data = $hr->send();
	if ($data === false)
		Logger::error('main', "query_url_post_xml($url_) error code: ".$hr->getResponseErrno()." text: '".$hr->getResponseError()."'");
	else {
		if (str_startswith($hr->getResponseContentType(), 'text/') && $log_returned_data_ === true)
			Logger::debug('main', "query_url_post_xml($url_) returntext: '$data'");
	}

	return $data;
}

function query_url_post($url_, $string_=NULL, $log_returned_data_=true) {
	Logger::debug('main', "query_url_post($url_, $string_, $log_returned_data_)");

	$hr = new HttpRequest($url_, 'POST', array('postFields' => $string_));
	$data = $hr->send();
	if ($data === false)
		Logger::error('main', "query_url_post($url_) error code: ".$hr->getResponseErrno(). " text: '".$hr->getResponseError()."'");
	else {
		if (str_startswith($hr->getResponseContentType(), 'text/') && $log_returned_data_ === true)
			Logger::debug('main', "query_url_post($url_) returntext: '$data'");
	}

	return $data;
}
