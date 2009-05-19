<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
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

class FS_dav extends Plugin {
	public function start_session($params_=array()) {
		global $base_url, $user;

		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed', __FILE__, __LINE__);

		$this->redir_args = array();
		$this->redir_args['module_fs'] = array();

		$usergroups = $user->usersGroups();
		$sharedfolders = array();
		foreach ($usergroups as $usergroup)
			$sharedfolders = array_merge($sharedfolders, UserGroup_SharedFolders::getByUserGroupId($usergroup->getUniqueID()));

		if (count($sharedfolders) == 0)
			return;

		$buf = '';
		foreach ($sharedfolders as $sharedfolder)
			$buf.= $sharedfolder->name.'|http://'.$_SERVER['SERVER_NAME'].$base_url.'webdav.php/'.$sharedfolder->usergroup_id.'/'.$sharedfolder->id.'/'."\n";

		$this->redir_args['module_fs']['dav_dirs'] = $buf;

		$buf = new DAV_User($user->getAttribute('login'));
		$buf->generatePassword();
		Abstract_DAV_User::save($buf);

		$this->redir_args['module_fs']['login'] = $buf->login;
		$this->redir_args['module_fs']['password'] = $buf->password;
	}

	public function remove_session($params_=array()) {
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed', __FILE__, __LINE__);

		$session = Abstract_Session::load($params_['session']);
		if (! $session)
			return;

		Abstract_DAV_User::delete($session->getAttribute('user_login'));
	}

	public function requirements() {
		$req = array();
		return $req;
	}

	public static function prettyName() {
		return _('Internal WebDAV');
	}

	public static function enable() {
		return true;
	}

	public static function configuration() {
		$ret = array();
		return $ret;
	}
}
