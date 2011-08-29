<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008,2011
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
class UserDB_sql_external extends UserDB {
	
	public function import($login_){
		Logger::debug('main','USERDB::MYSQL_external::import('.$login_.')');
		$users = array();
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$config = $prefs->get('UserDB','sql_external');
		
		$sql2 = new SQL($config);
		$status = $sql2->CheckLink(false);
		if ( $status == false) {
			Logger::error('main', 'USERDB::MYSQL_external::getList link to mysql external failed');
			return array();
		}
		if ($config['match'] == array()) {
			Logger::error('main', 'USERDB::MYSQL_external::getList not match');
			return array();
		}
		$fields = '`'.implode('`,`', array_values($config['match'])).'`';
		$match2 = array();
		// key->value are unique (because of dictionary) BUT in this case is also value->key
		foreach ($config['match'] as $key => $value) {
			$match2[$value] = $key;
		}
		
		$res = $sql2->DoQuery('SELECT '.$fields.' FROM @1 WHERE @2=%3', $config['table'], $config['match']['login'], $login_);
		if ( $sql2->NumRows() == 0)
			return NULL;
		$row = $sql2->FetchResult($res);
		$u = new User();
		foreach ($config['match']  as $key => $value) {
			$u->setAttribute($key,$row[$value]);
		}
		if ($this->isOK($u)) {
			return $u;
		}
		else {
			Logger::info('main', 'USERDB::MYSQL_external::import failed to import \''.$login_.'\'');
			return NULL;
		}
	}
	
	public function isWriteable(){
		return false;
	}
	
	public function canShowList(){
		return true;
	}
	
	public function needPassword(){
		return true;
	}

	public function getList($sort_=false){
		Logger::debug('main','USERDB::MYSQL_external::getList');
		$users = array();
		
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$config = $prefs->get('UserDB','sql_external');
		
		$sql2 = new SQL($config);
		$status = $sql2->CheckLink(false);
		if ( $status == false) {
			Logger::error('main', 'USERDB::MYSQL_external::getList link to mysql external failed');
			return array();
		}
		if ($config['match'] == array()) {
			Logger::error('main', 'USERDB::MYSQL_external::getList not match');
			return array();
		}
		$fields = '`'.implode('`,`', array_values($config['match'])).'`';
		$match2 = array();
		// key->value are unique (because of dictionary) BUT in this case is also value->key
		foreach ($config['match'] as $key => $value) {
			$match2[$value] = $key;
		}
		
		$res = $sql2->DoQuery('SELECT '.$fields.' FROM @1', $config['table']);
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row) {
			$u = new User();
			
			foreach ($config['match']  as $key => $value) {
				$u->setAttribute($key,$row[$value]);
			}
			if ($this->isOK($u))
				$users[] = $u;
		}
		//do we need to sort alphabetically ?
		if ($sort_) {
			usort($users, "user_cmp");
		}
		return $users;
	}
	
	public function isOK($user_){
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$config = $prefs->get('UserDB','sql_external');
		$minimun_attribute = array_keys($config['match']);
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
		if (! is_object($user_)) {
			Logger::error('main', 'UserDB::sql_external::authenticate user_ paramater is not an object');
			return false;
		}
		if (!($user_->hasAttribute('login'))) {
			Logger::error('main', 'UserDB::sql_external::authenticate user has not attribute login');
			return false;
		}
		if (!($user_->hasAttribute('password'))) {
			Logger::error('main', 'UserDB::sql_external::authenticate user has not attribute password');
			return false;
		}
		
		$login = $user_->getAttribute('login');
		$password_db = $user_->getAttribute('password');
				
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$config = $prefs->get('UserDB','sql_external');;
		if (! is_array($config)) {
			Logger::error('main', 'UserDB::sql_external::authenticate fail to get preferences');
			return false;
		}
		if (! array_key_exists('hash_method' ,$config)) {
			Logger::error('main', 'UserDB::sql_external::authenticate no hash method');
			return false;
		}
		
		$method = $config['hash_method'];
		if ($method == 'plain') {
			return ($password_ == $password_db);
		}
		else if ($method == 'crypt') {
			$hash = crypt($password_, $login);
			return ($password_db == $hash);
		}
		else if ($method == 'md5') {
			return (md5($password_) == $password_db);
		}
		else if ($method == 'shadow') {
			return shadow($password_db, $password_);
		}
		else {
			Logger::error('main', 'UserDB::sql_external::authenticate hash method \''.$method.'\' does not support');
			return false;
		}
		return false;
	}
	
	public static function configuration() {
		$ret = array();
		$c = new ConfigElement_input('host', _('Server host address'), _('The address of your MySQL server.'), _('The address of your MySQL server.'), '');
		$ret []= $c;
		$c = new ConfigElement_input('user', _('User login'), _('The user login that must be used to access the database (to list users accounts).'), _('The user login that must be used to access the database (to list users accounts).'), '');
		$ret []= $c;
		$c = new ConfigElement_password('password', _('User password'), _('The user password that must be used to access the database (to list users accounts).'), _('The user password that must be used to access the database (to list users accounts).'), '');
		$ret []= $c;
		$c = new ConfigElement_input('database', _('Database name'), _('The name of the database.'), _('The name of the database.'), '');
		$ret []= $c;
		$c = new ConfigElement_input('table', _('Table of users'), _('Table of users'), _('Table of users'), '');
		$ret []= $c;
		$c = new ConfigElement_dictionary('match',_('Matching'), _('Matching'), _('Matching'), array('login' => 'login', 'displayname' => 'displayname'));
		$ret []= $c;
		$c = new ConfigElement_select('hash_method',_('Hash method'), _('Hash method'), _('Hash method'), 'plain');
		$c->setContentAvailable(array('md5' => _('MD5'), 'crypt' => _('Crypt'), 'shadow' => _('Shadow'), 'plain' => _('Plain')));
		$ret []= $c;
		return $ret;
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		$config = $prefs_->get('UserDB','sql_external');
		$sql2 = new SQL($config);
		$status = $sql2->CheckLink(false);
		return $status;
	}
	
	public static function prettyName() {
		return _('MySQL external');
	}
	
	public static function isDefault() {
		return false;
	}

	public function getAttributesList() {
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$config = $prefs->get('UserDB','sql_external');
		if (!isset($config['match']))
			return array();
		return array_keys($config['match']);
	}
	
	public function add($user_){
		return false;
	}
	
	public function remove($user_){
		return false;
	}
	
	public function update($user_){
		return false;
	}
	
	public static function init($prefs_) {
		return true;
	}
	
	public static function enable() {
		return true;
	}
}
