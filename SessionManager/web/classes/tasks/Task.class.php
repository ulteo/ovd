<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
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
	public $server = NULL;
	public $job_id = NULL;
	public $status = NULL;

	public function __construct($task_id_, $server_) {
		Logger::debug('main', 'Starting TASK::__construct for task '.$task_id_);

		$this->id = $task_id_;
		$this->t_begin = time();
		$this->status = 'not inited';
		$this->server = $server_;
	}

	public function __toString() {
		return get_class($this).'('.$this->id.')';
	}

	public function init() {
		$server = Abstract_Server::load($this->server);
		if (! is_object($server)) {
			Logger::error('apt-get', 'TASK::init for task '.$this->id.' returned an error (unknown server '.$this->server.')');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('debian');
		$node->setAttribute('request', $this->getRequest());

		foreach ($this->getPackages() as $package) {
			$buf = $dom->createElement('package');
			$buf->setAttribute('name', $package);
			$node->appendChild($buf);
		}
		$dom->appendChild($node);

		$xml = $dom->saveXML();

		$xml = query_url_post_xml($server->getBaseURL().'/aps/debian', $xml);
		if (! $xml) {
			popup_error(sprintf(_("Unable to submit Task to server '%s'"), $server->fqdn));
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$buf = @$dom->loadXML($xml);
		if (! $buf)
			return false;

		if (! $dom->hasChildNodes())
			return false;

		$node = $dom->getElementsByTagname('debian_request')->item(0);
		if (is_null($node))
			return false;

		$this->job_id = $node->getAttribute('id');
		$this->status = $node->getAttribute('status');
		return true;	
	}

	public function refresh() {
		$server = Abstract_Server::load($this->server);
		if (! is_object($server)) {
			Logger::error('apt-get', 'TASK::refresh for task '.$this->id.' returned an error (unknown server '.$this->server.')');
			return false;
		}

		$xml = query_url($server->getBaseURL().'/aps/debian/'.$this->job_id.'/status');
		if (! $xml) {
			Logger::error('apt-get', 'TASK::refresh for task '.$this->id.' on server '.$this->server.' returned an error');
			$this->status = 'error';
			return true;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$buf = @$dom->loadXML($xml);
		if (! $buf)
			return false;

		if (! $dom->hasChildNodes())
			return false;

		$node = $dom->getElementsByTagname('debian_request')->item(0);
		if (is_null($node))
			return false;

		$this->status = $node->getAttribute('status');

		$ret = true;
		switch ($this->status) {
			case 'created':
				Logger::warning('apt-get', 'TASK::refresh for task '.$this->id.' on server '.$this->server.' is created');
				$ret = false;
				break;
			case 'in progress':
				Logger::warning('apt-get', 'TASK::refresh for task '.$this->id.' on server '.$this->server.' is in progress');
				$ret = false;
				break;
			case 'success':
				Logger::info('apt-get', 'TASK::refresh for task '.$this->id.' on server '.$this->server.' is now finished');
				$this->t_end = time();
				$server->updateApplications();
				$ret = true;
				break;
			case 'error':
				Logger::error('apt-get', 'TASK::refresh for task '.$this->id.' on server '.$this->server.' returned an error');
				$ret = true;
				break;
		}

		return $ret;
	}
	
	public function get_AllInfos() {
		$server = Abstract_Server::load($this->server);
		if (! is_object($server)) {
			Logger::error('apt-get', 'TASK::get_AllInfos for task '.$this->id.' returned an error (unknown server '.$this->server.')');
			return false;
		}

		$infos = array();
		foreach (array('stdout', 'stderr') as $elem)
			$infos[$elem] = query_url_no_error($server->getBaseURL().'/aps/debian/'.$this->job_id.'/'.$elem);
		
		return $infos;
	}

	public function succeed() {
		return $this->status == 'success';
	}
	
	public function failed() {
		return $this->status == 'error';
	}

	public function getPackages() {
		return array();
	}
}
