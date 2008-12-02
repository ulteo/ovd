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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class Liaison {
	public $element; // id
	public $group; // id
	public $table; // name of sql table

	public function __construct($element_=NULL, $group_=NULL) {
		Logger::debug('admin','LIAISON::contructor from_scratch');
		$this->element = $element_;
		$this->group = $group_;
	}

	public function onDB() {
		Logger::debug('admin','LIAISON::onDB');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT 1 FROM @1 WHERE @2=%3 AND @4 =%5',$this->table,'element',$this->element,'group',$this->group);
		if ($res === false)
			return NULL;
		if ($sql2->NumRows($res) == 1)
			return true;
		else
			return false;
	}

	public function insertDB(){
		// return:
		// 	false :  problem, true : ok
		Logger::debug('admin','LIAISON::insertDB');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('INSERT INTO @1 ( @2,@3 ) VALUES ( %4,%5)',$this->table,'element','group',$this->element,$this->group);
		return ($res !== false);
	}

	public function removeDB(){
		// return:
		// 	false :  problem, true : ok
		Logger::debug('admin','LIAISON::removeDB');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2=%3 AND @4 =%5',$this->table,'element',$this->element,'group',$this->group);
		return ($res !== false);
	}

	public function updateGroupDB($new_group_){
		Logger::debug('admin','LIAISON::updateGroupDB');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('UPDATE @1 SET  @2 = %3 WHERE @4 = %5 AND @2 = %7 ',$this->table,'group',$new_group_,'element',$this->element,$this->group);
		if ($res !== false){
			$this->group = $new_group_;
			return true;
		}
		else {
			// delete TODO
			return false;
		}
	}

	public function groups(){
		Logger::debug('admin','LIAISON::groups');
		$result = array();
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4','group',$this->table,'element',$this->element);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$result []= $row['group'];
			}
			return $result;
		}
		else
			return false;
	}

	public function elements(){
		Logger::debug('admin','LIAISON::elements');
		$result = array();
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1 FROM @2 WHERE @3 = %4','element',$this->table,'group',$this->group);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$result []= $row['element'];
			}
			return $result;
		}
		else
			return false;
	}
	
	public function all(){
		Logger::debug('admin','LIAISON::all');
		$result = array();
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('SELECT @1,@2 FROM @3','element','group',$this->table);
		if ($res !== false){
			$result = array();
			$rows = $sql2->FetchAllResults($res);
			foreach ($rows as $row){
				$r = array();
				$r['element'] = $row['element'];
				$r['group'] = $row['group'];
				$result []= $r;
			}
			return $result;
		}
		else
			return false;
	}
}
