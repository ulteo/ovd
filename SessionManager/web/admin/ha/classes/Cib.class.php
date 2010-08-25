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

class Cib {
	protected $xml, $dom, $logs, $crm_config, $nodes, $dc_uuid, $cib_time_old, $resources_tree, $resources_index, $resources_status_index, $logs_failed, $count_master;

	public function __construct() {
		$this->xml=new DomDocument();
		$this->crm_config=new CibNode();
		$this->nodes=array();
		$this->resources_tree=new CibNode();
		$this->dc_uuid=_("Unknown");
		$this->cib_time_old=_("Unknown");
		$this->resources_index=array();
		$this->resources_status_index=array();
		$this->logs_failed=array();
		$this->count_master=array();
	}

	public function load_cib() {
		$res=ShellExec::exec_cibadmin();
		if (count($res)<2) {
			Logger::error('ha', "cib.class.php::get_cib() - An error has occured, please check permissions on ulteo-ovd.conf or if 'cibadmin -Q' work fine");
			return false;
		}
		$this->xml->loadXML(implode("", $res));
		$racine = $this->xml->documentElement;
		$this->get_gen_attribute();
		return true;
	}

	function get_gen_attribute() {
		$racine = $this->xml->documentElement;
		if($racine->hasAttribute("dc-uuid")) {
			$this->dc_uuid=$racine->getAttribute("dc-uuid");
		}
		else {
			Logger::error('ha',"cib.class.php::get_attribute() - An error has occured, no dc_uuid found at rootnode".$racine->nodeName); 
		 }
	}
	
	function init_master() {
		foreach (array_keys($this->nodes) as $c) {
			$this->count_master[(String)$c]=0;
		}
		 
	}

	public function get_producer() {
		$master=false;
		$score_master=0;
		foreach ($this->count_master as $n => $v) {
			if($v>$score_master) {$score_master=$v; $master=$n;}
		}
		return $master;
	}
	
	public function get_resources_tree() {
		return $this->resources_tree;
	}
	
	public function extract_crm_config() {
		$n=$this->xml->getElementsByTagName('cluster_property_set')->item(0);
		$this->crm_config->setNodename('cluster_property_set');
		$this->extract_attributes($this->crm_config,$n);
	}
	
	public function extract_nodes() {
		$n_nodes=$this->xml->getElementsByTagName('node');
		foreach ($n_nodes as $c) {
			if ($c->nodeType == XML_ELEMENT_NODE) {
				if($c->hasAttribute("id")) {
					$id="".$c->getAttribute("id");
					$cnode_tmp=new CibNode();
					$cnode_tmp->setNodename("node");
					$this->extract_attributes($cnode_tmp,  $c);
					$this->nodes[(String)$id]= $cnode_tmp;
				}
			}
		}
		$n_nodes=$this->xml->getElementsByTagName('node_state');
		foreach ($n_nodes as $c) {
			if ($c->nodeType == XML_ELEMENT_NODE) {
				if($c->hasAttribute("id")) {
					$id=$c->getAttribute("id");
					if (isset($this->nodes[(String)$id])) {
						$cnode_tmp=& $this->nodes[(String)$id];
						$this->extract_rootnode_attributes($cnode_tmp,  $c);
					}
					else{
						Logger::error('ha',"cib.class.php::extract_nodes() - cibnode instance ".$id." doesn't exist");
					}
				}
			}
		}
		$this->init_master();
	}
	
	public function extract_resources() {
		$n_resource=$this->xml->getElementsByTagName('resources')->item(0);
		$this->resources_tree->setNodename("resources");
		$this->extract_resource($this->resources_tree,$n_resource);
	}
	
	function extract_resource(& $cibnode,$n) {
		foreach($n->childNodes as $c) {
			if ($c->nodeType == XML_ELEMENT_NODE ) {
				$prim=false;
				unset($node_tmp);
				switch($c->nodeName) {
					case "primitive":
						$prim=true;
						$node_tmp=new PRNode();
						$node_tmp->setNodename($c->nodeName);
						$this->extract_attributes($node_tmp,  $c);
						$cibnode->set_child($node_tmp);
						if($node_tmp->has_attribute("id")) {
							$nid = (String)$node_tmp->get_attribute("id");
							$this->resources_index[$nid]= & $node_tmp;
						}
						break;
					
					case "group" :
						$node_tmp=new GRNode();
						$node_tmp->setNodename($c->nodeName);
						$this->extract_attributes($node_tmp,  $c);
						$cibnode->set_child($node_tmp);
						$this->extract_resource($node_tmp,$c);
						break;
					
					case "master":
						$node_tmp=new MSNode();
						$node_tmp->setNodename($c->nodeName);
						$this->extract_attributes($node_tmp,  $c);
						$cibnode->set_child($node_tmp);
						$this->extract_resource_master($node_tmp,$c);
						break;
					
					case "clone":
						$node_tmp=new CLNode();
						$node_tmp->setNodename($c->nodeName);
						$this->extract_attributes($node_tmp,  $c);
						$cibnode->set_child($node_tmp);
						$this->extract_resource($node_tmp,$c);
						break;
					default:
						break;
				}
			}
		}
	}
	
