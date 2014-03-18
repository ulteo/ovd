<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2012, 2013
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


$usersList = new UsersList($_REQUEST);
$us = $usersList->search();
$searchDiv = $usersList->getForm();


page_header();
echo'<h2>'._('Users').'</h2>';

echo $searchDiv;

		
if (is_null($us)){
}
else{
	$users_groups_cache = array();
	$applications_groups_cache = array();
	
	if (count($us) > 0){
		echo '<table id="users_table" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
		echo '<thead>';
		echo '<tr class="title">';
		echo '<th>'._('login').'</th><th>'._('name').'</th><th>'._('in this user group').'</th><th>'._('in this application group').'</th><th>'._('access to these applications').'</th><th>'._('access to these network folders').'</th>';
// 		<th>'._('Desktop File').'</th>
		echo '<th>'._('Available servers').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		$count = 0;
		foreach($us as $u){
			$user_info = $_SESSION['service']->session_simulate($u->getAttribute('login'));
			
			echo '<tr class="content';
			if ($count % 2 == 0)
				echo '1';
			else
				echo '2';
			echo '">';
			echo '<td><a href="users.php?action=manage&id='.$u->getAttribute('login').'">'.$u->getAttribute('login').'</a></td>'; // login
			echo '<td><a href="users.php?action=manage&id='.$u->getAttribute('login').'">'.$u->getAttribute('displayname').'</a></td>'; //nam

			echo '<td>'; // in user group

			$users_grps = $user_info['user_grps'];
			if ( count($users_grps) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($users_grps as $group_id => $group_name){
					echo '<tr>';
					echo '<td>
					<a href="usersgroup.php?action=manage&id='.$group_id.'">'.$group_name.'</a></td>';
					echo '</tr>';
				}
				
				if ($user_info['groups_partial_list'] === true) {
					echo '<tr><td>...</td></tr>';
				}
				echo '</table>';
			}


			echo '</td>';
			echo '<td>'; // in app group
			$apps_grps = $user_info['apps_grps'];
			if ( count($apps_grps) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($apps_grps as $agrp_id => $agrp_name){
					if (! array_key_exists($agrp_id, $applications_groups_cache)) {
						$applications_groups_cache[$agrp_id] = $_SESSION['service']->applications_group_info($agrp_id);
					}
					
					$agrp = $applications_groups_cache[$agrp_id];
					if (is_object($agrp)) {
						echo '<tr>';
						echo '<td><a href="appsgroup.php?action=manage&id='.$agrp->id.'">'.$agrp->name.'</a></td>';
						
						
						
						
						if ($agrp->published)
							echo '<td>('._('Yes').')</td>';
						else
							echo '<td>('._('No').')</td>';
						echo '</tr>';
					}
				}
				echo '</table>';
			}
			echo '</td>';

			echo '<td>'; // in app
			$apps_s = $user_info['apps'];
			$apps_type = array();
			if (count($apps_s) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($apps_s as $app_id => $aaa) {
					echo '<tr>';
					echo '<td>'.$aaa['type'].'</td>';
					echo '<td><a href="applications.php?action=manage&id='.$aaa['id'].'">'.$aaa['name'].'</a></td>';
					
					if (in_array($aaa['type'], $apps_type) == false)
						$apps_type []= $aaa['type'];
					echo '</tr>';
				}
				echo '</table>';
			}
			echo '</td>';
			
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

			/*
			echo '<td>'; // desktop file
			$desktopfile_s = $u->desktopfiles();
			if (count($desktopfile_s) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($desktopfile_s as $file){
					echo '<tr>';
					echo '<td>'.$file.'</td>';
					echo '</tr>';
				}
				echo '</table>';
			}
			echo '</td>';
			*/

			echo '<td>'; // server
			if (array_key_exists('servers', $user_info) && count($user_info['servers']) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($user_info['servers'] as $server_id => $s) {
					echo '<tr><td><strong>('.$s['type'].')</strong></td><td><a href="servers.php?action=manage&id='.$server_id.'">'.$s['name'].'</a></td></tr>';
				}
				echo '</table>';
			}
			echo '</td>';
			echo '</tr>';
			$count++;
		}
		echo '</tbody>';
		echo '</table>';
	}
}

echo '<h2>'._('List of servers').'</h2>';

$servs_all = $_SESSION['service']->servers_list();
if (is_null($servs_all)){
	echo _('No server available').'<br />';
}
else{
	if (count($servs_all)==0){
		echo _('No server available').'<br />';
	}
	else {
		?>
		<table id="servers_table" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">
		<thead>
		<tr class="title">
			<th><?php echo _('FQDN');?></th>
			<th><?php echo _('Type');?></th>
			<th><?php echo _('Roles');?></th>
			<th><?php echo _('Applications');?></th>
			<th><?php echo _('Status');?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$count = 0;
		foreach($servs_all as $server){
			$server =  $_SESSION['service']->server_info($server->getAttribute('id'));
			if (is_null($server)) {
				continue;
			}
			
			$roles = $server->roles;
			if (array_key_exists(Server::SERVER_ROLE_APS, $roles)) {
				$applications = $server->applications;
				if (! is_array($applications)) {
					$applications = array();
				}
				asort($applications);
			}
			else {
				$applications = array();
			}
			
			echo '<tr class="content';
			if ($count % 2 == 0)
				echo '1';
			else
				echo '2';
			echo '">';
			
			echo '<td><a href="servers.php?action=manage&id='.$server->id.'">'.$server->getDisplayName().'</a></td>';
			echo '<td>'.$server->stringType().'</td>';
			echo '<td>';
			if (is_array($roles)) {
				echo '<ul>';
				foreach ($roles as $a_role => $role_enable) {
					if ($role_enable) {
						echo "<li>$a_role</li>";
					}
				}
				echo '</ul>';
			}
			echo '</td>';
			echo '<td>';
			if ((is_array($applications))&& (count($applications)>0) ){
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($applications as $application_id => $application_name){
					echo '<tr>';
					echo '<td>('.$application_id.')</td>';
					echo '<td>';
					echo '<a href="applications.php?action=manage&id='.$application_id.'">'.$application_name.'</a>';
					
					echo '</td>';
					echo '</tr>';
				}
				echo '</table>';
			}
			echo '</td>';
			echo '<td>'.$server->stringStatus().'</td>';
			$count++;
			echo '</tr>';
		}
		echo '</tbody>';
	echo '</table>';
	}
}
page_footer();
