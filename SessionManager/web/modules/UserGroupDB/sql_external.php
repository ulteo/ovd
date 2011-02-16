<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
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
class UserGroupDB_sql_external extends UserGroupDB {
	public function __construct(){
		$prefs = Preferences::getInstance();
		if ($prefs) {
			$this->config = $prefs->get('UserGroupDB', 'sql_external');
		}
		else {
			die_error('USERGROUPDB::MYSQL_external::construct get Prefs failed',__FILE__,__LINE__);
		}
	}
	
	public function import($id_){
		Logger::debug('main', "USERGROUPDB::MYSQL_external::fromDB (id = $id_)");		
		$groups = array();
		
		$sql2 = new MySQL($this->config['host'], $this->config['login'], $this->config['password'], $this->config['database']);
		$status = $sql2->CheckLink(false);
		if ( $status == false) {
			Logger::error('main', 'USERGROUPDB::MYSQL_external::getList link to mysql external failed');
			return array();
		}
		Logger::debug('main', 'USERGROUPDB::MYSQL_external::getList link to mysql success');
		
		if (!array_key_exists('id', $this->config['match']) || !array_key_exists('name', $this->config['match']) || !array_key_exists('description', $this->config['match'])) {
			Logger::error('main', 'USERGROUPDB::MYSQL_external::getList match key failed');
			return array();
		}
		
		$res = $sql2->DoQuery('SELECT @2,@3,@4 FROM @1 WHERE @2=%5', $this->config['table'], $this->config['match']['id'], $this->config['match']['name'], $this->config['match']['description'], $id_);
		$row = $sql2->FetchResult($res);
		$ug = new UsersGroup($row[$this->config['match']['id']], $row[$this->config['match']['name']], $row[$this->config['match']['description']], true);
		if ($this->isOK($ug))
			return $ug;
		else {
			Logger::info('main', 'USERGROUPDB::MYSQL_external::getList group \''.$row[$this->config['match']['id']].'\' not ok');
			return NULL;
		}
	}
	
	public function isWriteable(){
		return false;
	}
	
	public function canShowList(){
		return true;
	}
	
	public function getList($sort_=false) {
		Logger::debug('main','USERGROUPDB::MYSQL_external::getList');
		$groups = array();
		
		$sql2 = new MySQL($this->config['host'], $this->config['login'], $this->config['password'], $this->config['database']);
		$status = $sql2->CheckLink(false);
		if ( $status == false) {
			Logger::error('main', 'USERGROUPDB::MYSQL_external::getList link to mysql external failed');
			return array();
		}
		Logger::debug('main', 'USERGROUPDB::MYSQL_external::getList link to mysql success');
		
		if (!array_key_exists('id', $this->config['match']) || !array_key_exists('name', $this->config['match']) || !array_key_exists('description', $this->config['match'])) {
			Logger::error('main', 'USERGROUPDB::MYSQL_external::getList match key failed');
			return array();
		}
		
		$res = $sql2->DoQuery('SELECT @2,@3,@4 FROM @1', $this->config['table'], $this->config['match']['id'], $this->config['match']['name'], $this->config['match']['description']);
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row) {
			$ug = new UsersGroup($row[$this->config['match']['id']], $row[$this->config['match']['name']], $row[$this->config['match']['description']], true);
			if ($this->isOK($ug))
				$groups[$ug->id]= $ug;
			else {
				Logger::info('main', 'USERGROUPDB::MYSQL_external::getList group \''.$row[$this->config['match']['id']].'\' not ok');
			}
		}
		if ($sort_) {
			usort($groups, "usergroup_cmp");
		}
		
		return $groups;
		
	}
	
	public static function configuration() {
		$ret = array();
		$c = new ConfigElement_input('host', _('Server host address'), _('The address of your MySQL server.'), _('The address of your MySQL server.'), '');
		$ret []= $c;
		$c = new ConfigElement_input('login', _('User login'), _('The user login that must be used to access the database (to list users groups).'), _('The user login that must be used to access the database (to list users groups).'), '');
		$ret []= $c;
		$c = new ConfigElement_password('password', _('User password'), _('The user password that must be used to access the database (to list users groups).'), _('The user password that must be used to access the database (to list users groups).'), '');
		$ret []= $c;
		$c = new ConfigElement_input('database', _('Database name'), _('The name of the database.'), _('The name of the database.'), '');
		$ret []= $c;
		$c = new ConfigElement_input('table', _('Database users groups table name'), _('The name of the database table which contains the users groups'), _('The name of the database table which contains the users groups'), '');
		$ret []= $c;
		$c = new ConfigElement_inputlist('match', _('Matching'), _('Matching'), _('Matching'), array('id' => 'id', 'name' => 'name', 'description' => 'description'));
		$ret []= $c;
		return $ret;
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		// dirty
		$ret = self::prefsIsValid2($prefs_, $log);
		if ( $ret != true) {
			$ret = UserGroupDB_sql::init($prefs_);
		}
		return $ret;
	}
	
	public static function prefsIsValid2($prefs_, &$log=array()) {
		$config = $prefs_->get('UserGroupDB', 'sql_external');
		$sql2 = new MySQL($config['host'], $config['login'], $config['password'], $config['database']);
		$status = $sql2->CheckLink(false);
		return $status;
	}
	
	public static function prettyName() {
		return _('MySQL external');
	}
	
	public static function isDefault() {
		return false;
	}
	
	public static function liaisonType() {
		return 'sql';
	}
	
	public function add($usergroup_){
		return false;
	}
	
	public function remove($usergroup_){
		return false;
	}
	
	public function update($usergroup_){
		return false;
	}
	
	public static function init($prefs_) {
		return true;
	}
	
	public static function enable() {
		return true;
	}
}
