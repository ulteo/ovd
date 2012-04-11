<?php
/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
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

class ServerReportItem {
	private $timestamp;
	private $fqdn;
	private $external_name;
	private $cpu;
	private $ram;
	private $data;
	
	public function __construct($timestamp_, $fqdn_, $external_name_, $cpu_, $ram_, $data_) {
		$this->timestamp = $timestamp_;
		$this->fqdn = $fqdn_;
		$this->external_name = $external_name_;
		$this->cpu = $cpu_;
		$this->ram = $ram_;
		$this->data = $data_;
	}
	
	public function getTime() {
		return $this->timestamp;
	}
	
	public function getFQDN() {
		return $this->fqdn;
	}
	
	public function getExternalName() {
		return $this->external_name;
	}
	
	public function getCPU() {
		return $this->cpu;
	}
	
	public function getRAM() {
		return $this->ram;
	}
	
	public function getData() {
		return $this->data;
	}
	
	
	public static function create_from_server_report($fqdn_, $xml_input_) {
		$server = Abstract_Server::load($fqdn_);
		if ($server === false)
			return;
		
		$external_name = $server->getAttribute('external_name');
		
		$dom = new DomDocument('1.0', 'utf-8');
		$dom->loadXML($xml_input_);
		if (! is_object($dom))
			return null;
		
		$node = $dom->getElementsByTagName('cpu');
		if ($node->length > 0)
			$cpu = round ($node->item(0)->getAttribute ('load'), 2);
		else
			$cpu = -1;

		$node = $dom->getElementsByTagName('ram');
		if ($node->length > 0) {
			$total = (float) ($server->ram_total);
			$used = (float) ($node->item(0)->getAttribute('used'));
			if ($total > 0)
				$ram = round (($used / $total) * 100, 2);
			else
				$ram = 0;
		} else {
			$ram = -1;
		}
		
		$sessions = $dom->getElementsByTagName('session');
		
		$apps_link = application_desktops_to_ids();
		$sessions_infos = array();
		$applications_infos = array();

		/* the interesting <session> nodes of the xml are like:
		     <session id="ID" mode="MODE" status="STATUS" user="LOGIN">
				<instance application="APP_ID" id="ApS_ID"/>
			</session>
		*/
		foreach ($sessions as $session) {
			$sessid = $session->getAttribute('id');

			$sessions_infos[$sessid] = $session->getAttribute('user');

			foreach ($session->childNodes as $instance_node) {
				if ($instance_node->tagName == 'instance') {
					$desktop = $instance_node->getAttribute('application');
					if (! array_key_exists($desktop, $apps_link))
						continue;

					$id = $apps_link[$desktop];
					if (! array_key_exists($id, $applications_infos))
						$applications_infos[$id] = array();

					$applications_infos[$id][] = $sessid;
				}
			}
		}
		
		$data = self::infos2Xml($sessions_infos, $applications_infos);
		$report = new self(time(), $fqdn_, $external_name, $cpu, $ram, $data);
		return $report;
	}

	private static function infos2Xml($sessions_, $applications_) {
		$dom = new DomDocument ('1.0', 'utf-8');
		$dom->formatOutput = true;

		/* main node */
		$snapshot = $dom->createElement('server_snapshot');
		$dom->appendChild($snapshot);

		/* sessions */
		$sessions = $dom->createElement('sessions');
		$snapshot->appendChild($sessions);
		foreach ($sessions_ as $sessid => $user) {
			$session = $dom->createElement('session');
			$session->setAttribute('user', $user);
			$session->setAttribute('id', $sessid);
			$sessions->appendChild($session);
		}

		$applications = $dom->createElement('applications');
		$snapshot->appendChild($applications);
		foreach ($applications_ as $desktop => $app_sessions) {
			$application = $dom->createElement('application');
			$application->setAttribute('id', $desktop);
			foreach ($app_sessions as $session_id) {
				$id = $dom->createElement('session');
				$id->setAttribute('id', $session_id);
				$application->appendChild ($id);
			}
			$applications->appendChild($application);
		}

		return $dom->saveXML();
	}
}
