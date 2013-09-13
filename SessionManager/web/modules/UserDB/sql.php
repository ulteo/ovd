<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
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
	const table = 'user';
	protected $cache_userlist=NULL;
	public function __construct(){
	}
	
	public function exists($login_) {
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT 1 FROM #1 WHERE @2=%3', self::table, 'login', $login_);
		return ($sql2->NumRows() == 1);
	}
	
	public function import($login_){
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT * FROM #1 WHERE @2=%3', self::table, 'login', $login_);
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
	
	public function imports($logins_) {
		if (count($logins_) == 0) {
			return array();
		}
		
		$sql2 = SQL::getInstance();
		
		$logins2 = array();
		foreach($logins_ as $login) {
			array_push($logins2, $sql2->Quote($login));
		}
		
		$request = 'SELECT * FROM #1 WHERE @2 IN ('.implode(', ',$logins2).')';
		$res = $sql2->DoQuery($request, self::table, 'login');
		if ($res === false){
			Logger::error('main', 'USERDB::MYSQL::getList_nocache failed (sql query failed)');
			// not the right argument
			return NULL;
		}
		
		$result = array();
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row){
			$u = $this->generateUserFromRow($row);
			if ($this->isOK($u))
				$result []= $u;
			else {
				if (isset($row['login']))
					Logger::info('main', 'USERDB::MYSQL::imports user \''.$row['login'].'\' not ok');
				else
					Logger::info('main', 'USERDB::MYSQL::imports user does not have login');
			}
		}
		
		return $result;
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
	
	public function getList() {
		Logger::debug('main','USERDB::MYSQL::getList');
		if (!is_array($this->cache_userlist)) {
			$users = $this->getList_nocache();
			$this->cache_userlist = $users;
		}
		else {
			$users = $this->cache_userlist;
		}
		
		return $users;
	}
	
	public function getList_nocache(){
		Logger::debug('main','USERDB::MYSQL::getList_nocache');
		$result = array();
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT * FROM #1', self::table);
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
	
	public function getUsersContains($contains_, $attributes_=array('login', 'displayname'), $limit_=0, $group_=null) {
		$sql2 = SQL::getInstance();
		
		$search = array();
		if (strlen($contains_) > 0) {
			$contains = str_replace('*', '%', $contains_);
			$contains = preg_replace('/\%\%+/', '%', '%'.$contains.'%');
			
			$rules_contain = array();
			foreach ($attributes_ as $attribute) {
				if (! in_array($attribute, array('login', 'displayname'))) {
					continue;
				}
				
				array_push($rules_contain, $sql2->QuoteField($attribute)." LIKE ".$sql2->Quote($contains));
			}
			
			if (count($rules_contain) > 0) {
				array_push($search, '('.implode(' OR ', $rules_contain).') ');
			}
		}
		
		if (! is_null($group_)) {
			$userGroupDB = UserGroupDB::getInstance('static');
			$group_filter_res = $userGroupDB->get_filter_groups_member($group_);
			if (! array_key_exists('users', $group_filter_res) || !is_array($group_filter_res['users']) || count($group_filter_res['users']) == 0) {
				return array(array(), false);
			}
			
			$users_login_sql = array();
			foreach($group_filter_res['users'] as $login) {
				array_push($users_login_sql, $sql2->Quote($login));
			}
			
			array_push($search, $sql2->QuoteField('login') .'IN ('.implode(',', $users_login_sql).')');
		}
		
		$users = array();
		$sizelimit_exceeded = false;
		
		$request = 'SELECT * FROM #1';
		if (count($search) > 0) {
			$request.= ' WHERE '.implode(' AND ', $search);
		}
		
		$count = 0;
		if ($limit_ != 0) {
			$request.= ' LIMIT '.(int)($limit_+1); // SQL do not have a status sizelimit_exceeded
		}
		
		$res = $sql2->DoQuery($request, self::table);
		if ($res === false) {
			Logger::error('main', 'USERDB::MYSQL::getUsersContains failed (sql query failed)');
			return NULL;
		}
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row){
			if ($limit_ > 0 && $count >= $limit_) {
					$sizelimit_exceeded = true;
					break;
			}
			
			$a_user = $this->generateUserFromRow($row);
			if ($this->isOK($a_user)) {
				$users []= $a_user;
				$count++;
			}
			else {
				if (isset($row['login']))
					Logger::info('main', 'USERDB::MYSQL::getUsersContains user \''.$row['login'].'\' not ok');
				else
					Logger::info('main', 'USERDB::MYSQL::getUsersContains user does not have login');
			}
		}
		
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
		
		$sql2 = SQL::newInstance($sql_conf);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2', $sql_conf['database'], $sql2->prefix.self::table);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows($ret);
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main', 'USERDB::MYSQL::prefsIsValid table \''.self::table.'\' does not exist');
				return false;
			}
		}
		else {
			Logger::error('main', 'USERDB::MYSQL::prefsIsValid table \''.self::table.'\' does not exist(2)');
			return false;
		}
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
				ErrorManager::report('user (login='.$user_->getAttribute('login').') already exists');
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
			$query = 'INSERT INTO #1 ( '.$query_keys.' ) VALUES ('.$query_values.' )';
			$ret = $SQL->DoQuery($query, self::table);
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
			return $SQL->DoQuery('DELETE FROM #1 WHERE @2 = %3', self::table, 'login', $user_->getAttribute('login'));
		}
		else {
			Logger::debug('main', 'UserDB::sql::remove failed (user not ok)');
			return false;
		}
	}
	
	public function update($user_){
		if ($this->isOK($user_)){
			$attributes = $user_->getAttributesList();
			$query = 'UPDATE #1 SET ';
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
			return $SQL->DoQuery($query, self::table);
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
		
		$sql2 = SQL::newInstance($sql_conf);
		
		$user_table_structure = array(
			'login' => 'varchar(100) NOT NULL',
			'displayname' => 'varchar(100) NOT NULL',
			'password' => 'varchar(50) NOT NULL');
		
		$ret = $sql2->buildTable(self::table, $user_table_structure, array('login'));
		
		if ( $ret === false) {
			Logger::error('main', 'USERDB::sql::init table '.self::table.' fail to created');
			return false;
		}
		else {
			Logger::debug('main', 'USERDB::sql::init table '.self::table.' created');
			return true;
		}
	}
	
	public static function enable() {
		return true;
	}
}
