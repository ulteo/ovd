<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2014
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

if (! checkAuthorization('viewStatus'))
	redirect('index.php');


if (isset($_GET['info'])) {
	$session = $_SESSION['service']->session_info($_GET['info']);
	if (! $session)
		redirect('sessions.php');

	$show_apps = true;
	
	$servers_cache = array();
	foreach ($session->getAttribute('servers') as $role => $servers) {
		foreach ($servers as $server_id => $data) {
			if (array_key_exists($server_id, $servers_cache))
				continue;
			
			$servers_cache[$server_id] =  $_SESSION['service']->server_info($server_id);
		}
	}

	page_header();

	echo '<h1>'.str_replace('%ID%', $session->getAttribute('id'), _('Session - %ID%')).'</h1>';

	echo '<h2>'._('Information').'</h2>';

	echo '<ul>';
	echo '<li><strong>'._('Servers:').'</strong><ul>';
	foreach ($session->getAttribute('servers') as $role => $servers) {
		if (count($servers) == 0)
			continue;

		echo '<li>'.$role.'</li>';
		echo '<ul>';
		foreach ($servers as $server_id => $data) {
			echo '<li>';
			echo '<a href="servers.php?action=manage&id='.$server_id.'">'.$servers_cache[$server_id]->getDisplayName().'</a>';
			if ($role == Server::SERVER_ROLE_APS || $role == Server::SERVER_ROLE_WEBAPPS) {
				echo ' (<span class="msg_'.Session::colorStatus($data['status']).'">'.Session::textStatus($data['status']).'</span>)';
				if ($session->getAttribute('mode') == Session::MODE_DESKTOP && $session->hasAttribute('desktop_server')) {
					if ($server_id == $session->getAttribute('desktop_server') && $role == Server::SERVER_ROLE_APS) {
						echo ' ('._('Desktop server').')';
					}
				}
			}
			else if ($role == Server::SERVER_ROLE_FS) {
				echo '<ul>';
				foreach($data as $i => $info) {
					if ($info['type'] != 'profile') {
						continue;
					}
					
					echo '<li>'._('User profile').'</li>';
				}
				
				foreach($data as $i => $info) {
					if ($info['type'] == 'profile') {
						continue;
					}
					
					echo '<li>'._('Shared foler').' - '.$info['name'].' <em>('.$info['mode'].')</em></li>';
				}
			}
			
			echo '</li>';
		}
		echo '</ul>';
	}
	echo '</ul></li>';
	echo '<li><strong>'._('User:').'</strong> <a href="users.php?action=manage&id='.$session->getAttribute('user_login').'">'.$session->getAttribute('user_displayname').'</a></li>';
	echo '<li><strong>'._('Type:').'</strong> ';
	if ($session->getAttribute('mode') == Session::MODE_DESKTOP)
		echo _('Desktop');
	elseif ($session->getAttribute('mode') == Session::MODE_APPLICATIONS)
		echo _('Applications');
	else
		echo _('Unknown');
	echo '</li>';
	echo '<li><strong>'._('Started:').'</strong> ';
	$buf = $session->getAttribute('start_time');
	if (! $buf)
		echo _('Not started yet');
	else
		echo @date('d/m/Y H:i:s', $session->getAttribute('start_time'));
	echo '</li>';
	echo '<li><strong>'._('Status:').'</strong> '.$session->stringStatus().'</li>';
	echo '</ul>';

	if ($show_apps) {
		echo '<h2>'._('Running applications').'</h2>';

		if (! $session->hasAttribute('instances') || count($session->getAttribute('instances')) == 0) {
			echo _('No running application');
		} else {
			echo '<ul>';
			echo '<table>';
			foreach ($session->getAttribute('instances') as $application_id => $application_name) {
				echo '<tr><td>';
				echo '<img class="icon32" src="media/image/cache.php?id='.$application_id.'" alt="" title="" /> <a href="applications.php?action=manage&id='.$application_id.'">'.$application_name.'</a>';
				echo '</td></tr>';
			}
			echo '</table>';
			echo '</ul>';
		}
		
		echo '<div>';
		echo '<h2>'._('Published applications').'</h2>';
		
		if (! $session->hasAttribute('applications') || count($session->getAttribute('applications')) == 0) {
			echo _('No published application');
		}
		else {
			echo '<ul>';
			echo '<table>';
			foreach ($session->getAttribute('applications') as $application_id => $application_name) {
				echo '<tr><td>';
				echo '<img class="icon32" src="media/image/cache.php?id='.$application_id.'" alt="" title="" /> <a href="applications.php?action=manage&id='.$application_id.'">'.$application_name.'</a>';
				echo '</td></tr>';
			}
			
			echo '</table>';
			echo '</ul>';
		}
		echo '</div>';
	}

	if (isAuthorized('manageSession')) {
		echo '<h2>'._('Kill this session').'</h2>';
		echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to kill this session?').'\');">';
		echo '  <input type="hidden" name="name" value="Session" />';
		echo '	<input type="hidden" name="action" value="del" />';
		echo '	<input type="hidden" name="selected_session[]" value="'.$session->id.'" />';
		echo '	<input type="submit" value="'._('Kill this session').'" />';
		echo '</form>';
	}

	echo '</div>';
	page_footer();
}

