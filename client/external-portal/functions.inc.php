<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
 
require_once('config.inc.php');


function getApplications($user) {
	$socket = curl_init('https://'.ULTEO_OVD_SM_HOST.'/ovd/client/applications.php?user='.$user); /*?token=token');*/
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($socket, CURLOPT_TIMEOUT, (15+5));
	$data = curl_exec($socket);
	$code = curl_getinfo($socket, CURLINFO_HTTP_CODE);
	$content_type = curl_getinfo($socket, CURLINFO_CONTENT_TYPE);
	curl_close($socket);
	
	if ($code != 200) {
		$_SESSION['error'] = 'Unable to talk to sm';
		return false;
	}
	
	if (strpos($content_type, 'text/xml') === FALSE) {
		$_SESSION['error'] = 'invalid returned format';
		return false;
	}
	
	$dom = new DomDocument('1.0', 'utf-8');
	$ret = @$dom->loadXML($data);
	if (! $ret) {
		$_SESSION['error'] = 'invalid returned format';
		return false;
	}
	
	if ($dom->documentElement->nodeName != 'user') {
		$_SESSION['error'] = 'authentication issue';
		return false;
	}
	
	$apps = array();
	
	foreach ($dom->getElementsByTagname('application') as $app_node) {
		$app = array();
		$app['id'] = $app_node->getAttribute('id');
		$app['name'] = $app_node->getAttribute('name');
		$app['mimetypes'] = array();
		foreach ($app_node->getElementsByTagname('mime') as $mime_node) {
			$app['mimetypes'] []= $mime_node->getAttribute('type');
		}
		
		$apps[$app['id']] = $app;
	}
	
	return $apps;
}

function getIcon($app_id) {
	$ret = array();
	
	$socket = curl_init('https://'.ULTEO_OVD_SM_HOST.'/ovd/client/icon.php?id='.$app_id); /*?token=token');*/
	curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($socket, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($socket, CURLOPT_TIMEOUT, (15+5));
	$ret['content_type'] = curl_getinfo($socket, CURLINFO_CONTENT_TYPE);
	$ret['data'] = curl_exec($socket);
	curl_close($socket);
	
	return $ret;
}


function getFiles() {
	$files = array();
	
	foreach (glob(GED_FOLDER.'/*') as $filename) {
		if (is_dir($filename))
			continue;
		
		$f = array();
		$f['name'] = basename($filename);
		$f['mimetype'] = mime_content_type($filename);
		
		$files[$f['name']] = $f;
	}
	
	return $files;
}
