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
	public $extras;

	public function __construct($id_='', $name_=NULL, $description_='', $published_=false) {
		Logger::debug('main', "USERSGROUP::contructor from_scratch (id_='$id_', name_='$name_', description_='$description_', published_=$published_)");
		$this->type = 'static';
		$this->id = $id_;
		$this->name = $name_;
		$this->description = $description_;
		$this->published = (bool)$published_;
		$this->extras = NULL;
	}
	
	public function __toString() {
		return get_class($this).'(id: \''.$this->id.'\' name: \''.$this->name.'\' description: \''.$this->description.'\' published: '.$this->published.')';
	}
	
	public function getUniqueID() {
		return $this->type.'_'.$this->id;
	}
	
	public function appsGroups(){
		Logger::debug('main', 'USERSGROUP::appsGroups (for id='.$this->getUniqueID().')');
		$ApplicationsGroupDB = ApplicationsGroupDB::getInstance();
		$groups = Abstract_Liaison::load('UsersGroupApplicationsGroup', $this->getUniqueID(), NULL);
		if (is_array($groups)) {
			$result = array();
			foreach ($groups as $UGAG_liaison){
// 				var_dump2($UGAG_liaison);
				$g = $ApplicationsGroupDB->import($UGAG_liaison->group);
				if (is_object($g))
					$result[$UGAG_liaison->group]= $g;
			}
			return $result;
		}
		else {
			Logger::error('main', 'USERSGROUP::appsGroups (for id='.$this->getUniqueID().') result query is false');
			return NULL;
		}
	}
	
	public function usersLogin(){
		Logger::debug('main', 'USERSGROUP::usersLogin (for id='.$this->getUniqueID().')');
		$logins = array();
		$prefs = Preferences::getInstance();
		if (!$prefs) {
			Logger::critical('main', 'USERSGROUP::usersLogin (for id='.$this->getUniqueID().') get prefs failed');
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
	
	public function getPolicy($with_default_=true) {
		Logger::debug('main', 'UsersGroup::getPolicy for '.$this->id);
		$prefs = Preferences::getInstance();
		$prefs_policy = $prefs->get('general', 'policy');
		$elements = $prefs->getElements('general', 'policy');
		if (array_key_exists('default_policy', $elements) == false) {
			Logger::error('main', 'UsersGroup::getPolicy, default_policy not found on general policy');
			return array();
		}
		$result = $elements['default_policy']->content_available;
		
		foreach ($result as $k => $v) {
			$result[$k] = false;
		}
		$default_policy = $prefs_policy['default_policy'];
		
		foreach ($default_policy as $k => $v) {
			if ( $with_default_) {
				$result[$v] = true;
			}
			else {
				unset($result[$k]);
			}
		}
		
		$acls = Abstract_Liaison::load('ACL', $this->getUniqueID(), NULL);
		if (is_array($acls)) {
			foreach ($acls as $acl_liaison) {
				$result[$acl_liaison->group] = True;
			}
		}
		return $result;
	}
	
	public function updatePolicy($new_policy_) {
		$old_policy = $this->getPolicy();
		Abstract_Liaison::delete('ACL', $this->getUniqueID(), NULL);
		foreach ($new_policy_ as $a_policy => $allow) {
			if ( $allow) {
				Abstract_Liaison::save('ACL', $this->getUniqueID(), $a_policy);
			}
		}
	}
}
