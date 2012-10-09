<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2012
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

function return_error($errno_, $errstr_) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	Logger::error('main', "(webservices/server_monitoring) return_error($errno_, $errstr_)");
	return $dom->saveXML();
}

function parse_monitoring_XML($xml_) {
	if (! $xml_ || strlen($xml_) == 0)
		return false;

	$dom = new DomDocument('1.0', 'utf-8');

	$buf = @$dom->loadXML($xml_);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$server_node = $dom->getElementsByTagName('server')->item(0);
	if (is_null($server_node))
		return false;

	if (! $server_node->hasAttribute('name'))
		return false;

	if (! Abstract_Server::exists($server_node->getAttribute('name')))
		die(); // An unknown Server should not send monitoring, so we reject it...

	$server = Abstract_Server::load($server_node->getAttribute('name'));
	if (! $server)
		return false;

	if (! $server->isAuthorized())
		return false;

	$ret = array(
		'server'	=>	$server_node->getAttribute('name')
	);

	$cpu_node = $dom->getElementsByTagName('cpu')->item(0);
	if (is_null($cpu_node))
		return false;

	if (! $cpu_node->hasAttribute('load'))
		return false;

	$ret['cpu_load'] = $cpu_node->getAttribute('load');

	$ram_node = $dom->getElementsByTagName('ram')->item(0);
	if (is_null($ram_node))
		return false;

	if (! $ram_node->hasAttribute('used'))
		return false;

	$ret['ram_used'] = $ram_node->getAttribute('used');

	$role_nodes = $dom->getElementsByTagName('role');
	foreach ($role_nodes as $role_node) {
		if (! $role_node->hasAttribute('name'))
			return false;

		switch ($role_node->getAttribute('name')) {
			case 'ApplicationServer':
				$ret['sessions'] = array();

				$session_nodes = $dom->getElementsByTagName('session');
				foreach ($session_nodes as $session_node) {
					$ret['sessions'][$session_node->getAttribute('id')] = array(
						'id'		=>	$session_node->getAttribute('id'),
						'server'	=>	$_SERVER['REMOTE_ADDR'],
						'status'	=>	$session_node->getAttribute('status'),
						'instances'	=>	array()
					);

					$childnodes = $session_node->childNodes;
					foreach ($childnodes as $childnode) {
						if ($childnode->nodeName != 'instance')
							continue;

						$ret['sessions'][$session_node->getAttribute('id')]['instances'][$childnode->getAttribute('id')] = $childnode->getAttribute('application');
					}

					$token = $session_node->getAttribute('id');

					if (Abstract_ReportSession::exists($session_node->getAttribute('id'))) {
						$report = Abstract_ReportSession::load($session_node->getAttribute('id'));
						if (is_object($report))
							$report->update($session_node);
					}
				}

				$sri = new ServerReportItem($ret['server'], $xml_);
				$sri->save();
				break;
			case 'FileServer':
				$size_node = $dom->getElementsByTagName('size')->item(0);
				if (is_null($size_node))
					break;

				$ret['disk_size'] = array(
					'total'	=>	NULL,
					'free'	=>	NULL
				);
				if ($size_node->hasAttribute('total'))
					$ret['disk_size']['total'] = $size_node->getAttribute('total');
				if ($size_node->hasAttribute('free'))
					$ret['disk_size']['free'] = $size_node->getAttribute('free');

				$ret['shares'] = array();

				$share_nodes = $dom->getElementsByTagName('share');
				foreach ($share_nodes as $share_node) {
					$ret['shares'][$share_node->getAttribute('id')] = array(
						'id'		=>	$share_node->getAttribute('id'),
						'status'	=>	$share_node->getAttribute('status'),
						'users'		=>	array()
					);

					$user_nodes = $share_node->getElementsByTagName('user');
					foreach ($user_nodes as $user_node)
						$ret['shares'][$share_node->getAttribute('id')]['users'][] = $user_node->getAttribute('login');
				}
				break;
		}
	}

	return $ret;
}

$ret = parse_monitoring_XML(@file_get_contents('php://input'));
if (! $ret) {
	echo return_error(1, 'Server does not send a valid XML');
	die();
}

if (! Abstract_Server::exists($ret['server']))
	die(); // An unknown Server should not send monitoring, so we reject it...

$server = Abstract_Server::load($ret['server']);
if (! $server) {
	echo return_error(2, 'Server does not exist');
	die();
}

if (! $server->isAuthorized()) {
	echo return_error(3, 'Server is not authorized');
	die();
}

$server->setAttribute('cpu_load', $ret['cpu_load']);
$server->setAttribute('ram_used', $ret['ram_used']);

if (array_key_exists('disk_size', $ret)) {
	$server->setAttribute('disk_total', $ret['disk_size']['total']);
	$server->setAttribute('disk_free', $ret['disk_size']['free']);
}

