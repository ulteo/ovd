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
class UserDB_sql {
	public function __construct(){
		$prefs = Preferences::getInstance();
		$mysql_conf = $prefs->get('general', 'mysql');
		if (is_array($mysql_conf)) {
			@define('USER_TABLE', $mysql_conf['prefix'].'user');
		}
	}
	
	public function import($login_){
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT * FROM @1 WHERE @2=%3',USER_TABLE, 'login', $login_);
		if ($res !== false){
			if ($sql2->NumRows($res) == 1){
				$row = $sql2->FetchResult($res);
				$u = $this->generateUserFromRow($row);
				if ($this->isOK($u))
					return $u;
				else
					Logger::error('main', 'USERDB::MYSQL::import \''.$login_.'\' failed (user not ok)');
			}
			else
				Logger::error('main', 'USERDB::MYSQL::import \''.$login_.'\' failed (user not found)');
		}
		else
			Logger::error('main', 'USERDB::MYSQL::import \''.$login_.'\' failed (sql query failed)');
		return NULL;
	}
	
	public function isWriteable(){
		return true;
	}
	
	public function canShowList(){
		return true;
	}
	
	public function needPassword(){
		return true;
	}

	public function getList(){
		Logger::debug('main','ApplicationDB_sql::getList');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT * FROM @1', USER_TABLE);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$u = $this->generateUserFromRow($row);
				if ($this->isOK($u))
					$result []= $u;
				else {
					if (isset($row['login']))
						Logger::info('main', 'USERDB::MYSQL::getList user \''.$row['login'].'\' not ok');
					else
						Logger::info('main', 'USERDB::MYSQL::getList user does not have login');
				}
			}
			
			return $result;
		}
		else {
			Logger::error('main', 'USERDB::MYSQL::getList failed (sql query failed)');
			// not the right argument
			return NULL;
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
	
	public function authenticate($user_,$password_){
		if (!($user_->hasAttribute('login')))
			return false;

		$login = $user_->getAttribute('login');
		$hash = crypt($password_, $login);
		// TODO very very ugly
		if ($user_->hasAttribute('password'))
			return ($user_->getAttribute('password') == $hash);
		else {
			Logger::error('main', 'USERDB::MYSQL::authenticate failed for \''.$user_->getAttribute('login').'\'');
			return false;
		}
	}
	
	private function generateUserFromRow($row){
		$u = new User();
		foreach ($row as $key => $value){
			$u->setAttribute($key,$value);
		}
		return $u;
	}
	
	public function configuration(){
		return array();
	}
	
	public function prefsIsValid($prefs_) {
		if (!defined('USER_TABLE'))
			return false;
		$mysql_conf = $prefs_->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			
			return false;
		}
		$sql2 = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2',$mysql_conf['database'],USER_TABLE);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows($ret);
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main','USERDB::MYSQL::prefsIsValid table \''.USER_TABLE.'\' not exists');
				return false;
			}
		}
		else {
			Logger::error('main','USERDB::MYSQL::prefsIsValid table \''.USER_TABLE.'\' not exists(2)');
			return false;
		}
	}
	
	public static function prettyName() {
		return _('MySQL');
	}
}
