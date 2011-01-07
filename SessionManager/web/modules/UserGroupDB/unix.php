<?php
/**
 * Copyright (C) 2010 Ulteo SAS
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
class UserGroupDB_unix  extends UserGroupDB {
	protected $table;
	public function __construct(){
	}
	public function __toString() {
		return get_class($this).'(table \''.$this->table.'\')';
	}
		
	public function isWriteable(){
		return false;
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
		Logger::debug('main','UserGroupDB::unix::import('.$id_.')');
		$tab = posix_getgrnam($id_);
		if (is_array($tab)) {
			$id = '';
			$name = '';
			$description = '';
			$published = true;
			$members = null;
			
			if (isset($tab['name'])) {
				$id = $tab['name'];
				$name = $tab['name'];
			}
			
			if (isset($tab['members']))
				$members = $tab['members'];
			
			$ug = new UsersGroup($id, $name, $description, $published);
			if (is_array($members))
				$ug->extras = array('member' => $members);
			
			if ($this->isOK($ug)) {
				return $ug;
			}
		}
		return NULL;
	}
	
	public function getList($sort_=false) {
		Logger::debug('main','UserGroupDB::unix::getList');
		$groups = array();
		$content = file_get_contents('/etc/group');
		$contents = explode("\n",$content);
		foreach($contents as $line){
			$infos = explode(':',$line);
			$g = $this->import($infos[0]);
			if (is_object($g)) {
				$groups[$g->id] = $g;
			}
		}
		// do we need to sort alphabetically ?
		if ($sort_) {
			usort($groups, "usergroup_cmp");
		}
		return $groups;
		
	}
	
	public function isDynamic() {
		return false;
	}
	
	public static function configuration() {
		return array();
	}
	
	public static function prefsIsValid($prefs_, &$log=array()) {
		return true;
	}
	
	public static function prettyName() {
		return _('Unix');
	}
	
	public static function isDefault() {
		return false;
	}
	
	public static function liaisonType() {
		return 'unix';
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