$slaveserver_settings = $prefs->get('general', 'slave_server_settings');
if ($slaveserver_settings['auto_recover'] == 1) {
	if (! $server->isOnline()) {
		$server->setAttribute('status', Server::SERVER_STATUS_ONLINE);
		$server->getStatus();
	}
}

Abstract_Server::save($server); //update Server cache timestamp

if (array_key_exists('sessions', $ret) && is_array($ret['sessions'])) {
	$monitored_session = array();
	foreach ($ret['sessions'] as $session) {
		$buf = Abstract_Session::exists($session['id']);
		if (! $buf)
			continue;

		$buf = Abstract_Session::load($session['id']);
		if (! $buf)
			continue;

		$modified = false;

		array_push($monitored_session, $session['id']);
		
		if (! array_key_exists($session['server'], $buf->servers[Server::SERVER_ROLE_APS]))
			continue;

		if ($session['status'] != $buf->servers[Server::SERVER_ROLE_APS][$session['server']]['status']) {
			$modified = true;
			$buf->setServerStatus($session['server'], $session['status']);
		}

		if ($session['status'] == Session::SESSION_STATUS_ACTIVE) {
			$modified = true;
			$buf->setRunningApplications($ret['server'], $session['instances']);
		}

		if ($modified === true)
			Abstract_Session::save($buf); //update Session cache timestamp
	}

	// Check state of sessions not present in xml
	Logger::debug('main', "Checking session from ".$server->fqdn);
	$sessions = Abstract_Session::getByServer($server->fqdn);
	foreach ($sessions as $session) {
		Logger::debug('main', "Inspecting session ".$session->id);
		if (! array_key_exists($server->fqdn, $session->servers[Server::SERVER_ROLE_APS])) {
			Logger::debug('main', "Session ".$session->id." on ".$server->fqdn." is not an APS session");
			continue;
		}
		
		// Check if the session id unknown by the APS
		if (! in_array($session->id, $monitored_session)) {
			$serverStatus = $session->servers[Server::SERVER_ROLE_APS][$server->fqdn]['status'];
			
			// If the monitoring is received during the APS session creation,
			// monitoring do not state about creating session and the session is destroy
			if (in_array($serverStatus, array(Session::SESSION_STATUS_CREATED, Session::SESSION_STATUS_READY))) {
				if ((time() - $session->start_time) > DEFAULT_UNUSED_SESSION_DURATION) {
					Logger::info('main', "Session ".$session->id." is expired");
					$session->setServerStatus($server->fqdn, Session::SESSION_STATUS_DESTROYED);
				}
			}
			// The session is unknown by APS, status is changed to destroyed
			else if ($serverStatus !== Session::SESSION_STATUS_DESTROYED) {
				Logger::info('main', "Session ".$session->id." switch status ".Session::SESSION_STATUS_DESTROYED." on ".$server->fqdn);
				$session->setServerStatus($server->fqdn, Session::SESSION_STATUS_DESTROYED);
			}
		}
	}
}

if (array_key_exists('shares', $ret) && is_array($ret['shares'])) {
	$profiledb = null;
	if (Preferences::moduleIsEnabled('ProfileDB')) {
		$profiledb = ProfileDB::getInstance();
	}
	$sharedfolderdb = null;
	if (Preferences::moduleIsEnabled('SharedFolderDB')) {
		$sharedfolderdb = SharedFolderDB::getInstance();
	}
	$disabled_users = array();
	foreach ($ret['shares'] as $share) {
		if (is_object($sharedfolderdb) && $sharedfolderdb->exists($share['id'])) {
			$buf = $sharedfolderdb->import($share['id']);
			$db = $sharedfolderdb;
		}
		else if (is_object($profiledb) && $profiledb->exists($share['id'])) {
			$buf = $profiledb->import($share['id']);
			$db = $profiledb;
		}
		else {
			$buf = false;
		}
		
		if (! $buf) {
			$server = Abstract_Server::load($ret['server']);
			if (! $server)
				continue;

			$server->deleteNetworkFolder($share['id'], true);
			continue;
		}

		$modified = false;

		switch ($share['status']) {
			case NetworkFolder::NF_STATUS_ACTIVE:
				$disabled = 0;
				foreach ($share['users'] as $user) {
					if (in_array($user, $disabled_users))
						continue;

					$sessions = Abstract_Session::getByFSUser($user);

					if (count($sessions) == 0) {
						$server = Abstract_Server::load($ret['server']);
						if (! $server)
							continue;

						if ($server->orderFSAccessDisable($user)) {
							$disabled += 1;
							$disabled_users[] = $user;
						}
					}
				}

				if ($disabled == count($share['users']))
					$share['status'] = NetworkFolder::NF_STATUS_INACTIVE;
				break;
		}

		if ($share['status'] != $buf->status) {
			$modified = true;
			$buf->status = $share['status'];
		}

		if ($modified === true)
			$db->update($buf);
	}
}

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');
$server_node = $dom->createElement('server');
$server_node->setAttribute('name', $ret['server']);
$dom->appendChild($server_node);

$xml = $dom->saveXML();

echo $xml;
die();
