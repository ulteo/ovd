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

class SharedFolder extends NetworkFolder {
	public $name = '';
	
	public function __toString() {
		return get_class($this).'(id \''.$this->id.'\' name \''.$this->name.'\' server \''.$this->server.'\' status \''.$this->status.'\' )';
	}
	
	public function getUserGroups() {
		$liaisons = Abstract_Liaison::load('UserGroupSharedFolder', NULL, $this->id);
		if (is_array($liaisons) == false) {
			Logger::error('main', 'NetworkFolder::getUserGroups()');
			return false;
		}
		
		$usergroupDB = UserGroupDB::getInstance();
		
		$usergroups = array();
		foreach ($liaisons as $liaison) {
			$usergroup = $usergroupDB->import($liaison->element);
			if (! is_object($usergroup))
				continue;
			
			$usergroups[$usergroup->getUniqueID()] = $usergroup;
		}
		return $usergroups;
	}
	
	public function addUserGroup($usergroup_) {
		$sharedfolderdb = SharedFolderDB::getInstance();
		return $sharedfolderdb->addUserGroupToSharedFolder($usergroup_, $this);
	}
	
	public function delUserGroup($usergroup_) {
		$sharedfolderdb = SharedFolderDB::getInstance();
		return $sharedfolderdb->delUserGroupToSharedFolder($usergroup_, $this);
	}
}
