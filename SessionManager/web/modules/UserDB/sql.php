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
class UserDB_sql extends UserDB  {
	protected $table;
	protected $cache_userlist=NULL;
	public function __construct(){
		$prefs = Preferences::getInstance();
		$mysql_conf = $prefs->get('general', 'mysql');
		if (is_array($mysql_conf)) {
			$this->table = $mysql_conf['prefix'].'user';
		}
	}
	
	public function import($login_){
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT * FROM @1 WHERE @2=%3', $this->table, 'login', $login_);
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
	
	public function getList($sort_=false) {
		Logger::debug('main','USERDB::MYSQL::getList');
		if (!is_array($this->cache_userlist)) {
			$users = $this->getList_nocache();
			$this->cache_userlist = $users;
		}
		else {
			$users = $this->cache_userlist;
		}
		// do we need to sort alphabetically ?
		if ($sort_ && is_array($users)) {
			usort($users, "user_cmp");
		}
		return $users;
	}
	
	public function getList_nocache(){
		Logger::debug('main','USERDB::MYSQL::getList_nocache');
		$result = array();
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT * FROM @1', $this->table);
		if ($res !== false){
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$u = $this->generateUserFromRow($row);
				if ($this->isOK($u))
					$result []= $u;
				else {
					if (isset($row['login']))
						Logger::info('main', 'USERDB::MYSQL::getList_nocache user \''.$row['login'].'\' not ok');
					else
						Logger::info('main', 'USERDB::MYSQL::getList_nocache user does not have login');
				}
			}
		}
		else {
			Logger::error('main', 'USERDB::MYSQL::getList_nocache failed (sql query failed)');
			// not the right argument
			return NULL;
		}
		return $result;
	}
	
	public function authenticate($user_,$password_){
		if (!($user_->hasAttribute('login')))
			return false;

		$login = $user_->getAttribute('login');
		$hash = crypt($password_, md5($login));
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
	
	public static function configuration() {
		return array();
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		// dirty
		$ret = self::prefsIsValid2($prefs_, $log);
		if ( $ret != true) {
			$ret = admin_UserDB_sql::init($prefs_);
		}
		return $ret;
	}
	
	public static function prefsIsValid2($prefs_, &$log=array()) {
		$mysql_conf = $prefs_->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('main', 'UserDB::sql::prefsIsValid2 no mysql_conf');
			return false;
		}
		if (!isset($mysql_conf['prefix'])) {
			Logger::error('main', 'UserDB::sql::prefsIsValid2 mysql_conf is not valid');
			return false;
		}
		$table = $mysql_conf['prefix'].'user';
		$sql2 = SQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database'], $mysql_conf['prefix']);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2', $mysql_conf['database'], $table);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows($ret);
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main', 'USERDB::MYSQL::prefsIsValid table \''.$table.'\' does not exist');
				return false;
			}
		}
		else {
			Logger::error('main', 'USERDB::MYSQL::prefsIsValid table \''.$table.'\' does not exist(2)');
			return false;
		}
	}
	
	public static function prettyName() {
		return _('MySQL');
	}
	
	public static function isDefault() {
		return false;
	}

	public function getAttributesList() {
		return array('uid', 'login', 'displayname', 'homedir', 'fileserver', 'fileserver_uid', 'password');
	}
}
