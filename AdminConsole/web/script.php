<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
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
require_once(dirname(dirname(__FILE__)).'/includes/core.inc.php');
require_once(dirname(dirname(__FILE__)).'/includes/page_template.php');

if (! checkAuthorization('viewServers'))
	redirect('index.php');


if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'rename' && isset($_REQUEST['id'])) {
		if (! checkAuthorization('manageNews'))
			redirect();

		if (isset($_REQUEST['script_data'])) {
			$res = $_SESSION['service']->script_modify($_REQUEST['id'], $_REQUEST['script_name'], $_REQUEST['script_os'], $_REQUEST['script_type'], $_REQUEST['script_data']);
			if ($res === true) {
				popup_info(_('News successfully modified'));
			}
		}

		redirect();
	}

	if ($_REQUEST['action'] == 'manage' && isset($_REQUEST['id']))
		show_manage($_REQUEST['id']);
} else
	show_default();

function show_default() {
	$scripts = $_SESSION['service']->scripts_list();

	$can_manage_script = isAuthorized('manageScripts');

	page_header();
	echo '<div>'; // general div
	echo '<div id="script_div">';
	echo '<h1>'._('Scripts').'</h1>';

	echo '<div id="script_list_div">';
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	echo '<tr><th>'._('Name').'</th><th>'._('OS').'</th><th>'._('Type').'</th><th></th></tr>';

	foreach ($scripts as $script) {
		echo '<tr>';
		echo '<td><a href="script.php?action=manage&amp;id='.$script->id.'">'.$script->name.'</a></td>';
		echo '<td>'.$script->os.'</a></td>';
		echo '<td>'.$script->type.'</a></td>';
		echo '<td>';
		if ($can_manage_script) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this script?').'\');">';
			echo '<input type="hidden" name="name" value="Script" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="id" value="'.$script->id.'" />';
			echo '<input type="submit" value="'._('Delete this script').'" />';
			echo '</form></td>';
		}
		echo '</tr>';
	}

	echo '</table>';
	echo '</div>';

	echo '<br />';
	echo '<h2>'._('Add Script').'</h2>';

	echo '<div>';
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	if ($can_manage_script) {
		echo '<form action="actions.php" method="post" enctype="multipart/form-data">';
		echo '<input type="hidden" name="name" value="Script" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<tr><td><strong>'._('Name:').'</strong></td><td><input type="text" name="script_name" value="" /></td></tr>';
		echo '<tr><td><strong>'._('OS:').'</strong></td><td><select name="script_os"><option value="Windows"/>Windows</option><option value="Linux"/>Linux</option></select></td></tr>';
		echo '<tr><td><strong>'._('Type:').'</strong></td><td><select name="script_type" onchange="changeScriptAreaSyntax(this, \'scriptArea\')"><option value="bash"/>bash</option><option value="python">python</option><option value="VBS"/>vbs</option><option value="batch"/>batch</option><option value="powershell"/>powershell</option></select></td></tr>';
		echo '<tr><td></td><td><textarea id="scriptArea" name="script_data" cols="100" rows="20"></textarea></td></tr>';
		echo '<script language="javascript" type="text/javascript"> initScriptArea(\'scriptArea\') </script>';
		echo '<tr><td colspan="2">';
		echo '<input type="file"  name="script_file" onchange="loadFileAsText(this,\'scriptArea\')" /><BR/>';
		echo '<input type="submit" value="'._('Add this script').'" /></td></tr>';
		echo '</form>';
	}
	echo '</table>';
	echo '</div>';

	page_footer();

	die();
}

