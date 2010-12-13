<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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



$userDB = UserDB::getInstance();
$userGroupDB = UserGroupDB::getInstance();
$applicationsGroupDB = ApplicationsGroupDB::getInstance();

$usersList = new UsersList($_REQUEST);
$us = $usersList->search();
$searchDiv = $usersList->getForm();


page_header();
echo'<h2>'._('List of users').'</h2>';

echo $searchDiv;

		
if (is_null($us)){
}
else{
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
			$session_settings_defaults = $u->getSessionSettings('session_settings_defaults');
			echo '<tr class="content';
			if ($count % 2 == 0)
				echo '1';
			else
				echo '2';
			echo '">';
			echo '<td><a href="users.php?action=manage&id='.$u->getAttribute('login').'">'.$u->getAttribute('login').'</a></td>'; // login
			echo '<td><a href="users.php?action=manage&id='.$u->getAttribute('login').'">'.$u->getAttribute('displayname').'</a></td>'; //nam

			echo '<td>'; // in user group

			$users_grps = $u->usersGroups();
			if ( count($users_grps) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($users_grps as $ugrp){
					echo '<tr>';
					echo '<td>
					<a href="usersgroup.php?action=manage&id='.$ugrp->getUniqueID().'">'.$ugrp->name.'</a></td>';
					if ($ugrp->published)
						echo '<td>('._('Yes').')</td>';
					else
						echo '<td>('._('No').')</td>';
					echo '</tr>';
				}
				echo '</table>';
			}


			echo '</td>';
			echo '<td>'; // in app group
			$apps_grps = $u->appsGroups();
			if ( count($apps_grps) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($apps_grps as $agrp_id){
					$agrp = $applicationsGroupDB->import($agrp_id);
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
			$apps_s = $u->applications();
			$apps_type = array();
			if (count($apps_s) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($apps_s as $aaa) {
					echo '<tr>';
					echo '<td>'.$aaa->getAttribute('type').'</td>';
					echo '<td><a href="applications.php?action=manage&id='.$aaa->getAttribute('id').'">'.$aaa->getAttribute('name').'</a></td>';
					
					if (in_array($aaa->getAttribute('type'), $apps_type) == false)
						$apps_type []= $aaa->getAttribute('type');
					echo '</tr>';
				}
				echo '</table>';
			}
			echo '</td>';
			
			echo '<td>';
			$folders = array();
			if (array_key_exists('enable_sharedfolders', $session_settings_defaults) && $session_settings_defaults['enable_sharedfolders'] == 1) {
				$folders = $u->getSharedFolders();
			}
			$profiles = array();
			if (array_key_exists('enable_profiles', $session_settings_defaults) && $session_settings_defaults['enable_profiles'] == 1) {
				$profiles = $u->getProfiles();
			}
			$networkfolder_s = array_merge($folders, $profiles);
			
			if (count($networkfolder_s) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($networkfolder_s as $a_networkfolder) {
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
			$serv_s = $u->getAvailableServers();
			if (is_array($serv_s) && count($serv_s) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($serv_s as $s) {
					echo '<tr><td><strong>('.$s->getAttribute('type').')</strong></td><td><a href="servers.php?action=manage&fqdn='.$s->fqdn.'">'.$s->fqdn.'</a></td></tr>';
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

$servs_all = Abstract_Server::load_all();
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
			$roles = $server->getAttribute('roles');
			if (is_array($roles) && array_key_exists('aps', $roles) && $roles['aps'] === true) {
				$applications = $server->getApplications();
				if (! is_array($applications)) {
					$applications = array();
				}
				usort($applications, "application_cmp");
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
			
			echo '<td><a href="servers.php?action=manage&fqdn='.$server->fqdn.'">'.$server->fqdn.'</a></td>';
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
				foreach ($applications as $a){
					echo '<tr>';
					echo '<td>('.$a->getAttribute('id').')</td>';
					echo '<td>';
					echo '<a href="applications.php?action=manage&id='.$a->getAttribute('id').'">'.$a->getAttribute('name').'</a>';
					
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
