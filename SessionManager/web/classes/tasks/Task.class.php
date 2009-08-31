<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class Task {
	public $id = NULL;
	public $t_begin = NULL;
	public $t_end = NULL;
	public $request = NULL;
	public $server = NULL;
	public $job_id = NULL;
	public $status = NULL;
	public $status_code = NULL;

	public function __construct($task_id_, $server_) {
		Logger::debug('main', 'Starting TASK::__construct for task '.$task_id_);

		$this->id = $task_id_;
		$this->t_begin = time();
		$this->status = 'not inited';
		$this->server = $server_;
	}

	public function init() {
		$job_id = query_url($this->server->getWebservicesBaseURL().'/apt-get.php?action=request&request='.urlencode($this->getRequest()));
		if ($job_id === false) {
			$this->status = 'error';
			$this->status_code = -4;
			return false;
		}

		$this->job_id = $job_id;
		$this->status = 'in progress';
		return true;	
	}

	public function refresh() {
		$buf = query_url($this->server->getWebservicesBaseURL().'/apt-get.php?action=status&job='.$this->job_id);
		if ($buf === false) {
			$this->status = 'error';
			return true;
		}

		$this->status_code = $buf;
		if ($buf < 0 || $buf > 2) {
			Logger::error('apt-get', 'TASK::refresh for task '.$this->id.' on server '.$this->server.' returned an error ('.$buf.')');

			$this->status = 'error';
			return true;
		}	

		if ($buf == 0 || $buf == 1) {
			Logger::warning('apt-get', 'TASK::refresh for task '.$this->id.' on server '.$this->server.' is in progress ('.$buf.')');

			$this->status = 'in progress';
			return false;
		}
			
		Logger::info('apt-get', 'TASK::refresh for task '.$this->id.' on server '.$this->server.' is now finished ('.$buf.')');

		$this->status = 'finished';
		$this->t_end = time();

		$server = Abstract_Server::load($this->server);
		$server->updateApplications();
		return true;
	}
	
	public function get_AllInfos() {
		$infos = array();
		foreach (array('status', 'stdout', 'stderr') as $elem)
			$infos[$elem] = query_url_no_error($this->server->getWebservicesBaseURL().'/apt-get.php?action=show&job='.$this->job_id.'&show='.$elem);
		
		return $infos;
	}

	public function succeed() {
		return $this->status == 'finished';
	}
	
	public function failed() {
		return $this->status == 'error';
	}
}
