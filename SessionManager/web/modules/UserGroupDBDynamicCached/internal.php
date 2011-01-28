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

class UserGroupDBDynamicCached_internal extends UserGroupDBDynamicCached {
	public static $tablename="usergroup_dynamic_cached";
	protected $table;
	public function __construct() {
		$prefs = Preferences::getInstance();
		if ($prefs) {
			$sql_conf = $prefs->get('general', 'sql');
			if (is_array($sql_conf)) {
				$this->table =  $sql_conf['prefix'].UserGroupDBDynamicCached_internal::$tablename;
			}
			else
				$this->table = NULL;
		}
	}

	protected function isOK($usergroup_) {
		if (is_object($usergroup_)) {
			if ((!isset($usergroup_->id)) || (!isset($usergroup_->name)) || (!isset($usergroup_->type)) || ($usergroup_->name == '') || (!isset($usergroup_->published)) || (!isset($usergroup_->schedule)))
				return false;
			else
				return true;
		}
		else
			return false;
	}
	public function import($id_) {
		Logger::debug('main', "UserGroupDBDynamic_cached::import (id = $id_)");
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4, @5, @6, @7 FROM @8 WHERE @1 = %9', 'id', 'name', 'description', 'published', 'validation_type', 'schedule', 'last_update', $this->table, $id_);
		if ($sql2->NumRows($res) == 1) {
			$row = $sql2->FetchResult();
			$rules = UserGroup_Rules::getByUserGroupId('dynamiccached_'.$row['id']);
			
			$ug = new UsersGroup_dynamic_cached($row['id'], $row['name'], $row['description'], (bool)$row['published'], $rules, $row['validation_type'], $row['schedule'], $row['last_update']);
			if ($this->isOK($ug))
				return $ug;
		}
		else {
			Logger::error('main', "UserGroupDBDynamic_cached::import import group '$id_' failed");
			return NULL;
		}
	}
	
