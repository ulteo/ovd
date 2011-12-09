<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2011 
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


class SessionManager {
	public static function query($url_) {
		$socket = self::build_curl_instance($url_);
		
		$string = curl_exec($socket);
		$buf = curl_getinfo($socket, CURLINFO_HTTP_CODE);
		curl_close($socket);
		
		if ($buf != 200)
			return false;
		
		return $string;
	}
	
	
	public static function query_post_xml($url_, $xml_) {
		$socket = self::build_curl_instance($url_);
		
		curl_setopt($socket, CURLOPT_HEADER, 1);
		
		curl_setopt($socket, CURLOPT_POSTFIELDS, $xml_);
		curl_setopt($socket, CURLOPT_HTTPHEADER, array('Connection: close', 'Content-Type: text/xml'));
		
		$string = curl_exec($socket);
		$http_code = curl_getinfo($socket, CURLINFO_HTTP_CODE);
		$headers_size = curl_getinfo($socket, CURLINFO_HEADER_SIZE);
		curl_close($socket);
		
		$headers = substr($string, 0, $headers_size);
		$body = substr($string, $headers_size);
		
		if (! in_array($http_code, array(200, 302)))
			return false;
		
		if (! array_key_exists('session_var', $_SESSION['ovd-client']['sessionmanager']) || ! array_key_exists('session_id', $_SESSION['ovd-client']['sessionmanager'])) {
			preg_match('@Set-Cookie: (.*)=(.*);@', $headers, $matches);
			if (count($matches) == 3) {
				$_SESSION['ovd-client']['sessionmanager']['session_var'] = $matches[1];
				$_SESSION['ovd-client']['sessionmanager']['session_id'] = $matches[2];
			}
		}
		
		if ($http_code == 302) {
			preg_match('@Location: (.*)\n@', $headers, $matches);
			if (count($matches) == 2)
				return array($http_code, $matches[1]);
		}
		
		return $body;
	}
	
	
	public static function build_curl_instance($url_) {
		if (! array_key_exists('sessionmanager', $_SESSION['ovd-client']))
			$_SESSION['ovd-client']['sessionmanager'] = array();
		
		$socket = curl_init($url_);
		curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($socket, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($socket, CURLOPT_TIMEOUT, (10+5));
		
		if (array_key_exists('session_var', $_SESSION['ovd-client']['sessionmanager']) && array_key_exists('session_id', $_SESSION['ovd-client']['sessionmanager']))
			curl_setopt($socket, CURLOPT_COOKIE, $_SESSION['ovd-client']['sessionmanager']['session_var'].'='.$_SESSION['ovd-client']['sessionmanager']['session_id']);
		
		return $socket;
	}
}
