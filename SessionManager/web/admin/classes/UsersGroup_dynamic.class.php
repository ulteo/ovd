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

class UsersGroup_dynamic extends UsersGroup {
	protected $rules;
	protected $validation_type; //(and/or)

	public function __construct($id_=NULL, $name_=NULL, $description_='', $published_=false, $rules_=array(), $validation_type_=NULL) {
		parent::__construct($id_, $name_, $description_, $published_);
		$this->rules = $rules_;
		$this->validation_type = $validation_type_;
	}

	public function usersLogin(){
		Logger::debug('main','UsersGroup_dynamic::usersLogin');
		$static = parent::usersLogin();
		$ls = Abstract_Liaison_dynamic::load('UsersGroup',NULL, $this->id);
		$dynamic = array();
		if (is_array($ls)) {
			foreach ($ls as $l) {
				$dynamic []= $l->element;
			}
		}
		return array_unique(array_merge($static, $dynamic));
	}

	public function containUser($user_) {
		Logger::debug('main','UsersGroup_dynamic::containUser');

		if (count($this->rules) == 0)
			return false;

		if (! in_array($this->validation_type, $this->validation_types))
			return false;

		foreach ($this->rules as $rule) {
			$buf = $rule->match($user_);

			if ($this->validation_type == 'or' && $buf === true)
				return true;

			if ($this->validation_type == 'and' && $buf === false)
				return false;
		}

		return true;
	}
}
