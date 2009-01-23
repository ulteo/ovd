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

class UsersGroup {
	public $id;
	public $name; // (ex: IT)
	public $description; // (ex: People from the basement)
	public $published; //(yes/no)

	public function __construct($id_=NULL, $name_=NULL, $description_=NULL, $published_=false) {
		Logger::debug('admin',"USERSGROUP::contructor from_scratch (id_=$id_, name_=$name_, description_=$description_, published_=$published_)");
		$this->id = $id_;
		$this->name = $name_;
		$this->description = $description_;
		$this->published = (bool)$published_;
	}

	public function fromDB($id_) {
		Logger::debug('admin',"USERSGROUP::fromDB (id = $id_)");
		if (is_numeric($id_)){
			$sql2 = MySQL::getInstance();
			$res = $sql2->DoQuery('SELECT @1, @2, @3, @4 FROM @5 WHERE @1= %6', 'id', 'name', 'description',  'published', USERSGROUP_TABLE, $id_);
			if ($sql2->NumRows($res) == 1){
				$row = $sql2->FetchResult($res);
				$this->id = $row['id'];
				$this->name = $row['name'];
				$this->description = $row['description'];
				$this->published = (bool)$row['published'];
				Logger::debug('admin','USERSGROUP::fromDB an UsersGroup (id='.$this->id.') has been created from DB');
			}
			else
				$this->delete();
		}
		else {
			// not the right argument
			$this->delete();
		}
	}

	public function insertDB(){
		// return:
		// 	false :  problem, true : ok
		Logger::debug('admin','USERSGROUP::insertDB');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('INSERT INTO @1 (@2,@3,@4,@5) VALUES (NULL,%6,%7,%8)',USERSGROUP_TABLE, 'id'  , 'name', 'description', 'published', $this->name, $this->description, $this->published);
		if ($res !== false) {
			$res = $sql2->DoQuery('SELECT @1 FROM @2 WHERE @3=%4 AND @5=%6 AND @7=%8', 'id', USERSGROUP_TABLE, 'name', $this->name, 'description', $this->description, 'published', $this->published);
			if ($sql2->NumRows($res) == 1){
				$row = $sql2->FetchResult($res);
				$this->id = $row['id'];
				return true;
			}
			else
				return false;
		}
		else
			return false;
	}

	public function updateDB(){
		// return:
		// 	false :  problem, true : ok
		Logger::debug('admin','USERSGROUP::updateDB');
		$sql2 = MySQL::getInstance();
		$res = $sql2->DoQuery('UPDATE @1  SET @2 = %3 , @4 = %5 , @6 = %7  WHERE @8 = %9',	USERSGROUP_TABLE, 'published', $this->published, 'name', $this->name, 'description', $this->description, 'id', $this->id);
		return ($res !== false);
	}

	public function removeDB(){
		// return:
		// 	false :  problem, true : ok
		Logger::debug('admin','USERSGROUP::removeDB');
		if (is_numeric($this->id)){
			// first we delete liaisons
			$sql2 = MySQL::getInstance();
			$sql2->DoQuery('DELETE FROM @1 WHERE @2 = %3', USERSGROUP_APPLICATIONSGROUP_LIAISON_TABLE, 'element', $this->id);
			$sql2->DoQuery('DELETE FROM @1 WHERE @2 = %3', LIAISON_USERS_GROUP_TABLE, 'group', $this->id);
			// second we delete the group
			$res = $sql2->DoQuery('DELETE FROM @1 WHERE @2 = %3', USERSGROUP_TABLE, 'id', $this->id);
			return ($res !== false);
		}
		else
			return false;
	}

	public function isOK(){
		if ((!isset($this->id))||(!is_numeric($this->id))||(!isset($this->name))||(!isset($this->description))||(!isset($this->published)))
			return FALSE;
		else
			return TRUE;
	}

	public function delete(){
		Logger::debug('admin','USERSGROUP::delete');
		unset($this->id);
		unset($this->name);
		unset($this->description);
		unset($this->published);
	}

	public function appsGroups(){
		Logger::debug('admin','USERSGROUP::appsGroups');
		
		$l = new UsersGroupApplicationsGroupLiaison($this->id, NULL);
		$groups = $l->groups();
		if (is_array($groups)) {
			$result = array();
			foreach ($groups as $id_group){
				$g = new AppsGroup();
				$g->fromDB($id_group);
				if ($g->isOK())
					$result []= $g;
			}
			return $result;
		}
		else {
			Logger::error('admin','USERSGROUP::appsGroups result query is false');
			return NULL;
		}
	}

	public function usersLogin(){
		Logger::debug('admin','USERSGROUP::usersLogin');
		$l = new UsersGroupLiaison(NULL,$this->id);
		return $l->elements();
	}
}
