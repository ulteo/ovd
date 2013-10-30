<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2013
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

$ALLOWED_PARAMS = array('id', 'user', 'pass');
$ALLOWED_SERVICES = array('connect', 'disconnect');
$SERVICE_HEADER = 'x-ovd-service';
$service = '';

$WEBAPPS_HEADER = 'x-ovd-webappsserver';
$webapps_server = '';

$headers = apache_request_headers();

/* Get service type */
if (is_array($headers) && array_key_exists($SERVICE_HEADER, $headers)) {
  $service = $headers[$SERVICE_HEADER];
  if ( ! in_array($service, $ALLOWED_SERVICES)) {
    echo 'Bad service requested';
    die();
  }
} else {
  echo 'No service requested';
  die();
}

if (is_array($headers) && array_key_exists($WEBAPPS_HEADER, $headers)) {
  $webapps_server = $headers[$WEBAPPS_HEADER];
} else {
  echo 'No WebApps server requested';
  die();
}

$request_url = $webapps_server.'/'.$service;

$separator = "?";
foreach($_GET as $k => $v) {
	if(in_array($k, $ALLOWED_PARAMS)) {
		$request_url = $request_url.$separator.urlencode($k)."=".urlencode($v);
		$separator = "&";
	}
}

/* Init */
$socket = curl_init($request_url);
curl_setopt($socket, CURLOPT_RETURNTRANSFER, true);

/* Send */
$string = curl_exec($socket);
$http_code = curl_getinfo($socket, CURLINFO_HTTP_CODE);
curl_close($socket);

if (! in_array($http_code, array(200, 302))) {
	echo 'HTTP Error : '.$http_code;
	die();
}

/* Push body and return */
header("Content-Type:text/xml");
$output = new DomDocument('1.0');
$output->loadXML($string);
echo $output->saveXML();
die();
