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

class MSNode extends Abstract_node{
	
	protected $id,$nodename,$attributes, $childs, $status;
	
	public function __construct() {
		$this->nodename="master";
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
	public function setNodename($name) {}
	public function set_attribute($name,$value) {
		$this->attributes[$name]=$value;
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
		$html='<tr class="content2"><td>'._('Master/Slaves');
		if(isset($this->attributes["id"]))
			$html.="  <strong>".$this->attributes["id"]."</strong>";
		$html.='</td></tr>';
		$masters=' <span class="ha_resource_master">'._('Master').' [';
		$slaves=' <span class="ha_resource_slave">'._('Slaves').' [';
		$stopped=' <span class="ha_resource_stopped">'._('Stopped').' [';
		$failed=' <span class="ha_resource_failed">'._('Failed').' [';
		$masters_tab=array();
		$slaves_tab=array();
		$stopped_tab=array();
		$failed_tab=array();
		if ($this->has_childs()) {
			$master=false;
			$slave=false;
			foreach ($this->childs as $c) {
				foreach($nodes as $node_id => $node_name) {
					$tab=$c->get_node_resource_state($node_id);
					if ($tab["is_started"]) {
						if ($tab["is_master"]) {
							$masters_tab[]=$node_name;
						}
						if ($tab["is_slave"]) {
							$slaves_tab[]=$node_name;
						}
						if ($tab["is_failed"]) {
							$failed_tab[]=$node_name;
						}
					}
					else {
						if ($tab["is_failed"]) {
							$failed_tab[]=$node_name;
						}
						else {
							if (true) {
								$stopped_tab[]=$node_name;
							}
						}
					}
				}
				$failed_tab=array_unique($failed_tab);
				$masters_tab=array_diff(array_unique($masters_tab),$failed_tab);
				$slaves_tab=array_diff(array_unique($slaves_tab),$failed_tab);
				$stopped_tab=array_unique($stopped_tab);
				
				if($tab["is_master"]) {
					$html.='<tr class="content1"><td> &rarr; '._('Resources configured').' <span>'.$c->get_attribute("id").'</span> ';
					if(count($masters_tab)) {$html.=$masters.implode(",",$masters_tab).']';}
					else{$html.='<span class="ha_resource_stopped">'._('No master').'</span>';}
					if(count($failed_tab)) {$html.=$failed.implode(",",$failed_tab).']';}
					
					$html.='</td></tr>';
				}
				if($tab["is_slave"]) {
					$html.='<tr class="content1"><td> &rarr; '._('Resources configured').' <span>'.$c->get_attribute("id").'</span> ';
					if(count($slaves_tab)) {$html.=$slaves.implode(",",$slaves_tab).']';}
					else{$html.='<span class="ha_resource_stopped">'._('No slaves').'</span>';}
					if(count($failed_tab)) {$html.=$failed.implode(",",$failed_tab).']';}
					
					$html.='</td></tr>';
				}
			}
		}
		return $html;
	}
}
