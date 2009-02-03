<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

class Token {
	public $id = NULL;

	public $type = NULL;
	public $link_to = NULL;
	public $valid_until = NULL;

	public function __construct($id_) {
// 		Logger::debug('main', 'Starting Token::__construct for \''.$id_.'\'');

		$this->id = $id_;
	}

	public function __toString() {
		return 'Token(\''.$this->id.'\', \''.$this->type.'\', \''.$this->link_to.'\', \''.$this->valid_until.'\')';
	}

	public function hasAttribute($attrib_) {
// 		Logger::debug('main', 'Starting Token::hasAttribute for \''.$this->id.'\' attribute '.$attrib_);

		if (! isset($this->$attrib_) || is_null($this->$attrib_))
			return false;

		return true;
	}

	public function getAttribute($attrib_) {
// 		Logger::debug('main', 'Starting Token::getAttribute for \''.$this->id.'\' attribute '.$attrib_);

		if (! $this->hasAttribute($attrib_))
			return false;

		return $this->$attrib_;
	}

	public function setAttribute($attrib_, $value_) {
// 		Logger::debug('main', 'Starting Token::setAttribute for \''.$this->id.'\' attribute '.$attrib_.' value '.$value_);

		$this->$attrib_ = $value_;

		return true;
	}

	public function isValid() {
// 		Logger::debug('main', 'Starting Token::isValid for \''.$this->id.'\'');

		if ($this->valid_until >= time())
			return true;

		return false;
	}
}
