<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
class UserDB_fake extends UserDB {
	public function __construct(){
		$this->users = array();
		$this->construct_addUser('mwilson', 'Marvin Wilson', 2001);
		$this->construct_addUser('jdoten', 'John Doten', 2002);
		$this->construct_addUser('rfukasawa', 'Ryuuji Fukasawa', 2003);
		$this->construct_addUser('jkang', 'Jesse Kang', 2004);
		$this->construct_addUser('cthompson', 'Chris Thompson', 2005);
		$this->construct_addUser('vkoch', 'Victor Koch', 2006);
		$this->construct_addUser('dpaul', 'Derrick Paul', 2007);
		$this->construct_addUser('scates', 'Sandra Cates', 2008);
		$this->construct_addUser('mwhiddon', 'Marcia Whiddon', 2009);
		$this->construct_addUser('cholland', 'Charlotte Holland', 2010);
		$this->construct_addUser('rbrady', 'Rosemary Brady', 2011);
		$this->construct_addUser('jeshelman', 'Joanie Eshelman', 2012);
		$this->construct_addUser('hcarpenter', 'Harriet Carpenter', 2013);
		$this->construct_addUser('rdavis', 'Ricardo Davis', 2014);
	}
	
	public function import($login_){
		Logger::debug('main','UserDB::fake::import('.$login_.')');
		if (isset($this->users[$login_])) {
			if ($this->isOK($this->users[$login_]) == true) {
				return $this->users[$login_];
			}
			else
				return NULL;
		}
		else
			return NULL;
	}
	

	public function getList($sort_=false) {
		Logger::debug('main','UserDB::fake::getList');
		$users = $this->users;
		foreach ($users as $k => $u) {
			if ($this->isOK($u) == false) {
				unset($users[$k]);
			}
		}
		// do we need to sort alphabetically ?
		if ($sort_) {
			usort($users, "user_cmp");
		}
		return $users;
	}
	
	public function isOK($user_){
		$minimun_attribute = array_unique(array_merge(array('login','displayname','uid'),get_needed_attributes_user_from_module_plugin()));
		if (is_object($user_)){
			foreach ($minimun_attribute as $attribute){
				if ($user_->hasAttribute($attribute) == false)
					return false;
				else {
					$a = $user_->getAttribute($attribute);
					if ( is_null($a) || $a == "")
						return false;
				}
			}
			return true;
		}
		else
			return false;
	}
	
	public function isWriteable(){
		return false;
	}
	
	public function canShowList(){
		return true;
	}
	
	public function needPassword(){
		return false;
	}
	
	public function authenticate($user_,$password){
		return true;
	}
	
	// 	public function showListOnLog(){
	// 		// get global config['showListOnLog']
	// 	}
	
	private function construct_addUser($login_,$displayname_,$uid_){
		unset($this->users[$login_]);
		$u = new User();
		$u->setAttribute('login',$login_);
		$u->setAttribute('displayname',$displayname_);
		$u->setAttribute('uid',$uid_);
		$u->setAttribute('fileserver_uid',$uid_);
		$this->users[$login_] = $u;
	}
	
	public static function configuration() {
		return array();
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}
	
	public static function prettyName() {
		return _('fake');
	}
	
	public static function isDefault() {
		return true;
	}
}
