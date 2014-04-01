<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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
  if ($_REQUEST['action']=='manage') {
    if (isset($_REQUEST['id']))
      show_manage($_REQUEST['id']);
  }
}

show_default();

function show_default() {
	$groups = $_SESSION['service']->servers_groups_list();
	$has_group = ! (is_null($groups) or (count($groups) == 0));
	$can_manage = isAuthorized('manageServers');
	
	page_header();
	echo '<div>';
	echo '<h1>'._('Server Groups').'</h1>';
	echo '<div>';
	if (! $has_group) {
		echo _('No available Server Group').'<br />';
	}
	else {
		echo '<table class="main_sub sortable" id="groups_list" border="0" cellspacing="1" cellpadding="5">';
		echo '<thead>';
		echo '<tr class="title">';
		if ($can_manage && count($groups) > 1) {
		    echo '<th class="unsortable"></th>';
		}
		echo '<th>'._('Name').'</th>';
		echo '<th>'._('Description').'</th>';
		echo '<th>'._('Status').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		
		$count = 0;
		foreach($groups as $group) {
			$content = 'content'.(($count++%2==0)?1:2);
			if ($group->published) {
				$publish = '<span class="msg_ok">'._('Enabled').'</span>';
			}
			else {
				$publish = '<span class="msg_error">'._('Blocked').'</span>';
			}
			
			echo '<tr class="'.$content.'">';
			if ($can_manage && count($groups) > 1) {
				echo '<td><input class="input_checkbox" type="checkbox" name="checked_groups[]" value="'.$group->id.'" /></td>';
			}
			
			echo '<td><a href="?action=manage&id='.$group->id.'">'.$group->name.'</a></td>';
			echo '<td>'.$group->description.'</td>';
			echo '<td class="centered">'.$publish.'</td>';
			
			echo '<td><form action="">';
			echo '<input type="submit" value="'._('Manage').'"/>';
			echo '<input type="hidden" name="action" value="manage" />';
			echo '<input type="hidden" name="id" value="'.$group->id.'" />';
			echo '</form></td>';
			if ($can_manage) {
				echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
				echo '<input type="submit" value="'._('Delete').'"/>';
				echo '<input type="hidden" name="name" value="ServersGroup" />';
				echo '<input type="hidden" name="action" value="del" />';
				echo '<input type="hidden" name="checked_groups[]" value="'.$group->id.'" />';
				echo '</form></td>';
				echo '</tr>';
			}
		}
		
		echo '</tbody>';
		if ($can_manage && count($groups) > 1) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tfoot>';
			echo '<tr class="'.$content.'">';
			echo '<td colspan="5"><a href="javascript:;" onclick="markAllRows(\'groups_list\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'groups_list\'); return false">'._('Unmark all').'</a></td>';
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete these groups?').'\') && updateMassActionsForm(this, \'groups_list\');">';
			echo '<input type="hidden" name="name" value="ServersGroup" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="submit" value="'._('Delete').'"/>';
			echo '</form>';
			echo '</td>';
			echo '</tr>';
			echo '</tfoot>';
		}
		echo '</table>';
	}
	
	echo '</div>';
	if ($can_manage) {
		echo '<div>';
		echo '<h2>'._('Create a new group').'</h2>';
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="ServersGroup" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';

		echo '<tr class="content1">';
		echo '<th>'._('Name').'</th>';
		echo '<td><input type="text" name="name_group" value="" /></td>';
		echo '</tr>';

		echo '<tr class="content2">';
		echo '<th>'._('Description').'</th>';
		echo '<td><input type="text" name="description_group" value="" /></td>';
		echo '</tr>';
		echo '<tr class="content1">';
		echo '<td class="centered" colspan="2"><input type="submit" value="'._('Add').'" /></td>';
		echo '</tr>';
		echo '</table>';
		echo '</form>';
		echo '</div>';
	}
	
	echo '</div>';
	echo '</div>';
	page_footer();
}

