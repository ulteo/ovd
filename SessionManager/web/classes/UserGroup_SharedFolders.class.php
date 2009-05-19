<?php
/**
 * Copyright (C) 2008-2009 Ulteo SAS
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

class UserGroup_SharedFolders {
	public static function getAll() {
// 		Logger::debug('main', 'Starting UserGroup_SharedFolders::getAll');

		$buf = Abstract_UserGroup_SharedFolder::load_all();

		if (! is_array($buf))
			return array();

		return $buf;
	}

	public static function getByName($sharedfolder_name_) {
// 		Logger::debug('main', 'Starting UserGroup_SharedFolders::getByName');

		$usergroup_sharedfolders = UserGroup_SharedFolders::getAll();

		foreach ($usergroup_sharedfolders as $k => $usergroup_sharedfolder) {
			if ($usergroup_sharedfolder->name != $sharedfolder_name_)
				unset($usergroup_sharedfolders[$k]);
		}

		return $usergroup_sharedfolders;
	}

	public static function getByUserGroupId($usergroup_id_) {
// 		Logger::debug('main', 'Starting UserGroup_SharedFolders::getByUserGroupId');

		$usergroup_sharedfolders = UserGroup_SharedFolders::getAll();

		foreach ($usergroup_sharedfolders as $k => $usergroup_sharedfolder) {
			if ($usergroup_sharedfolder->usergroup_id != $usergroup_id_)
				unset($usergroup_sharedfolders[$k]);
		}

		return $usergroup_sharedfolders;
	}
}
