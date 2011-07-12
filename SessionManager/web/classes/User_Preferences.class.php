<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
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

class User_Preferences {
	public $login;
	public $key;
	public $container;
	public $element_id;
	public $value;
	
	public function __construct($login_, $key_, $container_, $element_id_, $value) {
		$this->login = $login_;
		$this->key = $key_;
		$this->container = $container_;
		$this->element_id = $element_id_;
		$this->value = $value;
	}
	
	public function __toString() {
		return get_class($this)."($this->login, $this->key, $this->container, $this->element_id, $this->value)";
	}
	
	public function toConfigElement() {
		$prefs = Preferences::getInstance();
		$settings = $prefs->getElements($this->key, $this->container);
		
		if (is_array($settings) == false) {
			Logger::error('main', 'User_Preferences::toConfigElement prefs('.$this->key.','.$this->container.') is not an array');
			return null;
		}
		
		if (array_key_exists($this->element_id, $settings) == false) {
			Logger::error('main', 'User_Preferences::toConfigElement \''.$this->element_id.'\' not in prefs('.$this->key.','.$this->container.') is not an array');
			return null;
		}
		
		$a_setting = clone $settings[$this->element_id];
		$a_setting->content = $this->value;
		
		return $a_setting;
	}
}
