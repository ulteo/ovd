<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008,2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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
		$ret = get_class($this).'(id: \''.$this->id.'\' name: \''.$this->name.'\' description: \''.$this->description.'\' published: '.$this->published.' extras: {';
		if (isset($this->extras) && is_array($this->extras)) {
			foreach ($this->extras as $key => $value) {
				$ret .= " $key => ".serialize($value)." ,";
			}
		}
		$ret .= '} )';
		return $ret;
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
	
	public function serverGroups(){
		Logger::debug('main', 'USERSGROUP::serversGroups (for id='.$this->getUniqueID().')');
		$ApplicationsGroupDB = ApplicationsGroupDB::getInstance();
		$groups = Abstract_Liaison::load('UsersGroupServersGroup', $this->getUniqueID(), NULL);
		if (! is_array($groups)) {
			Logger::error('main', 'USERSGROUP::serversGroups (for id='.$this->getUniqueID().') result query is false');
			return NULL;
		}
		
		$result = array();
		foreach ($groups as $UGAG_liaison){
			$g = Abstract_ServersGroup::load($UGAG_liaison->group);
			if (is_object($g))
				$result[$UGAG_liaison->group]= $g;
		}
		
		return $result;
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
	
	public function getPolicy() {
		Logger::debug('main', 'UsersGroup::getPolicy for '.$this->id);
		$prefs = Preferences::getInstance();
		$elements = $prefs->getElements('general', 'policy');
		
		$result = array();
		foreach($elements as $element_key => $element) {
			if ($element->content != true) {
				continue;
			}
			
			$result[$element] = true;
		}
		
		$group_settings = Abstract_Preferences::load_group($this->getUniqueID(), 'general.policy.*');
		foreach($group_settings as $setting_id => $setting_value) {
			if ($setting_value == true) {
				$result[$setting_id] = true;
			}
			else if (array_key_exists($setting_id, $result)) {
				unset($result[$setting_id]);
			}
		}
		
		return $result;
	}
}
