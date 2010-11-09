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

require_once(dirname(__FILE__).'/../includes/core.inc.php');
require_once(dirname(__FILE__).'/../includes/page_template.php');

require_once(dirname(__FILE__).'/classes/Abstract_node.class.php');
require_once(dirname(__FILE__).'/classes/CLNode.class.php');
require_once(dirname(__FILE__).'/classes/CibNode.class.php');
require_once(dirname(__FILE__).'/classes/GRNode.class.php');
require_once(dirname(__FILE__).'/classes/MSNode.class.php');
require_once(dirname(__FILE__).'/classes/PRNode.class.php');
require_once(dirname(__FILE__).'/classes/Cib.class.php');
require_once(dirname(__FILE__).'/classes/ShellExec.class.php');

if (! checkAuthorization('viewStatus'))
	redirect('../index.php');
$cmd=false;
$action=false;
if(isset($_POST["action"])) {
	$action=$_POST["action"];
	unset($_POST["action"]);
	switch($action) {
		case "standby":
			if(isset($_POST["node"])) {
				$ret=ShellExec::exec_action_to_host($_POST["node"],"on");
			}
			break;
		case "online":
			if(isset($_POST["node"])) {
				$ret=ShellExec::exec_action_to_host($_POST["node"],"off");

			}
			break;
		case "cleanup":
			if(isset($_POST["resource"])) {
				  action_to_resources($_POST["resource"]);
			}
			break;
		default:
			unset($_POST["action"]);
			Logger::error('ha', "status.php::bad action command, hacking attempt with action '".$action."'!");
	}
	unset($_POST["node"]);
	unset($_POST["resource"]);
}

function action_to_resources($resource) {
	Logger::warning('ha', "status.php::cleaning up resource requested for : '".$resource."'");
	$ret=ShellExec::exec_cleanup_resource($resource);
}

function action_to_hosts($action, $host) {
	Logger::warning('ha', "status.php::action to host requested standby='".$action."' to host '".$host."'");
	$ret=ShellExec::exec_action_to_host($action,$host);
}

function view_general_informations($cib) {
	echo '<table style="width: 100%;" class="main_sub" border="0" cellpadding="3" cellspacing="1">';
	echo '<tr class="title"><th colspan="2" style="text-transform:uppercase;">'._('General Status').'</th></tr>';
	echo '<tr class="content1"><td style="width: 150px;">';
	echo '<span onmouseover="showInfoBulle(\''._('Last time update of this page and included information').'\'); return false;" onmouseout="hideInfoBulle(); return false;">'._('Last update').'</span>';
	echo '</td><td>';
	echo '<b style="color:#FF0000;">'.date("j F Y, H:i:s").'</b>'; 
	echo '</td></tr>';
	echo '<tr class="content2"><td>';
	echo '<span onmouseover="showInfoBulle(\''._('The DC (Designated Coordinator) has elected a node in the cluster. It is responsible for actions of each cluster element.').'\'); return false;" onmouseout="hideInfoBulle(); return false;">'._('Current DC').'</span>';
	echo '</td><td style="padding: 3px;">';
	echo $cib->get_dc_uuid();
	echo '</td></tr>';
	echo '<tr class="content1"><td style="width: 150px;">';
	echo '<span onmouseover="showInfoBulle(\''._('Number of nodes provided and configured in the cluster').'\'); return false;" onmouseout="hideInfoBulle(); return false;">'._('Nodes configured').'</span>';
	echo '</td><td>';
	echo $cib->get_nb_nodes_conf();
	echo '</td></tr>';
	echo '<tr class="content2"><td>';
	echo '<span onmouseover="showInfoBulle(\''._('Number of resources configured for all nodes').'\'); return false;" onmouseout="hideInfoBulle(); return false;">'._('Number of resources configured for all nodes').'</span>';
	echo '</td><td>';
	echo $cib->get_nb_resources_conf();
	echo '</td></tr>';
	echo '</table>';
	echo '<br />';
}

function view_resources_status($cib) {
	echo '<table style="width: 100%;" class="main_sub" border="0" cellpadding="3" cellspacing="1">';
	echo '<tr class="title"><th colspan="2" style="text-transform:uppercase;">'._('Ressources Status').'</th></tr>';
	$r_tree=& $cib->get_resources_tree();
	echo $r_tree->view_format_table($cib);
	echo '</table>';
	echo '<br />';
}

function view_resources_errors($errors) {
	echo '<table style="width: 100%;" class="main_sub" border="0" cellpadding="3" cellspacing="1" width="100%">';
	echo '<tr class="title"><th colspan="2" style="text-transform:uppercase;">'._('Ressources errors').'</th></tr>';
	foreach ($errors as $error){
		echo '<tr class="content1"><td>';
		echo $error["time"]." Resource ".$error["resource"]." FAILDED on ".$error["node"].", rc-code=".$error["rc_code"];
		echo '</td><td width="1%"> ';
		echo '<form name="clean_resources_errors" action="status.php" method="post"><input type="hidden" name="resource" value="'.$error["resource"].'" /><input type="hidden" name="action" value="cleanup" /><input type="submit" value="cleanup" />';
		echo '</form></td></tr>';
	}
	echo '</table>';
}

