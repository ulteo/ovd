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


class CLNode extends Abstract_node{
	protected $id,$nodename,$attributes, $childs, $status;
	public function __construct() {
		$this->nodename="clone";
		$this->attributes=array();
		$this->childs=array();
	}
	public function get_node_resource_state($node_id) {
		return array("is_started"=>false,"is_failed"=>false);
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
	public function get_attributes_toARRAY() {return $this->attributes;}
	public function get_childs_toARRAY($id) {}
	public function setNodename($name) {}
	public function set_attribute($name,$value) {$this->attributes[$name]=$value;
	}
	public function set_child(& $child) {
		$this->childs[]=$child;
	}
	public function clear_attributes() {$this->attributes=array();}
	public function clear_childs() {$this->childs=array();}
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
	public function view_format_table($nodes) {
		$html="Not implemented";
		return $html;
	}
}
