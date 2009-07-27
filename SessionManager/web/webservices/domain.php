<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

Logger::debug('main', 'Starting webservices/domain.php');

function get_domain() {
	$prefs = Preferences::getInstance();

	$buf = $prefs->get('UserDB', 'enable');
	if ($buf == 'activedirectory') {
		$buf = $prefs->get('UserDB', 'activedirectory');
		return $buf['domain'];
	}

	if ($buf == 'ldap') {
		$buf = $prefs->get('UserDB', 'ldap');
		if (isset($buf['ad']) && $buf['ad'] == 1)
			return $buf['host'];
	}

	return NULL;
}

function compare_domain($domain1_, $domain2_) {
	// ToDo: in ldap case, the host!=domain
	return (strtolower($domain1_) == strtolower($domain2_));
}


$dom = new DomDocument('1.0', 'utf-8');
$node = $dom->createElement('response');
$dom->appendChild($node);

if (! isset($_GET["domain"]) || $_GET["domain"]=='') {
	$node->setAttribute('status', '1');
	$textnode = $dom->createTextNode('Usage error: missing "domain" get parameter');
	$node->appendChild($textnode);
	
	header('Content-Type: text/xml; charset=utf-8');
	die($dom->saveXML());
}

$domain = get_domain();
if ($domain == NULL) {
	$node->setAttribute('status', '2');
	$textnode = $dom->createTextNode('NOT USING ACTIVE DIRECTORY');
	$node->appendChild($textnode);
	
	header('Content-Type: text/xml; charset=utf-8');
	die($dom->saveXML());
}

if (! compare_domain($domain, $_GET['domain'])) {
	$node->setAttribute('status', '3');
	$node->setAttribute('domain', $domain);
	$textnode = $dom->createTextNode('NOT USING SAME ACTIVE DIRECTORY');
	$node->appendChild($textnode);
	
	header('Content-Type: text/xml; charset=utf-8');
	die($dom->saveXML());
}

$node->setAttribute('status', '0');
$textnode = $dom->createTextNode('OK');
$node->appendChild($textnode);
	
header('Content-Type: text/xml; charset=utf-8');
die($dom->saveXML());

