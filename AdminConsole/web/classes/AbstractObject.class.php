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

class AbstractObject {
	public $attributes = array();
	
	public function __construct($attributes_) {
		if (! is_array($attributes_))
			return;
		
		$this->attributes = $attributes_;
	}
	
	public function setAttribute($attribute_, $value_){
		$this->attributes[$attribute_] = $value_;
	}

	public function hasAttribute($attribute_){
		return array_key_exists($attribute_, $this->attributes);
	}

	public function getAttribute($attribute_){
		if (! $this->hasAttribute($attribute_)) {
			return null;
		}
		
		return $this->attributes[$attribute_];
	}
	
	protected function required_attributes() {
		return array();
	}
	
	public function is_valid() {
		foreach($this->required_attributes() as $attr) {
			if (! $this->hasAttribute($attr)) {
				return false;
			}
		}
		
		return true;
	}
}
