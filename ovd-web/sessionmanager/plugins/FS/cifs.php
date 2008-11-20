<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

class FS_cifs extends Plugin {
	public function start_session($params_) {
		global $user;

		//BEGIN temp hack
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);

		$mods_enable = $prefs->get('general', 'module_enable');
		if (!in_array('UserDB', $mods_enable))
			die_error('Module UserDB must be enabled',__FILE__,__LINE__);

		$cifs_login = '';
		$cifs_password = '';
// 		if ($prefs->get('UserDB', 'enable') == 'activedirectory') {
			$cifs_config = $prefs->get('UserDB', 'activedirectory');
			$cifs_login = $cifs_config['login'];
			$cifs_password = $cifs_config['password'];
// 		}
		//END temp hack

		$this->redir_args = array(
			'module_fs' => array(
				'user_fileserver'	=>	$user->getAttribute('fileserver'),
				'fileserver_uid'	=>	$user->getAttribute('uid'),
				'user_homedir'		=>	$user->getAttribute('homedir'),
				//BEGIN temp hack related
				'cifs_login'		=>	$cifs_login,
				'cifs_password'	=>	$cifs_password
				//END temp hack related
			)
		);
	}

	public function requirements() {
		$req = array();
		$req['UserDB'] = array('fileserver', 'uid', 'homedir');
		return $req;
	}

	public function prettyName() {
		return _('Common Internet File System (CIFS)');
	}
	
	public static function enable() {
		return true;
	}
}
