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

class SessionReportItem {
	private $token;
	private $sql_id = -1;
	private $server;
	private $node;
	private $current_apps = array();

	/* Session items are stored in the db before computing anything else
	 * because the sql_id is an auto-incremented int. we need to know this id
	 * so that the server reports can be linked to it */
	public function __construct($token_, $session_node_) {
		$this->token = $token_;
		$session = Abstract_Session::load($token_);
		$this->server = $session->getAttribute('server');
		$this->user = $session->getAttribute('user_login');

		$this->node = $session_node_;
		$this->current_apps = $this->currentApps();

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

	public function getCurrentApps() {
		return $this->current_apps;
	}

	/* private methods */
	private function currentApps() {
		$apps_link = application_desktops_to_ids();
		$user_node = null;

		foreach ($this->node->childNodes as $tmp) {
			if ($tmp->nodeType != XML_ELEMENT_NODE ||
				$tmp->tagName != 'user')
					continue;
			$user_node = $tmp;
			break;
		}

		if ($user_node == null)
			return array();

		foreach ($user_node->childNodes as $pid_node) {
			if ($pid_node->nodeType != XML_ELEMENT_NODE ||
				$pid_node->tagName != 'pid')
					continue;

			$desktop = $pid_node->getAttribute('desktop');

			if (array_key_exists ($desktop, $apps_link))
				$ret[] = $apps_link[$desktop];
		}

		return $ret;
	}
}
