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
			$mysql_conf = $prefs->get('general', 'mysql');
			if (is_array($mysql_conf)) {
				$this->table =  $mysql_conf['prefix'].'usergroup_dynamic';
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
		Logger::debug('admin',"UserGroupDBDynamic::import (id = $id_)");
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4, @7, @8 FROM @5 WHERE @1 = %6', 'id', 'name', 'description', 'published', $this->table, $id_, 'type', 'validation_type');
		
		if ($sql2->NumRows($res) == 1) {
			$row = $sql2->FetchResult($res);
			
			$rules = UserGroup_Rules::getByUserGroupId($row['id']);
			
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
		Logger::debug('main','UserGroupDB_sql::getList');
		if (is_null($this->table)) {
			Logger::error('main', 'USERGROUPDB::MYSQL::getList table is null');
			return NULL;
		}
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1, @2, @3, @4, @6 FROM @5', 'id', 'name', 'description', 'published', $this->table, 'type');
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$ug = new UsersGroup_dynamic($row['id'], $row['name'], $row['description'], (bool)$row['published']);
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
	public function isWriteable() { // TODO
	}
	public function canShowList() { // TODO
	}
	
	// admin function
	public static function init($prefs_) { // TODO
	}
	public function add($usergroup_){
		Logger::debug('admin','UserGroupDBDynamic::add');
		$sql2 = MySQL::getInstance();
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
			$a_rule->usergroup_id = $usergroup_->id;
			if (Abstract_UserGroup_Rule::save($a_rule) == false) {
				Logger::error('main', 'UserGroupDBDynamic::add failed to save rule');
				return false;
			}
		}
		return true;
	}
	
	public function remove($usergroup_){
		Logger::debug('admin','UserGroupDBDynamic::remove');
		// first we delete liaisons
		$sql2 = MySQL::getInstance();
		$liaisons = Abstract_Liaison::load('UsersGroupApplicationsGroup', $usergroup_->id, NULL);
		foreach ($liaisons as $liaison) {
			Abstract_Liaison::delete('UsersGroupApplicationsGroup', $liaison->element, $liaison->group);
		}
		foreach ($liaisons as $liaison) {
			Abstract_Liaison::delete('UsersGroup', NULL, $usergroup_->id);
		}
		// second we delete the group
		$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2 = %3', $this->table, 'id', $usergroup_->id);
		return ($res !== false);
	}
	
	public function update($usergroup_){
		Logger::debug('admin','UserGroupDBDynamic::update');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('UPDATE @1  SET @2 = %3 , @4 = %5 , @6 = %7  WHERE @8 = %9', $this->table, 'published', $usergroup_->published, 'name', $usergroup_->name, 'description', $usergroup_->description, 'id', $usergroup_->id);
		return ($res !== false);
	}

	public static function enable() {} // TODO
	public static function configuration() {} // TODO
	public static function prefsIsValid($prefs_, &$log=array()) {} // TODO
	public static function prettyName() {
		return 'UserGroupDBDynamic';
	}
// 	public static function isDefault() {} // TODO
	public static function liaisonType() {} // TODO
}
