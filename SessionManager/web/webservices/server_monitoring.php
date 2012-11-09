<?php
/**
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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
require_once(dirname(__FILE__).'/../includes/webservices.inc.php');

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

	$server = webservices_load_server($_SERVER['REMOTE_ADDR']);
	if (! $server) {
		Logger::error('main', '(webservices/server/monitoring) Server does not exist (error_code: 2)');
		webservices_return_error(2, 'Server does not exist');
	}

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
				}

				$sri = ServerReportItem::create_from_server_report($ret['server'], $xml_);
				if ($sri !== null)
					Abstract_ReportServer::save($sri);
				
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
	Logger::error('main', '(webservices/server/monitoring) Server does not send a valid XML (error_code: 1)');
	webservices_return_error(1, 'Server does not send a valid XML');
}

$server = webservices_load_server($_SERVER['REMOTE_ADDR']);
if (! $server) {
	Logger::error('main', '(webservices/server/monitoring) Server does not exist (error_code: 1)');
	webservices_return_error(2, 'Server does not exist');
}

if (! $server->isAuthorized()) {
	Logger::error('main', '(webservices/server/monitoring) Server is not authorized (error_code: 3)');
	webservices_return_error(3, 'Server is not authorized');
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
			$buf->reportRunningApplicationsOnServer($ret['server'], $session['instances']);
		}

		if ($modified === true)
			Abstract_Session::save($buf); //update Session cache timestamp
	}

	// Check state of sessions not present in xml
	Logger::debug('main', "Checking session from ".$server->fqdn);
	$sessions = Abstract_Session::getByServer($server->id);
	foreach ($sessions as $session) {
		Logger::debug('main', "Inspecting session ".$session->id);
		if (! array_key_exists($server->id, $session->servers[Server::SERVER_ROLE_APS])) {
			Logger::debug('main', "Session ".$session->id." on ".$server->fqdn." is not an APS session");
			continue;
		}
		
		// Check if the session id unknown by the APS
		if (! in_array($session->id, $monitored_session)) {
			$serverStatus = $session->servers[Server::SERVER_ROLE_APS][$server->id]['status'];
			
			// If the monitoring is received during the APS session creation,
			// monitoring do not state about creating session and the session is destroy
			if (in_array($serverStatus, array(Session::SESSION_STATUS_CREATED, Session::SESSION_STATUS_READY))) {
				if ((time() - $session->start_time) > DEFAULT_UNUSED_SESSION_DURATION) {
					Logger::info('main', "Session ".$session->id." is expired");
					$session->setServerStatus($server->id, Session::SESSION_STATUS_DESTROYED);
				}
			}
			// The session is unknown by APS, status is changed to destroyed
			else if ($serverStatus !== Session::SESSION_STATUS_DESTROYED) {
				Logger::info('main', "Session ".$session->id." switch status ".Session::SESSION_STATUS_DESTROYED." on ".$server->fqdn);
				$session->setServerStatus($server->id, Session::SESSION_STATUS_DESTROYED);
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
			if (! Abstract_Network_Folder::exists($share['id'])) {
				Logger::warning('main', "Share ".$share['id'].' do not exist on SM. It will be add in the orphan network folder list');
				Abstract_Network_Folder::save(new NetworkFolder($share['id'], $server->id, NetworkFolder::NF_STATUS_NOT_EXISTS));
			}
			continue;
		}
	}
	
	if (array_key_exists('shares', $ret) && is_array($ret['shares'])) {
		$SMFolders = array();
		
		if (is_object($profiledb)) {
			$profiles = $profiledb->importFromServer($ret['server']);
			if (is_array($profiles))
				$SMFolders = array_merge($SMFolders, $profiles);
		}
		if (is_object($sharedfolderdb)) {
			$shares = $sharedfolderdb->importFromServer($ret['server']);
			if (is_array($shares))
				$SMFolders = array_merge($SMFolders, $shares);
		}
		
		foreach ($SMFolders as $SMFolder) {
			$delete = true;
			if (! is_object($SMFolder)) {
				continue;
			}
			foreach ($ret['shares'] as $FSFolder) {
				if (strcasecmp($FSFolder['id'], $SMFolder->id) == 0) {
					$delete = false;
				}
			}
			
			if ($delete == true) {
				Logger::error('main','Share '.$SMFolder->id.' do not exist on the FS. It will be add in the orphan network folder list');
				if (is_object($sharedfolderdb) && $sharedfolderdb->exists($SMFolder->id)) {
					Logger::debug('main','Share '.$SMFolder->id.' invalidated from the sharedb');
					$sharedfolderdb->invalidate($SMFolder->id);
					
					if ($sharedfolderdb->isInternal())
						$server->deleteNetworkFolder($SMFolder->id, true);
				}
				else if (is_object($profiledb) && $profiledb->exists($SMFolder->id)) {
					Logger::debug('main','Profile '.$SMFolder->id.' invalidated from the profiledb');
					$profiledb->invalidate($SMFolder->id);
					
					if ($profiledb->isInternal())
						$server->deleteNetworkFolder($SMFolder->id, true);
				}
				else {
					Logger::warning('main','Share '.$SMFolder->id.' do not exist on the SM');
				}
			}
		}
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
