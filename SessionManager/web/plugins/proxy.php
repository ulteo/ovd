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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class Plugin_proxy extends Plugin {
	public function start_session($params_) {
		global $_REQUEST;

		if (isset($_REQUEST['enable_proxy']) && $_REQUEST['enable_proxy'] == 1) {
			$this->redir_args = array(
				'enable_proxy'		=>	1,
				'proxy_type'		=>	$_REQUEST['proxy_type'],
				'proxy_host'		=>	$_REQUEST['proxy_host'],
				'proxy_port'		=>	$_REQUEST['proxy_port'],
				'proxy_username'	=>	$_REQUEST['proxy_username'],
				'proxy_password'	=>	$_REQUEST['proxy_password']
			);
		}
	}

	public function requirements() {
		$req = array();
		return $req;
	}
	
	public function prettyName() {
		return _('proxy');
	}
	
	public static function enable() {
		return true;
	}
}
