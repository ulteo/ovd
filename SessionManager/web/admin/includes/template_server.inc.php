<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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


function server_display_role_preparation($role, $server) {
	if (function_exists('server_display_role_preparation_'.$role) === false) {
		return false;
	}
	$fct = 'server_display_role_preparation_'.$role;
	return $fct($server);
}

function server_display_role($role, $server, $var) {
	if (function_exists('server_display_role_'.$role) === false) {
		return false;
	}
	$fct = 'server_display_role_'.$role;
	$fct($server, $var);
}


function server_display_role_preparation_aps($server) {
	$ret = array();
	
	$server_online = $server->isOnline();
	
	if ($server_online) {
		$buf = $server->updateApplications();
		if (! $buf)
			popup_error(_('Cannot list available applications'));
	}
	
	$applicationDB = ApplicationDB::getInstance();
	
	$applications_all = $applicationDB->getList(true);
	$applications = $server->getApplications();
	if (! is_array($applications))
		$applications = array();
	usort($applications, 'application_cmp');
	
	$applications_available = array();
	$static_applications_available = array();
	
	if (!$server_online && count($applications) == 0)
		$applications_all = array();
	
	$servers_all = Abstract_Server::load_by_status(Server::SERVER_STATUS_ONLINE);
	foreach($servers_all as $k => $v) {
		if ($v->fqdn == $server->fqdn)
			unset($servers_all[$k]);
	}
	
	$servers_replication = Abstract_Server::load_by_status(Server::SERVER_STATUS_ONLINE);
	foreach($servers_replication as $k => $v) {
		if ($v->fqdn == $server->fqdn)
			unset($servers_replication[$k]);

		if ($v->type != $server->getAttribute('type'))
			unset($servers_replication[$k]);

		if (! array_key_exists('aps', $v->roles) || $v->roles['aps'] !== true)
			unset($servers_replication[$k]);

		if ($server->hasAttribute('ulteo_system') == false || $server->getAttribute('ulteo_system') == 0)
			unset($servers_replication[$k]);
	}
	$sessions = array();
	$total = Abstract_Session::countByServer($_GET['fqdn']);
	
	if ($total > 0) {
		$has_sessions = true;
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);

		if ($total > $prefs->get('general', 'max_items_per_page')) {
			if (! isset($_GET['start']) || (! is_numeric($_GET['start']) || $_GET['start'] >= $total))
				$start = 0;
			else
				$start = $_GET['start'];

			$pagechanger = get_pagechanger('servers.php?action=manage&fqdn='.$_GET['fqdn'].'&', $prefs->get('general', 'max_items_per_page'), $total);

			$sessions = Abstract_Session::getByServer($_GET['fqdn'], $prefs->get('general', 'max_items_per_page'), $start);
		} else
			$sessions = Abstract_Session::getByServer($_GET['fqdn']);
	} else
		$has_sessions = false;

	$external_name_checklist = array('localhost', '127.0.0.1');
	if (in_array($server->fqdn, $external_name_checklist) && in_array($server->getAttribute('external_name'), $external_name_checklist))
		popup_error(sprintf(_('Server "%s": redirection name may be invalid!'), $server->fqdn));
	if ($server->getAttribute('external_name') == '')
		popup_error(sprintf(_('Server "%s": redirection name cannot be empty!'), $server->fqdn));
	
	if ($server_online) {
		//FIX ME ?
		$tm = new Tasks_Manager();
		$tm->load_from_server($server->fqdn);
		$tm->refresh_all();

		$apps_in_remove = array();
		$apps_in_install = array();

		$tasks = array();
		if ($server_online) {
			foreach($tm->tasks as $task) {
				if (! $task->succeed())
					$tasks[]= $task;
			}
			
			foreach($tasks as $task) {
				if (get_class($task) == 'Task_install') {
					foreach($task->applications as $app) {
						if (! in_array($app, $apps_in_install))
							$apps_in_install[]= $app;
					}
				}
				
				if (get_class($task) == 'Task_remove') {
					foreach($task->applications as $app) {
						if (! in_array($app, $apps_in_remove))
							$apps_in_remove[]= $app;
					}
				}
			}
			
			foreach($applications_all as $app) {
			if (in_array($app, $applications))
				continue;
			if (in_array($app, $apps_in_install))
				continue;
			if ($app->getAttribute('type') != $server->getAttribute('type'))
				continue;
			
			$applications_available[]= $app;
			}
		}
		$ret['tasks'] = $tasks;
		$ret['apps_in_install'] = $apps_in_install;
		$ret['apps_in_remove'] = $apps_in_remove;
	}
	
	$ret['can_do_action'] = isAuthorized('manageServers');
	$ret['web_port'] = $server->getAttribute('web_port');
	$ret['can_use_apt'] = ((isset($server->ulteo_system) && $server->ulteo_system == 1)?true:false);
	$ret['server_online'] = $server_online;
	$ret['sessions'] = $sessions;
	$ret['has_sessions'] = $has_sessions;
	$ret['total_sessions'] = $total;
	if (isset($pagechanger))
		$ret['pagechanger'] = $pagechanger;
	$ret['applications'] = $applications;
	$ret['applications_available'] = $applications_available;
	$ret['applications_all'] = $applications_all;
	$ret['servers_all'] = $servers_all;
	$ret['servers_replication'] = $servers_replication;
	
	return $ret;
}

