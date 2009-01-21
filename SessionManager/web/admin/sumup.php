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
		echo '<table border="2">';
		echo '<tr>';
		echo '<th>'._('login').'</th><td>'._('name').'</th><th>'._('in this users group').'</th><th>'._('in this applications group').'</th><th>'._('access to these applications').'</th><th>'._('Desktop File').'</th><th>'._('Server available').'</th>';
		echo '</tr>';

		foreach($us as $u){
			echo '<tr>';
			echo '<td>'.$u->getAttribute('login').'</td>'; // login
			echo '<td>'.$u->getAttribute('displayname').'</td>'; //nam

			echo '<td>'; // in user group

			$users_grps = $u->usersGroups();
// 			var_dump($users_grps);
			foreach ($users_grps as $ugrp){
				echo '('.$ugrp->id.') \''.$ugrp->name.'\' ';
				if ($ugrp->published)
					echo '('._('Yes').')';
				else
					echo '('._('No').')';
				echo '<br>';
			}


			echo '</td>';
			echo '<td>'; // in app group
			$apps_grps = $u->appsGroups();
			foreach ($apps_grps as $agrp_id){
				$agrp = new AppsGroup();
				$agrp->fromDB($agrp_id);
				if (is_object($agrp) && $agrp->isOK()) {
					echo '('.$agrp->id.') ';
					echo $agrp->name;
					if ($agrp->published)
						echo '('._('Yes').')';
					else
						echo '('._('No').')';
					echo "<br>";
				}
			}
			echo '</td>';

			echo '<td>'; // in app
			$apps_s = $u->applications();
			foreach ($apps_s as $aaa){
				echo '('.$aaa->getAttribute('type').') '.'('.$aaa->getAttribute('id').')'.$aaa->getAttribute('name').'<br>';
			}
			echo '</td>';

			echo '<td>'; // desktop file
			$desktopfile_s = $u->desktopfiles();
			foreach ($desktopfile_s as $file){
				echo $file.'<br>';
			}
			echo '</td>';

			echo '<td>'; // server
			$serv_s = $u->getAvailableServers();
			if (is_array($serv_s)){
				foreach ($serv_s as $s){
					echo $s->fqdn.'<br>';
				}
			}
			echo '</td>';
			echo '</tr>';
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
		<table id="servers_table" class="main_sub sortable" border="1" cellspacing="1" cellpadding="3">
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
			$apps_name = '';
			if (is_array($applications)){
				foreach ($applications as $a){
					$apps_name .= '('.$a->getAttribute('id').')'.$a->getAttribute('name').'<br />';
				}
			}
			echo '<tr class="content';
			if ($count % 2 == 0)
				echo '1';
			else
				echo '2';
			echo '">';
			echo '<td>'.$server->fqdn.'</td>';
			echo '<td>'.$server->stringType().'</td>';
			echo '<td>'.$apps_name.'</td>';
			echo '<td>'.$server->stringStatus().'</td>';
			$count++;
			echo '</tr>';
		}
	echo '</table>';
	}
}
