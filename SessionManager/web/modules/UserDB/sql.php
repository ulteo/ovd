<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
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
		$sql_conf = $prefs->get('general', 'sql');
		if (is_array($sql_conf)) {
			$this->table = $sql_conf['prefix'].'user';
		}
	}
	
	public function exists($login_) {
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT 1 FROM @1 WHERE @2=%3', $this->table, 'login', $login_);
		return ($sql2->NumRows() == 1);
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
	
	public function getUsersContains($contains_, $attributes_=array('login', 'displayname'), $limit_=0) {
		$sql2 = SQL::getInstance();
		
		$contains = str_replace('*', '%', $contains_);
		$contains = "%$contains%";
		$sep = " OR ";
		$search = '';
		foreach ($attributes_ as $attribute) {
			$search .= " ".$attribute." LIKE ".$sql2->Quote($contains);
			$search .= $sep;
		}
		if (count($attributes_) > 0) {
			$search = substr($search, 0, -1*strlen($sep)); // remove the last sep
		}
		
		$users = array();
		$sizelimit_exceeded = false;
		$count = 0;
		$limit = '';
		if ($limit_ != 0)
			$limit = 'LIMIT '.(int)($limit_+1); // SQL do not have a status sizelimit_exceeded
		
		$res = $sql2->DoQuery('SELECT * FROM '.$this->table.' WHERE '.$search.' '.$limit);
		if ($res === false) {
			Logger::error('main', 'USERDB::MYSQL::getUsersContains failed (sql query failed)');
			return NULL;
		}
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row){
			$a_user = $this->generateUserFromRow($row);
			if ($this->isOK($a_user)) {
				$users []= $a_user;
				$count++;
				if ($limit_ > 0 && $count >= $limit_) {
					$sizelimit_exceeded = next($rows) !== false; // is it the last element ?
					return array($users, $sizelimit_exceeded);
				}
			}
			else {
				if (isset($row['login']))
					Logger::info('main', 'USERDB::MYSQL::getUsersContains user \''.$row['login'].'\' not ok');
				else
					Logger::info('main', 'USERDB::MYSQL::getUsersContains user does not have login');
			}
		}
		usort($users, "user_cmp");
		return array($users, $sizelimit_exceeded);
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
			$ret = UserDB_sql::init($prefs_);
		}
		return $ret;
	}
	
	public static function prefsIsValid2($prefs_, &$log=array()) {
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			Logger::error('main', 'UserDB::sql::prefsIsValid2 no sql_conf');
			return false;
		}
		if (!isset($sql_conf['prefix'])) {
			Logger::error('main', 'UserDB::sql::prefsIsValid2 sql_conf is not valid');
			return false;
		}
		$table = $sql_conf['prefix'].'user';
		$sql2 = SQL::newInstance($sql_conf);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2', $sql_conf['database'], $table);
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
		return true;
	}

	public function getAttributesList() {
		return array('login', 'displayname', 'password');
	}
	
	public function add($user_){
		Logger::debug('main', 'UserDB::sql::add');
		if ($this->isOK($user_)){
			// user already exists ?
			$user_from_db = $this->exists($user_->getAttribute('login'));
			if ($user_from_db === true) {
				Logger::error('main', 'UserDB_sql::add user (login='.$user_->getAttribute('login').') already exists');
				popup_error(_('User already exists'));
				return false;
			}
			
			$query_keys = "";
			$query_values = "";
			$attributes = $user_->getAttributesList();
			foreach ($attributes as $key){
				if ($key == 'password')
					$value = crypt($user_->getAttribute($key), md5($user_->getAttribute('login')));
				else
					$value = $user_->getAttribute($key);

				$query_keys .= '`'.$key.'`,';
				$query_values .= '"'.mysql_escape_string($value).'",';
			}
			$query_keys = substr($query_keys, 0, -1); // del the last ,
			$query_values = substr($query_values, 0, -1); // del the last ,
			$SQL = SQL::getInstance();
			$query = 'INSERT INTO `'.$this->table.'` ( '.$query_keys.' ) VALUES ('.$query_values.' )';
			$ret = $SQL->DoQuery($query);
			$id = $SQL->InsertId();
			$user_->setAttribute('id', $id);
			return $ret;
			
		}
		else {
			if ($user_->hasAttribute('login'))
				Logger::debug('main', 'UserDB::sql::add failed (user \''.$user_->getAttribute('login').'\' not ok)');
			else
				Logger::debug('main', 'UserDB::sql::add failed (user not ok)');
			return false;
		}
	}
	
	public function remove($user_){
		Logger::debug('main', 'UserDB::sql::remove');
		if (is_object($user_) && $user_->hasAttribute('login')){
			$SQL = SQL::getInstance();
			// first we delete all liaisons 
			$liaisons = Abstract_Liaison::load('UsersGroup', $user_->getAttribute('login'), NULL);
			foreach ($liaisons as $liaison) {
				Abstract_Liaison::delete('UsersGroup', $liaison->element, $liaison->group);
			}
			
			// second we delete the user
			return $SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3', $this->table, 'login', $user_->getAttribute('login'));
		}
		else {
			Logger::debug('main', 'UserDB::sql::remove failed (user not ok)');
			return false;
		}
	}
	
	public function update($user_){
		if ($this->isOK($user_)){
			$attributes = $user_->getAttributesList();
			$query = 'UPDATE `'.$this->table.'` SET ';
			foreach ($attributes as $key){
				if ($key == 'password') {
					$user_ori = $this->import($user_->getAttribute('login'));
					if (!is_object($user_ori)) {
						Logger::error('main', 'UserDB::sql::update, change of login is not supported');
						return false;
					}
					if ($user_ori->hasAttribute($key) and ($user_ori->getAttribute($key) != $user_->getAttribute($key))) {
						$value = crypt($user_->getAttribute($key), md5($user_->getAttribute('login')));
					}
					else {
						$value = $user_->getAttribute($key);
					}
				}
				else
					$value = $user_->getAttribute($key);

				$query .=  '`'.$key.'` = \''.$value.'\' , ';
			}
			$query = substr($query, 0, -2); // del the last ,
			$query .= ' WHERE `login` = \''.$user_->getAttribute('login').'\'';
			$SQL = SQL::getInstance();
			return $SQL->DoQuery($query);
		}
		return false;
	}
	
	public function populate($override, $password = NULL) {
		$users = array(
			array('login' => 'mwilson', 'displayname' => 'Marvin Wilson',),
			array('login' => 'jdoten', 'displayname' => 'John Doten'),
			array('login' => 'rfukasawa', 'displayname' => 'Ryuuji Fukasawa',),
			array('login' => 'jkang', 'displayname' => 'Jesse Kang'),
			array('login' => 'cthompson', 'displayname' => 'Chris Thompson'),
			array('login' => 'vkoch', 'displayname' => 'Victor Koch'),
			array('login' => 'dpaul', 'displayname' => 'Derrick Paul'),
			array('login' => 'scates', 'displayname' => 'Sandra Cates'),
			array('login' => 'mwhiddon', 'displayname' => 'Marcia Whiddon'),
			array('login' => 'cholland', 'displayname' => 'Charlotte Holland'),
			array('login' => 'rbrady', 'displayname' => 'Rosemary Brady'),
			array('login' => 'jeshelman', 'displayname' => 'Joanie Eshelman'),
			array('login' => 'hcarpenter', 'displayname' => 'Harriet Carpenter'),
			array('login' => 'rdavis', 'displayname' => 'Ricardo Davis')
		);
		
		foreach ($users as $row) {
			$user = $this->generateUserFromRow($row);
			if ($password == NULL)
				$pass = $row['login'];
			else
				$pass = $password;
			$user->setAttribute('password', $pass);
			$user_on_db = $this->exists($user->getAttribute('login'));
			if ($user_on_db === false) {
				$ret = $this->add($user);
				if ($ret !== true) {
					Logger::error('main', 'UserDB::sql::populate failed to add user \''.$user->getAttribute('login').'\'');
				}
			}
			else if ($override) {
				$ret = $this->update($user);
				if ($ret !== true) {
					Logger::error('main', 'UserDB::sql::populate failed to update user \''.$user->getAttribute('login').'\'');
				}
			}
		}
		return true;
	}
	
	public static function init($prefs_) {
		Logger::debug('main', 'USERDB::sql::init');
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			Logger::error('main', 'USERDB::sql::init sql conf not valid');
			return false;
		}
		$table = $sql_conf['prefix'].'user';
		$sql2 = SQL::newInstance($sql_conf);
		
		$user_table_structure = array(
			'login' => 'varchar(100) NOT NULL',
			'displayname' => 'varchar(100) NOT NULL',
			'password' => 'varchar(50) NOT NULL');
		
		$ret = $sql2->buildTable($table, $user_table_structure, array('login'));
		
		if ( $ret === false) {
			Logger::error('main', 'USERDB::sql::init table '.$table.' fail to created');
			return false;
		}
		else {
			Logger::debug('main', 'USERDB::sql::init table '.$table.' created');
			return true;
		}
	}
	
	public static function enable() {
		return true;
	}
}
