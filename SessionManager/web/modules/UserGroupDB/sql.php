<?php
/**
 * Copyright (C) 2009-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013, 2014
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
class UserGroupDB_sql {
	const table = 'usergroup';
	protected $cache;
	public function __construct(){
		$this->cache = array();
	}
	public function __toString() {
		return get_class($this).'(table \''.self::table.'\')';
	}
		
	public function isWriteable(){
		return true;
	}
	
	public function canShowList(){
		return true;
	}
	
	public function isOK($usergroup_) {
		if (is_object($usergroup_)) {
			if ((!isset($usergroup_->id)) || (!isset($usergroup_->name)) || ($usergroup_->name == '') || (!isset($usergroup_->published)))
				return false;
			else
				return true;
		}
		else
			return false;
	}
	
	public function import($id_) {
		if (array_key_exists($id_, $this->cache)) {
			return $this->cache[$id_];
		}
		else {
			$ug = $this->import_nocache($id_);
			if (is_object($ug)) {
				$this->cache[$ug->id] = $ug;
			}
			return $ug;
		}
	}
	
	public function import_nocache($id_) {
		Logger::debug('main', "USERGROUPDB::sql::import_noche (id = $id_)");
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM #5 WHERE @1 = %6', 'id', 'name', 'description', 'published', self::table, $id_);
			
		if ($sql2->NumRows($res) == 1) {
			$row = $sql2->FetchResult($res);
			$ug = new UsersGroup($row['id'], $row['name'], $row['description'], (bool)$row['published']);
			if ($this->isOK($ug))
				return $ug;
		}
		else {
			Logger::error('main' ,"USERGROUPDB::sql::import import_nocache group '$id_' failed");
			return NULL;
		}
	}
	
	public function imports($ids_) {
		Logger::debug('main', 'USERGROUPDB::sql::imports (['.implode(', ', $ids_).'])');
		if (count($ids_) == 0) {
			return array();
		}
		
		$sql2 = SQL::getInstance();
		
		$result = array();
		$ids_filter = array();
		foreach($ids_ as $id) {
			if (array_key_exists($id, $this->cache)) {
				$g = $this->cache[$id];
				if (! $this->isOK($g)) {
					continue;
				}
				
				$result[$g->getUniqueID()] = $g;
			}
			else {
				array_push($ids_filter, $sql2->Quote($id));
			}
		}
		
		if (count($ids_filter) == 0) {
			return $result;
		}
		
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM #5 WHERE @1 IN ('.implode(',', $ids_filter).')', 'id', 'name', 'description', 'published', self::table);
		if ($res === false) {
			Logger::error('main', 'USERGROUPDB::MYSQL::imports failed (sql query failed)');
			return $result;
		}
		
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row){
			$g = new UsersGroup($row['id'], $row['name'], $row['description'], (bool)$row['published']);
			$this->cache[$g->id] = $g;
			if (! $this->isOK($g)) {
				Logger::info('main', 'USERGROUPDB::MYSQL::imports group \''.$row['id'].'\' not ok');
				continue;
			}
			
			$result[$g->getUniqueID()]= $g;
		}
		
		return $result;
	}
	
	public function getList() {
		Logger::debug('main','UserGroupDB_sql::getList');
		
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM #5', 'id', 'name', 'description', 'published', self::table);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$ug = new UsersGroup($row['id'], $row['name'], $row['description'], (bool)$row['published']);
				if ($this->isOK($ug))
					$result[$ug->id]= $ug;
				else {
					Logger::info('main', 'USERGROUPDB::MYSQL::getList group \''.$row['id'].'\' not ok');
				}
			}
			
			return $result;
		}
		else {
			Logger::error('main', 'USERGROUPDB::MYSQL::getList failed (sql query failed)');
			// not the right argument
			return NULL;
		}
	}
	
	public function isDynamic() {
		return false;
	}
	
	public static function configuration() {
		return array();
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
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			
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
				Logger::error('main', 'USERGROUPDB::MYSQL::prefsIsValid table \''.self::table.'\' does not exist');
				return false;
			}
		}
		else {
			Logger::error('main', 'USERGROUPDB::MYSQL::prefsIsValid table \''.self::table.'\' does not exist(2)');
			return false;
		}
	}
	
	public static function isDefault() {
		return true;
	}
	
	public function add($usergroup_){
		Logger::debug('main', "USERGROUPDB::add($usergroup_)");
		$sql2 = SQL::getInstance();
		// usergroup already exists ?
		$res = $sql2->DoQuery('SELECT 1 FROM #1 WHERE @2 = %3 AND @4 = %5', self::table, 'name', $usergroup_->name, 'description', $usergroup_->description);
		if ($sql2->NumRows($res) > 0) {
			ErrorManager::report('usersgroup (name='.$usergroup_->name.',description='.$usergroup_->description.') already exists');
			return false;
		}
		
		$res = $sql2->DoQuery('INSERT INTO #1 (@2,@3,@4) VALUES (%5,%6,%7)',self::table, 'name', 'description', 'published', $usergroup_->name, $usergroup_->description, $usergroup_->published);
		if ($res !== false) {
			$usergroup_->id = $sql2->InsertId();
			return is_object($this->import($sql2->InsertId()));
		}
		else
			return false;
	}
	
	public function remove($usergroup_){
		Logger::debug('main', "USERGROUPDB::remove($usergroup_)");
		if (array_key_exists($usergroup_->id, $this->cache)) {
			unset($this->cache[$usergroup_->id]);
		}
		// first we delete liaisons
		$sql2 = SQL::getInstance();
		Abstract_Liaison::delete('UsersGroupApplicationsGroup', $usergroup_->getUniqueID(), NULL);
		Abstract_Liaison::delete('UsersGroup', NULL, $usergroup_->getUniqueID());
		
		// second we delete sharedfolder acls for the group
		if (Preferences::moduleIsEnabled('SharedFolderDB')) {
			$sharedfolderdb = SharedFolderDB::getInstance();
			$networkfolders = $sharedfolderdb->importFromUsergroup($usergroup_->getUniqueID());
			if (is_array($networkfolders) && count($networkfolders) > 0) {
				foreach ($networkfolders as $mode => $networkfolders2) {
					foreach ($networkfolders2 as $networkfolder) {
						$networkfolder->delUserGroup($usergroup_);
					}
				}
			}
		}
		
		// third remove the preferences if it is default
		if ($usergroup_->isDefault()) {
			// unset the default usergroup
			$prefs = new Preferences_admin();
			$mods_enable = $prefs->set('general', 'user_default_group', '');
			$prefs->backup();
		}

		// fourth we delete the group
		$res = $sql2->DoQuery('DELETE FROM #1 WHERE @2 = %3', self::table, 'id', $usergroup_->id);

		return ($res !== false);
	}
	
	public function update($usergroup_){
		Logger::debug('main',"USERGROUPDB::update($usergroup_)");
		if (array_key_exists($usergroup_->id, $this->cache)) {
			unset($this->cache[$usergroup_->id]);
		}
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('UPDATE #1  SET @2 = %3 , @4 = %5 , @6 = %7  WHERE @8 = %9', self::table, 'published', $usergroup_->published, 'name', $usergroup_->name, 'description', $usergroup_->description, 'id', $usergroup_->id);
		return ($res !== false);
	}
	
	public static function init($prefs_) {
		Logger::debug('main', 'USERGROUPDB::sql::init');
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			Logger::error('main','USERGROUPDB::sql::init sql conf is not valid');
			return false;
		}
		
		$sql2 = SQL::newInstance($sql_conf);
		
		$usersgroup_table_structure = array(
			'id' => 'int(8) NOT NULL auto_increment',
			'name' => 'varchar(150) NOT NULL',
			'description' => 'varchar(150) NOT NULL',
			'published' => 'tinyint(1) NOT NULL');
		
		$ret = $sql2->buildTable(self::table, $usersgroup_table_structure, array('id'));
		
		if ( $ret === false) {
			Logger::error('main', 'USERGROUPDB::sql::init table '.self::table.' fail to created');
			return false;
		}
		
		return true;
	}
	
	public static function enable() {
		return true;
	}
	
	public function getGroupsContains($contains_, $attributes_=array('name', 'description'), $limit_=0, $user_=null) {
		if (! is_null($user_)) {
			$liasons = Abstract_Liaison::load('UsersGroup', $user_->getAttribute('login'), NULL);
			$groups2 = array();
			foreach($liasons as $group_id => $liason) {
				array_push($groups2, $group_id);
			}
		}
		
		$groups = array();
		$count = 0;
		$sizelimit_exceeded = false;
		$list = $this->getList();
		foreach ($list as $a_group) {
			if (! is_null($user_) && !in_array($a_group->getUniqueID(), $groups2)) {
				continue;
			}
			
			if ($contains_ != '' && count($attributes_) > 0) {
				$is_ok = false;
				foreach ($attributes_ as $an_attribute) {
					if (isset($a_group->$an_attribute) and is_string(strstr($a_group->$an_attribute, $contains_))) {
						$is_ok = true;
						break;
					}
				}
				
				if (! $is_ok) {
					continue;
				}
			}
			
			if ($limit_ > 0 && $count >= $limit_) {
				$sizelimit_exceeded = true;
				break;
			}
			
			array_push($groups, $a_group);
			$count++;
		}
		
		return array($groups, $sizelimit_exceeded);
	}
	
	public function get_filter_groups_member($group_) {
		Logger::debug('main', 'UsersGroupDB::sql::get_filter_groups_member ('.$group_->getUniqueID().')');
		
		$liasons = Abstract_Liaison::load('UsersGroup', NULL, $group_->getUniqueID());
		if (! is_array($liasons)) {
			return array('users' => array());
		}
		
		$users_login = array();
		foreach($liasons as $user_login => $liason) {
			array_push($users_login, $user_login);
		}
		
		return array('users' => $users_login);
	}
	
	public function get_groups_including_user_from_list($groups_id_, $user_) {
		$liasons = Abstract_Liaison::load('UsersGroup', $user_->getAttribute('login'), NULL);
		$groups_id2 = array();
		foreach($liasons as $useless => $liason) {
			if (! str_startswith($liason->group, 'static_')) {
				continue;
			}
			
			$group_id = substr($liason->group, strlen('static_'));
			if (! in_array($group_id, $groups_id_)) {
				continue;
			}
			
			array_push($groups_id2, $group_id);
		}
		
		return $this->imports($groups_id2);
	}
}
