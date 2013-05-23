<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2010
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
header('Content-Type: text/xml; charset=utf-8');

/* Errors :
	0 : bad xml
	1 : no session node
  2 : can't run ajaxplorer
*/

function print_error($errno) {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('ajaxplorer');
	$node->setAttribute('status', 'error');
	$node->setAttribute('error' , $errno);
	$dom->appendChild($node);
	return $dom->saveXML();
}

$input = file_get_contents('php://input');
$xml = new DomDocument('1.0', 'utf-8');
if( ! $xml->loadXML($input)) {
	echo print_error(0);
	die();
}

$session_node = $xml->getElementsByTagname('session')->item(0);

if( ! $session_node) {
	echo print_error(1);
	die();
}

$aj = new Ajaxplorer($session_node);
$use_explorer = ($aj->can_run() && $aj->is_required());

if($use_explorer === true) {
	$_SESSION['ovd-client']['ajxp'] = $aj->build_data();
} else {
	echo print_error(2);
	die();
}


$dom = new DomDocument('1.0', 'utf-8');
$node = $dom->createElement('ajaxplorer');
$node->setAttribute('status', 'ok');
$dom->appendChild($node);
echo $dom->saveXML();
die();
