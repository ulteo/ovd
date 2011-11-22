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

function build_error_xml($error_) {
	$dom = new DomDocument('1.0', 'utf-8');
	$rootNode = $dom->createElement('error');
	$textNode = $dom->createTextNode($error_);
	$rootNode->appendChild($textNode);
	$dom->appendChild($rootNode);
	
	return $dom->saveXML();
}

$filename = WEB_CLIENT_CONF_DIR.'/config.client.ini';

header('Content-Type: text/xml; charset=utf-8');

$ini = @parse_ini_file($filename, true);
if ($ini === false) {
	echo build_error_xml('INI syntax error on '.$filename);
	die();
}

$dom = new DomDocument('1.0', 'utf-8');
$rootNode = $dom->createElement('ini');
$dom->appendChild($rootNode);

foreach ($ini as $section_name => $section) {
	$sectionNode = $dom->createElement('section');
	$sectionNode->setAttribute('id', $section_name);
	$rootNode->appendChild($sectionNode);
	
	foreach ($section as $key => $value) {
		$entryNode = $dom->createElement('entry');
		$entryNode->setAttribute('key', $key);
		$entryNode->setAttribute('value', $value);
		
		$sectionNode->appendChild($entryNode);
	}
}

header('Content-Type: text/xml; charset=utf-8');

$xml = $dom->saveXML();
echo $xml;
die();
