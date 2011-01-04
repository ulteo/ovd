<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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

abstract class UserDB extends Module  {
	protected static $instance=NULL;
	public static function getInstance() {
		if (is_null(self::$instance)) {
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed',__FILE__,__LINE__);
			
			$mods_enable = $prefs->get('general','module_enable');
			if (!in_array('UserDB',$mods_enable)){
				die_error(_('UserDB module must be enabled'),__FILE__,__LINE__);
			}
			$mod_app_name = 'UserDB_'.$prefs->get('UserDB','enable');
			self::$instance = new $mod_app_name();
		}
		return self::$instance;
	}
	
	public function exists($login_) {
		return is_object($this->import($login_));
	}
	
	public function isOK($user_){
		$minimun_attribute = array('login','displayname');
		if (is_object($user_)){
			foreach ($minimun_attribute as $attribute){
				if ($user_->hasAttribute($attribute) == false) {
					Logger::debug('main', 'UserDB::isOK attribute \''.$attribute.'\' missing');
					return false;
				}
				else {
					$a = $user_->getAttribute($attribute);
					if (is_null($a)) {
						Logger::debug('main', 'UserDB::isOK attribute \''.$attribute.'\' null');
						return false;
					}
					if ($a == "") {
						Logger::debug('main', 'UserDB::isOK attribute \''.$attribute.'\' empty');
						return false;
					}
				}
			}
			return true;
		}
		else {
			Logger::debug('main', 'UserDB::isOK user not an object ('.gettype($user_).')');
			return false;
		}
	}
	public function getAttributesList() {
		return array();
	}
	public function populate($override, $password = NULL) {
		// populate with sample users
		return false;
	}
	
	public function getUsersContains($contains_, $attributes_=array('login', 'displayname'), $limit_=0) {
		$users = array();
		$count = 0;
		$sizelimit_exceeded = false;
		$list = $this->getList(true);
		foreach ($list as $a_user) {
			foreach ($attributes_ as $an_attribute) {
				if ($contains_ == '' or is_string(strstr($a_user->getAttribute($an_attribute), $contains_))) {
					$users []= $a_user;
					$count++;
					if ($limit_ > 0 && $count >= $limit_) {
						$sizelimit_exceeded = next($list) !== false; // is it the last element ?
						return array($users, $sizelimit_exceeded);
					}
					break;
				}
			}
		}
		
		return array($users, $sizelimit_exceeded);
	}
}
