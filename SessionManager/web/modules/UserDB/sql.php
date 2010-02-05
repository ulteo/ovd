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
		return false;
	}

	public function getAttributesList() {
		return array('uid', 'login', 'displayname', 'homedir', 'fileserver', 'fileserver_uid', 'password');
	}
	
	public function add($user_){
		Logger::debug('main', 'UserDB::sql::add');
		if ($this->isOK($user_)){
			// user already exists ?
			$user_from_db = $this->import($user_->getAttribute('login'));
			if (is_object($user_from_db)) {
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
				if ($key == 'password')
					$value = crypt($user_->getAttribute($key), md5($user_->getAttribute('login')));
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
	
	public function populate() {
		$users = array(
			array('login' => 'mwilson', 'displayname' => 'Marvin Wilson', 'uid' => 2001),
			array('login' => 'jdoten', 'displayname' => 'John Doten', 'uid' => 2002),
			array('login' => 'rfukasawa', 'displayname' => 'Ryuuji Fukasawa', 'uid' => 2003),
			array('login' => 'jkang', 'displayname' => 'Jesse Kang', 'uid' => 2004),
			array('login' => 'cthompson', 'displayname' => 'Chris Thompson', 'uid' => 2005),
			array('login' => 'vkoch', 'displayname' => 'Victor Koch', 'uid' => 2006),
			array('login' => 'dpaul', 'displayname' => 'Derrick Paul', 'uid' => 2007),
			array('login' => 'scates', 'displayname' => 'Sandra Cates', 'uid' => 2008),
			array('login' => 'mwhiddon', 'displayname' => 'Marcia Whiddon', 'uid' => 2009),
			array('login' => 'cholland', 'displayname' => 'Charlotte Holland', 'uid' => 2010),
			array('login' => 'rbrady', 'displayname' => 'Rosemary Brady', 'uid' => 2011),
			array('login' => 'jeshelman', 'displayname' => 'Joanie Eshelman', 'uid' => 2012),
			array('login' => 'hcarpenter', 'displayname' => 'Harriet Carpenter', 'uid' => 2013),
			array('login' => 'rdavis', 'displayname' => 'Ricardo Davis', 'uid' => 2014)
		);
		
		foreach ($users as $row) {
			$user = $this->generateUserFromRow($row);
			$user->setAttribute('password', 'test');
			$user_on_db = $this->import($user->getAttribute('login'));
			if (!is_object($user_on_db)) {
				$ret = $this->add($user);
				if ($ret !== true) {
					Logger::error('main', 'UserDB::sql::populate failed to add user \''.$user->getAttribute('login').'\'');
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
		
		// TODO : use get_needed_attributes_user_from_module_plugin to get all right fields
		$user_table_structure = array(
			'uid' => 'int(8) NOT NULL',
			'login' => 'varchar(100) NOT NULL',
			'displayname' => 'varchar(100) NOT NULL',
			'homedir' => 'varchar(200) NOT NULL',
			'fileserver' => 'varchar(250) NOT NULL',
			'fileserver_uid' => 'int(8) NOT NULL',
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
