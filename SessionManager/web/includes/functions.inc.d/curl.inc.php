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

function query_url_no_error($url_) {
	Logger::debug('main', 'HTTP Query: "'.$url_.'"');

	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, DEFAULT_REQUEST_TIMEOUT);
	curl_setopt($socket, CURLOPT_TIMEOUT, (DEFAULT_REQUEST_TIMEOUT+5));
	$string = curl_exec($socket);
	curl_close($socket);

	Logger::debug('main', 'HTTP returntext: "'.$string.'"');

	return $string;
}

function query_url_return_errorcode($url_) {
	Logger::debug('main', 'HTTP query: "'.$url_.'"');

	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, DEFAULT_REQUEST_TIMEOUT);
	curl_setopt($socket, CURLOPT_TIMEOUT, (DEFAULT_REQUEST_TIMEOUT+5));
	$string = curl_exec($socket);
	$buf = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	curl_close($socket);

	if ($buf != 200)
		Logger::debug('main', 'HTTP returncode: "'.$buf.'"');

	Logger::debug('main', 'HTTP returntext: "'.$string.'"');

	return array($buf, $string);
}

function query_url($url_) {
	Logger::debug('main', 'HTTP query: "'.$url_.'"');

	$socket = curl_init($url_);
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, DEFAULT_REQUEST_TIMEOUT);
	curl_setopt($socket, CURLOPT_TIMEOUT, (DEFAULT_REQUEST_TIMEOUT+5));
	$string = curl_exec($socket);
	$buf = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	curl_close($socket);

	if ($buf != 200) {
		Logger::debug('main', 'HTTP returncode: "'.$buf.'"');
		return false;
	}

	Logger::debug('main', 'HTTP returntext: "'.$string.'"');

	return $string;
}
