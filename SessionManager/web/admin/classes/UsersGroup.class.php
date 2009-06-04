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
	public $type; // static

	public function __construct($id_='', $name_=NULL, $description_='', $published_=false) {
		Logger::debug('admin',"USERSGROUP::contructor from_scratch (id_='$id_', name_='$name_', description_='$description_', published_=$published_)");
		$this->type = 'static';
		$this->id = $id_;
		$this->name = $name_;
		$this->description = $description_;
		$this->published = (bool)$published_;
	}
	
	public function __toString() {
		return get_class($this).'(id: \''.$this->id.'\' name: \''.$this->name.'\' description: \''.$this->description.'\' published: '.$this->published.')';
	}
	
	public function getUniqueID() {
		return $this->type.'_'.$this->id;
	}
	
	public function appsGroups(){
		Logger::debug('admin','USERSGROUP::appsGroups');
		
		$groups = Abstract_Liaison::load('UsersGroupApplicationsGroup', $this->getUniqueID(), NULL);
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
		$logins = array();
		$prefs = Preferences::getInstance();
		if (!$prefs) {
			Logger::critical('main', 'USERSGROUP::usersLogin get prefs failed');
			die_error('get Preferences failed', __FILE__, __LINE__);
		}
		$user_default_group = $prefs->get('general', 'user_default_group');
		if ($user_default_group === $this->getUniqueID()) {
			// it's the default group -> we add all users
			$userdb = UserDB::getInstance();
			$users = $userdb->getList();
			foreach ($users as $a_user) {
				$logins []= $a_user->getAttribute('login');
			}
		}
		else {
			$ls = Abstract_Liaison::load('UsersGroup',NULL, $this->getUniqueID());
			if (is_array($ls)) {
				foreach ($ls as $l) {
					$logins []= $l->element;
				}
			}
		}
		
		return $logins;
	}
	
	public function containUser($user_) {
		Logger::debug('main','USERSGROUP::containUser (login='.$user_->getAttribute('login').')');
		if (!$user_->hasAttribute('login')) {
			Logger::error('main', 'USERSGROUP::containUser user '.$user_.' has no attribute \'login\'');
			return false;
		}
		$users_login = $this->usersLogin();
		return in_array($user_->getAttribute('login'), $users_login);
	}
	
	public function isDefault() {
		$prefs = Preferences::getInstance();
		if (!$prefs) {
			Logger::critical('main', 'USERSGROUP::isDefault get prefs failed');
			die_error('get Preferences failed', __FILE__, __LINE__);
		}
		$user_default_group = $prefs->get('general', 'user_default_group');
		return $user_default_group === $this->getUniqueID();
	}
}