function server_display_role_aps($server, $var) {
	$can_do_action = $var['can_do_action'];
	$can_use_apt = $var['can_use_apt'];
	$server_online = $var['server_online'];
	$applications = $var['applications'];
	$applications_available = $var['applications_available'];
	$applications_all = $var['applications_all'];
	$servers_all = $var['servers_all'];
	$servers_replication = $var['servers_replication'];
	$has_sessions = $var['has_sessions'];
	$sessions = $var['sessions'];
	$total = $var['total_sessions'];
	if (array_key_exists('pagechanger', $var))
		$pagechanger = $var['pagechanger'];
	
	$tasks = array();
	$apps_in_remove = array();
	$apps_in_install = array();
	if ($server_online) {
		$tasks = $var['tasks'];
		$apps_in_install = $var['apps_in_install'];
		$apps_in_remove = $var['apps_in_remove'];
	}
	
// 	if ($server->type == 'windows' && isset($server->windows_domain) && ! is_null($server->windows_domain)) {
// 		echo '<tr><td>';
// 		echo _('Inside Active Directory domain').': ';
// 		echo '</td><td>';
// 		echo $server->windows_domain;
// 		echo '</td></tr>';
// 	}
	
	echo '<table>';
	echo '<tr><td>';
	echo _('Number of available sessions on this server').': ';
	echo '</td><td>';
	if ($can_do_action) {
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'" />';
		echo '<input type="hidden" name="action" value="available_sessions" />';
		echo '<input type="button" value="-" onclick="field_increase(\'number\', -1);" /> ';
	}
	echo '<input type="text" id="number" name="max_sessions" value="'.$server->getNbMaxSessions().'" size="3" onchange="field_check_integer(this);" />';
	if ($var['can_do_action']) {
		echo ' <input type="button" value="+" onclick="field_increase(\'number\', 1);" />';
		echo ' <input type="button" value="'._('default').'" onclick="$(\'number\').value=\''.$server->getDefaultMaxSessions().'\';" />';
		echo ' <input type="submit" value="'._('change').'" />';
		echo '</form>';
	}
	echo '</td></tr>';

	echo '</table>';
	
	if ($server_online && $can_do_action && $can_use_apt) {
		echo '<div class="section">';
		echo '<h2>'._('Install an application').'</h2>';
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="action" value="install_line">';
		echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'">';
		echo '<input type="text" name="line"> ';
		echo '<input type="submit" value="'._('Install from a package name').'">';
		echo '</form>';
		echo '<br />';

		echo '<div id="installableApplicationsList">';
		echo '<a href="javascript:;" onclick="toggleInstallableApplicationsList(\''.$server->fqdn.'\'); return false;"><div style="width: 16px; height: 16px; float: left;" id="installableApplicationsList_ajax"></div></a><div style="float: left;"><a href="javascript:;" onclick="toggleInstallableApplicationsList(\''.$server->fqdn.'\'); return false;">&nbsp;'._('more options').'</a></div>';
		echo '<div style="clear: both;"></div>';
		echo '<div id="installableApplicationsList_content" style="display: none;"><script type="text/javascript">Event.observe(window, \'load\', function() { offContent(\'installableApplicationsList\'); });</script></div>';
		echo '</div>';

		echo '<div id="installableApplicationsListDefault" style="display: none; visibility: hidden;">';
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="action" value="install_line">';
		echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'">';
		echo '<table>';
		echo '<tr>';
		echo '<td>'._('Category').'</td>';
		echo '<td><div id="installable_applications_category"></div></td>';
		echo '<td></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'._('Application').'</td>';
		echo '<td><div id="installable_applications_application"></div></td> ';
		echo '<td><input type="submit" value="'._('Install').'" /></td>';
		echo '<td></td>';
		echo '</tr>';
		echo '</table>';
		echo '</form>';
		echo '</div>';
		echo '</div>';
		
		echo '<div class="section">';
		echo '<h2>'._('Upgrade').'</h2>';
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="action" value="upgrade">';
		echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'">';
		echo '<input type="submit" value="'._('Upgrade the internal system and applications').'">';
		echo '</form>';
		echo '</div>';
	}
	
	// Application part
	if (count($applications_all) > 0) {
		$count = 0;
		echo '<div class="section">';
		echo '<h2>'._('Applications available on this server').'</h2>';
		echo '<table border="0" cellspacing="1" cellpadding="3">';
		
		if (count($applications) > 0) {
			foreach ($applications as $app) {
				$content = 'content'.(($count++%2==0)?1:2);
				$remove_in_progress = in_array($app, $apps_in_remove);
			
				echo '<tr class="'.$content.'">';
				echo '<td>';
				echo '<img src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" /> ';
				echo '<a href="applications.php?action=manage&id='.$app->getAttribute('id').'">';
				echo $app->getAttribute('name').'</a>';
				echo '</td>';
				if ($server_online && $can_do_action && $can_use_apt) {
					echo '<td>';
					if ($remove_in_progress)
						echo 'remove in progress';
					else {
						echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to remove this application from this server?').'\');">';
						echo '<input type="hidden" name="action" value="del" />';
						echo '<input type="hidden" name="name" value="Application_Server" />';
						echo '<input type="hidden" name="server" value="'.$server->fqdn.'" />';
						echo '<input type="hidden" name="application" value="'.$app->getAttribute('id').'" />';
						echo '<input type="submit" value="'._('Remove from this server').'" />';
						echo '</form>';
					}
				}
				
				echo '</td>';
				echo '</tr>';
			}
		}
		
		foreach ($apps_in_install as $app) {
			$content = 'content'.(($count++%2==0)?1:2);
			
			echo '<tr class="'.$content.'">';
			echo '<td>';
			echo '<a href="applications.php?action=manage&id='.$app->getAttribute('id').'">';
			echo $app->getAttribute('name').'</a>';
			echo '</td>';
			echo '<td>'._('install in progress').'</td>';
			echo '</tr>';
			}
		
		if (count($applications_available) > 0 && $can_do_action && $can_use_apt) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'"><form action="actions.php" method="post">';
			echo '<input type="hidden" name="action" value="add" />';
			echo '<input type="hidden" name="name" value="Application_Server" />';
			echo '<input type="hidden" name="server" value="'.$server->fqdn.'" />';
			echo '<td>';
			
			echo '<select name="application">';
			foreach ($applications_available as $app)
				echo '<option value="'.$app->getAttribute('id').'">'.$app->getAttribute('name').'</option>';
			echo '</select>';
			
			echo '</td>';
			echo '<td><input type="submit" value="'._('Install on this server').'" /></td>';
			echo '</form></tr>';
		}
		
		echo '</table>';
		echo "</div>\n";
	}

	// Server Replication part
	if (count($servers_replication)>0 && $can_use_apt && $can_do_action) {
		echo '<div class="section">'; // div replication
		echo '<h2>'._('Replication').'</h2>';
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="action" value="replication" />';
		echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'" />';

		echo '<table border="0" cellspacing="1" cellpadding="3">';
		foreach($servers_replication as $server_) {
		echo '<tr>';
		echo '<td><input class="input_checkbox" type="checkbox" name="servers[]" value="'.$server_->fqdn.'" /></td>';
		echo '<td><a href="servers.php?action=manage&fqdn='.$server_->fqdn.'">'.$server_->fqdn.'</a></td></tr>';
		}
		echo '<tr><td></td><td><input type="submit" value="'._('Replicate on those servers').'" /></td></tr>';
		echo '</table>';
		echo '</form>';
		echo "</div>"; // div replication
	}

	// Tasks part
	if (count($tasks) >0) {
		echo '<div class="section">'; // div tasks
		echo '<h2>'._('Active tasks on this server').'</h2>';
		echo '<table border="0" cellspacing="1" cellpadding="3">';
		echo '<tr class="title">';
		echo '<th>'._('ID').'</th>';
		echo '<th>'._('Type').'</th>';
		echo '<th>'._('Status').'</th>';
		echo '<th>'._('Details').'</th>';
		echo '</tr>';

		$count = 0;
		foreach($tasks as $task) {
			$content = 'content'.(($count++%2==0)?1:2);
			if ($task->failed())
				$status = '<span class="msg_error">'._('Error').'</span>';
			else
				$status = '<span class="msg_ok">'.$task->status.'</span>';
			
			echo '<tr class="'.$content.'">';
			echo '<td><a href="tasks.php?action=manage&id='.$task->id.'">'.$task->id.'</a></td>';
			echo '<td>'.get_class($task).'</td>';
			echo '<td>'.$status.'</td>';
			echo '<td>'.$task->getRequest().'</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo "</div>"; // div tasks
	}

	// Sessions part
	if ($has_sessions) {
		echo '<div>'; // div 1 has_sessions
		$total = Abstract_Session::countByServer($server->fqdn);
		echo '<h2>'.sprintf(_('Active sessions (total: %s)'), $total).'</h2>';
		echo '<div>';
		if (isset($pagechanger))
			echo $pagechanger;
		echo '<table border="0" cellspacing="1" cellpadding="3">';
		foreach($sessions as $session) {
			echo '<form action="sessions.php"><tr>';
			echo '<td>';
			$buf = $session->getAttribute('start_time');
			if (! $buf)
				echo _('Not started yet');
			else
				echo @date('d/m/Y H:i:s', $session->getAttribute('start_time'));
			echo '</td>';
			echo '<td><a href="users.php?action=manage&id='.$session->getAttribute('user_login').'">'.$session->getAttribute('user_displayname').'</td>';
			echo '<td>';
			echo '<input type="hidden" name="info" value="'.$session->id.'" />';
			echo '</td><td><input type="submit" value="'._('Information about this session').'" /></td>';
			echo '</td>';
			echo '</tr></form>';
		}
		echo '</table>';
		if (isset($pagechanger))
			echo $pagechanger;
		echo '</div>';
		echo '</div>';
	}
}

