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
require_once(dirname(dirname(__FILE__)).'/includes/core.inc.php');
require_once(dirname(dirname(__FILE__)).'/includes/page_template.php');

if (! checkAuthorization('viewSummary'))
	redirect('index.php');


function my_own_callback($matches) {
	return '<span class="'.strtolower($matches[1]).'">'.trim($matches[0]).'</span>';
}

show_default();
  
  
function show_default() {
	$usersList = new UsersList($_REQUEST);
	$us = $usersList->search();
	$searchDiv = $usersList->getForm();
	
	$users_info = array();
	foreach($us as $user){
		$user_info = $_SESSION['service']->session_simulate($user->getAttribute('login'));
		if (is_null($user_info)) {
			popup_error(sprintf(_('Unable to perform session simulation for user %s'), $user->getAttribute('login')));
			continue;
		}
		
		$user_info['user'] = $user;
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
				foreach ($user_info['user_grps'] as $group_id => $group_name) {
					echo '<tr>';
					echo '<td><a href="usersgroup.php?action=manage&id='.$group_id.'">'.$group_name.'</a></td>';
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
				foreach ($user_info['apps_grps'] as $group_id => $group_name) {
					echo '<tr>';
					echo '<td><a href="appsgroup.php?action=manage&id='.$group_id.'">'.$group_name.'</a></td>';
					echo '</tr>';
					
				}
				echo '</table>';
				echo '</td>';

				echo '<td>'; // in app
				if (count($user_info['apps']) == 0)
					echo '<em>'._('No applications in these groups').'</em>';
				else {
					echo '<table border="0" cellspacing="1" cellpadding="3">';
					foreach ($user_info['apps'] as $application_id => $aaa) {
						echo '<tr>';
						echo '<td><img class="icon32" src="media/image/cache.php?id='.$aaa['id'].'" alt="" title="" /></td>';
						echo '<td><a href="applications.php?action=manage&id='.$aaa['id'].'">'.$aaa['name'].'</a></td>';
						echo '<td style="text-align: center;"><img src="media/image/server-'.$aaa['type'].'.png" width="16" height="16" alt="'.$aaa['type'].'" title="'.$aaa['type'].'" /></td>';
						echo '</tr>';
					}
					echo '</table>';
				}
				echo '</td>';
			}
			
			echo '<td>';
			
			if (count($user_info['profiles']) + count($user_info['shared_folders']) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($user_info['profiles'] as $profile_id => $profile_name) {
					echo '<tr>';
					echo '<td>'._('Profile').'</td>';
					echo '<td>';
					echo '<a href="profiles.php?action=manage&id='.$profile_id.'">'.$profile_name.'</a></td>';
					echo '</tr>';
				}
				foreach ($user_info['shared_folders'] as $share_id => $share_name) {
					echo '<tr>';
					echo '<td>'._('Shared folder').'</td>';
					echo '<td>';
					echo '<a href="sharedfolders.php?action=manage&id='.$share_id.'">'.$share_name.'</a></td>';
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
