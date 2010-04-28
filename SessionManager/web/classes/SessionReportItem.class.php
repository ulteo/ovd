<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com> 2009
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2010
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
	public $token;
	public $sql_id = -1;
	public $server;
	public $user;
	public $node;
	public $current_apps = array();
	public $apps_raw_data = array(); /* pid, id, running (ReportRunningItem) */
	public $stop_why;

	public function __construct($token_) {
		$this->token = $token_;
		$session = Abstract_Session::load($token_);
		if (! is_object($session)) {
			Logger::error('main', "SessionReportItem failed to load $token_");
		}
		$this->server = $session->getAttribute('server');
		$this->user = $session->getAttribute('user_login');
		$this->current_apps = array();
		$this->sql_id = $token_;
	}

	public function getId() {
		return $this->sql_id;
	}

	public function update($session_node_) {
		$sessid = $session_node_->getAttribute('id');
		$session = Abstract_Session::load($sessid);

		//$apps_link = application_desktops_to_ids();
		$user_node = null;
		/* reset the current apps data */
		$this->current_apps = array();

		foreach ($session_node_->childNodes as $tmp) {
			if ($tmp->nodeType != XML_ELEMENT_NODE ||
				$tmp->tagName != 'user')
					continue;
			$user_node = $tmp;
			break;
		}

		/* in case the xml is not as expected */
		if ($user_node == null) {
			$this->current_apps = array();
			return;
		}

		/* get the running apps for a start */
		$tmp = array();
		foreach ($user_node->childNodes as $pid_node) {
			if ($pid_node->nodeType != XML_ELEMENT_NODE ||
				$pid_node->tagName != 'application')
					continue;

			$app_pid = $pid_node->getAttribute('pid');
			$app_id = $pid_node->getAttribute('app_id');
			$this->current_apps[] = $app_id;
			$tmp[$app_pid] = $app_id;
		}

		if (is_object($session) && in_array($session->getAttribute('mode'), array('portal', 'external'))) {
			$this->current_apps = array();
			foreach ($user_node->childNodes as $sid_node) {
				if ($sid_node->nodeType != XML_ELEMENT_NODE ||
					$sid_node->tagName != 'session')
						continue;

				$session_id = $sid_node->getAttribute('id');
				$app_id = $sid_node->getAttribute('app_id');
				$this->current_apps[$session_id] = $app_id;
			}
		}

		/* for each app that was already active, we check if it's still there
		 * and:
		   - if yes, drop it from $tmp
		   - if no, regeister the end of the application
		 */
		foreach ($this->apps_raw_data as $app) {
			$app_pid = $app['pid'];
			$app_id = $app['id'];
			$app_running = $app['running'];
			if ($app_running->isDone())
				/* already ended, we don't care */
				continue;

			if (array_key_exists ($app_pid, $tmp) && ($app_id == $tmp[$app_pid]))
				unset ($tmp[$app_pid]);
			else
				$app_running->stop();
		}

		/* now register each remaining item in $tmp */
		foreach ($tmp as $app_pid => $app_id) {
			$this->apps_raw_data[] = array(
				'pid' => $app_pid,
				'id' => $app_id,
				'running' => new ReportRunningItem()
			);
		}

		$this->saveSessionCurrentApps();
	}

	public function end() {
		/* end all applications */
		$now = time();
		foreach ($this->apps_raw_data as $app) {
			$app_running = $app['running'];
			if (! $app_running->isDone())
				$app['running']->stop($now);
		}

		$sql = MySQL::getInstance();
		$res = $sql->DoQuery(
			'UPDATE @1 SET @2=NOW(), @3=%4 WHERE @5=%6',
			SESSIONS_HISTORY_TABLE,'stop_stamp','data',$this->toXml(),
			'id',$this->sql_id);
		return ($res !== false);
	}

	public function test() {
		print $this->toXml();
	}
	
	public static function getStatusPrettyName($status_) {
		$statusPrettyName = array(
			'exit' => _('No error'), 
			'adminkill' => _('Killed by administrator'),
			'internal1' => _('Internal error: wrong session status'),
			'internal2' => _('Internal error: failed to initialize session'),
			'internal3' => _('Internal error: failed to suspend session'),
			'timeout' => _('timeout'),
			'shutdown' => _('Shutdown of the Application Server'),
			'kma' => _('KMA expirated'),
			'ssh' => _('Never connected to SSH server')
		);
	
		if (array_key_exists($status_, $statusPrettyName))
			return $statusPrettyName[$status_];
		
		return $status_;
	}

	/*
	 * private methods
	 */
	private function saveSessionCurrentApps() {
		$session = Abstract_Session::load($this->token);
		if (is_object($session)) {
			$session->setAttribute('applications', $this->current_apps);
			Abstract_Session::save($session);
		}
	}

	private function toXml() {
		$dom = new DomDocument ('1.0', 'utf-8');
	    $dom->formatOutput = true;

	    /* main node */
	    $snapshot = $dom->createElement('session_snapshot');
	    $dom->appendChild($snapshot);

		/* applications */
		$applications = $dom->createElement('applications');
		$snapshot->appendChild($applications);
		foreach ($this->apps_raw_data as $data) {
			$application = $dom->createElement('application');
			$applications->appendChild($application);

			$node = $dom->createElement('id');
			$application->appendChild($node);
			$txt = $dom->createTextNode($data['id']);
			$node->appendChild($txt);

			$node = $dom->createElement('start');
			$application->appendChild($node);
			$txt = $dom->createTextNode($data['running']->start);
			$node->appendChild($txt);

			$node = $dom->createElement('stop');
			$application->appendChild($node);
			$txt = $dom->createTextNode($data['running']->end);
			$node->appendChild($txt);
		}

		return $dom->saveXML();
	}
}

