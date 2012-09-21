<?php
/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com> 2009
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');
require_once(CLASSES_DIR.'/ReportItems.class.php');

class SessionReportItem {
	private $id = -1; // id of the report is also the id of the session
	private $server;
	private $user;
	private $start_time;
	private $stop_time = null;
	private $stop_why = null;
	private $data = null;

	public function __construct($id_, $user_, $server_, $start_time_, $stop_time_ = null, $stop_why_ = null, $data_ = null) {
		$this->id = $id_;
		$this->user = $user_;
		$this->server = $server_;
		$this->start_time = $start_time_;
		if ($stop_time_ !== null)
			$this->stop_time = $stop_time_;
		if ($stop_why_ !== null)
			$this->stop_why = $stop_why_;
		if ($data_ !== null)
			$this->data = $data_;
	}

	public function getId() {
		return $this->id;
	}
	
	public function getServer() {
		return $this->server;
	}
	
	public function getUser() {
		return $this->user;
	}
	
	public function getStartTime() {
		return $this->start_time;
	}
	
	public function getStopTime() {
		return $this->stop_time;
	}
	
	public function setStartTime($start_time_) {
		$this->start_time = $start_time_;
	}
	
	public function getStopWhy() {
		return $this->stop_why;
	}
	
	public function setStopWhy($stop_why_) {
		$this->stop_why = $stop_why_;
	}
	
	public function getData() {
		return $this->data;
	}

	public function end($session_) {
		$this->data = $this->session2Xml($session_);
	}
	
	private static function session2Xml($session_) {
		$dom = new DomDocument ('1.0', 'utf-8');
		$dom->formatOutput = true;
		
		$session_node = $dom->createElement('session');
		$dom->appendChild($session_node);
		
		$session_node->setAttribute('id', $session_->id);
		$session_node->setAttribute('mode', $session_->mode);
		
		$user_node = $dom->createElement('user');
		$session_node->appendChild($user_node);
		$user_node->setAttribute('login', $session_->user_login);
		$user_node->setAttribute('display_name', $session_->user_displayname);
		
		// Begin session servers part
		$servers_node = $dom->createElement('servers');
		$session_node->appendChild($servers_node);
		
		foreach ($session_->servers as $role => $servers) {
			foreach ($servers as $server_id => $data) {
				$server_node = $dom->createElement('server');
				$servers_node->appendChild($server_node);
				$server_node->setAttribute('id', $server_id);
				$server_node->setAttribute('role', $role);
				
				if ($session_->mode == Session::MODE_DESKTOP && $session_->server == $server_id)
					$server_node->setAttribute('desktop_server', 'true');
				
				if (array_key_exists('dump', $data)) {
					foreach($data['dump'] as $name => $dump) {
						$node = $dom->createElement('dump');
						$node->setAttribute('name', $name);
						$server_node->appendChild($node);
						
						$textNode = $dom->createTextNode($dump);
						$node->appendChild($textNode);
					}
				}
				
				$server = Abstract_Server::load($server_id);
				if (! $server || $server->getAttribute('registered') === false)
					continue;
				
				$server_node->setAttribute('fqdn', $server->fqdn);
				$server_node->setAttribute('type', $server->type);
			}
		}
		// Finish session servers part
		
		$applications = $dom->createElement('published_applications');
		$session_node->appendChild($applications);
		
		foreach ($session_->getPublishedApplications() as $application_id => $application) {
			$app_node = $dom->createElement('application');
			$applications->appendChild($app_node);
			
			$app_node->setAttribute('id', $application_id);
			$app_node->setAttribute('name', $application->getAttribute('name'));
		}
		
		$apps_instances_node = $dom->createElement('applications_instances');
		$session_node->appendChild($apps_instances_node);
		foreach ($session_->getClosedApplications() as $instance_id => $instance) {
			$node = $dom->createElement('instance');
			$apps_instances_node->appendChild($node);
			
			$node->setAttribute('id', $instance_id);
			$node->setAttribute('application', $instance['application']);
			$node->setAttribute('server', $instance['server']);
			$node->setAttribute('start', $instance['start']);
			$node->setAttribute('stop', $instance['stop']);
		}
		foreach ($session_->getRunningApplications() as $instance_id => $instance) {
			$node = $dom->createElement('instance');
			$apps_instances_node->appendChild($node);
			
			$node->setAttribute('id', $instance_id);
			$node->setAttribute('application', $instance['application']);
			$node->setAttribute('server', $instance['server']);
			$node->setAttribute('start', $instance['start']);
			$node->setAttribute('stop', time());
		}
		
		return $dom->saveXML();
	}
}

