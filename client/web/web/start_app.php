<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
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

require_once(dirname(__FILE__).'/includes/core.inc.php');

if (! array_key_exists('start_app', $_SESSION['ovd-client']))
	$_SESSION['ovd-client']['start_app'] = array();

if (array_key_exists('check', $_GET)) {
	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument('1.0', 'utf-8');

	$start_apps_node = $dom->createElement('start_apps');
	foreach ($_SESSION['ovd-client']['start_app'] as $k => $v) {
		$start_app_node = $dom->createElement('start_app');
		$start_app_node->setAttribute('id', $v['id']);
		
		if (array_key_exists('file', $v)) {
			$file_node = $dom->createElement('file');
			$file_node->setAttribute('type', $v['file']['type']);
			$file_node->setAttribute('path', $v['file']['path']);
			$file_node->setAttribute('share', $v['file']['share']);
			
			$start_app_node->appendChild($file_node);
		}
		
		$start_apps_node->appendChild($start_app_node);

		unset($_SESSION['ovd-client']['start_app'][$k]);
	}
	$dom->appendChild($start_apps_node);

	$xml = $dom->saveXML();

	echo $xml;
	die();
}

$_SESSION['ovd-client']['start_app'][] = array(
	'id'			=>	$_POST['id'],
	'file' => array(
		'path' => substr($_POST['path'], 1),
		'type' => 'sharedfolder',
		'share' => $_SESSION['ovd-client']['ajxp']['folders'][$_POST['repository']],
	)
);
