<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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

class UserGroupDBDynamic {
	protected $table;
	public function __construct() {
		$prefs = Preferences::getInstance();
		if ($prefs) {
			$sql_conf = $prefs->get('general', 'sql');
			if (is_array($sql_conf)) {
				$this->table =  $sql_conf['prefix'].'usergroup_dynamic';
			}
			else
				$this->table = NULL;
		}
	}
	protected function isOK($usergroup_) {
		if (is_object($usergroup_)) {
			if ((!isset($usergroup_->id)) || (!isset($usergroup_->name)) || (!isset($usergroup_->type)) || ($usergroup_->name == '') || (!isset($usergroup_->published)))
				return false;
			else
				return true;
		}
		else
			return false;
	}
	public function import($id_) {
		Logger::debug('main', "UserGroupDBDynamic::import (id = $id_)");
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4, @7 FROM @5 WHERE @1 = %6', 'id', 'name', 'description', 'published', $this->table, $id_, 'validation_type');
		
		if ($sql2->NumRows($res) == 1) {
			$row = $sql2->FetchResult($res);
			
			$rules = UserGroup_Rules::getByUserGroupId('dynamic_'.$row['id']);
			
			$ug = new UsersGroup_dynamic($row['id'], $row['name'], $row['description'], (bool)$row['published'], $rules, $row['validation_type']);
			if ($this->isOK($ug))
				return $ug;
		}
		else {
			Logger::error('main' ,"UserGroupDBDynamic::import import group '$id_' failed");
			return NULL;
		}
	}
	
	public function getList() {
		Logger::debug('main','UserGroupDBDynamic::getList');
		if (is_null($this->table)) {
			Logger::error('main', 'UserGroupDBDynamic::getList table is null');
			return NULL;
		}
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4, @5 FROM @6', 'id', 'name', 'description', 'published',  'validation_type', $this->table);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$rules = UserGroup_Rules::getByUserGroupId('dynamic_'.$row['id']);
				$ug = new UsersGroup_dynamic($row['id'], $row['name'], $row['description'], (bool)$row['published'], $rules, $row['validation_type']);
				if ($this->isOK($ug))
					$result[$ug->id]= $ug;
				else {
					Logger::info('main', 'UserGroupDBDynamic::getList group \''.$row['id'].'\' not ok');
				}
			}
			return $result;
		}
		else {
			Logger::error('main', 'UserGroupDBDynamic::getList failed (sql query failed)');
			// not the right argument
			return NULL;
		}
	}
	public function isWriteable() { // TODO
	}
	public function canShowList() { // TODO
	}
	
	// admin function
	public function add($usergroup_){
		Logger::debug('main', 'UserGroupDBDynamic::add');
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('INSERT INTO @1 (@2,@3,@4,@8) VALUES (%5,%6,%7,%9)',$this->table, 'name', 'description', 'published', $usergroup_->name, $usergroup_->description, $usergroup_->published, 'validation_type', $usergroup_->validation_type);
		if ($res === false) {
			Logger::error('main','UserGroupDBDynamic::add SQL insert request failed');
			return false;
		}
		$usergroup_->id = $sql2->InsertId();
		$status = is_object($this->import($sql2->InsertId()));
		if ( $status === false) {
			return false;
		}
		foreach ($usergroup_->rules as $a_rule) {
			$a_rule->usergroup_id = $usergroup_->getUniqueID();
			if (Abstract_UserGroup_Rule::save($a_rule) == false) {
				Logger::error('main', 'UserGroupDBDynamic::add failed to save rule');
				return false;
			}
		}
		return true;
	}
	
	public function remove($usergroup_){
		Logger::debug('main', 'UserGroupDBDynamic::remove');
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
			Logger::error('main', 'UserGroupDBDynamic::remove Failed to remove group from SQL DB');
			return false;
		}
		// third we delete the rules
		$rules = UserGroup_Rules::getByUserGroupId($usergroup_->getUniqueID());
		foreach ($rules as $a_rule) {
			if ( Abstract_UserGroup_Rule::delete($a_rule->id) === false) {
				Logger::error('main', 'UserGroupDBDynamic::remove Failed to remove rule from SQL DB');
				return false;
			}
		}
		return true;
	}
	
	public function update($usergroup_){
		Logger::debug('main', 'UserGroupDBDynamic::update');
		$old_usergroup = $this->import($usergroup_->id);
		$old_rules = $old_usergroup->rules;
		
		$sql2 = SQL::getInstance();
		$res = $sql2->DoQuery('UPDATE @1  SET @2 = %3 , @4 = %5 , @6 = %7 , @10 = %11  WHERE @8 = %9', $this->table, 'published', $usergroup_->published, 'name', $usergroup_->name, 'description', $usergroup_->description, 'id', $usergroup_->id, 'validation_type', $usergroup_->validation_type);
		if ( $res === false) {
			Logger::error('main', 'UserGroupDBDynamic::update failed to update the group from DB');
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

	public static function enable() {} // TODO
	public static function configuration() {} // TODO
	
	public static function init($prefs_) {
		Logger::debug('main', 'UserGroupDBDynamic::init');
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			Logger::error('main', 'UserGroupDBDynamic::init sql conf not valid');
			return false;
		}
		$usersgroup_table = $sql_conf['prefix'].'usergroup_dynamic';
		$sql2 = SQL::newInstance($sql_conf);
		
		$usersgroup_table_structure = array(
			'id' => 'int(8) NOT NULL auto_increment',
			'name' => 'varchar(150) NOT NULL',
			'description' => 'varchar(150) NOT NULL',
			'published' => 'tinyint(1) NOT NULL',
			'validation_type' => 'varchar(15) NOT NULL',
			);
		
		$ret = $sql2->buildTable($usersgroup_table, $usersgroup_table_structure, array('id'));
		
		if ( $ret === false) {
			Logger::error('main', 'UserGroupDBDynamic::init table '.$usersgroup_table.' fail to created');
			return false;
		}
		
		return true;
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		// dirty
		$ret = self::prefsIsValid2($prefs_, $log);
		if ( $ret != true) {
			$ret = self::init($prefs_);
		}
		return $ret;
	}
	
	public static function prefsIsValid2($prefs_, &$log=array()) {
		$sql_conf = $prefs_->get('general', 'sql');
		if (!is_array($sql_conf)) {
			
			return false;
		}
		$table =  $sql_conf['prefix'].'usergroup_dynamic';
		$sql2 = SQL::newInstance($sql_conf);
		$ret = $sql2->DoQuery('SHOW TABLES FROM @1 LIKE %2', $sql_conf['database'], $table);
		if ($ret !== false) {
			$ret2 = $sql2->NumRows($ret);
			if ($ret2 == 1) {
				return true;
			}
			else {
				Logger::error('main', 'UserGroupDBDynamic::prefsIsValid table \''.$table.'\' does not exist');
				return false;
			}
		}
		else {
			Logger::error('main', 'UserGroupDBDynamic::prefsIsValid table \''.$table.'\' does not exist(2)');
			return false;
		}
	}
	public static function prettyName() {
		return 'UserGroupDBDynamic';
	}
// 	public static function isDefault() {} // TODO
	public static function liaisonType() {} // TODO
}
