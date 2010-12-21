<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author David LECHEVALIER <david@ulteo.com>
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

class HttpRequest {
	protected $url = NULL;
	protected $method = 'POST';

	protected $contentType = 'application/x-www-form-urlencoded';
	protected $postFields = NULL;
	protected $toFile = NULL;

	protected $responseCode = 0;
	protected $responseContentType = NULL;
	protected $responseBody = NULL;

	protected $responseErrno = 0;
	protected $responseError = NULL;

	private $max_requests = 5;

	public function __construct($url_=NULL, $request_method_='POST', $options_=array()) {
		$this->url = $url_;
		$this->method = $request_method_;

		foreach ($options_ as $k => $v)
			$this->$k = $v;
	}

	private function reset() {
		$this->contentType = 'application/x-www-form-urlencoded';
		$this->postFields = NULL;
		$this->toFile = NULL;
	}

	public function setUrl($url_) {
		if (! is_string($url_))
			return false;

		$this->url = $url_;

		return true;
	}

	public function setMethod($request_method_) {
		if (! in_array($request_method_, array('GET', 'POST')))
			return false;

		$this->method = $request_method_;

		return true;
	}

	public function setContentType($content_type_) {
		if (! is_string($content_type_))
			return false;

		$this->contentType = $content_type_;

		return true;
	}

	public function setPostFields($post_data_) {
		if (! is_string($post_data_))
			return false;

		$this->postFields = $post_data_;

		return true;
	}

	public function setToFile($to_file_) {
		if (! is_resource($to_file_))
			return false;

		$this->toFile = $to_file_;

		return true;
	}

	public function getResponseCode() {
		return $this->responseCode;
	}

	public function getResponseContentType() {
		return $this->responseContentType;
	}

	public function getResponseBody() {
		return $this->responseBody;
	}

	public function getResponseErrno() {
		return $this->responseErrno;
	}

	public function getResponseError() {
		return $this->responseError;
	}

	public function send() {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->url);
		switch ($this->method) {
			case 'GET':
				curl_setopt($ch, CURLOPT_HTTPGET, 1);
				if (is_resource($this->toFile))
					curl_setopt($ch, CURLOPT_FILE, $this->toFile);
				break;
			case 'POST':
			default:
				curl_setopt($ch, CURLOPT_HTTPGET, 0);
				if (is_string($this->postFields) && strlen($this->postFields) > 0)
					curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postFields);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close', 'Content-Type: '.$this->contentType));
				break;
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);

		$this->responseBody = curl_exec($ch);
		$i = 1;
		while ((curl_errno($ch) == CURLE_COULDNT_CONNECT || curl_errno($ch) == CURLE_RECV_ERROR || curl_errno($ch) == CURLE_OPERATION_TIMEOUTED || curl_errno($ch) == CURLE_GOT_NOTHING) && $i < $this->max_requests) {
			usleep(rand(1000000, 3000000));
			$this->responseBody = curl_exec($ch);
			$i++;
		}

		$this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->responseContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

		$this->responseErrno = curl_errno($ch);
		$this->responseError = curl_error($ch);

		curl_close($ch);

		$this->reset();

		return $this->responseBody;
	}
}
