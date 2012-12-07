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

class ServerReport extends AbstractObject {
	protected function required_attributes() {
		return array('time', 'id', 'fqdn', 'external_name', 'cpu', 'ram', 'data');
	}
	
	public function getTime() {
		return $this->getAttribute('time');
	}
	
	public function getID() {
		return $this->getAttribute('id');
	}
	
	public function getFQDN() {
		return $this->getAttribute('fqdn');
	}
	
	public function getExternalName() {
		return $this->getAttribute('external_name');
	}
	
	public function getCPU() {
		return $this->getAttribute('cpu');
	}
	
	public function getRAM() {
		return $this->getAttribute('ram');
	}
	
	public function getData() {
		return $this->getAttribute('data');
	}
}
