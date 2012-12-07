<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <laurent@ulteo.com> 2012
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

class UsersGroup extends AbstractObject {
	public $id;
	public $name; // (ex: IT)
	public $description; // (ex: People from the basement)
	public $published; //(yes/no)
	public $type; // static
	public $default = false;
	public $extras;

	public function __construct($attributes_) {
		parent::__construct($attributes_);
		if (! $this->is_valid()) {
			return;
		}
		
		$this->id = $attributes_['id'];
		$this->name = $attributes_['name'];
		$this->description = $attributes_['description'];
		$this->published = (bool)$attributes_['published'];
		$this->type = $attributes_['type'];
	}
	
	protected function required_attributes() {
		return array('id', 'name', 'description', 'published', 'type');
	}
	
	public function isDefault() {
		return (array_key_exists('default', $this->attributes) && $this->attributes['default'] == true);
	}
}
