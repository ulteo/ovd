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
class PRNode extends Abstract_node{
	protected $id,$nodename,$attributes, $childs, $nodes_states,$is_master,$is_slave;
	public function __construct() {
		$this->nodename="primitive";
		$this->attributes=array();
		$this->childs=array();
		$this->nodes_states=array();
		$this->is_master=false;
		$this->is_slave=false;
	}
	public function set_mastered_cooperation($m,$s) {
		$this->is_master=$m;
		$this->is_slave=$s;
	}
	public function set_node_state($node_id, $operation,$call_id,$rc_code,$op_status,$interval) {
		if(! isset($this->nodes_states["".$node_id])) {
			$this->nodes_states["".$node_id]=array();
			$this->nodes_states["".$node_id]["".$operation]=array("call_id"=>intval($call_id),"rc_code"=>intval($rc_code),"op_status"=>intval($op_status),"interval"=>intval($interval));
		}
		else{
			if(!isset($this->nodes_states["".$node_id]["".$operation])) {
				$this->nodes_states["".$node_id]["".$operation]=array("call_id"=>intval($call_id),"rc_code"=>intval($rc_code),"op_status"=>intval($op_status),"interval"=>intval($interval));
			}
			else{
				if($this->nodes_states["".$node_id]["".$operation]["call_id"] > intval($call_id)) {
					$this->nodes_states["".$node_id]["".$operation]=array("call_id"=>intval($call_id),"rc_code"=>intval($rc_code),"op_status"=>intval($op_status),"interval"=>intval($interval));
				}
			}
		}
	}
	public function get_node_resource_state($node_id) {
		$node_states=& $this->nodes_states["".$node_id];
		$is_started=false;
		$is_failed=false;
		$tmp_call_id=0;
		if (isset($node_states["start"]) && isset($node_states["stop"])) {
			if (intval($node_states["start"]["call_id"])>intval($node_states["stop"]["call_id"])) {
				$is_started=true;
				$tmp_call_id=$node_states["start"]["call_id"];
			}
			else{
				$tmp_call_id=$node_states["stop"]["call_id"];
			}
		}
		else{
			if (isset($node_states["start"]) && !isset($node_states["stop"])) {
				$is_started=true;
			}
		}
		if(isset($node_states["asyncmon"])) {
			if($node_states["asyncmon"]["call_id"]>$tmp_call_id) {
				$is_failed=true;
			}
		}
		return array("is_started"=>$is_started,"is_failed"=>$is_failed,"is_master"=>$this->is_master,"is_slave"=>$this->is_slave);
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
	public function set_attribute($name,$value) {
		$this->attributes["".$name]=$value;
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
		return false;
	}
	public function view_format_table($nodes) {
		$html='<tr class="content1"><td> &rarr; '._('Resources configured');
		$started=' <span class="ha_resource_started">'._('Started on').' [';
		$stopped=' <span class="ha_resource_stopped">'._('Stopped on').' [';
		$failed=' <span class="ha_resource_failed">'._('Failed on').' [';
		$started_tab=array();
		$stopped_tab=array();
		$failed_tab=array();
		if(isset($this->attributes["id"])) {
			$html.="  <strong>".$this->attributes["id"]."</strong>";
			foreach($nodes as $node_id => $node_name) {
				$tmp="";
				$states=$this->get_node_resource_state($node_id);
				if ($states["is_started"]) {
					$started_tab[]="".$node_name;
				}
				else{
					$stopped_tab[]="".$node_name;
				}
				if ($states["is_failed"]) {
					$failed_tab[]="".$node_name;
				}
			}
		}
		$stopped_tab=array_unique($stopped_tab);
		$failed_tab=array_unique($failed_tab);
		$started_tab=array_diff(array_unique($started_tab),$failed_tab);
		if(count($started_tab)) {$html.=$started.implode(",",$started_tab).']';}
		if(count($failed_tab)) {$html.=$failed.implode(",",$failed_tab).']';}
		$html.='</td></tr>';
		return $html;
	}

}
