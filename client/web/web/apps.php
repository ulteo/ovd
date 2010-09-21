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
$buf = @$dom->loadXML($_SESSION['xml']);
if (! $buf) {
	echo return_error(0, 'Invalid XML');
	die();
}

if (! $dom->hasChildNodes()) {
	echo return_error(0, 'Invalid XML');
	die();
}

$apps = array();
$app_nodes = $dom->getElementsByTagName('application');
foreach ($app_nodes as $app_node) {
	$apps[] = array(
		'id'		=>	$app_node->getAttribute('id'),
		'name'		=>	$app_node->getAttribute('name'),
		'server'	=>	$app_node->getAttribute('server')
	);
}

$dom = new DomDocument('1.0', 'utf-8');

$applications_node = $dom->createElement('applications');
foreach ($apps as $app) {
	$application_node = $dom->createElement('application');
	$application_node->setAttribute('id', $app['id']);
	$application_node->setAttribute('name', $app['name']);
	$application_node->setAttribute('server', $app['server']);
	$applications_node->appendChild($application_node);
}
$dom->appendChild($applications_node);

$xml = $dom->saveXML();

echo $xml;
die();
