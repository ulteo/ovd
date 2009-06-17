<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

class UserGroup_Rule {
	public $id = NULL;

	public $attribute = NULL;
	public $type = NULL;
	public $value = NULL;
	public $usergroup_id = NULL;

	public static $types = array('equal', 'not_equal', 'contains', 'not_contains', 'startswith', 'not_startswith', 'endswith', 'not_endswith');

	public function __construct($id_) {
// 		Logger::debug('main', 'Starting UserGroup_Rule::__construct for \''.$id_.'\'');

		$this->id = $id_;
	}

	public function __toString() {
		return get_class($this).'(id \''.$this->id.'\' attribute \''.$this->attribute.'\' type \''.$this->type.'\' value \''.$this->value.'\' usergroup_id \''.$this->usergroup_id.'\')';
	}

	public function match($user_) {
		switch ($this->type) {
			case 'equal':
				return ($user_->getAttribute($this->attribute) == $this->value);
				break;
			case 'not_equal':
				return (! ($user_->getAttribute($this->attribute) == $this->value));
				break;
			case 'contains':
				return (strstr($user_->getAttribute($this->attribute), $this->value) !== false);
				break;
			case 'not_contains':
				return (! (strstr($user_->getAttribute($this->attribute), $this->value) !== false));
				break;
			case 'startswith':
				return (str_startswith($user_->getAttribute($this->attribute), $this->value));
				break;
			case 'not_startswith':
				return (! (str_startswith($user_->getAttribute($this->attribute), $this->value)));
				break;
			case 'endswith':
				return (str_endswith($user_->getAttribute($this->attribute), $this->value));
				break;
			case 'not_endswith':
				return (! (str_endswith($user_->getAttribute($this->attribute), $this->value)));
				break;
		}
		Logger::error('main', 'UserGroup_Rule::match type (='.$this->type.') not matched');
		return false;
	}
}