function server_display_role_preparation_fs($server_) {
	$ret = array();
	
	if (Preferences::moduleIsEnabled('ProfileDB') == false && Preferences::moduleIsEnabled('SharedFolderDB') == false) {
		return $ret;
	}
	
	$networkfolders = false;
	if (Preferences::moduleIsEnabled('SharedFolderDB')) {
		$sharedfolderdb = SharedFolderDB::getInstance();
		$networkfolders = $sharedfolderdb->importFromServer($server_->getAttribute('fqdn'));
	}
	
	$profiles = false;
	if (Preferences::moduleIsEnabled('ProfileDB')) {
		$profiledb = ProfileDB::getInstance();
		$profiles = $profiledb->importFromServer($server_->getAttribute('fqdn'));
	}
	
	$ret['NetworkFolders'] =  array();
	$ret['profiles'] =  array();
	$ret['sharedfolders'] =  array();
	$ret['used by profiles'] = array();
	$ret['used by sharedfolders'] = array();
	
	if (is_array($networkfolders)) {
		foreach ($networkfolders as $a_networkfolder) {
			$ret['sharedfolders'][$a_networkfolder->id] = $a_networkfolder;
			if (isset($ret['used by sharedfolders'][$a_networkfolder->id]) === false) {
				$ret['used by sharedfolders'][$a_networkfolder->id] = array();
			}

			$groups = $a_networkfolder->getUserGroups();
			if (is_array($groups) && count($groups) > 0) {
				foreach ($groups as $a_group) {
					$buf = array();
					$buf['id'] = $a_group->getUniqueID();
					$buf['name'] = $a_group->name;
					$ret['used by sharedfolders'][$a_networkfolder->id] []= $buf;
				}
			}
		}
	}
	
	if (is_array($profiles)) {
		foreach ($profiles as $a_networkfolder) {
			$ret['profiles'][$a_networkfolder->id] = $a_networkfolder;
			if (isset($ret['used by profiles'][$a_networkfolder->id]) === false) {
				$ret['used by profiles'][$a_networkfolder->id] = array();
			}
			
			$users = $a_networkfolder->getUsers();
			if (is_array($users) && count($users) > 0) {
				foreach ($users as $a_user) {
					$buf = array();
					$buf['id'] =  $a_user->getAttribute('login');
					$buf['name'] = $a_user->getAttribute('displayname');
					$ret['used by profiles'][$a_networkfolder->id] []= $buf;
				}
			}
		}
	}
	
	return $ret;
}

