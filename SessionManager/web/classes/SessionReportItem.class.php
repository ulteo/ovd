<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com>
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
	private $token;
	private $sql_id = -1;
	private $server;
	private $node;
	private $current_apps = array();
	private $apps_raw_data = array(); /* pid, id, running (ReportRunningItem) */

	/* Session items are stored in the db before computing anything else
	 * because the sql_id is an auto-incremented int. We need to know this id
	 * so that the server reports can be linked to it */
	public function __construct($token_) {
		$this->token = $token_;
		$session = Abstract_Session::load($token_);
		$this->server = $session->getAttribute('server');
		$this->user = $session->getAttribute('user_login');
		$this->current_apps = array();

		$sql = MySQL::getInstance();
		$res = $sql->DoQuery(
			'INSERT INTO @1 (@2,@3,@4) VALUES (%5,%6,%7)',
			SESSIONS_HISTORY_TABLE,'user','server','data',
			$this->user,$this->server,'');
		if ($res !== false)
			$this->sql_id = $sql->InsertId();
	}

	public function getId() {
		return $this->sql_id;
	}

	public function update($session_node_) {
		$apps_link = application_desktops_to_ids();
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
				$pid_node->tagName != 'pid')
					continue;

			$app_pid = $pid_node->getAttribute('id');
			$app_desktop = $pid_node->getAttribute('desktop');
			if (array_key_exists ($app_desktop, $apps_link)) {
				$app_id = $apps_link[$app_desktop];
				$this->current_apps[] = $app_id;
				$tmp[$app_pid] = $app_id;
			} else {
				Logger::warning('main', 'SessionReportItem::init: unknow application '.$app_desktop);
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
		$dom = new DomDocument ();
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