	public function getList() {
		Logger::debug('main', 'UserGroupDBDynamic_cached::getList');
		if (is_null($this->table)) {
			Logger::error('main', 'UserGroupDBDynamic_cached::getList table is null');
			return NULL;
		}
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4, @5, @6, @7 FROM @8', 'id', 'name', 'description', 'published',  'validation_type', 'schedule', 'last_update', $this->table);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$rules = UserGroup_Rules::getByUserGroupId('dynamiccached_'.$row['id']);
				$ug = new UsersGroup_dynamic_cached($row['id'], $row['name'], $row['description'], (bool)$row['published'], $rules, $row['validation_type'], $row['schedule'], $row['last_update']);
				if ($this->isOK($ug))
					$result[$ug->id]= $ug;
				else {
					Logger::info('main', 'UserGroupDBDynamic_cached::getList group \''.$row['id'].'\' not ok');
				}
			}
			return $result;
		}
		else {
			Logger::error('main', 'UserGroupDBDynamic_cached::getList failed (sql query failed)');
			// not the right argument
			return NULL;
		}
	}
	public function isWriteable() {
		return true;
	}
	public function canShowList() {
		return true;
	}
	
	public function add($usergroup_){
		Logger::debug('main', 'UserGroupDBDynamic_cached::add');
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('INSERT INTO @1 (@2,@4,@6,@8,@10) VALUES (%3,%5,%7,%9,%11)',$this->table, 'name', $usergroup_->name, 'description', $usergroup_->description, 'published', $usergroup_->published, 'validation_type', $usergroup_->validation_type, 'schedule', $usergroup_->schedule);
		if ($res === false) {
			Logger::error('main', 'UserGroupDBDynamic_cached::add SQL insert request failed');
			return false;
		}
		$usergroup_->id = $sql2->InsertId();
		$status = is_object($this->import($sql2->InsertId()));
		if ( $status === false) {
			Logger::error('main', 'UserGroupDBDynamic_cached::add import failed (import of '.$sql2->InsertId());
			return false;
		}
		foreach ($usergroup_->rules as $a_rule) {
			$a_rule->usergroup_id = $usergroup_->getUniqueID();
			if (Abstract_UserGroup_Rule::save($a_rule) == false) {
				Logger::error('main', 'UserGroupDBDynamic_cached::add failed to save rule');
				return false;
			}
		}
		//$usergroup_->updateCache(); // create the initial cache ( ??? do to here on in the cache management)
		return true;
	}
	
	public function remove($usergroup_){
		Logger::debug('main', 'UserGroupDBDynamic_cached::remove');
		// first we delete liaisons
		$sql2 = SQL::getInstance();
		$liaisons = Abstract_Liaison::load('UsersGroupApplicationsGroup', $usergroup_->id, NULL);
		foreach ($liaisons as $liaison) {
			Abstract_Liaison::delete('UsersGroupApplicationsGroup', $liaison->element, $liaison->group);
		}
		foreach ($liaisons as $liaison) {
			Abstract_Liaison::delete('UsersGroup', NULL, $usergroup_->getUniqueID());
		}
		// second we delete the group
		$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2 = %3', $this->table, 'id', $usergroup_->id);
		if ( $res === false) {
			Logger::error('main', 'UserGroupDBDynamic_cached::remove Failed to remove group from SQL DB');
			return false;
		}
		// third we delete the rules
		$rules = UserGroup_Rules::getByUserGroupId($usergroup_->getUniqueID());
		foreach ($rules as $a_rule) {
			if ( Abstract_UserGroup_Rule::delete($a_rule->id) === false) {
				Logger::error('main', 'UserGroupDBDynamic_cached::remove Failed to remove rule from SQL DB');
				return false;
			}
		}
		return true;
	}
	
	public function update($usergroup_){
		Logger::debug('main', 'UserGroupDBDynamic_cached::update');
		$old_usergroup = $this->import($usergroup_->id);
		$old_rules = $old_usergroup->rules;
		
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('UPDATE @1  SET @2 = %3 , @4 = %5 , @6 = %7 , @10 = %11, @12 = %13  WHERE @8 = %9', $this->table, 'published', $usergroup_->published, 'name', $usergroup_->name, 'description', $usergroup_->description, 'id', $usergroup_->id, 'validation_type', $usergroup_->validation_type, 'schedule', $usergroup_->schedule);
		if ( $res === false) {
			Logger::error('main', 'UserGroupDBDynamic_cached::update failed to update the group from DB');
			return false;
		}
		
		foreach ($old_rules as $a_rule) {
			Abstract_UserGroup_Rule::delete($a_rule->id);
		}
		
		$new_rules = $usergroup_->rules;
		foreach ($new_rules as $a_rule) {
			$a_rule->usergroup_id = $usergroup_->getUniqueID();
			Abstract_UserGroup_Rule::save($a_rule);
		}
		return true;
	}
	
	public function updateCache($usergroup_) {
		if ( time() > ( $usergroup_->last_update + $usergroup_->schedule )) {
			Logger::debug('main', 'UserGroupDBDynamic_cached::updateCache usergroup (id='.$usergroup_->getUniqueID().') must update his cache');
			$usergroup_->updateCache(); // update the liaison
			$sql2 = SQL::getInstance();
			$res = $sql2->DoQuery('UPDATE @1  SET @2 = %3  WHERE @4 = %5', $this->table, 'last_update', time(), 'id', $usergroup_->id);
			if ( $res === false) {
				Logger::error('main', 'UserGroupDBDynamic_cached::updateCache failed to update the group from DB');
				return false;
			}
		}
		else {
			Logger::debug('main', 'UserGroupDBDynamic_cached::updateCache usergroup (id='.$usergroup_->id.') does NOT need to update his cache');
		}
		return true;
		
	}
	
	public static function init($prefs_) {
		Logger::debug('main', 'UserGroupDBDynamic_cached::init');
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			Logger::error('main', 'UserGroupDBDynamic_cached::init sql conf not valid');
			return false;
		}
		$usersgroup_table = $sql_conf['prefix'].'usergroup_dynamic_cached';
		$sql2 = SQL::newInstance($sql_conf);
		
		$usersgroup_table_structure = array(
			'id' => 'int(8) NOT NULL auto_increment',
			'name' => 'varchar(150) NOT NULL',
			'description' => 'varchar(150) NOT NULL',
			'published' => 'tinyint(1) NOT NULL',
			'validation_type' => 'varchar(15) NOT NULL',
			'schedule' => 'int(8) default 0',
			'last_update' => 'int(12)',
			);
		
		$ret = $sql2->buildTable($usersgroup_table, $usersgroup_table_structure, array('id'));
		
		if ( $ret === false) {
			Logger::error('main', 'UserGroupDBDynamic_cached::init table '.$usersgroup_table.' fail to created');
			return false;
		}
		
		return true;
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		$ret = parent::prefsIsValid($prefs_, $log);
		if ($ret === false) {
			return false;
		}
		
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			return false;
		}
		$table =  $sql_conf['prefix'].'usergroup_dynamic_cached';
		$sql2 = SQL::newInstance($sql_conf);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2', $sql_conf['database'], $table);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows();
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main', 'UserGroupDBDynamic_cached::prefsIsValid table \''.$table.'\' does not exist');
				return false;
			}
		}
		else {
			Logger::error('main', 'UserGroupDBDynamic_cached::prefsIsValid table \''.$table.'\' does not exist(2)');
			return false;
		}
	}
	
	public static function prettyName() {
		return _('Internal');
	}
	
	public function getGroupsContains($contains_, $attributes_=array('name', 'description'), $limit_=0) {
		$groups = array();
		$count = 0;
		$sizelimit_exceeded = false;
		$list = $this->getList(false);
		foreach ($list as $a_group) {
			foreach ($attributes_ as $an_attribute) {
				if ($contains_ == '' or (isset($a_group->$an_attribute) and is_string(strstr($a_group->$an_attribute, $contains_)))) {
					$groups []= $a_group;
					$count++;
					if ($limit_ > 0 && $count >= $limit_) {
						$sizelimit_exceeded = next($list) !== false; // is it the last element ?
						return array($users, $sizelimit_exceeded);
					}
					break;
				}
			}
		}
		
		return array($groups, $sizelimit_exceeded);
	}
}
