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

function return_error($errno_, $errstr_) {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	return $dom->saveXML();
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$buf = @$dom->loadXML(query_sm($sessionmanager_url.'/session_status.php'));
if (! $buf) {
	echo return_error(0, 'Invalid XML');
	die();
}

if (! $dom->hasChildNodes()) {
	echo return_error(0, 'Invalid XML');
	die();
}

$session_nodes = $dom->getElementsByTagName('session');
if (count($session_nodes) != 1) {
	echo return_error(1, 'Invalid XML: No session node');
	die();
}
$session_node = $session_nodes->item(0);
if (! is_object($session_node)) {
	echo return_error(1, 'Invalid XML: No session node');
	die();
}
$session = array(
	'id'		=>	$session_node->getAttribute('id'),
	'status'	=>	$session_node->getAttribute('status')
);

$dom = new DomDocument('1.0', 'utf-8');

$session_node = $dom->createElement('session');
$session_node->setAttribute('id', $session['id']);
$session_node->setAttribute('status', $session['status']);
$dom->appendChild($session_node);

$xml = $dom->saveXML();

echo $xml;
die();
