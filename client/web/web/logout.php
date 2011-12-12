<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
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

require_once(dirname(__FILE__).'/includes/core.inc.php');

if (! array_key_exists('mode', $_POST))
	$_POST['mode'] = 'logout';

header('Content-Type: text/xml; charset=utf-8');

if (! array_key_exists('ovd-client', $_SESSION) || ! array_key_exists('sessionmanager', $_SESSION['ovd-client'])) {
	echo return_error(0, 'System not yet initialized');
	die();
}

$sm = $_SESSION['ovd-client']['sessionmanager'];

$dom = new DomDocument('1.0', 'utf-8');
$logout_node = $dom->createElement('logout');
$logout_node->setAttribute('mode', $_POST['mode']);
$dom->appendChild($logout_node);

$xml = $dom->saveXML();

$dom = new DomDocument('1.0', 'utf-8');
$buf = @$dom->loadXML($sm->query_post_xml('logout.php', $xml));
if (! $buf) {
	echo return_error(0, 'Invalid XML');
	die();
}

if (! $dom->hasChildNodes()) {
	echo return_error(0, 'Invalid XML');
	die();
}

$logout_nodes = $dom->getElementsByTagName('logout');
if (count($logout_nodes) != 1) {
	echo return_error(1, 'Invalid XML: No session node');
	die();
}
$logout_node = $logout_nodes->item(0);
if (! is_object($logout_node)) {
	echo return_error(1, 'Invalid XML: No session node');
	die();
}
$logout = array(
	'mode'		=>	$logout_node->getAttribute('mode')
);

$dom = new DomDocument('1.0', 'utf-8');

$logout_node = $dom->createElement('logout');
$logout_node->setAttribute('mode', $logout['mode']);
$dom->appendChild($logout_node);

$xml = $dom->saveXML();

echo $xml;

unset($_SESSION['ovd-client']);

die();
