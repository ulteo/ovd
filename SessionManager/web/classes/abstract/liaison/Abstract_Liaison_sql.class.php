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
require_once(dirname(__FILE__).'/../../../includes/core.inc.php');

class Abstract_Liaison_sql {
	public static function load($type_, $element_=NULL, $group_=NULL) {
		if (is_null($element_) && is_null($group_))
			return Abstract_Liaison_sql::loadAll($type_);
		else if (is_null($element_))
			return Abstract_Liaison_sql::loadElements($type_, $group_);
		else if (is_null($group_))
			return Abstract_Liaison_sql::loadGroups($type_, $element_);
		else
			return Abstract_Liaison_sql::loadUnique($type_, $element_, $group_);
	}
	public static function save($type_, $element_, $group_) {
		Logger::debug('admin', "Abstract_Liaison_sql::save ($type_,$element_,$group_)");
		$sql2 = MySQL::getInstance();
		$table = $sql2->prefix.'liaison';
		$res = $sql2->DoQuery('INSERT INTO @1 ( @2,@3,@4 ) VALUES ( %5,%6,%7)', $table, 'type', 'element', 'group', $type_, $element_, $group_);
		return ($res !== false);
	}
	public static function delete($type_, $element_, $group_) {
		Logger::debug('main', "Abstract_Liaison_sql::delete ($type_,$element_,$group_)");
		$sql2 = MySQL::getInstance();
		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('main', 'Abstract_Liaison_sql::delete get Preferences failed');
			return false;
		}
		$mysql_conf = $prefs->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('main', 'Abstract_Liaison_sql::delete mysql conf not valid');
			return false;
		}
		$table = $mysql_conf['prefix'].'liaison';
		
		$res = false;
		if (is_null($element_) && is_null($group_)) {
			$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2=%3', $table, 'type', $type_);
		}
		else if (is_null($element_)) {
			$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2=%3 AND @4=%5', $table, 'type', $type_, 'group', $group_);
		}
		else if (is_null($group_)) {
			$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2=%3 AND @4=%5', $table, 'type', $type_, 'element', $element_);
		}
		else {
			$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2=%3 AND @4=%5 AND @6=%7', $table, 'type', $type_, 'element', $element_, 'group', $group_);
		}
		return ($res !== false);
	}
	public static function loadElements($type_, $group_) {
		Logger::debug('admin',"Abstract_Liaison_sql::loadElements ($type_,$group_)");
		$result = array();
		$sql2 = MySQL::getInstance();
		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('main', 'Abstract_Liaison_sql::loadElements get Preferences failed');
			return NULL;
		}
		$mysql_conf = $prefs->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('main', 'Abstract_Liaison_sql::loadElements mysql conf not valid');
			return $result;
		}
		$table = $mysql_conf['prefix'].'liaison';
		$res = $sql2->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4 AND @5 = %6','element', $table, 'type', $type_, 'group', $group_);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$l = new Liaison($row['element'], $group_);
				$result[$l->element]= $l;
			}
			return $result;
		}
		return NULL;
	}
	
	public static function loadGroups($type_, $element_) {
		Logger::debug('admin',"Abstract_Liaison_sql::loadGroups ($type_,$element_)");
		$result = array();
		$sql2 = MySQL::getInstance();
		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('main', 'Abstract_Liaison_sql::loadGroups get Preferences failed');
			return NULL;
		}
		$mysql_conf = $prefs->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('main', 'Abstract_Liaison_sql::loadGroups mysql conf not valid');
			return $result;
		}
		$table = $mysql_conf['prefix'].'liaison';
		$res = $sql2->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4 AND @5 = %6', 'group', $table, 'type', $type_, 'element', $element_);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$l = new Liaison($element_, $row['group']);
				$result[$l->group]= $l;
			}
			return $result;
		}
		return NULL;
	}
	
	public static function loadAll($type_) {
		Logger::debug('admin',"Abstract_Liaison_sql::loadAll ($type_)");
		$result = array();
		$sql2 = MySQL::getInstance();
		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('main', 'Abstract_Liaison_sql::loadAll get Preferences failed');
			return NULL;
		}
		$mysql_conf = $prefs->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('main', 'Abstract_Liaison_sql::loadAll mysql conf not valid');
			return $result;
		}
		$table = $mysql_conf['prefix'].'liaison';
		$res = $sql2->DoQuery('SELECT @1,@2 FROM @3 WHERE @4 = %5', 'element', 'group', $table, 'type', $type_);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$l = new Liaison($row['element'], $row['group']);
				$result[]= $l;
			}
			return $result;
		}
		return NULL;
	}
	public static function loadUnique($type_, $element_, $group_) {
		Logger::debug('admin',"Abstract_Liaison_sql::loadUnique ($type_,$element_,$group_)");
		$result = array();
		$sql2 = MySQL::getInstance();
		$prefs = Preferences::getInstance();
		if (! $prefs) {
			Logger::error('main', 'Abstract_Liaison_sql::loadAll get Preferences failed');
			return NULL;
		}
		$mysql_conf = $prefs->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('main', 'Abstract_Liaison_sql::loadAll mysql conf not valid');
			return $result;
		}
		$table = $mysql_conf['prefix'].'liaison';
		$res = $sql2->DoQuery('SELECT @3,@4 FROM @1 WHERE @2=%5 AND @3=%6 AND @4=%7', $table, 'type', 'element', 'group',  $type_, $element_, $group_);
// 		echo 'FetchAllResults ';var_dump2($sql2->FetchAllResults());
		if ($res !== false){
			if ($sql2->NumRows() == 1)
				return  new Liaison($element_, $group_);
			else
				return NULL;
		}
		else
			return NULL;
	}
	
	public static function init($prefs_) {
		$mysql_conf = $prefs_->get('general', 'mysql');
		if (!is_array($mysql_conf)) {
			Logger::error('admin','Abstract_Liaison::init mysql conf not valid');
			return false;
		}
		$LIAISON_TABLE = $mysql_conf['prefix'].'liaison';
		// we create the sql table
		$sql2 = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database'], $mysql_prefix['prefix']);
		
		$LIAISON_table_structure = array(
			'type' => 'varchar(200) NOT NULL',
			'element' => 'varchar(200) NOT NULL',
			'group' => 'varchar(200) NOT NULL');
		
		$ret = $sql2->buildTable($LIAISON_TABLE, $LIAISON_table_structure, array());
		
		if ( $ret === false) {
			Logger::error('admin','Abstract_Liaison::init table '.$LIAISON_TABLE.' fail to created');
			return false;
		}
		else {
			Logger::debug('admin','Abstract_Liaison::init table '.$LIAISON_TABLE.' created');
			return true;
		}
	}
}
