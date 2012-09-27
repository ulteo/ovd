<?php
/**
 * Copyright (C) 2012 Ulteo SAS
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

function webservices_load_server($addr_) {
	$server = null;

	// First, try to detect the server using the reverse DNS resolution
	$reverse = @gethostbyaddr($addr_);
	if ($reverse !== false && $reverse != $addr_) {
		$server = Abstract_Server::load_by_fqdn($reverse);
	}

	if (is_null($server)) {
		// Then, try to detect the server using the source IP address
		$server = Abstract_Server::load_by_fqdn($addr_);
	}
	
	return $server;
}

function webservices_return_error($errno_, $errstr_) {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	
	header('Content-Type: text/xml; charset=utf-8');
	echo $dom->saveXML();
	die();
}
