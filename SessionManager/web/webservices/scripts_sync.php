<?php
/**
 * Copyright (C) 2012-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent Roullier <vincent.roullier@ulteo.com> 2012
 * Author David LECHEVALIER <david@ulteo.com> 2013
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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');
require_once(dirname(__FILE__).'/../includes/webservices.inc.php');

function return_error($errno_, $errstr_) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	Logger::error('main', "(webservices/script_rsync) return_error($errno_, $errstr_)");
	return $dom->saveXML();
}

$server = webservices_load_server($_SERVER['REMOTE_ADDR']);
if (! $server) {
	echo return_error(1, 'Server does not exist');
	die();
}

if (! $server->isAuthorized()) {
	echo return_error(2, 'Server is not authorized');
	die();
}

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$node = $dom->createElement('scripts');

$scripts = $server->getScripts();
if (is_array($scripts)) {
	foreach ($scripts as $script) {
		$script_node = $dom->createElement('script');
		$script_node->setAttribute('id', $script->getAttribute('id'));
		$script_node->setAttribute('name', $script->getAttribute('name'));
		$script_node->setAttribute('type', $script->getAttribute('type'));
		$script_node->setAttribute('os', $script->getAttribute('os'));
		$data = base64_encode($script->getAttribute('data'));
		$text_node = $dom->createTextNode($data);
		$script_node->appendChild($text_node);
		$node->appendChild($script_node);
	}
}

$dom->appendChild($node);

echo $dom->saveXML();
exit(0);
