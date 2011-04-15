<?php
/**
 * Copyright (C) 2009-2011 Ulteo SAS
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
class UserGroupDB_sql {
	protected $table;
	protected $cache;
	public function __construct(){
		$prefs = Preferences::getInstance();
		if ($prefs) {
			$sql_conf = $prefs->get('general', 'sql');
			if (is_array($sql_conf)) {
				$this->table =  $sql_conf['prefix'].'usergroup';
			}
			else
				$this->table = NULL;
		}
		$this->cache = array();
	}
	public function __toString() {
		return get_class($this).'(table \''.$this->table.'\')';
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
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM @5 WHERE @1 = %6', 'id', 'name', 'description', 'published', $this->table, $id_);
			
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
	
	public function getList($sort_=false) {
		Logger::debug('main','UserGroupDB_sql::getList');
		if (is_null($this->table)) {
			Logger::error('main', 'USERGROUPDB::MYSQL::getList table is null');
			return NULL;
		}
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM @5', 'id', 'name', 'description', 'published', $this->table);
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
			if ($sort_) {
				usort($result, "usergroup_cmp");
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
		$table =  $sql_conf['prefix'].'usergroup';
		$sql2 = SQL::newInstance($sql_conf);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2', $sql_conf['database'], $table);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows($ret);
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main', 'USERGROUPDB::MYSQL::prefsIsValid table \''.$table.'\' does not exist');
				return false;
			}
		}
		else {
			Logger::error('main', 'USERGROUPDB::MYSQL::prefsIsValid table \''.$table.'\' does not exist(2)');
			return false;
		}
	}
	
	public static function prettyName() {
		return _('MySQL');
	}
	
	public static function isDefault() {
		return true;
	}
	
	public static function liaisonType() {
		return 'sql';
	}
	
	public function add($usergroup_){
		Logger::debug('main', "USERGROUPDB::add($usergroup_)");
		$sql2 = SQL::getInstance();
		// usergroup already exists ?
		$res = $sql2->DoQuery('SELECT 1 FROM @1 WHERE @2 = %3 AND @4 = %5', $this->table, 'name', $usergroup_->name, 'description', $usergroup_->description);
			
		if ($sql2->NumRows($res) > 0) {
			Logger::error('main', 'UserGroupDB_sql::add usersgroup (name='.$usergroup_->name.',description='.$usergroup_->description.') already exists');
			popup_error(_('Users group already exists'));
			return false;
		}
		
		$res = $sql2->DoQuery('INSERT INTO @1 (@2,@3,@4) VALUES (%5,%6,%7)',$this->table, 'name', 'description', 'published', $usergroup_->name, $usergroup_->description, $usergroup_->published);
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
				foreach ($networkfolders as $networkfolder) {
					$networkfolder->delUserGroup($usergroup_);
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
		$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2 = %3', $this->table, 'id', $usergroup_->id);

		return ($res !== false);
	}
	
	public function update($usergroup_){
		Logger::debug('main',"USERGROUPDB::update($usergroup_)");
		if (array_key_exists($usergroup_->id, $this->cache)) {
			unset($this->cache[$usergroup_->id]);
		}
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('UPDATE @1  SET @2 = %3 , @4 = %5 , @6 = %7  WHERE @8 = %9', $this->table, 'published', $usergroup_->published, 'name', $usergroup_->name, 'description', $usergroup_->description, 'id', $usergroup_->id);
		return ($res !== false);
	}
	
	public static function init($prefs_) {
		Logger::debug('main', 'USERGROUPDB::sql::init');
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			Logger::error('main','USERGROUPDB::sql::init sql conf is not valid');
			return false;
		}
		$usersgroup_table = $sql_conf['prefix'].'usergroup';
		$sql2 = SQL::newInstance($sql_conf);
		
		$usersgroup_table_structure = array(
			'id' => 'int(8) NOT NULL auto_increment',
			'name' => 'varchar(150) NOT NULL',
			'description' => 'varchar(150) NOT NULL',
			'published' => 'tinyint(1) NOT NULL');
		
		$ret = $sql2->buildTable($usersgroup_table, $usersgroup_table_structure, array('id'));
		
		if ( $ret === false) {
			Logger::error('main', 'USERGROUPDB::sql::init table '.$usersgroup_table.' fail to created');
			return false;
		}
		
		return true;
	}
	
	public static function enable() {
		return true;
	}
	
	public function getGroupsContains($contains_, $attributes_=array('name', 'description'), $limit_=0) {
		$groups = array();
		$count = 0;
		$sizelimit_exceeded = false;
		$list = $this->getList(true);
		foreach ($list as $a_group) {
			foreach ($attributes_ as $an_attribute) {
				if ($contains_ == '' or (isset($a_group->$an_attribute) and is_string(strstr($a_group->$an_attribute, $contains_)))) {
					$groups []= $a_group;
					$count++;
					if ($limit_ > 0 && $count >= $limit_) {
						$sizelimit_exceeded = next($list) !== false; // is it the last element ?
						return array($groups, $sizelimit_exceeded);
					}
					break;
				}
			}
		}
		
		return array($groups, $sizelimit_exceeded);
	}
}
