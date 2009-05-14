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

class UserGroup_Rules {
	public static function getAll() {
// 		Logger::debug('main', 'Starting UserGroup_Rules::getAll');

		$buf = Abstract_UserGroup_Rule::load_all();

		if (! is_array($buf))
			return array();

		return $buf;
	}

	public static function getByUserGroupId($usergroup_id_) {
// 		Logger::debug('main', 'Starting UserGroup_Rules::getByUserGroupId');

		$usergroup_rules = UserGroup_Rules::getAll();

		foreach ($usergroup_rules as $k => $usergroup_rule) {
			if ($usergroup_rule->usergroup_id != $usergroup_id_)
				unset($usergroup_rules[$k]);
		}

		return $usergroup_rules;
	}
}
