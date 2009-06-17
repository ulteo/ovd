<?php
/**
 * Copyright (C) 2008-2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class SharedFolders {
	public static function getAll() {
// 		Logger::debug('main', 'Starting SharedFolders::getAll');

		$buf = Abstract_SharedFolder::load_all();

		if (! is_array($buf)) {
			Logger::error('main', 'SharedFolders::getAll Abstract_SharedFolder::load_all failed (not an array)');
			return array();
		}

		return $buf;
	}

	public static function getByName($sharedfolder_name_) {
// 		Logger::debug('main', 'Starting SharedFolders::getByName');

		$sharedfolders = SharedFolders::getAll();

		foreach ($sharedfolders as $k => $sharedfolder) {
			if ($sharedfolder->name != $sharedfolder_name_)
				unset($sharedfolders[$k]);
		}

		return $sharedfolders;
	}

	public static function getByUserGroupId($usergroup_id_) {
// 		Logger::debug('main', 'Starting SharedFolders::getByUserGroupId');

		$sharedfolders = Abstract_SharedFolder::load_by_usergroup_id($usergroup_id_);

		return $sharedfolders;
	}
}
