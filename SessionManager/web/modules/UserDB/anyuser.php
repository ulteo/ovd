<?php
/**
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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

class UserDB_anyuser extends UserDB  {

	public function __construct(){
	}

	public function exists($login_) {
		return true;
	}

	public function import($login_){
		$u = new User();
		$u->setAttribute('login', $login_);
		$u->setAttribute('displayname', $login_);
		return $u;
	}

	public function imports($logins_) {
		$result = array();

		foreach($logins_ as $login) {
			array_push($result, $this->import($login));
		}

		return $result;
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

	public function getList() {
		$liaisons = Abstract_Liaison::load('UserProfile', NULL, NULL);
		if (is_array($liaisons) == false) {
			Logger::error('main', 'UserDB::anyuser::getList() problem with liaison');
			return false;
		}
		$results = array();
		foreach ($liaisons as $liaison) {
			$results[] = $this->import($liaison->element);
		}
		return $results;
	}

	public function getUsersContains($contains_, $attributes_=array('login', 'displayname'), $limit_=0, $group_=null) {
		return array($this->getList(), false);
	}

	public function authenticate($user_,$password_){
		return true;
	}

	public static function configuration() {
		return array();
	}

	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}

	public static function isDefault() {
		return false;
	}

	public function getAttributesList() {
		return array('login', 'displayname');
	}

	public function add($user_){
		return false;
	}

	public function remove($user_){
		return false;
	}

	public function modify($user_){
		return false;
	}

	public static function init($prefs_) {
		return true;
	}

	public static function enable() {
		return true;
	}

}
