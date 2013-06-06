<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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

class ApplicationsGroup extends AbstractObject {
	public $id;
	public $name; // (ex: Officeapps)
	public $description; // (ex: Office application)
	public $published; // (yes/no) (the group is available to user)
	
	public function __construct($attributes_) {
		parent::__construct($attributes_);
		if (! $this->is_valid()) {
			return;
		}
		
		$this->id = $attributes_['id'];
		$this->name = $attributes_['name'];
		$this->description = $attributes_['description'];
		$this->published = $attributes_['published'];
	}
	
	protected function required_attributes() {
		return array('id', 'name', 'description', 'published');
	}
}
