<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class FS_nfs extends Plugin {
	public function start_session($params_=array()) {
		global $user;

		$this->redir_args = array(
			'module_fs' => array(
				'user_id'			=>	$user->getAttribute('fileserver_uid'),
				'fileserver_uid'	=>	$user->getAttribute('fileserver_uid'),
				'user_fileserver'	=>	$user->getAttribute('fileserver'),
				'user_homedir'		=>	$user->getAttribute('homedir'),
				'homebase'		=>	$user->getAttribute('homebase')
			)
		);
	}

	public function requirements() {
		$req = array();
		$req['UserDB'] = array('fileserver_uid', 'fileserver', 'homedir');
		return $req;
	}

	public static function prettyName() {
		return _('Network File System (NFS)');
	}

	public static function enable() {
		return false;
	}
}