function view_node_box_status($nid,$node,$is_master) {
	$node_status="down";
	$node_status_str=_('Out of Network');
	$node_online=false;
	$node_online_str=_('Out of Network');
	$node_vignet="down";
	$node_name=false;
	$node_standby=false;
	$node_name=$node->get_attribute("uname");
	if($node->has_attribute("in_ccm") && $node->has_attribute("ha") &&  $node->has_attribute("join") && $node->has_attribute("crmd") ) {
		if($node->get_attribute("crmd") == "online" && $node->get_attribute("in_ccm") =="true") {
			if( $node->get_attribute("ha") == "active" && $node->get_attribute("join") == "member") {
				$node_online=true;
				$node_vignet="online";
				$node_status="online";
				if ($is_master) {
					 $node_online_str=_('Online');
					 $node_status_str=_('Production');
				}
				else {
					$node_online_str=_('Online');
					$node_status_str=_('Ready'); 
				}
			}
			if($node->has_attribute("standby")) {
				if($node->get_attribute("standby") == "on") {
					$node_standby=true;
					$node_vignet="standby";
					$node_status="standby";
					$node_status_str=_('Standby');
				}
			}
		}
		else {
			$node_online=false;
			$node_vignet="down";
			$node_status="down";
			$node_online_str=_('Out of Network');
			$node_status_str=_('Out of Network');
			if($node->has_attribute("standby")){
				if($node->get_attribute("standby") == "on") {
					$node_standby=true;
				}
			}
		}
	}
	else{
		return _('An error has occured, please call your administrator!');
	}
	echo '<div class="host_status"><table cellpadding="3" cellspacing="0" border="0" width="100%" class="host_status '.$node_status.'"><tr class="host_title"><td>';
	echo '<span class="status">'.$node_status_str.'</span> ';
	echo '<span class="host_name"><span class="mod_ha_sm">'._('Session Manager').'</span> '.$node_name.'</span>';
	echo '</td></tr>';
	echo '<tr><td>';
	echo '<table cellpadding="0" cellspacing="0" border="0" width="100%" class="ha_status_content"><tr><td width="1%"><img src="media/image/ha_'.$node_vignet.'.png" class="host_status_img" height="63" /></td><td>';
	echo '<div class="host_status_element"><span>'.$node_online_str." / ".$node_status_str.'</span></div>';
	if (! $node_standby) {
		echo '<div class="host_status_element"><span>'._('Unpaused').'</span></div>';
	} else{
		 echo '<div class="host_status_element"><span>'._('Paused').'</span></div>';
	}
	echo '<div class="host_status_element"><span>'._('ID').' : '.$nid.'</span></div>';
	echo '</td></tr></table>';
	if ($node_name && $node_online && (! $node_standby)) {
		echo '<div class="host_status_buttons">';
		echo '<form name="manage_node" action="status.php" method="post">';
		echo '<input type="hidden" name="action" value="standby" />';
		echo '<input type="hidden" name="node" value="'.$node_name.'" />';
		echo '<input type="submit" value="'._('Pause').'" class="online" /></form>';
		echo '</div>';
	}
	if ($node_name && $node_online && ( $node_standby)) {
		echo '<div class="host_status_buttons">';
		echo '<form name="manage_node" action="status.php" method="post">';
		echo '<input type="hidden" name="action" value="online" />';
		echo '<input type="hidden" name="node" value="'.$node_name.'" />';
		echo '<input type="submit" value="'._('Set it online').'" class="online" /></form>';
		echo '</div>';
	}
	echo '</td></tr></table></div>';
}

$cib_logs_ha=array();
$cib= new Cib();
if ($cib->load_cib()) {
	$cib->extract_crm_config();
	$cib->extract_nodes();
	$cib->extract_resources();
	$cib->extract_status();
	show_default($cib);
}
else {
	show_error();
}

function show_default($cib) {
	$cib_logs_ha=$cib->get_logs_toARRAY();
	foreach($cib_logs_ha as $log){
				Logger::error('ha', $log["time"]." Resource ".$log["resource"]." FAILDED on ".$log["node"].", rc-code=".$log["rc_code"]);
	}
	page_header();
	echo '<link rel="stylesheet" type="text/css" href="media/style/media-ha.css" />';
	echo '<script type="text/javascript" charset="utf-8">Event.observe(window, \'load\', function() { setTimeout(function() { location.reload(true); }, 5000); });</script>';
	echo '<div>';
	echo '<h1>'._('High Availability Status').'</h1>';
	echo '<table cellpadding="0" cellspacing="0" border="0" width="100%" id="ha_main">';
	echo '<tr id="ha_main_row">';
	echo '<td width="43%" id="ha_main_left_column">';
	view_general_informations($cib);
	view_resources_status($cib);
	if (count($cib_logs_ha)){
		view_resources_errors($cib_logs_ha);
	}
	echo '</td><td id="ha_main-separator">';
	echo '<td id="ha_main_right_column">';
	$master=$cib->get_producer();
	foreach($cib->get_nodes() as $nid => $n) {
		$is_master=false;
		if($master == $nid){$is_master=true;}
		view_node_box_status($nid, $n,$is_master);
	}
	echo '</td>';
	echo '</tr>';
	echo '</table>';
	echo '</div>';
	page_footer();
}
function show_error() {
	page_header();
	echo '<div>';
	echo '<link rel="stylesheet" type="text/css" href="media/style/media-ha.css" />';
	echo '<div class="ha_error_cib"><div class="ha_error_txt_block"><h2>'._('Error').'</h2><div class="ha_error_loader"><p><b>'._("Waiting for Heartbeat's CIB...").'</b><br />'._('Please check permissions on ulteo-ovd.conf or if "cibadmin -Q" works fine! Apache could have been started by another user than www-data.').'</p><div><img src="media/image/loader.gif" /></div></div></div></div>';
	echo '<script type="text/javascript" charset="utf-8">Event.observe(window, \'load\', function() { setTimeout(function() { location.reload(true); }, 5000); });</script>';
	echo '</div>';
	page_footer();
}
die();