else {
	page_header();
	$nb_session_by_status = $_SESSION['service']->sessions_count();
	if (is_null($nb_session_by_status)) {
		$nb_session_by_status = array('total' => 0);
	}
	
	$total = $nb_session_by_status['total'];
	
	$max_items_per_page = $_SESSION['configuration']['max_items_per_page'];

	echo '<h1>'.sprintf(_('Sessions (total: %s)'), $total).'</h1>';

	if ($total > $max_items_per_page) {
		if (! isset($_GET['start']) || (! is_numeric($_GET['start']) || $_GET['start'] >= $total))
			$start = 0;
		else
			$start = $_GET['start'];

		$pagechanger = get_pagechanger('sessions.php?', $max_items_per_page, $total);

		$sessions = $_SESSION['service']->sessions_list($start);
	} else
		$sessions = $_SESSION['service']->sessions_list();

	if (is_array($sessions) && count($sessions) > 0) {
	        $buf = array();
	        foreach ($sessions as $session)
	                $buf[$session->getAttribute('user_login')] = $session;
	        ksort($buf);
	        $sessions = $buf;

		echo '<div>';
		if (isset($pagechanger))
			echo $pagechanger;
		echo '<table style="width: 100%;" border="0" cellspacing="1" cellpadding="3"><tr><td style="vertical-align: top;">';
		echo '<table style="margin-left: 0px; margin-right: auto;" class="main_sub sortable" id="sessions_list_table" border="0" cellspacing="1" cellpadding="3">';
		echo '<thead>';
		echo '	<tr class="title">';
		if (count($sessions) > 1)
			echo '		<th class="unsortable"></th>';
		echo '		<th>'._('Session').'</th>';
		echo '		<th>'._('Servers').'</th>';
		echo '		<th>'._('User').'</th>';
		echo '		<th>'._('Status').'</th>';
		echo '	</tr>';
		echo '</thead>';
		echo '<tbody>';
		
		$server_cache = array();

		$i = 0;
		foreach ($sessions as $session) {
			$css_class = 'content'.(($i++%2==0)?1:2);

			echo '	<tr class="'.$css_class.'">';
			if (isAuthorized('manageSession') && count($sessions) > 1)
				echo '		<td><input class="input_checkbox" type="checkbox" name="selected_session[]" value="'.$session->id.'" /></td>';
			echo '		<td><a href="sessions.php?info='.$session->id.'">'.$session->id.'</td>';
			echo '		<td><ul>';
			if (is_array($session->getAttribute('servers'))) {
				foreach ($session->getAttribute('servers') as $role => $servers) {
					if (count($servers) == 0)
						continue;

					echo '<li>'.$role.'</li>';
					echo '<ul>';
					foreach ($servers as $server_id => $data) {
						$server_name = $server_id;
						
						if (array_key_exists($server_id, $server_cache)) {
							$server_name = $server_cache[$server_id];
						} else {
							$server =  $_SESSION['service']->server_info($server_id); // Avoid to load a whole server just to display the name ...
							if (! is_null($server)) {
								$server_name = $server->getDisplayName();
							}
							$server_cache[$server_id] = $server_name;
						}
						
						echo '<li><a href="servers.php?action=manage&id='.$server_id.'">'.$server_name.'</a></li>';
					}
					echo '</ul>';
				}
			}
			echo '		</ul></td>';
			echo '		<td><a href="users.php?action=manage&id='.$session->getAttribute('user_login').'">'.$session->getAttribute('user_displayname').'</td>';
			echo '		<td>'.$session->stringStatus().'</td>';
			if (isAuthorized('manageSession')) {
				echo '<td style="vertical-align: middle;">';
				echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to kill this session?').'\');">';
				echo '<input type="hidden" name="name" value="Session" />';
				echo '<input type="hidden" name="action" value="del" />';
				echo '<input type="hidden" name="selected_session[]" value="'.$session->id.'" />';
				echo '<input type="submit" value="'._('Kill').'" />';
				echo '</form>';
				echo '</td>';
			}
			echo '	</tr>';
		}
		echo '</tbody>';
		$css_class = 'content'.(($i++%2==0)?1:2);
		if (isAuthorized('manageSession') && count($sessions) > 1) {
			echo '<tfoot>';
			echo '	<tr class="'.$css_class.'">';
			echo '		<td colspan="5"><a href="javascript:;" onclick="markAllRows(\'sessions_list_table\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'sessions_list_table\'); return false">'._('Unmark all').'</a></td>';
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to kill selected sessions?').'\') && updateMassActionsForm(this, \'sessions_list_table\');">';
			echo '  <input type="hidden" name="name" value="Session" />';
			echo '  <input type="hidden" name="action" value="del" />';
			echo '<input type="submit" name="kill" value="'._('Kill').'" />';
			echo '</td>';
			echo '	</tr>';
			echo '</tfoot>';
		}
		echo '</table>';
		echo '</td><td style="vertical-align: top;">';
		echo '<table style="margin-left: auto; margin-right: 0px;" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
		echo '<tr class="title">';
		echo '<th>'._('Status').'</th>';
		echo '<th>'._('Number of sessions').'</th>';
		echo '</tr>';
		echo '<tfoot>';
		$i = 0;
		foreach ($nb_session_by_status as $state => $total) {
			if ($state == 'total') {
				continue;
			}

			$css_class = 'content'.(($i++%2==0)?1:2);
			echo '<tr class="'.$css_class.'">';
			echo '<td><span class="msg_'.Session::colorStatus($state).'">'.Session::textStatus($state).'</span></td>';
			echo '<td style="text-align: right;">'.$total.'</td>';
			echo '</tr>';
		}
		echo '</tfoot>';
		echo '</table>';
		echo '</td></tr></table>';
		if (isset($pagechanger))
			echo $pagechanger;
		echo '</div>';
	} else {
		echo _('No active session');
		echo '<br /><br />';
	}

	echo '</div>';
	page_footer();
}
