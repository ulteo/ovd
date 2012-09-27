<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
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
require_once(dirname(__FILE__).'/../includes/webservices.inc.php');

function generate_server_id() {
	return gen_string(5, 'abcdefghijklmnopqrstuvwxyz0123456789');
}

$server = webservices_load_server($_SERVER['REMOTE_ADDR']);
if (is_null($server)) {
	$server = new Server(generate_server_id());
	$server->registered = false;
	$server->locked = true;

	$prefs = Preferences::getInstance();
	if (! $prefs) {
		Logger::error('main', '(webservices/server/name) Unable to load preferences (error_code: 0)');
		webservices_return_error(0, 'Internal error');
	}

	$buf = $prefs->get('general', 'slave_server_settings');

	if ($buf['auto_register_new_servers'] == 1)
		$server->registered = true;

	if ($buf['auto_switch_new_servers_to_production'] == 1)
		$server->locked = false;

	$server->fqdn = $_SERVER['REMOTE_ADDR'];

	$server->max_sessions = 20;
/*
	if (! $server->isAuthorized()) {
		Logger::error('main', '(webservices/server/name) Server is not authorized (error_code: 1)');
		webservices_return_error(1, 'Server is not authorized');
	}

	if (! $server->isOnline()) {
		Logger::error('main', '(webservices/server/name) Server is not OK (error_code: 2)');
		webservices_return_error(2, 'Server is not OK');
	}

	if (! $server->isOK()) {
		Logger::error('main', '(webservices/server/name) Server is not online (error_code: 3)');
		webservices_return_error(3, 'Server is not online');
	}
*/
	$ret = Abstract_Server::create($server);
	$try_count = 0;
	while($ret !== true && $try_count < 10) {
		$server->id = generate_server_id();
		$ret = Abstract_Server::create($server);
		$try_count++;
	}
	
	if ($ret !== true) {
		Logger.error('');
		Logger::error('main', 'Unable to save new server after '.$try_count.' try');
		webservices_return_error(0, 'Internal error');
	}
	
	Abstract_Server::save($server);
}

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$node = $dom->createElement('server');
$node->setAttribute('name', $server->id);
$dom->appendChild($node);

echo $dom->saveXML();
exit(0);
