<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

if (! checkAuthorization('viewSummary'))
	redirect('index.php');


function my_own_callback($matches) {
	return '<span class="'.strtolower($matches[1]).'">'.trim($matches[0]).'</span>';
}

show_default();
  
  
function show_default() {
	$userDB = UserDB::getInstance();
	$userGroupDB = UserGroupDB::getInstance();
	$applicationsGroupDB = ApplicationsGroupDB::getInstance();
	try {
		$sessionmanagement = SessionManagement::getInstance();
	}
	catch (Exception $err) {
		die_error('Unable to instanciate SessionManagement: '.$err->getMessage(), __FILE__, __LINE__);
	}

	$usersList = new UsersList($_REQUEST);
	$us = $usersList->search();
	$searchDiv = $usersList->getForm();
	
	$users_info = array();
	foreach($us as $user){
		$user_info = array();
		$user_info['user'] = $user;
		$user_info['settings'] = $user->getSessionSettings('session_settings_defaults');
		
		$user_info['user_grps'] = $user->usersGroups();
		$user_info['apps_grps'] = array();
		
		$apps_grps = $user->appsGroups();
		foreach ($apps_grps as $agrp_id) {
				$agrp = $applicationsGroupDB->import($agrp_id);
				if (! is_object($agrp))
					continue;
				
				$user_info['apps_grps'][]= $agrp;
		}
		
		$user_info['apps'] = $user->applications();
		
		$user_info['folders'] = array();
		if (array_key_exists('enable_sharedfolders', $user_info['settings']) && $user_info['settings']['enable_sharedfolders'] == 1) {
			$user_info['folders']= $user->getSharedFolders();
		}
		
		$user_info['profiles'] = array();
		if (array_key_exists('enable_profiles', $user_info['settings']) && $user_info['settings']['enable_profiles'] == 1) {
			$user_info['profiles'] = $user->getProfiles();
		}
		
		$user_info['networkfolders'] = array_merge($user_info['folders'], $user_info['profiles']);
	
		$remote_desktop_settings = $user->getSessionSettings('remote_desktop_settings');
		$remote_desktop_enabled = ($remote_desktop_settings['enabled'] == 1);
		$remote_applications_settings = $user->getSessionSettings('remote_applications_settings');
		$remote_applications_enabled = ($remote_applications_settings['enabled'] == 1);
	
		$sessionmanagement2 = clone($sessionmanagement);
		$sessionmanagement2->user = $user;
		$user_info['can_start_session_desktop'] = $remote_desktop_enabled && 
			$sessionmanagement2->getDesktopServer() && 
			$sessionmanagement2->buildServersList(true);
		
		$sessionmanagement2 = clone($sessionmanagement);
		$sessionmanagement2->user = $user;
		$user_info['can_start_session_applications'] = $remote_applications_enabled &&
			$sessionmanagement2->buildServersList(true);
		
		$users_info[]= $user_info;
	}


	page_header();
	echo'<h2>'._('List of users').'</h2>';

	echo $searchDiv;

	if (count($users_info) == 0)
		echo _('No available user').'<br />';
	else {
		echo '<table id="users_table" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
		echo '<thead>';
		echo '<tr class="title2">';
		echo '<th class="unsortable" colspan="3">'._('Users').'</th>';
		echo '<th colspan="2">'._('Applications').'</th>';
		echo '<th>'._('Folders').'</th>';
		echo '<th>'._('Session').'</th>';
		echo '</tr>';
		echo '<tr class="title">';
		echo '<th>'._('Login').'</th>';
		echo '<th>'._('Name').'</th>';
		echo '<th>'._('In these users groups').'</th>';
		echo '<th>'._('Published applications groups').'</th>';
		echo '<th>'._('Access to these applications').'</th>';
		echo '<th>'._('Access to these folders').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		
		$count = 0;
		foreach($users_info as $user_info) {
			$u = $user_info['user'];
			
			echo '<tr class="content';
			if ($count % 2 == 0)
				echo '1';
			else
				echo '2';
			echo '">';
			echo '<td><a href="users.php?action=manage&id='.$u->getAttribute('login').'">'.$u->getAttribute('login').'</a></td>'; // login
			echo '<td><a href="users.php?action=manage&id='.$u->getAttribute('login').'">'.$u->getAttribute('displayname').'</a></td>'; //nam

			echo '<td>';
			if ( count($user_info['user_grps']) == 0)
				echo '<em>'._('Not in any users group').'</em>';
			else {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($user_info['user_grps'] as $ugrp) {
					echo '<tr>';
					echo '<td><a href="usersgroup.php?action=manage&id='.$ugrp->getUniqueID().'">'.$ugrp->name.'</a></td>';
					echo '</tr>';
				}
				echo '</table>';
			}
			echo '</td>';
			
			if ( count($user_info['apps_grps']) == 0) {
				echo '<td colspan="2">';
				echo '<em>'._('No publication').'</em>';
				echo '</td>';
			}
			else {
				echo '<td>';
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($user_info['apps_grps'] as $agrp) {
					echo '<tr>';
					echo '<td><a href="appsgroup.php?action=manage&id='.$agrp->id.'">'.$agrp->name.'</a></td>';
					echo '</tr>';
					
				}
				echo '</table>';
				echo '</td>';

				echo '<td>'; // in app
				if (count($user_info['apps']) == 0)
					echo '<em>'._('No applications in these groups').'</em>';
				else {
					echo '<table border="0" cellspacing="1" cellpadding="3">';
					foreach ($user_info['apps'] as $aaa) {
						echo '<tr>';
						echo '<td><img class="icon32" src="media/image/cache.php?id='.$aaa->getAttribute('id').'" alt="" title="" /></td>';
						echo '<td><a href="applications.php?action=manage&id='.$aaa->getAttribute('id').'">'.$aaa->getAttribute('name').'</a></td>';
						echo '<td style="text-align: center;"><img src="media/image/server-'.$aaa->getAttribute('type').'.png" width="16" height="16" alt="'.$aaa->getAttribute('type').'" title="'.$aaa->getAttribute('type').'" /></td>';
						echo '</tr>';
					}
					echo '</table>';
				}
				echo '</td>';
			}
			
			echo '<td>';
			
			if (count($user_info['networkfolders']) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($user_info['networkfolders'] as $a_networkfolder) {
					echo '<tr>';
					echo '<td>'.$a_networkfolder->prettyName().'</td>';
					if (isset($a_networkfolder->name) && $a_networkfolder->name !== '')
						$name = $a_networkfolder->name;
					else
						$name = $a_networkfolder->id;
					echo '<td>';
					if (isset($a_networkfolder->name)) {
						$page = 'sharedfolders';
					}
					else {
						$page = 'profiles';
					}
					echo '<a href="'.$page.'.php?action=manage&id='.$a_networkfolder->id.'">'.$name.'</a></td>';
					echo '</tr>';
				}
				echo '</table>';
			}
			echo '</td>';

			echo '<td style="white-space: nowrap;">';
			echo '<div>';
			if ($user_info['can_start_session_desktop'] === true)
				echo '<img src="media/image/ok.png" alt="" title="" />';
			else
				echo '<img src="media/image/cancel.png" alt="" title="" />';
			echo ' '._('Desktop');
			echo '</div>';
			
			echo '<div>';
			if ($user_info['can_start_session_applications'] === true)
				echo '<img src="media/image/ok.png" alt="" title="" />';
			else
				echo '<img src="media/image/cancel.png" alt="" title="" />';
			echo ' '._('Applications');
			echo '</div>';
			
			echo '</td>';
			echo '</tr>';
			$count++;
		}
		echo '</tbody>';
		echo '</table>';
	}

	page_footer();
}
