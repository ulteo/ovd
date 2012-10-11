<?php
/**
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009
 * Author Omar AKHAM <oakham@ulteo.com> 2011
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
}
else {
	show_default();
}

function show_default() {
	$profiledb = ProfileDB::getInstance();
	$profiles = $profiledb->getList();
	if (is_array($profiles) == false) {
		$profiles = array();
	}
	
	$can_manage_profiles = isAuthorized('manageSharedFolders');
	$can_manage_configuration = isAuthorized('manageConfiguration');

	page_header();

	echo '<div id="profiles_div">';
	echo '<h1>'._('Profiles').'</h1>';

	$nb_profiles = count($profiles);

	if($nb_profiles > 0) {
		echo '<div id="profiles_list_div">';
		echo '<table id="profils_list_table" border="0" class="main_sub sortable" cellspacing="1" cellpadding="3">';	
		echo '<thead>';
		echo '<tr class="title">';
		if ($nb_profiles > 1 and $can_manage_profiles)
			echo '<th class="unsortable"></th>';
		echo '<th>'._('Profile').'</th><th>'._('User').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		$count = 0;
		
		foreach ($profiles as $profile) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			
			if($nb_profiles > 1 and $can_manage_profiles)
				echo '<td><input class="input_checkbox" type="checkbox" name="ids[]" value="'.$profile->id.'" /></td>';
			
			echo '<td><a href="profiles.php?action=manage&amp;id='.$profile->id.'">'.$profile->id.'</a></td>';
			$users = '';
			$profile_users = $profile->getUsers();
			$count1 = count($profile_users);
			
			foreach($profile_users as $user)
				$users[] = '<a href="users.php?action=manage&amp;id='.$user->getAttribute('login').'">'.$user->getAttribute('displayname').'</a>';
			
			$str_users = implode(', ', $users);
			echo '<td>'.$str_users.'</td>';
			
			if ($can_manage_profiles) {
				echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this profile?').'\');">';
				echo '<input type="hidden" name="name" value="Profile" />';
				echo '<input type="hidden" name="action" value="del" />';
				echo '<input type="hidden" name="ids[]" value="'.$profile->id.'" />';
				echo '<input type="submit" value="'._('Delete this profile').'" />';
				echo '</form></td>';
			}
			
			echo '</tr>';
		}
		
		echo '</tbody>';
		
		if ($nb_profiles > 1 and $can_manage_profiles) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tfoot>';
			echo '<tr class="'.$content.'">';
			echo '<td colspan="'.(($nb_profiles > 0)?3:2).'">';
			echo '<a href="javascript:;" onclick="markAllRows(\'profils_list_table\'); return false">'._('Mark all').'</a>';
			echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'profils_list_table\'); return false">'._('Unmark all').'</a>';
			echo '</td>';
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete selected profiles?').'\') && updateMassActionsForm(this, \'profils_list_table\');">';
			echo '<input type="hidden" name="name" value="Profile" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="submit" value="'._('Delete profiles').'"/>';
			echo '</form>';
			echo '</td>';
			echo '</tr>';
			echo '</tfoot>';
		}
		
		echo '</table>';
	}
	else {
		echo _('No profile');
	}

	echo '</div>';

	echo '</div>';

	page_footer();

	die();
}

function show_manage($profile_id_) {
	$profiledb = ProfileDB::getInstance();
	$profile = $profiledb->import($profile_id_);

	if (! is_object($profile))
		redirect('profiles.php');

	$used_users = $profile->getUsers();
	$server = Abstract_Server::load($profile->server);

	$can_manage_profiles = isAuthorized('manageSharedFolders');

	page_header();

	echo '<div id="profiles_div">';
	echo '<h1>'.sprintf(_("Profile: %s"),$profile->id).'</h1>';
	

	echo '<div>';
	echo '<h2>'._('Server').'</h2>';
	echo '<a href="servers.php?action=manage&id='.$profile->server.'"> '.$server->getDisplayName().'</a>';
	echo '</div>';
	echo '<br />';

	echo '<div>';
	echo '<h2>'._('Used by').'</h2>';

	echo '<table border="0" cellspacing="1" cellpadding="3">';

	foreach ($used_users as $user) {
		echo '<tr>';
		echo '<td><a href="users.php?action=manage&amp;id='.$user->getAttribute('login').'">'.$user->getAttribute('displayname').'</a></td>';
		echo '</tr>';
	}

	echo '</table>';

	echo '</div>';
	
	if ($can_manage_profiles) {
		echo '<div>';
		echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this profile?').'\');">';
		echo '<input type="hidden" name="name" value="Profile" />';
		echo '<input type="hidden" name="action" value="del" />';
		echo '<input type="hidden" name="ids[]" value="'.$profile->id.'" />';
		echo '<input type="submit" value="'._('Delete this profile').'" />';
		echo '</form>';
		echo '</div>';
	}

	echo '</div>';

	page_footer();
}
