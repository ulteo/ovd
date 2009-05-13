<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
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

class UsersGroup {
	public $id;
	public $name; // (ex: IT)
	public $description; // (ex: People from the basement)
	public $published; //(yes/no)

	public function __construct($id_=NULL, $name_=NULL, $description_='', $published_=false) {
		Logger::debug('admin',"USERSGROUP::contructor from_scratch (id_='$id_', name_='$name_', description_='$description_', published_=$published_)");
		$this->id = $id_;
		$this->name = $name_;
		$this->description = $description_;
		$this->published = (bool)$published_;
	}
	
	public function __toString() {
		return get_class($this).'(id: \''.$this->id.'\' name: \''.$this->name.'\' description: \''.$this->description.'\' published: '.$this->published.')';
	}
	
	public function appsGroups(){
		Logger::debug('admin','USERSGROUP::appsGroups');
		
		$groups = Abstract_Liaison::load('UsersGroupApplicationsGroup', $this->id, NULL);
		if (is_array($groups)) {
			$result = array();
			foreach ($groups as $UGAG_liaison){
// 				var_dump2($UGAG_liaison);
				$g = new AppsGroup();
				$g->fromDB($UGAG_liaison->group);
				if ($g->isOK())
					$result[$UGAG_liaison->group]= $g;
			}
			return $result;
		}
		else {
			Logger::error('admin','USERSGROUP::appsGroups result query is false');
			return NULL;
		}
	}
	
	public function usersLogin(){
		Logger::debug('admin','USERSGROUP::usersLogin');
		$ls = Abstract_Liaison::load('UsersGroup',NULL, $this->id);
		$logins = array();
		if (is_array($ls)) {
			foreach ($ls as $l) {
				$logins []= $l->element;
			}
		}
		return $logins;
	}
	
	public function containUser($user_) {
		Logger::debug('main','USERSGROUP::containUser');
		if (!$user_->hasAttribute('login')) {
			Logger::error('main', 'USERSGROUP::containUser user '.$user_.' has no attribute \'login\'');
			return false;
		}
		$users_login = $this->usersLogin();
		return in_array($user_->getAttribute('login'), $users_login);
	}
}
