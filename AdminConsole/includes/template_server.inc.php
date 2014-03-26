<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2012
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2014
 * Author David LECHEVALIER <david@ulteo.com> 2012
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2014
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
	
 	$applications_all = $_SESSION['service']->applications_list();
	$applications = array();
	foreach($server->applications as $application_id => $application_name) {
		if (array_key_exists($application_id, $applications_all))
			$applications[]= $applications_all[$application_id];
	}
	
	uasort($applications, 'application_cmp');
	
	$applications_available = array();
	$static_applications_available = array();
	
	if (!$server_online && count($applications) == 0)
		$applications_all = array();
	
	$servers_replication = $_SESSION['service']->servers_list('online');
	if (is_null($servers_replication)) {
		$servers_replication = array();
	}
	
	foreach($servers_replication as $k => $v) {
		if ($v->id == $server->id)
			unset($servers_replication[$k]);

		if ($v->type != $server->getAttribute('type'))
			unset($servers_replication[$k]);

		if (! array_key_exists(Server::SERVER_ROLE_APS, $v->roles))
			unset($servers_replication[$k]);

		if ($server->hasAttribute('ulteo_system') == false || $server->getAttribute('ulteo_system') == 0)
			unset($servers_replication[$k]);
	}
	$sessions = array();
	
	$total = 0;
	if ($server->hasAttribute('sessions_number')) {
		$total = $server->getAttribute('sessions_number');
	}
	
	if ($total > 0) {
		$has_sessions = true;

		$search_limit = $_SESSION['configuration']['max_items_per_page'];

		if ($total > $search_limit) {
			if (! isset($_GET['start']) || (! is_numeric($_GET['start']) || $_GET['start'] >= $total))
				$start = 0;
			else
				$start = $_GET['start'];

			$pagechanger = get_pagechanger('servers.php?action=manage&id='.$server->id.'&', $search_limit, $total);

			$sessions = $_SESSION['service']->sessions_list_by_server($server->id, $start);
			
		} else
			$sessions = $_SESSION['service']->sessions_list_by_server($server->id);
	} else
		$has_sessions = false;

	$external_name_checklist = array('localhost', '127.0.0.1');
	if (in_array($server->getExternalName(), $external_name_checklist))
		popup_error(sprintf(_('Server "%s": redirection name may be invalid!'), $server->getDisplayName()));
	
	if ($server_online) {
		$apps_in_remove = array();
		$apps_in_install = array();

		$tasks = array();
		if ($server_online) {
			$all_tasks = $_SESSION['service']->tasks_list();
			if (is_null($all_tasks)) {
				$all_tasks = array();
			}
			
			foreach($all_tasks as $task) {
				if ($task->getAttribute('server') != $server->id) {
					continue;
				}
				
				if (! $task->succeed())
					$tasks[]= $task;
			}
			
			foreach($tasks as $task) {
				if ($task->hasAttribute('applications')) {
					continue;
				}
				
				if ($task->getAttribute('type') == 'Task_install') {
					foreach($task->getAttribute('applications') as $app) {
						if (! in_array($app, $apps_in_install))
							$apps_in_install[]= $app;
					}
				}
				
				if ($task->getAttribute('type') == 'Task_remove') {
					foreach($task->getAttribute('applications') as $app) {
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
			
			if ($app->getAttribute('static') == 1)
				$static_applications_available[] = $app;
			else
				$applications_available[] = $app;
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
	$ret['static_applications_available'] = $static_applications_available;
	$ret['applications_all'] = $applications_all;
	$ret['servers_replication'] = $servers_replication;
	
	return $ret;
}

function server_display_role_aps($server, $var) {
	$can_do_action = $var['can_do_action'];
	$can_use_apt = false;
	$server_online = $var['server_online'];
	$applications = $var['applications'];
	$applications_available = $var['applications_available'];
	$static_applications_available = $var['static_applications_available'];
	$applications_all = $var['applications_all'];
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
		echo '<input type="hidden" name="server" value="'.$server->id.'" />';
		echo '<input type="hidden" name="action" value="available_sessions" />';
		echo '<input type="button" value="-" onclick="field_increase(\'number\', -1);" /> ';
	}
	echo '<input type="text" id="number" name="max_sessions" value="'.$server->getAttribute('max_sessions').'" size="3" onchange="field_check_integer(this);" />';
	if ($var['can_do_action']) {
		echo ' <input type="button" value="+" onclick="field_increase(\'number\', 1);" />';
		echo ' <input type="button" value="'._('default').'" onclick="$(\'number\').value=\''.$server->getAttribute('max_sessions_default').'\';" />';
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
		echo '<input type="hidden" name="server" value="'.$server->id.'">';
		echo '<input type="text" name="line"> ';
		echo '<input type="submit" value="'._('Install from a package name').'">';
		echo '</form>';
		echo '<br />';

		echo '<div id="installableApplicationsList">';
		echo '<a href="javascript:;" onclick="toggleInstallableApplicationsList(\''.$server->id.'\'); return false;"><div style="width: 16px; height: 16px; float: left;" id="installableApplicationsList_ajax"></div></a><div style="float: left;"><a href="javascript:;" onclick="toggleInstallableApplicationsList(\''.$server->id.'\'); return false;">&nbsp;'._('more options').'</a></div>';
		echo '<div style="clear: both;"></div>';
		echo '<div id="installableApplicationsList_content" style="display: none;"><script type="text/javascript">Event.observe(window, \'load\', function() { initContent(\'installableApplicationsList\'); });</script></div>';
		echo '</div>';

		echo '<div id="installableApplicationsListDefault" style="display: none; visibility: hidden;">';
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="action" value="install_line">';
		echo '<input type="hidden" name="server" value="'.$server->id.'">';
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
		echo '<input type="hidden" name="server" value="'.$server->id.'">';
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
				echo '<img class="icon32" src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" /> ';
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
						echo '<input type="hidden" name="server" value="'.$server->id.'" />';
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
			echo '<input type="hidden" name="server" value="'.$server->id.'" />';
			echo '<td>';
			
			echo '<select name="application">';
			foreach ($applications_available as $app)
				echo '<option value="'.$app->getAttribute('id').'">'.$app->getAttribute('name').'</option>';
			echo '</select>';
			
			echo '</td>';
			echo '<td><input type="submit" value="'._('Install on this server').'" /></td>';
			echo '</form></tr>';
		}
		
		if (count($static_applications_available) > 0 && $can_do_action && $can_use_apt) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'"><form action="actions.php" method="post">';
			echo '<input type="hidden" name="action" value="add" />';
			echo '<input type="hidden" name="name" value="Application_Server" />';
			echo '<input type="hidden" name="server" value="'.$server->id.'" />';
			echo '<td>';
			
			echo '<select name="application">';
			foreach ($static_applications_available as $app)
				echo '<option value="'.$app->getAttribute('id').'">'.$app->getAttribute('name').'</option>';
			echo '</select>';
			
			echo '</td>';
			echo '<td><input type="submit" value="'._('Add to this server').'" /></td>';
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
		echo '<input type="hidden" name="server" value="'.$server->id.'" />';

		echo '<table border="0" cellspacing="1" cellpadding="3">';
		foreach($servers_replication as $server_) {
		echo '<tr>';
		echo '<td><input class="input_checkbox" type="checkbox" name="servers[]" value="'.$server_->id.'" /></td>';
		echo '<td><a href="servers.php?action=manage&id='.$server_->id.'">'.$server_->getDisplayName().'</a></td></tr>';
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
			echo '<td>'.$task->getAttribute('request').'</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo "</div>"; // div tasks
	}

	// Sessions part
	if ($has_sessions) {
		echo '<div>'; // div 1 has_sessions
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
	
	$ret['NetworkFolders'] =  array();
	$ret['profiles'] =  array();
	$ret['sharedfolders'] =  array();
	$ret['used by profiles'] = array();
	$ret['used by sharedfolders'] = array();
	$ret['profiles_currently_used'] = array();
	$ret['sharedfolders_currently_used'] = array();
	$ret['orphanfolders'] = array();
	
	if ($server_->hasAttribute('orphan_folders'))
		$ret['orphanfolders'] = $server_->getAttribute('orphan_folders');
	
	if ($server_->hasAttribute('shared_folders')) {
		foreach ($server_->getAttribute('shared_folders') as $a_networkfolder_id => $a_networkfolder) {
			$ret['sharedfolders'][$a_networkfolder_id] = $a_networkfolder;
			if (isset($ret['used by sharedfolders'][$a_networkfolder_id]) === false) {
				$ret['used by sharedfolders'][$a_networkfolder_id] = array();
			}

			if (array_key_exists('groups', $a_networkfolder)) {
				foreach ($a_networkfolder['groups'] as $mode => $networkfolder) {
					foreach ($networkfolder as $group_id => $group_name) {
						$buf = array();
						$buf['id'] = $group_id;
						$buf['name'] = "$group_name ($mode)";
						$ret['used by sharedfolders'][$a_networkfolder_id] []= $buf;
					}
				}
			}
			
			if (array_key_exists('sessions_nb', $a_networkfolder) && $a_networkfolder['sessions_nb'] > 0) {
				$ret['sharedfolders_currently_used'][]= $a_networkfolder_id;
			}
		}
	}
	
	if ($server_->hasAttribute('user_profiles')) {
		foreach ($server_->getAttribute('user_profiles') as $a_networkfolder_id => $a_networkfolder) {
			$ret['profiles'][$a_networkfolder_id] = $a_networkfolder;
			if (isset($ret['used by profiles'][$a_networkfolder_id]) === false) {
				$ret['used by profiles'][$a_networkfolder_id] = array();
			}
			
			if (array_key_exists('users', $a_networkfolder)) {
				foreach ($a_networkfolder['users'] as $user_login => $user_display_name) {
					$buf = array();
					$buf['id'] =  $user_login;
					$buf['name'] = $user_display_name;
					$ret['used by profiles'][$a_networkfolder_id] []= $buf;
				}
			}
			
			if (array_key_exists('sessions_nb', $a_networkfolder) && $a_networkfolder['sessions_nb'] > 0) {
				$ret['profiles_currently_used'][]= $a_networkfolder_id;
			}
		}
	}
	
	return $ret;
}

function server_display_role_fs($server_, $var_) {
	if (is_array($var_['profiles']) && count($var_['profiles']) > 0 && is_array($var_['used by profiles'])) {
		$show_action_column = (isAuthorized('manageServers') && $server_->isOnline() &&  count($var_['profiles_currently_used']) < count($var_['profiles']));
		$show_mass_action = ($show_action_column && count($var_['profiles_currently_used']) + 1 < count($var_['profiles']));
		
		echo '<h3>'._('User profiles on the server').'</h3>';
		$count = 0;
		echo '<table id="available_networkfolder_table_profile" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
		echo '<thead>';
		echo '<tr class="title">';
		if ($show_mass_action === true)
			echo '<th class="unsortable"></th>';
		echo '<th class="unsortable">'._('Owner').'</th>';
		echo '<th>'._('Status').'</th>';
		if ($show_action_column === true)
			echo '<th class="unsortable"></th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ($var_['profiles'] as $a_networkfolder) {
			
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			if ($show_mass_action === true) {
				echo '<td>';
				if (! in_array( $a_networkfolder['id'], $var_['profiles_currently_used']))
					echo '<input class="input_checkbox" type="checkbox" name="ids[]" value="'.$a_networkfolder['id'].'" />';
				echo '</td>';
			}
			
			echo '<td>';
			if (array_key_exists($a_networkfolder['id'], $var_['used by profiles'])) {
				$objs = $var_['used by profiles'][$a_networkfolder['id']];
					$a_obj = array_pop($objs);
					echo '<a href="users.php?action=manage&id='.$a_obj['id'].'">'.$a_obj['name'].'</a>';
			}
			echo '</td>';
			echo '<td>';
			echo '<span class="msg_'.NetworkFolder::colorStatus($a_networkfolder['status']).'">'.NetworkFolder::textStatus($a_networkfolder['status']).'</span>';
			echo '</td>';
			
			if ($show_action_column === true) {
				echo '<td>';
				if (! in_array( $a_networkfolder['id'], $var_['profiles_currently_used'])) {
					
					echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this user profile?').'\');">';
					echo '<input type="hidden" name="name" value="Profile" />';
					echo '<input type="hidden" name="action" value="del" />';
					echo '<input type="hidden" name="ids[]" value="'.$a_networkfolder['id'].'" />';
					echo '<input type="submit" value="'._('Delete').'" />';
					echo '</form>';
				}
				echo '</td>';
			}
			
			echo '</tr>';
		}
		echo '</tbody>';

		if ($show_mass_action === true) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tfoot>';
			echo '<tr class="'.$content.'">';
			echo '<td colspan="3">';
			echo '<a href="javascript:;" onclick="markAllRows(\'available_networkfolder_table_profile\'); return false">'._('Mark all').'</a>';
			echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'available_networkfolder_table_profile\'); return false">'._('Unmark all').'</a>';
			echo '</td>';
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete these user profiles?').'\') && updateMassActionsForm(this, \'available_networkfolder_table_profile\');">';
			echo '<input type="hidden" name="name" value="Profile" />';
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
	
	if (is_array($var_['sharedfolders']) && count($var_['sharedfolders']) > 0 && is_array($var_['used by sharedfolders'])) {
		$show_action_column = (isAuthorized('manageServers') && $server_->isOnline() && count($var_['sharedfolders_currently_used']) < count($var_['sharedfolders']));
		$show_mass_action = ($show_action_column && count($var_['sharedfolders_currently_used']) + 1 < count($var_['sharedfolders']));
		
		echo '<h3>'._('Shared Folders on the server').'</h3>';
		$count = 0;
		echo '<table id="available_networkfolder_table_sf" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
		echo '<thead>';
		echo '<tr class="title">';
		if ($show_mass_action === true)
			echo '<th class="unsortable"></th>';
		echo '<th>'._('Name').'</th>';
		echo '<th class="unsortable">'._('Used by').'</th>';
		echo '<th>'._('Status').'</th>';
		if ($show_action_column === true)
			echo '<th class="unsortable"></th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ($var_['sharedfolders'] as $a_networkfolder) {
			
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			if ($show_mass_action === true) {
				echo '<td>';
				if (! in_array( $a_networkfolder['id'], $var_['sharedfolders_currently_used']))
					echo '<input class="input_checkbox" type="checkbox" name="ids[]" value="'.$a_networkfolder['id'].'" />';
				echo '</td>';
			}
			
			echo '<td>';
			echo '<a href="sharedfolders.php?action=manage&id='.$a_networkfolder['id'].'">'.$a_networkfolder['name'].'</a>';
			echo '</td>';
			echo '<td>';
			if (array_key_exists($a_networkfolder['id'], $var_['used by sharedfolders'])) {
				$objs = $var_['used by sharedfolders'][$a_networkfolder['id']];
				echo '<ul>';
				foreach ($objs as $a_obj) {
					echo '<li>';
					echo '<a href="usersgroup.php?action=manage&id='.$a_obj['id'].'">'.$a_obj['name'].'</a>';
					echo '</li>';
				}
				echo '</ul>';
			}
			echo '</td>';
			echo '<td>';
			echo '<span class="msg_'.NetworkFolder::colorStatus($a_networkfolder['status']).'">'.NetworkFolder::textStatus($a_networkfolder['status']).'</span>';
			echo '</td>';
			
			if ($show_action_column === true) {
				echo '<td>';
				if (! in_array( $a_networkfolder['id'], $var_['sharedfolders_currently_used'])) {
					
					echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this network folder?').'\');">';
					echo '<input type="hidden" name="name" value="SharedFolder" />';
					echo '<input type="hidden" name="action" value="del" />';
					echo '<input type="hidden" name="ids[]" value="'.$a_networkfolder['id'].'" />';
					echo '<input type="submit" value="'._('Delete').'" />';
					echo '</form>';
				}
				echo '</td>';
			}
			
			echo '</tr>';
		}
		echo '</tbody>';

		if ($show_mass_action === true) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tfoot>';
			echo '<tr class="'.$content.'">';
			echo '<td colspan="4">';
			echo '<a href="javascript:;" onclick="markAllRows(\'available_networkfolder_table_sf\'); return false">'._('Mark all').'</a>';
			echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'available_networkfolder_table_sf\'); return false">'._('Unmark all').'</a>';
			echo '</td>';
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete these network folders?').'\') && updateMassActionsForm(this, \'available_networkfolder_table_sf\');">';
			echo '<input type="hidden" name="name" value="SharedFolder" />';
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
	
	if (is_array($var_['orphanfolders']) && count($var_['orphanfolders']) > 0) {
		$show_action_column = (isAuthorized('manageServers') && $server_->isOnline());
		$show_mass_action = ($show_action_column && count($var_['orphanfolders']) > 1);
		
		echo '<h3>'._('Orphan network folders on the server').'</h3>';
		$count = 0;
		echo '<table id="orphanfolder_table" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
		echo '<thead>';
		echo '<tr class="title">';
		if ($show_mass_action === true)
			echo '<th class="unsortable"></th>';
		echo '<th>'._('Name').'</th>';
		if ($show_action_column === true)
			echo '<th class="unsortable"></th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ($var_['orphanfolders'] as $a_orphanfolder) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			if ($show_mass_action === true) {
				echo '<td>';
				echo '<input class="input_checkbox" type="checkbox" name="ids[]" value="'.$a_orphanfolder.'" />';
				echo '</td>';
			}
			
			echo '<td>'.$a_orphanfolder.'</td>';
			
			if ($show_action_column === true) {
				echo '<td>';
				echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this orphan folder?').'\');">';
				echo '<input type="hidden" name="name" value="NetworkFolder" />';
				echo '<input type="hidden" name="action" value="del" />';
				echo '<input type="hidden" name="ids[]" value="'.$a_orphanfolder.'" />';
				echo '<input type="submit" value="'._('Delete').'" />';
				echo '</form>';
				echo '</td>';
			}
			
			echo '</tr>';
		}
		echo '</tbody>';

		if ($show_mass_action === true) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tfoot>';
			echo '<tr class="'.$content.'">';
			echo '<td colspan="2">';
			echo '<a href="javascript:;" onclick="markAllRows(\'orphanfolder_table\'); return false">'._('Mark all').'</a>';
			echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'orphanfolder_table\'); return false">'._('Unmark all').'</a>';
			echo '</td>';
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete these orphan folders?').'\') && updateMassActionsForm(this, \'orphanfolder_table\');">';
			echo '<input type="hidden" name="name" value="NetworkFolder" />';
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
	
	if ($server_->isOnline()) {
		echo '<h3>'._('Action').'</h3>';
		echo '<table>';
		echo '<tr><form action="actions.php" method="post"><td>';
		echo '<input type="hidden" name="name" value="SharedFolder" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="text" name="sharedfolder_name" value="" />';
		echo '<input type="hidden" name="sharedfolder_server" value="'.$server_->id.'" />';
		echo '</td><td><input type="submit" value="'._('Create this shared folder').'" /></td>';
		echo '</form></tr>';
		echo '</table>';
	}
}
