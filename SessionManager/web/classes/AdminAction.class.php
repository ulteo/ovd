<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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

class AdminAction {
	public $when = NULL;
	public $who = NULL;
	public $what = NULL;
	public $where = NULL;
	public $infos = NULL;
	
	public function __construct($when_, $who_, $what_, $where_) {
		$this->when = $when_;
		$this->who = $who_;
		$this->what = $what_;
		$this->where = $where_;
		$this->infos = array();
	}
}
