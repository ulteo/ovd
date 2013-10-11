<?php
/**
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
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

abstract class ConfigElement{
	public $id;
	public $content;
	public $content_default;
	public $content_available;
	public $formSeparator='';
	public $path=array();

	public function __construct($id_, $content_){
		$this->id = $id_;
		$this->content = $content_;
		$this->content_default = $content_;
// 		$this->content_available = $content_available_;
// 		$this->type = $type_;
	}
	
	public function reset() {
		if (is_string($this->content)) {
			$this->content = '';
		}
		else if (is_array($this->content)){
			$this->content = array();
		}
		else{
			// TODO
			$this->content = '';
		}
	}

	public function setPath($path_) {
		$this->path = $path_;
	}

	public function setContentAvailable($content_available_) {
		$this->content_available = $content_available_;
	}
	
	public function contentEqualsTo($content_) {
		return self::values_equals($this->content, $content_);
	}
	
	public static function values_equals($value1_, $value2_) {
		return ($value1_ == $value2_);
	}
}



