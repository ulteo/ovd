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

require_once(dirname(__FILE__).'/includes/core.inc.php');

$xml = query_sm($sessionmanager_url.'/applications.php?user='.$_REQUEST['user']);
if (! $xml) {
	die($xml);
}

$dom = new DomDocument('1.0', 'utf-8');
$buf = @$dom->loadXML($xml);
if (! $buf) {
	die($xml);
}

$mime_type = NULL;
if (isset($_REQUEST['filter_extension'])) {
	require_once(dirname(__FILE__).'/includes/mime.inc.php');
	$mime_type = getMimeTypefromExtension($_REQUEST['filter_extension']);
}
else if (isset($_REQUEST['filter_mime']))
	$mime_type = $_REQUEST['filter_mime'];

if (! is_null($mime_type)) {
	$user_node = $dom->documentElement;
	$user_node_new = $user_node->cloneNode(false);

	$applications_nodes = $user_node->getElementsByTagName('application');
	foreach ($applications_nodes as $application_node) {
		$found_mime = false;
		$mime_nodes = $application_node->getElementsByTagName('mime');
		foreach ($mime_nodes as $mime_node) {
			if ($mime_node->getAttribute('type') == $mime_type) {
				$user_node_new->appendChild($application_node->cloneNode(true));	
				break;
			}
		}
	}

	$dom->removeChild($user_node);
	$dom->appendChild($user_node_new);
}

header('Content-Type: text/xml');
echo $dom->saveXML();
