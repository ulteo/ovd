<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Gauvain Pocentek <gauvain@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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
	private $fqdn;
	private $external_name;
	private $cpu;
	private $ram;
	private $sessions = array();
	private $applications = array();

	public function __construct($fqdn_, $xml_input_) {
		$server = Abstract_Server::load($fqdn_);
		if ($server === false)
			return;

		$this->fqdn = $fqdn_;
		$this->external_name = $server->getAttribute('external_name');

		$this->dom = new DomDocument('1.0', 'utf-8');
		$this->dom->loadXML($xml_input_);

		$this->compute_load();
		$this->compute_sessions();
	}

	public function save() {
		$sql = SQL::getInstance();
		$res = $sql->DoQuery(
			'INSERT INTO @1 (@2,@3,@4,@5,@6) VALUES (%7,%8,%9,%10,%11)',
			SERVERS_HISTORY_TABLE,'fqdn','external_name','cpu','ram','data',
			$this->fqdn,$this->external_name,$this->cpu,$this->ram,$this->toXML());
		return ($res !== false);
	}

	/* private methods */
	private function compute_load() {
		$server = Abstract_Server::load($this->fqdn);
		if ($server === false)
			return;

		if (! is_object($this->dom))
			return;

		$node = $this->dom->getElementsByTagName('cpu');
		if ($node->length > 0)
			$this->cpu = round ($node->item(0)->getAttribute ('load'), 2);
		else
			$this->cpu = -1;

		$node = $this->dom->getElementsByTagName('ram');
		if ($node->length > 0) {
			$total = (float) ($server->ram_total);
			$used = (float) ($node->item(0)->getAttribute('used'));
			if ($total > 0)
				$this->ram = round (($used / $total) * 100, 2);
			else
				$this->ram = 0;
		} else {
			$this->ram = -1;
		}
	}

	private function compute_sessions() {
		if (! is_object($this->dom))
			return;

		$sessions = $this->dom->getElementsByTagName('session');
		if ($sessions->length == 0)
			return;

		$apps_link = application_desktops_to_ids();

		/* the interesting <session> nodes of the xml are like:
		     <session id="ID" mode="MODE" status="STATUS" user="LOGIN">
				<instance application="APP_ID" id="ApS_ID"/>
			</session>
		*/
		foreach ($sessions as $session) {
			$sessid = $session->getAttribute('id');

			$this->sessions[$sessid] = $session->getAttribute('user');

			foreach ($session->childNodes as $instance_node) {
				if ($instance_node->tagName == 'instance') {
					$desktop = $instance_node->getAttribute('application');
					if (! array_key_exists($desktop, $apps_link))
						continue;

					$id = $apps_link[$desktop];
					if (! array_key_exists($id, $this->applications))
						$this->applications[$id] = array();

					$this->applications[$id][] = $sessid;
				}
			}
		}
	}

	public function toXml() {
		$dom = new DomDocument ('1.0', 'utf-8');
		$dom->formatOutput = true;

		/* main node */
		$snapshot = $dom->createElement('server_snapshot');
		$dom->appendChild($snapshot);

		/* sessions */
		$sessions = $dom->createElement('sessions');
		$snapshot->appendChild($sessions);
		foreach ($this->sessions as $sessid => $user) {
			$session = $dom->createElement('session');
			$session->setAttribute('user', $user);
			$session->setAttribute('id', $sessid);
			$sessions->appendChild($session);
		}

		$applications = $dom->createElement('applications');
		$snapshot->appendChild($applications);
		foreach ($this->applications as $desktop => $app_sessions) {
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