	function extract_resource_master(& $cibnode,$n) {
		
		foreach($n->childNodes as $c) {
			if ($c->nodeType == XML_ELEMENT_NODE ) {
				$prim=false;
				unset($node_tmp);
					if ($c->nodeName == "primitive") {
						$prim=true;
						$node_tmp=new PRNode();
						$node_tmp->setNodename($c->nodeName);
						$this->extract_attributes($node_tmp,  $c);
						$tid=$node_tmp->get_attribute("id");
						$id_node_m=$tid.":0";
						$id_node_s=$tid.":1";
						$node_tmp->set_attribute("id",$id_node_m);
						$node_tmp->set_mastered_cooperation(true,false);
						$cibnode->set_child($node_tmp);
						$this->resources_index[$id_node_m]= & $node_tmp;
						$node_tmp2=new PRNode();
						$node_tmp2->setNodename($c->nodeName);
						$this->extract_attributes($node_tmp2,  $c);
						$node_tmp2->set_attribute("id",$id_node_s);
						$node_tmp2->set_mastered_cooperation(false,true);
						$cibnode->set_child($node_tmp2);
						$this->resources_index[$id_node_s]= & $node_tmp2;
					}
			}
		}
	}
	
	function extract_status() {
		$s_nodes=$this->xml->getElementsByTagName('node_state');
		foreach ($s_nodes as $c) {
			if ($c->nodeType == XML_ELEMENT_NODE) {
				if($c->hasAttribute("id")) {
					$node_id=$c->getAttribute("id");
					$this->extract_lrm_resource_node($node_id,$c);
				}
			}
		}
		foreach($this->resources_index as $n=> $res) {
			foreach(array_keys($this->nodes) as $node) {
				$tmp= $res->get_node_resource_state($node);
				$is_started="not started";
				$is_failed="not failed";
				if($tmp["is_started"]) {
					$is_started="STARTED";
					$this->count_master[(String)$node]+=1;
				}
				if($tmp["is_failed"]) {
					$is_failed="FAILED";
				}
				unset($tmp);
			}
			unset($res);
		}
		
	}
	
	function extract_lrm_resource_node($node_id,  $s_nodes) {
		$lrm_nodes=$s_nodes->getElementsByTagName('lrm_resources')->item(0);
		if (count($lrm_nodes)) {
			foreach ($lrm_nodes->childNodes as $c) {
				if ($c->nodeType == XML_ELEMENT_NODE) {
					if ($c->hasAttribute("id")) {
						$resource_id=$c->getAttribute("id");
						if (isset($this->resources_index[(String)$resource_id])) {
							$tmp_cibnode=& $this->resources_index[(String)$resource_id];
							foreach ($c->childNodes as $op) {
								if ($op->nodeType == XML_ELEMENT_NODE) {
									$op_attrs=$this->extract_xml_resource_node_attributes_to_array($op);
									if (count($op_attrs)>0) {
										if($op_attrs["operation"]== "asyncmon") {
												$op_attrs["node"]=$this->nodes[(String)$node_id]->get_attribute("uname");
												$op_attrs["time"]= date('r');
												$op_attrs["resource"]=$resource_id;
												$this->logs_failed[]=$op_attrs;
										}
										$tmp_cibnode->set_node_state($node_id,$op_attrs["operation"],$op_attrs["call_id"],$op_attrs["rc_code"],$op_attrs["op_status"],$op_attrs["interval"]);
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	function extract_xml_resource_node_attributes_to_array($n_op) {
		$attributes_array=array();
		if($n_op->hasAttributes()) {
			if($n_op->hasAttribute("operation") && $n_op->hasAttribute("call-id") && $n_op->hasAttribute("rc-code") && $n_op->hasAttribute("op-status") && $n_op->hasAttribute("interval") ) {
				$attributes_array["operation"]=$n_op->getAttribute("operation");
				$attributes_array["call_id"]=$n_op->getAttribute("call-id");
				$attributes_array["rc_code"]=$n_op->getAttribute("rc-code");
				$attributes_array["op_status"]=$n_op->getAttribute("op-status");
				$attributes_array["interval"]=$n_op->getAttribute("interval");
				return $attributes_array;
			}
		}
		return array();
	}
	
	function extract_rootnode_attributes(& $cibnode,  $n) {
		if($n->hasAttributes()) {
			$attrs = $n->attributes;
			foreach ($attrs as $i => $attr) {
				$cibnode->set_attribute($attr->name, $attr->value); 
			}
		}
	}

	function extract_attributes(& $cibnode,$n) {
		$this->extract_rootnode_attributes( $cibnode,  $n);
		foreach($n->childNodes as $c) {
			if ($c->nodeType == XML_ELEMENT_NODE) {
				if ($c->nodeName=="instance_attributes" || $c->nodeName=="meta_attributes") {
					$this->extract_nvpairs_to_attributes(& $cibnode,$c);
				}
				if ($c->nodeName=="nvpair") {
					$this->extract_nvpair_to_attribute(& $cibnode,$c);
				}
			}
        }
	}
	
	function extract_nvpairs_to_attributes(& $cibnode,$n) {
		$nvpairs=$n->getElementsByTagName("nvpair");
		foreach ($nvpairs as $c) {
			if ($c->nodeType == XML_ELEMENT_NODE) {
				$this->extract_nvpair_to_attribute(& $cibnode,$c);
			}
		}
	}

	function extract_nvpair_to_attribute(& $cibnode,$n) {
		if($n->hasAttribute("name") && $n->hasAttribute("value")) {
			$cibnode->set_attribute($n->getAttribute("name"), $n->getAttribute("value"));
		}
	}
	
	public function get_logs_toARRAY() {
		return $this->logs_failed;
	}
	
	public function get_cib_time() {return $this->cib_time_old;}
	public function get_dc_uuid() {
		if (isset($this->nodes[(String)$this->dc_uuid])) {
			if ($this->nodes[(String)$this->dc_uuid]->get_attribute("uname")) {
				return $this->nodes[(String)$this->dc_uuid]->get_attribute("uname");
			}
		}
		return _("Unknown");
	}
	public function get_nb_nodes_conf() {return count(array_keys($this->nodes));}
	public function get_nb_resources_conf() {
		return count(array_keys($this->resources_index));
	}
	
	public function get_nodes() {
		return $this->nodes;
	}
}
