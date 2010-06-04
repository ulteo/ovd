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
// require_once(dirname(__FILE__).'/../admin/includes/core.inc.php');

abstract class ConfigElement{
	public $id;
	public $label;
	public $description;
	public $description_detailed;
	public $content;
	public $content_available;
	public $formSeparator='';
	public $path=array();

	abstract public function toHTML($readonly=false);

	public function __construct($id_, $label_, $description_, $description_detailed_, $content_){
		$this->id = $id_;
		$this->label = $label_;
		$this->description = $description_;
		$this->description_detailed = $description_detailed_;
		$this->content = $content_;
// 		$this->content_available = $content_available_;
// 		$this->type = $type_;
	}
	public function __toString(){
		$str =  '<strong>'.get_class($this)."</strong>( '".$this->id."','".$this->label."','";
		$str .=  '<strong>';
		if (is_array($this->content)) {
			$str .= 'array(';
			foreach($this->content as $k => $v)
				$str .= '\''.$k.'\' => \''.$v.'\' , ';
			$str .= ') ';
		}
		else
			$str .= $this->content;
		$str .=  '</strong>';
		$str .=  "','";
		if (is_array($this->content_available)) {
			$str .= 'array(';
			foreach($this->content_available as $k => $v)
				$str .= '\''.$k.'\' => \''.$v.'\' , ';
			$str .= ') ';
		}
		else
			$str .= $this->content_available;
		$str .=  "','".$this->description."','".$this->description_detailed."'";
		$str .= ')';
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

	public function setPath($path_) {
		$this->path = $path_;
	}

	public function setFormSeparator($sep_) {
		$this->formSeparator = $sep_;
	}

	public function setContentAvailable($content_available_) {
		$this->content_available = $content_available_;
	}

	protected function htmlID() {
		$html_id = '';
		foreach ($this->path as $node) {
			$html_id .= $node.$this->formSeparator;
		}
		$html_id = substr($html_id, 0, (strlen($this->formSeparator)*-1));
		return $html_id;
	}
}



