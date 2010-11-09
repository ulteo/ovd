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

function view_log($log) {
	echo '<div class="mod_ha_logbar">';
	echo '<span class="mod_ha_log_date">'.$log[0].' '.$log[1].' '.$log[2].'</span>';
	echo '<span class="mod_ha_log_host">'.$log[3].'</span>';
	$grv=strtoupper(str_replace(":","",$log[5]));
	echo '<span class="mod_ha_log_gravity mod_ha_'.strtolower($grv).'">'.$grv.'</span>';
	echo '<span class="mod_ha_log_info">'.$log[6].'</span>';
	echo "</div>";

}
$cmd=false;
$action=false;
$severity=array("ERROR");
$warn=0;
$info=0;
if(isset($_POST["action"])) {
	$action=$_POST["action"];
	unset($_POST["action"]);
	if($action == "cleanup_logs") {
		$res=ShellExec::exec_clean_logs();
	}
}
if (isset($_GET["warn"])) {
		$severity[]="WARN";
		$warn=1;
}
if (isset($_GET["info"])) {
		$severity[]="info";
		$info=1;
}
page_header();
echo '<div>';
echo '<h1>'._('High Availability logs').'</h1>';
echo '<link rel="stylesheet" type="text/css" href="media/style/media-ha.css" />';
$res=ShellExec::exec_view_logs($severity);
echo '<table cellpadding="0" cellspacing="3" border="0"><tr><td>';
echo '<form name="mod_ha_logs" action="logs.php" method="get">';
if ($warn) {
	echo '<input type="checkbox" name="warn" value="1" checked="checked" /> '._('Show alerts').' ';
}
else {
	echo '<input type="checkbox" name="warn" value="1" /> '._('Show alerts').' ';
}
if ($info) {
	echo '<input type="checkbox" name="info" value="1" checked="checked" /> '._('Show information').' ';
}
else{
	echo '<input type="checkbox" name="info" value="1" /> '._('Show informations').' ';
}
echo '<input type="submit" value="reload" /> ';
echo '</form></td><td>';
echo '<form name="mod_ha_logs" action="logs.php" method="post">';
echo '<input type="hidden" name="action" value="cleanup_logs" />';
echo '<input type="submit" value="'._('Cleanup logs').'" />';
echo '</form></td></tr></table>';
echo '<div><span class="mod_ha_log_gravity mod_ha_host"> '.sprintf(_('%s logs'),count($res)).'</span></div><br />';
if (count($res)) {
	foreach ($res as $line) {
		$tmp=explode(' ',$line);
		$log=array();
		for($i=0; $i<6; $i++) {
			$log[$i]=$tmp[0];
			array_shift($tmp);
		}
		$log[6]=implode(" ",$tmp);
		unset($tmp);
		view_log($log);
	}
}
echo '</div>';
page_footer();
