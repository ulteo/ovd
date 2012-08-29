<?php
/**
 * Copyright (C) 2011-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2011 
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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
	private $base_url;
	private $cookies;
	private $lastfilemtime;
	
	public function __construct($base_url_) {
		$this->base_url = $base_url_;
		$this->cookies = array();
	}
	
	public function query($url_) {
		$socket = $this->build_curl_instance($url_);
		
		$string = curl_exec($socket);
		$buf = curl_getinfo($socket, CURLINFO_HTTP_CODE);
		$this->lastfilemtime = curl_getinfo($socket, CURLINFO_FILETIME);
		curl_close($socket);
		
		if ($buf != 200)
			return false;
		
		return $string;
	}
	
	
	public function get_last_file_mtime() {
		return $this->lastfilemtime;
	}
	
	
	public function query_post_xml($url_, $xml_) {
		$socket = $this->build_curl_instance($url_);
		
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
		
		preg_match('@Set-Cookie: (.*)=(.*);@', $headers, $matches);
		if (count($matches) == 3)
			$this->cookies[$matches[1]] = $matches[2];
		
		if ($http_code == 302) {
			preg_match('@Location: (.*)\n@', $headers, $matches);
			if (count($matches) == 2)
				return array($http_code, $matches[1]);
		}
		
		return $body;
	}
	
	
	private function build_curl_instance($url_) {
		$socket = curl_init($this->base_url.'/'.$url_);
		curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($socket, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($socket, CURLOPT_TIMEOUT, (10+5));
		curl_setopt($socket, CURLOPT_FILETIME, true);
		
		foreach($this->cookies as $k => $v)
			curl_setopt($socket, CURLOPT_COOKIE, $k.'='.$v);
		
		return $socket;
	}
	
	public function get_base_url() {
		return $this->base_url;
	}
	
	public function set_base_url($base_url_) {
		$this->base_url = $base_url_;
	}
}