function show_manage($script_id_) {
	$script = $_SESSION['service']->script_info($script_id_);

	if (! is_object($script)) {
		redirect('script.php');
	}

	$can_manage_script = isAuthorized('manageNews');

	page_header();

	echo '<div id="script_div">';
	echo '<h1><a href="?">'._('Login Script management').'</a> - '.$script->name.'</h1>';

	echo '<div>';
	echo '<h2>'._('Modify').'</h2>';

	echo '<table border="0" cellspacing="1" cellpadding="3">';
	if ($can_manage_script) {
		$os_options = array("Windows", "Linux");
		$type_options = array("bash", "python", "VBS", "batch", "powershell");
		
		echo '<form action="script.php" method="post" enctype="multipart/form-data">';
		echo '<input type="hidden" name="action" value="rename" />';
		echo '<input type="hidden" name="id" value="'.$script->id.'" />';
		echo '<tr><td><strong>'._('Name:').'</strong></td><td><input type="text" name="script_name" value="'.$script->name.'" /></td></tr>';
		echo '<tr><td><strong>'._('OS:').'</strong></td><td><select name="script_os">';
		foreach($os_options as $os_option){
			echo '<option value="'.$os_option.'" ';
			if ($os_option == $script->os)
				echo 'selected="selected"';
			echo '/>'.$os_option.'</option>';
		}
		echo '</select></td></tr>';
		
		echo '<tr><td><strong>'._('Type:').'</strong></td><td><select name="script_type" onchange="changeScriptAreaSyntax(this, \'scriptArea\')">';
		foreach($type_options as $type_option){
			echo '<option value="'.$type_option.'" ';
			if ($type_option == $script->type)
				echo 'selected="selected"';
			echo '/>'.$type_option.'</option>';
		}
	
		echo '</select></td></tr>';
		echo '<tr><td></td><td><textarea name="script_data" id=\'scriptArea\' cols="100" rows="20">'.$script->data.'</textarea></td></tr>';
		echo '<script language="javascript" type="text/javascript"> initScriptArea(\'scriptArea\') </script>';
		echo '<tr><td colspan="2">';
		echo '<input type="file"  name="script_file" onchange="loadFileAsText(this,\'scriptArea\')"/><BR/>';
		echo '<input type="submit" value="'._('Modify').'" /></td></tr>';
		echo '</form>';
	}
	echo '</table>';

	echo '</div>';

	// User groups part
	$groups_mine = $script->getAttribute('groups');
	$groups_partial_list = $script->getAttribute('groups_partial_list');
	$usersgroupsList = new UsersGroupsList($_REQUEST);
	if ($groups_partial_list) {
		if ($usersgroupsList->is_empty_filter()) {
			$usersgroupsList->set_external_result($groups_mine, true);
		}
		else {
			$groups_mine2 = $usersgroupsList->search($login);
			if (is_null($groups_mine)) {
				die_error(_('Error while requesting User Group data'),__FILE__,__LINE__);
			}
			
			$groups_mine = array();
			foreach($groups_mine2 as $group) {
				$groups_mine[$group->id] = $group->name;
			}
		}
	}

	// do not request other groups if we do not display the 'add to' panel ...
	$groups_all = $usersgroupsList->search();
	usort($groups_all, "usergroup_cmp");
	$searchDiv = $usersgroupsList->getForm();
	$groups_available = array();
	foreach($groups_all as $group)
	if (! array_key_exists($group->id, $groups_mine))
		$groups_available[]= $group;

	echo '<div>';
	echo '<h2>'._('User Groups with this user').'</h2>';
	echo $searchDiv;
	
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	foreach ($groups_mine as $group_id => $group_name) {
		echo '<tr><td>';
		if ($can_manage_script) {
			echo '<a href="usersgroup.php?action=manage&id='.$group_id.'">'.$group_name.'</a>';
		}
		else {
			echo $group_name;
		}
		echo '</td>';
		if ($can_manage_script) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this user from this group?').'\');">';
			echo '<input type="hidden" name="name" value="Script_UserGroup" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="group" value="'.$group_id.'" />';
			echo '<input type="hidden" name="element" value="'.$script->id.'" />';
			echo '<input type="submit" value="'._('Delete from this group').'" />';
			echo '</form></td>';
		}
		echo '</tr>';
	}
	
	if ((count($groups_available) > 0) and $can_manage_script) {
		echo '<tr><form action="actions.php" method="post"><td>';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="hidden" name="name" value="Script_UserGroup" />';
		echo '<input type="hidden" name="element" value="'.$script->id.'" />';
		echo '<select name="group">';
		foreach($groups_available as $group)
			echo '<option value="'.$group->id.'" >'.$group->name.'</option>';
		echo '</select>';
		echo '</td><td><input type="submit" value="'._('Add to this group').'" /></td>';
	}
	echo '</form></tr>';
	echo '</table>';
	echo '</div>';
	echo '</div>';

	page_footer();
}
