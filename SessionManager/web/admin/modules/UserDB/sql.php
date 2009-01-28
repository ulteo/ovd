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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class admin_UserDB_sql extends UserDB_sql {
// 	public function __construct(){
// 	}
	public function add($user_){
		Logger::debug('UserDB::sql::add');
		if ($this->isOK($user_)){
			$query_keys = "";
			$query_values = "";
			$attributes = $user_->getAttributesList();
			foreach ($attributes as $key){
				if ($key == 'password')
					$value = crypt($user_->getAttribute($key), $user_->getAttribute('login'));
				else
					$value = $user_->getAttribute($key);

				$query_keys .= '`'.$key.'`,';
				$query_values .= '"'.mysql_escape_string($value).'",';
			}
			$query_keys = substr($query_keys, 0, -1); // del the last ,
			$query_values = substr($query_values, 0, -1); // del the last ,
			$SQL = MySQL::getInstance();
			$query = 'INSERT INTO `'.USER_TABLE.'` ( '.$query_keys.' ) VALUES ('.$query_values.' )';
			$ret = $SQL->DoQuery($query);
			$id = $SQL->InsertId();
			$user_->setAttribute('id', $id);
			return $ret;
			
		}
		else {
			if ($user_->hasAttribute('login'))
				Logger::debug('UserDB::sql::add failed (user \''.$user_->getAttribute('login').'\' not ok)');
			else
				Logger::debug('UserDB::sql::add failed (user not ok)');
			return false;
		}
	}
	
	public function remove($user_){
		Logger::debug('UserDB::sql::remove');
		if (is_object($user_) && $user_->hasAttribute('login')){
			$SQL = MySQL::getInstance();
			// first we delete all liaisons 
			$liaisons = Abstract_Liaison::load('UsersGroup', $user_->getAttribute('login'), NULL);
			foreach ($liaisons as $liaison) {
				Abstract_Liaison::delete('UsersGroup', $liaison->element, $liaison->group);
			}
			
			// second we delete the user
			return $SQL->DoQuery('DELETE FROM @1 WHERE @2 = %3', USER_TABLE, 'login', $user_->getAttribute('login'));
		}
		else {
			Logger::debug('UserDB::sql::remove failed (user not ok)');
			return false;
		}
	}
	
	public function update($user_){
		if ($this->isOK($user_)){
			$attributes = $user_->getAttributesList();
			$query = 'UPDATE `'.USER_TABLE.'` SET ';
			foreach ($attributes as $key){
				if ($key == 'password')
					$value = crypt($user_->getAttribute($key), $user_->getAttribute('login'));
				else
					$value = $user_->getAttribute($key);

				$query .=  '`'.$key.'` = \''.$value.'\' , ';
			}
			$query = substr($query, 0, -2); // del the last ,
			$query .= ' WHERE `login` = \''.$user_->getAttribute('login').'\'';
			$SQL = MySQL::getInstance();
			return $SQL->DoQuery($query);
		}
		return false;
	}
	
	public function configuration(){
		$c = new config_element('mysql','host','host_des(mysql)','un host',NULL,1);
		$this->add('general',$c);
		$c = new config_element('mysql','port','port_des(mysql)','un port',NULL,0);
		$this->add('general',$c);
		$c = new config_element('mysql','login','login_des(mysql)','un login',NULL,1);
		$this->add('general',$c);
		$c = new config_element('mysql','pass','pass_des(mysql)','un password',NULL,1);
		$this->add('general',$c);
		$p = new Preferences_admin();
	}
	
	public static function init($prefs_) {
		Logger::debug('admin','USERDB::sql::init');
		$mysql_conf = $prefs_->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('admin','USERDB::sql::init mysql conf not valid');
			return false;
		}
		@define('USER_TABLE', $mysql_conf['prefix'].'user');
		$sql2 = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);
		
		// TODO : use get_needed_attributes_user_from_module_plugin to get all right fields
		$ret = $sql2->DoQuery(
			'CREATE TABLE IF NOT EXISTS @1 (
			@2 int(8) NOT NULL,
			@3 varchar(50) NOT NULL,
			@4 varchar(50) NOT NULL,
			@5 varchar(100) NOT NULL,
			@6 varchar(100) NOT NULL,
			@7 int(8) NOT NULL,
			@8 varchar(50) NOT NULL,
			PRIMARY KEY  (@3)
		)',USER_TABLE,'uid','login','displayname','homedir','fileserver','fileserver_uid','password');
		if ( $ret === false) {
			Logger::error('admin','USERDB::sql::init table '.USER_TABLE.' fail to created');
			return false;
		}
		else {
			Logger::debug('admin','USERDB::sql::init table '.USER_TABLE.' created');
			return true;
		}
	}
	
	public static function enable() {
		return true;
	}
	
}