function server_display_role_fs($server_, $var_) {
	if (Preferences::moduleIsEnabled('ProfileDB') == false && Preferences::moduleIsEnabled('SharedFolderDB') == false) {
		return;
	}
	$datas = array(
		0 => array(
			'name' => _('User profiles on the server'),
			'folder' => $var_['profiles'],
			'usedby' => $var_['used by profiles'],
			'page' => 'users',
		),
		1 => array(
			'name' => _('Shared folders on the server'),
			'folder' => $var_['sharedfolders'],
			'usedby' => $var_['used by sharedfolders'],
			'page' => 'usersgroup',
		)
	);
	
	foreach ($datas as $k => $data) {
		if (is_array($data['folder']) && count($data['folder']) > 0 && is_array($data['usedby'])) {
			$mass_action = false;
			if (count($data['folder']) > 1) {
				foreach ($data['folder'] as $a_networkfolder) {
					if ($a_networkfolder->isUsed())
						continue;

					$mass_action = true;
					break;
				}
			}
			echo '<h3>'.$data['name'].'</h3>';
			$count = 0;
			echo '<table id="available_networkfolder_table_'.$k.'" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
			echo '<thead>';
			echo '<tr class="title">';
			if (isset($mass_action) && $mass_action === true)
				echo '<th class="unsortable"></th>';
			if ($k != 0)
				echo '<th>'._('Name').'</th>';
			echo '<th class="unsortable">'.(($k == 0)?_('Owner'):_('Used by')).'</th>';
			echo '<th>'._('Status').'</th>';
			if (isset($mass_action) && $mass_action === true)
				echo '<th class="unsortable"></th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			foreach ($data['folder'] as $a_networkfolder) {
				
				$content = 'content'.(($count++%2==0)?1:2);
				echo '<tr class="'.$content.'">';
				if (count($data['folder']) > 1 && isset($mass_action) && $mass_action === true) {
					echo '<td>';
					if (! $a_networkfolder->isUsed())
						echo '<input class="input_checkbox" type="checkbox" name="ids[]" value="'.$a_networkfolder->id.'" />';
					echo '</td>';
				}
				if ($k != 0) {
					echo '<td>';
					if ($data['page'] !== 'users') {
						echo '<a href="sharedfolders.php?action=manage&id='.$a_networkfolder->id.'">'.$a_networkfolder->name.'</a>';
					}
					else {
						echo $a_networkfolder->name;
					}
					echo '</td>';
				}
				echo '<td>';
				if (array_key_exists($a_networkfolder->id, $data['usedby']) &&  (is_null($data['page']) === false)) {
					$objs = $data['usedby'][$a_networkfolder->id];
					if ($k != 0) {
						echo '<ul>';
						foreach ($objs as $a_obj) {
							echo '<li>';
							echo '<a href="'.$data['page'].'.php?action=manage&id='.$a_obj['id'].'">'.$a_obj['name'].'</a>';
							echo '</li>';
						}
						echo '</ul>';
					} else {
						$a_obj = array_pop($objs);
						echo '<a href="'.$data['page'].'.php?action=manage&id='.$a_obj['id'].'">'.$a_obj['name'].'</a>';
					}
				}
				echo '</td>';
				echo '<td>';
				echo '<span class="msg_'.NetworkFolder::colorStatus($a_networkfolder->status).'">'.NetworkFolder::textStatus($a_networkfolder->status).'</span>';
				echo '</td>';
				echo '<td>';
				if (! $a_networkfolder->isUsed()) {
					echo '<form action="actions.php" method="post" onsubmit="return confirm(\''.(($k == 0)?_('Are you sure you want to delete this user profile?'):_('Are you sure you want to delete this network folder?')).'\');">';
					echo '<input type="hidden" name="name" value="'.(($k == 0)?'Profile':'SharedFolder').'" />';
					echo '<input type="hidden" name="action" value="del" />';
					echo '<input type="hidden" name="ids[]" value="'.$a_networkfolder->id.'" />';
					echo '<input type="submit" value="'._('Delete').'" />';
					echo '</form>';
				}
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody>';

			if (count($data['folder']) > 1 && isset($mass_action) && $mass_action === true) {
				$content = 'content'.(($count++%2==0)?1:2);
				echo '<tfoot>';
				echo '<tr class="'.$content.'">';
				echo '<td colspan="'.(($k != 0)?4:3).'">';
				echo '<a href="javascript:;" onclick="markAllRows(\'available_networkfolder_table_'.$k.'\'); return false">'._('Mark all').'</a>';
				echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'available_networkfolder_table_'.$k.'\'); return false">'._('Unmark all').'</a>';
				echo '</td>';
				echo '<td>';
				echo '<form action="actions.php" method="post" onsubmit="return confirm(\''.(($k == 0)?_('Are you sure you want to delete these user profiles?'):_('Are you sure you want to delete these network folders?')).'\') && updateMassActionsForm(this, \'available_networkfolder_table_'.$k.'\');">';
				echo '<input type="hidden" name="name" value="'.(($k == 0)?'Profile':'SharedFolder').'" />';
				echo '<input type="hidden" name="action" value="del" />';
				echo '<input type="submit" name="to_production" value="'._('Delete').'"/>';
				echo '</form>';
				echo '</td>';
				echo '</tr>';
				echo '</tfoot>';
			}

			echo '</table>';
			echo '<br />';
		}
	}
	
	if ($server_->isOnline()) {
		echo '<h3>'._('Action').'</h3>';
		echo '<table>';
		echo '<tr><form action="actions.php" method="post"><td>';
		echo '<input type="hidden" name="name" value="SharedFolder" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="text" name="sharedfolder_name" value="" />';
		echo '<input type="hidden" name="sharedfolder_server" value="'.$server_->fqdn.'" />';
		echo '</td><td><input type="submit" value="'._('Create this shared folder').'" /></td>';
		echo '</form></tr>';
		echo '</table>';
	}
}