function show_manage($id_) {
	$group = $_SESSION['service']->servers_group_info($id_);
	if (! is_object($group)) {
		popup_error(sprintf(_('Unable to load group %s'), $id_));
		redirect();
	}
	
	if ($group->published) {
		$status = '<span class="msg_ok">'._('Enabled').'</span>';
		$status_change = _('Block');
		$status_change_value = 0;
	}
	else {
		$status = '<span class="msg_error">'._('Blocked').'</span>';
		$status_change = _('Enable');
		$status_change_value = 1;
	}
	
	$servers_all = $_SESSION['service']->servers_list();
	$servers_id = array();
	if ($group->hasAttribute('servers')) {
		$servers_id = $group->getAttribute('servers');
	}
	
	$servers = array();
	$servers_available = array();
	foreach($servers_all as $server) {
		if (array_key_exists($server->id, $servers_id)) {
			$servers[$server->id]= $server;
		}
		else {
			$servers_available[$server->id]= $server;
		}
	}
	
	// Publications
	
	$groups_users = array();
	if ($group->hasAttribute('usersgroups')) {
		$groups_users = $group->getAttribute('usersgroups');
	}
	
	$usersgroupsList = new UsersGroupsList($_REQUEST);
	$groups_users_all = $usersgroupsList->search();
	if (! is_array($groups_users_all)) {
		$groups_users_all = array();
		popup_error(_("Failed to get User Group list"));
	}
	usort($groups_users_all, "usergroup_cmp");
	$searchDiv = $usersgroupsList->getForm(array('action' => 'manage', 'id' => $id_));

	$groups_users_available = array();
	foreach($groups_users_all as $group_users) {
		if (! array_key_exists($group_users->id, $groups_users)) {
			$groups_users_available[]= $group_users;
		}
	}
	
	$can_manage = isAuthorized('manageServers');
	$can_manage_publications = isAuthorized('managePublications');
	
	page_header();
	echo '<div>';
	echo '<h1><a href="?">'._('Server Group management').'</a> - '.$group->name.'</h1>';
	echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
	echo '<tr class="title">';
	echo '<th>'._('Description').'</th>';
	echo '<th>'._('Status').'</th>';
	echo '</tr>';
	
	echo '<tr class="content1">';
	echo '<td>'.$group->description.'</td>';
	echo '<td>'.$status.'</td>';
	echo '</tr>';
	echo '</table>';
	
	if ($can_manage) {
		echo '<div>';
		echo '<h2>'._('Settings').'</h2>';
		echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
		echo '<input type="submit" value="'._('Delete this group').'"/>';
		echo '<input type="hidden" name="name" value="ServersGroup" />';
		echo '<input type="hidden" name="action" value="del" />';
		echo '<input type="hidden" name="checked_groups[]" value="'.$id_.'" />';
		echo '<input type="hidden" name="id" value="'.$id_.'" />';
		echo '</form>';
		echo '<br/>';
		
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="ServersGroup" />';
		echo '<input type="hidden" name="action" value="modify" />';
		echo '<input type="hidden" name="id" value="'.$id_.'" />';
		echo '<input type="hidden" name="published_group" value="'.$status_change_value.'" />';
		echo '<input type="submit" value="'.$status_change.'"/>';
		echo '</form>';
		echo '<br/>';
		
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="ServersGroup" />';
		echo '<input type="hidden" name="action" value="modify" />';
		echo '<input type="hidden" name="id" value="'.$id_.'" />';
		echo '<input type="text" name="name_group"  value="'.$group->name.'" size="50" /> ';
		echo '<input type="submit" value="'._('Update the name').'"/>';
		echo '</form>';
		echo '<br/>';
		
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="ServersGroup" />';
		echo '<input type="hidden" name="action" value="modify" />';
		echo '<input type="hidden" name="id" value="'.$id_.'" />';
		echo '<input type="text" name="description_group"  value="'.$group->description.'" size="50" /> ';
		echo '<input type="submit" value="'._('Update the description').'"/>';
		echo '</form>';
		echo '<br/>';
	}
	
	// Servers
	echo '<div>';
	echo '<h2>'._('List of servers including this group').'</h2>';
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	
	if (count($servers) == 0) {
		echo '<tr><td colspan="2">'._('No server has this group defined').'</td></tr>';
	}
	else {
		foreach($servers as $server_id => $server) {
			echo '<tr>';
			echo '<td><a href="servers.php?action=manage&id='.$server_id.'">'.$server->getDisplayName().'</a>';
			echo '</td>';
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group from this server?').'\');">';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="name" value="Server_ServersGroup" />';
			echo '<input type="hidden" name="group" value="'.$id_.'" />';
			echo '<input type="hidden" name="server" value="'.$server_id.'" />';
			echo '<input type="submit" value="'._('Delete from this group').'" />';
			echo '</form>';
			echo '</td>';
			echo '</tr>';
		}
	}
	
	if (count ($servers_available) ==0) {
		echo '<tr><td colspan="2">'._('Not any available server to add').'</td></tr>';
	}
	else {
		echo '<tr><form action="actions.php" method="post"><td>';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="hidden" name="name" value="Server_ServersGroup" />';
		echo '<input type="hidden" name="group" value="'.$id_.'" />';
		echo '<select name="server">';
		foreach($servers_available as $server) {
			echo '<option value="'.$server->id.'" >'.$server->getDisplayName().'</option>';
		}
		
		echo '</select>';
		echo '</td><td><input type="submit" value="'._('Add to this server').'" /></td>';
		echo '</form></tr>';
	}
	
	echo '</table>';
	
	
	// Publication part
	if (count($groups_users_all) > 0) {
		echo '<div>';
		echo '<h2>'._('List of published User Groups for this group').'</h1>';
		echo '<table border="0" cellspacing="1" cellpadding="3">';
		
		if (count($groups_users) > 0) {
			foreach($groups_users as $group_users_id => $group_users_name) {
				echo '<tr>';
				echo '<td><a href="usersgroup.php?action=manage&id='.$group_users_id.'">'.$group_users_name.'</td>';
				if ($can_manage_publications) {
					echo '<td>';
					echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this publication?').'\');">';
					echo '<input type="hidden" name="action" value="del" />';
					echo '<input type="hidden" name="name" value="UsersGroupServersGroup" />';
					echo '<input type="hidden" name="servers_group" value="'.$id_.'" />';
					echo '<input type="hidden" name="users_group" value="'.$group_users_id.'" />';
					echo '<input type="submit" value="'._('Delete this publication').'" />';
					echo '</form>';
					echo '</td>';
				}
				
				echo '</tr>';
			}
		}
		
		if (count ($groups_users_available) > 0 and $can_manage_publications) {
			echo '<tr><form action="actions.php" method="get"><td>';
			echo '<input type="hidden" name="action" value="add" />';
			echo '<input type="hidden" name="name" value="UsersGroupServersGroup" />';
			echo '<input type="hidden" name="servers_group" value="'.$id_.'" />';
			echo '<select name="users_group">';
			foreach($groups_users_available as $group_users) {
				echo '<option value="'.$group_users->id.'" >'.$group_users->name.'</option>';
			}
			
			echo '</select>';
			echo '</td><td><input type="submit" value="'._('Add this publication').'" /></td>';
			echo '</form></tr>';
		}
		
		echo '</table>';
		echo $searchDiv;
		echo '</div>';
	}
	
	echo '</div>';
	page_footer();
	die();
}
