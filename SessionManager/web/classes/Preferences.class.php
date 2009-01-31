<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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

class Preferences {
	public $prefs;
	protected $conf_file;
	private static $instance;
	public $prettyName;

	public function __construct(){
		$this->conf_file = SESSIONMANAGER_CONFFILE_SERIALIZED;
		$this->constructFromFile();
		$this->prettyName = array(
			'general' => _('General configuration'),
			'mysql' => _('MySQL configuration'),
			'plugins' => _('Plugins configuration'));
	}

	public static function getInstance() {
		if (!isset(self::$instance)) {
			try {
				self::$instance = new Preferences();
			} catch (Exception $e) {
				return false;
			}
		}
		return self::$instance;
	}

	public function getKeys(){
		return array_keys($this->elements);
	}

	public function get($container_,$container_sub_,$sub_sub_=NULL){
		if (isset($this->prefs[$container_])) {
			if (isset($this->prefs[$container_][$container_sub_])) {
				if (is_null($sub_sub_)) {
					$buf = $this->prefs[$container_][$container_sub_];
					if (is_array($buf) && (count(array_keys($buf)) == 1)) {
						$buf_keys = array_keys($buf);
						if ($buf_keys[0] === $container_sub_) {
							return $buf[$container_sub_];
						}
						else
							return $buf;

					}
					else
						return $buf;
				}
				else {
					if (isset($this->prefs[$container_][$container_sub_][$sub_sub_])) {
						$buf = $this->prefs[$container_][$container_sub_][$sub_sub_];
						return $buf->content;
					}
					else {
						return NULL;
					}
				}
			}
			else {
				return NULL;
			}

		}
		else {
// 			Logger::error('main','Preferences::get \''.$container_.'\' not found');
			return NULL;
		}
	}

	protected function constructFromFile(){
		if (!is_readable($this->conf_file))
			throw new Exception('Unable to read config file');

		$this->prefs = unserialize(file_get_contents($this->conf_file));
	}

	public function getPrettyName($key_) {
		if (isset($this->prettyName[$key_]))
			return $this->prettyName[$key_];
		else {
			return $key_;
		}
	}

}

class ConfigElement{
	public $id;
	public $label;
	public $description;
	public $description_detailed;
	public $content;
	public $content_available;
	public $type;

	static $TEXT = 0;
	static $INPUT = 1;
	static $SELECT = 2;
	static $MULTISELECT = 3;
	static $TEXTMAXLENGTH = 4;
	static $HIDDEN = 5;
	static $TEXTAREA = 6;
	static $PASSWORD = 7;
	static $INPUT_LIST = 8;
	static $SLIDERS = 9;

	public function __construct($id_, $label_, $description_, $description_detailed_, $content_, $content_available_, $type_){
		$this->id = $id_;
		$this->label = $label_;
		$this->description = $description_;
		$this->description_detailed = $description_detailed_;
		$this->content = $content_;
// 		$this->content_default = $content_default_;
		$this->content_available = $content_available_;
		$this->type = $type_;
	}

	public function __toString(){
		$str =  "'".$this->id."','".$this->label."','";
		if (is_array($this->content)) {
			$str .= 'array(';
			foreach($this->content as $k => $v)
				$str .= '\''.$k.'\' => \''.$v.'\' , ';
			$str .= ') ';
		}
		else
			$str .= $this->content;
		$str .=  "','";
		if (is_array($this->content_available)) {
			$str .= 'array(';
			foreach($this->content_available as $k => $v)
				$str .= '\''.$k.'\' => \''.$v.'\' , ';
			$str .= ') ';
		}
		else
			$str .= $this->content_available;
		$str .=  "','".$this->description."','".$this->description_detailed."','".$this->type."'";
		return $str;
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
}



