<?php
/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
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
require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once(dirname(__FILE__).'/includes/page_template.php');

if (! checkAuthorization('viewSharedFolders'))
	redirect('index.php');


if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'manage' && isset($_REQUEST['id']))
		show_manage($_REQUEST['id']);
} else
	show_default();

function show_default() {
	$sharedfolders = $group = $_SESSION['service']->shared_folders_list();
	if (is_null($sharedfolders))
		$sharedfolders = array();
	
	$can_manage_sharedfolders = isAuthorized('manageSharedFolders');
	$can_manage_configuration = isAuthorized('manageConfiguration');

	page_header();

	echo '<div id="sharedfolders_div">';
	echo '<h1>'._('Shared folders').'</h1>';

	echo '<div id="sharedfolders_list_div">';
	echo '<table border="0" cellspacing="1" cellpadding="3">';

	foreach ($sharedfolders as $sharedfolder) {
		echo '<tr>';
		echo '<td><a href="sharedfolders.php?action=manage&amp;id='.$sharedfolder->id.'">'.$sharedfolder->name.'</a></td>';
		if ($can_manage_sharedfolders) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this shared folder?').'\');">';
			echo '<input type="hidden" name="name" value="SharedFolder" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="ids[]" value="'.$sharedfolder->id.'" />';
			echo '<input type="submit" value="'._('Delete this shared folder').'" />';
			echo '</form></td>';
		}
		echo '</tr>';
	}

	if ($can_manage_sharedfolders) {
		echo '<tr><form action="actions.php" method="post"><td>';
		echo '<input type="hidden" name="name" value="SharedFolder" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="text" name="sharedfolder_name" value="" />';
		echo '</td><td><input type="submit" value="'._('Create this shared folder').'" /></td>';
		echo '</form></tr>';
	}

	echo '</table>';
	echo '</div>';

	echo '</div>';

	page_footer();

	die();
}

function show_manage($sharedfolder_id_) {
	$sharedfolder = $group = $_SESSION['service']->shared_folder_info($sharedfolder_id_);
	if (is_null($sharedfolder)) {
		popup_error(sprintf(_("Failed to import shared folder '%s'"), $sharedfolder_id_));
		redirect('sharedfolders.php');
	}
	
	$server_displayname = $sharedfolder->server;
	
	$server =  $_SESSION['service']->server_info($sharedfolder->server);
	if (! is_null($server)) {
		$server_displayname = $server->getDisplayName();
	}
	
	$usersgroupsList = new UsersGroupsList($_REQUEST);
	$all_groups = $usersgroupsList->search();
	if (! is_array($all_groups)) {
		$all_groups = array();
		popup_error(_("Failed to get users groups list"));
	}
	usort($all_groups, "usergroup_cmp");
	$searchDiv = $usersgroupsList->getForm(array('action' => 'manage', 'id' => $sharedfolder_id_));

	$available_groups = array();
	$used_groups = array();
	if ($sharedfolder->hasAttribute('groups')) {
		$used_groups = array();
		$mods_by_group = array();
		$groups2 = $sharedfolder->getAttribute('groups');
		foreach($groups2 as $mode => $groups3) {
			foreach($groups3 as $group_id => $group_name) {
				$used_groups[$group_id] = $group_name;
				$mods_by_group[$group_id] = $mode;
			}
		}
	}
	foreach ($all_groups as $group) {
		if (array_key_exists($group->id, $used_groups) === false) {
			$available_groups[$group->id] = $group;
		}
	}

	$can_manage_sharedfolders = isAuthorized('manageSharedFolders');

	page_header();

	echo '<div id="sharedfolders_div">';
	echo '<h1>'.$sharedfolder->name.'</h1>';

	echo '<div>';
	echo '<h2>'._('Server').'</h2>';
	echo '<a href="servers.php?action=manage&id='.$sharedfolder->server.'"> '.$server_displayname.'</a>';
	echo '</div>';
	echo '<br />';

	echo '<div>';
	echo '<h2>'._('Configuration').'</h2>';

	echo '<table>';

	echo '<tr><td>';
	echo _('Name').': ';
	echo '</td><td>';
	if ($can_manage_sharedfolders) {
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="SharedFolder" />';
		echo '<input type="hidden" name="action" value="rename" />';
		echo '<input type="hidden" name="id" value="'.$sharedfolder->id.'" />';
	}
	echo '<input type="text" name="sharedfolder_name" value="'.$sharedfolder->name.'" />';
	if ($can_manage_sharedfolders) {
		echo ' <input type="submit" value="'._('Rename').'" />';
		echo '</form>';
	}
	echo '</td></tr>';

	echo '</table>';

	echo '</div>';
	echo '<br />';

	echo '<div>';
	echo '<h2>'._('ACL').'</h2>';

	echo '<table border="0" cellspacing="1" cellpadding="3">';

	foreach ($used_groups as $group_id => $group_name) {
		echo '<tr>';
		echo '<td><a href="usersgroup.php?action=manage&amp;id='.$group_id.'">'.$group_name.'</a></td>';
		echo '<td>'.$mods_by_group[$group_id].'</td>';
		if ($can_manage_sharedfolders) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this shared folder access?').'\');">';
			echo '<input type="hidden" name="name" value="SharedFolder_ACL" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="sharedfolder_id" value="'.$sharedfolder->id.'" />';
			echo '<input type="hidden" name="usergroup_id" value="'.$group_id.'" />';
			echo '<input type="submit" value="'._('Delete access to this shared folder').'" />';
			echo '</form></td>';
		}
		echo '</tr>';
	}

	if (count($available_groups) > 0 and $can_manage_sharedfolders) {
		echo '<tr><form action="actions.php" method="post"><td>';
		echo '<input type="hidden" name="name" value="SharedFolder_ACL" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="hidden" name="sharedfolder_id" value="'.$sharedfolder->id.'" />';
		echo '<select name="usergroup_id">';
		foreach($available_groups as $group)
			echo '<option value="'.$group->id.'" >'.$group->name.'</option>';
		echo '</select>';
		echo '</td><td>';
		echo '<select name="mode">';
		echo '<option value="rw" >'._('Read-write').'</option>';
		echo '<option value="ro" >'._('Read only').'</option>';
		echo '</select>';
		echo '</td><td><input type="submit" value="'._('Add access to this shared folder').'" /></td>';
		echo '</form></tr>';
	}

	echo '</table>';

	echo $searchDiv;
	echo '</div>';

	echo '</div>';

	page_footer();
}
