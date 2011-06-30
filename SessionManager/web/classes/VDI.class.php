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

class VDI {
	public $id = '';

	public $type = '';
	public $name = '';
	public $server = '';

	public $master_id = '';

	public $used_by = '';

	public $cpu_model = '';
	public $cpu_nb_cores = 0;
	public $ram_total = 0;

	public $status = '';
	public $ip = '';

	public function __construct($id_='0') {
		$this->id = $id_;

		$this->hypervisor = 'kvm'; //FIXME
	}

	public function __toString() {
		return get_class($this).' ('.$this->id.')';
	}

	public function init() {
		return true;
	}

	public static function textHypervisorType($type_) {
		$ret = 'Inconnu';

		switch ($type_) { //FIXME
			case 'kvm':
				$ret = 'KVM';
				break;
			case 'vbox':
				$ret = 'VirtualBox';
				break;
		}

		return $ret;
	}

	public static function isUsed() {
		return false; //FIXME
	}
}
