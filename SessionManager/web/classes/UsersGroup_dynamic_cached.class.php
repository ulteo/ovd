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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

class UsersGroup_dynamic_cached extends UsersGroup_dynamic {
	public $schedule;  // time in seconds
	public $last_update;
	
	public function __construct($id_='', $name_=NULL, $description_='', $published_=false, $rules_=array(), $validation_type_=NULL, $schedule_=86400 /* 24h */, $last_update_=0) {
		Logger::debug('main', "UsersGroup_dynamic_cached::construct(id:'$id_', name:'$name_',description: '$description_', published: '$published_',validation_type: '$validation_type_',schedule: '$schedule_')");
		parent::__construct($id_, $name_, $description_, $published_, $rules_, $validation_type_);
		$this->type = 'dynamiccached';
		$this->schedule = $schedule_;
		$this->last_update = $last_update_;
	}
	
	public function __toString() {
		$ret = get_class($this).'(id: \''.$this->id.'\' name: \''.$this->name.'\' description: \''.$this->description.'\' published: '.$this->published.' validation_type \''.$this->validation_type.'\' rules [';
		foreach ($this->rules as $a_rule) {
			$ret .= $a_rule.' , ';
		}
		$ret .= '], schedule \''.$this->schedule.'\', last_update \''.$this->last_update.'\')';
		return $ret;
	}
	
	public function usersLogin() { // get from cache
		Logger::debug('main', 'UsersGroup_dynamic_cached::usersLogin');
		$ls = Abstract_Liaison::load('UsersGroupCached', NULL, $this->getUniqueID());
		$logins = array();
		if (is_array($ls)) {
			foreach ($ls as $l) {
				$logins []= $l->element;
			}
		}
		return $logins;
	}
	
	public function updateCache() {  // update the liaison
		Logger::debug('main', 'UsersGroup_dynamic_cached::updateCache for ID='.$this->getUniqueID());
		$logins = parent::usersLogin();
		$liaisons = Abstract_Liaison::load('UsersGroupCached', NULL, $this->getUniqueID());
		foreach ($liaisons as $a_liaison) {
			if (! in_array($a_liaison->element, $logins))
				Abstract_Liaison::delete('UsersGroupCached', $a_liaison->element, $a_liaison->group);
		}
		foreach ($logins as $a_login) {
			if (! isset($liaisons[$a_login]))
				Abstract_Liaison::save('UsersGroupCached', $a_login, $this->getUniqueID());
		}
	}
}
