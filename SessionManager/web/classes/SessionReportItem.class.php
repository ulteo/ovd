<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
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
require_once(CLASSES_DIR.'/ReportItems.class.php');

class SessionReportItem {
	public $id = -1; // id of the report is also the id of the session
	public $server;
	public $user;
	private $node;
	private $current_apps = array();
	private $apps_raw_data = array(); /* pid, id, running (ReportRunningItem) */
	public $stop_why;

	public function __construct() {
		$this->current_apps = array();
 	}

	public function getId() {
		return $this->id;
	}

	public function update($session_node_) {
		$sessid = $session_node_->getAttribute('id');
		$buf = Abstract_Session::exists($sessid);
		if (! $buf)
			return;

		$session = Abstract_Session::load($sessid);
		if (! $session)
			return;

		//$apps_link = application_desktops_to_ids();
		/* reset the current apps data */
		$this->current_apps = array();

		/* get the running apps for a start */
		$tmp = array();
		foreach ($session_node_->childNodes as $instance_node) {
			if ($instance_node->tagName != 'instance')
				continue;

			$app_pid = $instance_node->getAttribute('id');
			$app_id = $instance_node->getAttribute('application');
			$this->current_apps[$sessid] = $app_id;
			$tmp[$app_pid] = $app_id;
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
	}

	public function end() {
		/* end all applications */
		$now = time();
		foreach ($this->apps_raw_data as $app) {
			$app_running = $app['running'];
			if (! $app_running->isDone())
				$app['running']->stop($now);
		}

		$sql = SQL::getInstance();
		$res = $sql->DoQuery(
			'UPDATE @1 SET @2=NOW(), @3=%4 WHERE @5=%6',
			SESSIONS_HISTORY_TABLE,'stop_stamp','data',$this->toXml(),
			'id',$this->id);
		return ($res !== false);
	}

	public function test() {
		print $this->toXml();
	}
	/*
	 * private methods
	 */
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

