<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Arnaud LEGRAND <arnaud@ulteo.com>
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

class CibNode extends Abstract_node{
	protected $id,$nodename,$attributes, $childs, $status;
	
	public function __construct() {
		$this->nodename="";
		$this->attributes=array();
		$this->childs=array();
		$this->attributes["is_master"]=false;
	}

	public function getNodename() {
		return $this->nodename;
	}

	public function get_attribute($n_attr) {
		if(isset($this->attributes[$n_attr])) {
			return $this->attributes[$n_attr];
		}
		return false;
	}

	public function get_attributes_toARRAY() {
		return $this->attributes;
	}

	public function setNodename($name) {
		$this->nodename=$name;
	}

	public function set_attribute($name,$value) {
		$this->attributes[$name]=$value;
	}

	public function set_child(& $child) {
		$this->childs[]=$child;
	}

	public function clear_attributes() {
		$this->attributes=array();
		$this->attributes["is_master"]=false;
	}

	public function clear_childs() {
		$this->childs=array();
	}

	public function has_attribute($attr_name) {
		if (isset($this->attributes[$attr_name])) {
			return true;
		}
		return false;
	}

	public function has_childs() {
		if (count($this->childs))
			return true;
		return false;
	}

	public function is_master() {
		return $this->attributes["is_master"];
	}

	public function view_format_table($cib) {
		$t=array();
		foreach($cib->get_nodes() as $nid => $n) {
			if(! $n->is_down()) {
				$t["".$nid]=$n->get_attribute("uname");
			}
		}
		$html="";
		if ($this->has_childs()) {
			foreach ($this->childs as $c) {
				$html.=$c->view_format_table($t);
			}
		}
		return $html;
	}

	public function is_down() {
		if ($this->get_attribute("ha") != "active") {return true;}
		if ($this->get_attribute("join") != "member") {return true;}
		if ($this->get_attribute("in_ccm") != "true") {return true;}
		if ($this->get_attribute("crmd") != "online") {return true;}
		return false;
	}
}
