<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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

include('header.php');

echo'<h2>'._('List of user applications').'</h2>';

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);
$mods_enable = $prefs->get('general','module_enable');
if (!in_array('UserDB',$mods_enable)){
	die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
}
$mod_user_name = 'admin_'.'UserDB_'.$prefs->get('UserDB','enable');
$userDB = new $mod_user_name();


$us = $userDB->getList();  // in admin, getList is always present (even if canShowList is false)
if (is_null($us)){
}
else{
	if (count($us) > 0){
		echo '<table id="users_table" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
		echo '<tr class="title">';
		echo '<th>'._('login').'</th><td>'._('name').'</th><th>'._('in this users group').'</th><th>'._('in this applications group').'</th><th>'._('access to these applications').'</th><th>'._('Desktop File').'</th><th>'._('Server available').'</th>';
		echo '</tr>';
		$count = 0;
		foreach($us as $u){
			echo '<tr class="content';
			if ($count % 2 == 0)
				echo '1';
			else
				echo '2';
			echo '">';
			echo '<td>'.$u->getAttribute('login').'</td>'; // login
			echo '<td>'.$u->getAttribute('displayname').'</td>'; //nam

			echo '<td>'; // in user group

			$users_grps = $u->usersGroups();
			if ( count($users_grps) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($users_grps as $ugrp){
					echo '<tr>';
					echo '<td>'.$ugrp->id.'</td>';
					echo '<td>'.$ugrp->name.'</td>';
					if ($ugrp->published)
						echo '<td>'._('Yes').'</td>';
					else
						echo '<td>'._('No').'</td>';
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
					$agrp = new AppsGroup();
					$agrp->fromDB($agrp_id);
					if (is_object($agrp) && $agrp->isOK()) {
						echo '<tr>';
						echo '<td>'.$agrp->id.'</td>';
						echo '<td>'.$agrp->name.'</td>';
						if ($agrp->published)
							echo '<td>'._('Yes').'</td>';
						else
							echo '<td>'._('No').'</td>';
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
					echo '<td>'.$aaa->getAttribute('id').'</td>';
					echo '<td>'.$aaa->getAttribute('name').'</td>';
					if (in_array($aaa->getAttribute('type'), $apps_type) == false)
						$apps_type []= $aaa->getAttribute('type');
					echo '</tr>';
				}
				echo '</table>';
			}
			echo '</td>';

			echo '<td>'; // desktop file
			$desktopfile_s = $u->desktopfiles();
			echo '<table border="0">';
			if (count($desktopfile_s) > 0) {
				foreach ($desktopfile_s as $file){
					echo '<tr>';
					echo '<td>'.$file.'</td>';
					echo '</tr>';
				}
				echo '</table>';
			}
			echo '</td>';

			echo '<td>'; // server
			if (count($apps_type) > 0) {
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($apps_type as $a_type) {
					echo '<tr>';
					$serv_s = $u->getAvailableServers($a_type);
					if (is_array($serv_s)){
						foreach ($serv_s as $s){
							echo '<tr><td><strong>('.$a_type.')</strong></td><td>'.$s->fqdn.'</td></tr>';
						}
					}
					echo '</tr>';
				}
				echo '</table>';
			}
			echo '</td>';
			echo '</tr>';
			$count++;
		}
		echo '</table>';
	}
}

echo '<h2>List of server</h2>';

$servs_all = Servers::getAll();
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
		<tr class="title">
			<th><?php echo _('FQDN');?></th>
			<th><?php echo _('Type');?></th>
			<th><?php echo _('Applications');?></th>
			<th><?php echo _('Status');?></th>
		</tr>
<!-- 		<table class="main_sub" border="1" cellspacing="1" cellpadding="5"> -->
<!-- 			<th>FQDN</th><th>Type</th><th>Version</th><th>Status</th><th>Applications(physical)</th> -->
<!-- 		</tr> -->
		<?php
		$count = 0;
		foreach($servs_all as $server){
			$applications = $server->getApplications();
// 			$apps_name = '';
			echo '<tr class="content';
			if ($count % 2 == 0)
				echo '1';
			else
				echo '2';
			echo '">';
			echo '<td>'.$server->fqdn.'</td>';
			echo '<td>'.$server->stringType().'</td>';
			echo '<td>';
			if ((is_array($applications))&& (count($applications)>0) ){
				echo '<table border="0" cellspacing="1" cellpadding="3">';
				foreach ($applications as $a){
					echo '<tr>';
					echo '<td>';
					echo $a->getAttribute('id');
					echo '</td>';
					echo '<td>';
					echo $a->getAttribute('name');
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
	echo '</table>';
	}
}
