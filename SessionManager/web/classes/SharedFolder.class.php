<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
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
	
	public function __construct($id, $name, $server, $status) {
		parent::__construct($id, $server, $status);
		$this->name = $name;
	}
	
	public function __toString() {
		return get_class($this).'(id \''.$this->id.'\' name \''.$this->name.'\' server \''.$this->server.'\' status \''.$this->status.'\' )';
	}
	
	public function getPublishedUserGroups() {
		$sharedfolderdb = SharedFolderDB::getInstance();
		$usergroups_id = $sharedfolderdb->get_usersgroups($this);
		
		$usergroupDB = UserGroupDB::getInstance();
		
		$usergroups = array();
		foreach ($usergroups_id as $usergroup_id => $mode) {
			$usergroup = $usergroupDB->import($usergroup_id);
			if (! is_object($usergroup))
				continue;
			
			if (! array_key_exists($mode, $usergroups))
				$usergroups[$mode] = array();
			
			$usergroups[$mode][$usergroup->getUniqueID()] = $usergroup;
		}
		return $usergroups;
	}
	
	public function addUserGroup($usergroup_, $mode_) {
		$sharedfolderdb = SharedFolderDB::getInstance();
		return $sharedfolderdb->addUserGroupToSharedFolder($usergroup_, $this, $mode_);
	}
	
	public function delUserGroup($usergroup_) {
		$sharedfolderdb = SharedFolderDB::getInstance();
		return $sharedfolderdb->delUserGroupToSharedFolder($usergroup_, $this);
	}
}
