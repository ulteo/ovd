<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
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

$server_name = $_SERVER['REMOTE_ADDR'];
$server = Abstract_Server::load($server_name);
if (! $server) {
	$server = new Server($server_name);

	$server->registered = false;
	$server->locked = true;

	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error(_('get Preferences failed'), __FILE__, __LINE__);

	$buf = $prefs->get('general', 'application_server_settings');

	if ($buf['auto_register_new_servers'] == 1)
		$server->registered = true;

	if ($buf['auto_switch_new_servers_to_production'] == 1)
		$server->locked = false;

	$server->external_name = $server->fqdn;

	$server->max_sessions = 20;

	Abstract_Server::save($server);
}

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$node = $dom->createElement('server');
$node->setAttribute('name', $server->fqdn);
$dom->appendChild($node);

echo $dom->saveXML();
exit(0);
