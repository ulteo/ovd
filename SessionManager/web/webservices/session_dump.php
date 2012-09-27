<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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

function parse_session_dump_XML($xml_) {
	if (! $xml_ || strlen($xml_) == 0)
		return false;

	$dom = new DomDocument('1.0', 'utf-8');

	$buf = @$dom->loadXML($xml_);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$node = $dom->getElementsByTagname('session')->item(0);
	if (is_null($node))
		return false;

	if (! $node->hasAttribute('id'))
		return false;
	
	$ret = array(
		'id'		=>	$node->getAttribute('id'),
		'server'	=>	$_SERVER['REMOTE_ADDR'],
		'dump'		=>	array(),
	);
	
	foreach($dom->getElementsByTagname('dump') as $node) {
		if (! $node->hasAttribute('name'))
			return false;
		
		$name = $node->getAttribute('name');
		
		$textNode = null;
		foreach($node->childNodes as $child_node) {
			if ($child_node->nodeType != XML_TEXT_NODE)
				continue;
			
			$textNode = $child_node;
			break;
		}
		
		if ($textNode === null)
			return false;
		
		$ret['dump'][$name] = trim($textNode->wholeText);
	}
	
	return $ret;
}

$infos = parse_session_dump_XML(@file_get_contents('php://input'));
if (! $infos) {
	Logger::error('main', '(webservices/session/dump) Server does not send a valid XML (error_code: 1)');
	webservices_return_error(1, 'Server does not send a valid XML');
}

$server = webservices_load_server($_SERVER['REMOTE_ADDR']);
if (! $server) {
	Logger::error('main', '(webservices/session/dump) Server does not exist (error_code: 2)');
	webservices_return_error(2, 'Server does not exist');
}

$session = Abstract_Session::load($infos['id']);
if (! $session) {
	Logger::error('main', '(webservices/session/dump) Session does not exist (error_code: 2)');
	webservices_return_error(2, 'Session does not exist');
}

$ret = $session->setServerDump($server->id, $infos['dump']);
if ($ret === false) {
	Logger::error('main', '(webservices/session/dump) Server is not used for this session (error_code: 1)');
	webservices_return_error(1, 'Server is not used for this session');
}

$ret = Abstract_Session::save($session);
if ($ret === false) {
	Logger::error('main', '(webservices/session/dump) Unable to save session with these information (error_code: 1)');
	webservices_return_error(1, 'Unable to save session with these information');
}

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$node = $dom->createElement('session');
$node->setAttribute('id', $session->id);
$dom->appendChild($node);

echo $dom->saveXML();
exit(0);
