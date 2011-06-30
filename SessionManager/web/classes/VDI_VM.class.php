<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2011
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

class VDI_VM extends VDI {
	public function __construct($id_='0') {
		parent::__construct($id_);

		$this->type = 'vm';
	}

	public function init() {
		parent::init();

		$this->master_os = Abstract_VDI::load($this->master_id);

		return true;
	}

	public function orderDeletion() {
		$server = Abstract_Server::load($this->server);
		if (! $server) {
			Logger::error('main', 'VDI_VM::orderDeletion unable to load Server \''.$this->server.'\'');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('vm');
		$node->setAttribute('id', $this->id);
		$dom->appendChild($node);

		$xml = $dom->saveXML();

		$xml = query_url_post_xml($server->getBaseURL().'/hypervisor/vm/destroy', $xml);
		if (! $xml) {
			$server->isUnreachable();
			Logger::error('main', 'VDI_VM::orderDeletion server \''.$server->fqdn.'\' is unreachable');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$buf = @$dom->loadXML($xml);
		if (! $buf)
			return false;

		if (! $dom->hasChildNodes())
			return false;

		$node = $dom->getElementsByTagname('vm')->item(0);
		if (is_null($node))
			return false;

		$status = $node->getAttribute('status');
		if ($status != 'OK') {
			Logger::error('main', 'VDI_VM::orderDeletion failed');
			return false;
		}

		return true;
	}

	public function orderCreation() {
		$server = Abstract_Server::load($this->server);
		if (! $server) {
			Logger::error('main', 'VDI_VM::orderCreation unable to load Server \''.$this->server.'\'');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('vm');
		$node->setAttribute('master', $this->master_id);
		$node->setAttribute('vcpu', $this->cpu_nb_cores);
		$node->setAttribute('ram', $this->ram_total);
		$dom->appendChild($node);

		$xml = $dom->saveXML();

		$xml = query_url_post_xml($server->getBaseURL().'/hypervisor/vm/create', $xml);
		if (! $xml) {
			$server->isUnreachable();
			Logger::error('main', 'VDI_VM::orderCreation server \''.$server->fqdn.'\' is unreachable');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$buf = @$dom->loadXML($xml);
		if (! $buf)
			return false;

		if (! $dom->hasChildNodes())
			return false;

		$node = $dom->getElementsByTagname('vm')->item(0);
		if (is_null($node))
			return false;

		$status = $node->getAttribute('status');
		if ($status != 'OK') {
			Logger::error('main', 'VDI_VM::orderCreation failed');
			return false;
		}

		return true;
	}

	public function orderAction($action_) {
		$server = Abstract_Server::load($this->server);
		if (! $server) {
			Logger::error('main', 'VDI_VM::orderAction unable to load Server \''.$this->server.'\'');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$node = $dom->createElement('vm');
		$node->setAttribute('id', $this->id);
		$node->setAttribute('action', $action_);
		$dom->appendChild($node);

		$xml = $dom->saveXML();

		$xml = query_url_post_xml($server->getBaseURL().'/hypervisor/vm/manage', $xml);
		if (! $xml) {
			$server->isUnreachable();
			Logger::error('main', 'VDI_VM::orderAction server \''.$server->fqdn.'\' is unreachable');
			return false;
		}

		$dom = new DomDocument('1.0', 'utf-8');

		$buf = @$dom->loadXML($xml);
		if (! $buf)
			return false;

		if (! $dom->hasChildNodes())
			return false;

		$node = $dom->getElementsByTagname('vm')->item(0);
		if (is_null($node))
			return false;

		$status = $node->getAttribute('status');
		if ($status != 'OK') {
			Logger::error('main', 'VDI_VM::orderAction failed');
			return false;
		}

		return true;
	}

	public function stop() {
		if (! $this->orderAction('destroy'))
			return false;

		return true;
	}

	public function start() {
		if (! $this->orderAction('run'))
			return false;

		return true;
	}

	public function suspend() {
		if (! $this->orderAction('suspend'))
			return false;

		return true;
	}

	public function resume() {
		if (! $this->orderAction('resume'))
			return false;

		return true;
	}

	public function isOnline() {
		if ($this->status == 'RUNNING')
			return true;

		return false;
	}

	public static function textStatus($status_) {
		$ret = '<strong>'._('Unknown').'</strong>';

		switch ($status_) {
			case 'RUNNING':
				$ret = '<span class="msg_ok">'._('Running').'</span>';
				break;
			case 'SUSPEND':
				$ret = '<span class="msg_warn">'._('Suspended').'</span>';
				break;
			case 'STOP':
				$ret = '<span class="msg_error">'._('Stopped').'</span>';
				break;
		}

		return $ret;
	}
}
