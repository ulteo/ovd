<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
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

class Profile extends NetworkFolder {
	public function getUsers() {
		$liaisons = Abstract_Liaison::load('UserProfile', NULL, $this->id);
		if (is_array($liaisons) == false) {
			Logger::error('main', 'NetworkFolder::getUsers()');
			return false;
		}
		
		$userDB = UserDB::getInstance();
		
		$users = array();
		foreach ($liaisons as $liaison) {
			$user = $userDB->import($liaison->element);
			if (! is_object($user))
				continue;
			
			$users[$user->getAttribute('login')] = $user;
		}
		return $users;
	}
	
	public function addUser($user_) {
		$profiledb = ProfileDB::getInstance();
		return $profiledb->addUserToProfile($user_, $this);
	}
	
	public function delGroup($usergroup_) {
		$profiledb = ProfileDB::getInstance();
		return $profiledb->delUserOfProfile($user_, $this);
	}
}
