<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008
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

if (! checkAuthorization('viewStatus'))
	redirect('index.php');


if (isset($_GET['info'])) {
	$session = Abstract_Session::load($_GET['info']);

	if (! $session)
		redirect('sessions.php');

	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);
	$mods_enable = $prefs->get('general','module_enable');
	if (! in_array('ApplicationDB',$mods_enable))
		$show_apps = false;
	else
	{
		$show_apps = true;
		$applicationDB = ApplicationDB::getInstance();
	}

//FIX ME?
	$session->getStatus();

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
		foreach ($servers as $fqdn => $data) {
			echo '<li>';
			echo '<a href="servers.php?action=manage&fqdn='.$fqdn.'">'.$fqdn.'</a>';
			if ($role == Server::SERVER_ROLE_APS)
				echo ' (<span class="msg_'.Session::colorStatus($data['status']).'">'.Session::textStatus($data['status']).'</span>)';
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

		$running_apps = $session->getRunningApplications();
		if (count($running_apps) == 0) {
			echo _('No running application');
		} else {
			echo '<ul>';
			echo '<table>';
			foreach ($running_apps as $access_id => $app_id) {
				if (is_null($app_id)) {
					Logger::warning('main', '(admin/sessions) Application ID is NULL for access_id \''.$access_id.'\', session \''.$session->getAttribute('id').'\'');
					continue;
				}

				$myapp = $applicationDB->import($app_id);
				if (! is_object($myapp)) {
					Logger::warning('main', '(admin/sessions) Unable to import application \''.$app_id.'\' for session \''.$session->getAttribute('id').'\'');
					continue;
				}

				echo '<tr><td>';
				echo '<img src="media/image/cache.php?id='.$myapp->getAttribute('id').'" alt="" title="" /> <a href="applications.php?action=manage&id='.$myapp->getAttribute('id').'">'.$myapp->getAttribute('name').'</a>';
				echo '</td><td>';
				echo '</td></tr>';
			}
			echo '</table>';
			echo '</ul>';
		}
	}

	echo '<h2>'._('Kill this session').'</h2>';
	echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to kill this session?').'\');">';
	echo '  <input type="hidden" name="name" value="Session" />';
	echo '	<input type="hidden" name="action" value="del" />';
	echo '	<input type="hidden" name="selected_session[]" value="'.$session->id.'" />';
	echo '	<input type="submit" value="'._('Kill this session').'" />';
	echo '</form>';

	echo '</div>';
	page_footer();
}

else {
	page_header();

	$total = Abstract_Session::countByStatus();

	echo '<h1>'.sprintf(_('Sessions (total: %s)'), $total).'</h1>';

	if ($total > $prefs->get('general', 'max_items_per_page')) {
		if (! isset($_GET['start']) || (! is_numeric($_GET['start']) || $_GET['start'] >= $total))
			$start = 0;
		else
			$start = $_GET['start'];

		$pagechanger = get_pagechanger('sessions.php?', $prefs->get('general', 'max_items_per_page'), $total);

		$sessions = Abstract_Session::load_partial($prefs->get('general', 'max_items_per_page'), $start);
	} else
		$sessions = Abstract_Session::load_all();

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

		$i = 0;
		foreach ($sessions as $session) {
			$css_class = 'content'.(($i++%2==0)?1:2);

			echo '	<tr class="'.$css_class.'">';
			if (count($sessions) > 1)
				echo '		<td><input class="input_checkbox" type="checkbox" name="selected_session[]" value="'.$session->id.'" /></td>';
			echo '		<td><a href="sessions.php?info='.$session->id.'">'.$session->id.'</td>';
			echo '		<td><ul>';
			if (is_array($session->getAttribute('servers'))) {
				foreach ($session->getAttribute('servers') as $role => $servers) {
					if (count($servers) == 0)
						continue;

					echo '<li>'.$role.'</li>';
					echo '<ul>';
					foreach ($servers as $fqdn => $data)
						echo '<li><a href="servers.php?action=manage&fqdn='.$fqdn.'">'.$fqdn.'</a></li>';
					echo '</ul>';
				}
			}
			echo '		</ul></td>';
			echo '		<td><a href="users.php?action=manage&id='.$session->getAttribute('user_login').'">'.$session->getAttribute('user_displayname').'</td>';
			echo '		<td>'.$session->stringStatus().'</td>';
			echo '		<td style="vertical-align: middle;">';
			echo '		<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to kill this session?').'\');">';
			echo '  		<input type="hidden" name="name" value="Session" />';
			echo '			<input type="hidden" name="action" value="del" />';
			echo '			<input type="hidden" name="selected_session[]" value="'.$session->id.'" />';
			echo '			<input type="submit" value="'._('Kill').'" />';
			echo '		</form>';
			echo '		</td>';
			echo '	</tr>';
		}
		echo '</tbody>';
		$css_class = 'content'.(($i++%2==0)?1:2);
		if (count($sessions) > 1) {
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
		foreach (Session::getAllStates() as $state) {
			$total = Abstract_Session::countByStatus($state);
			if ($total == 0)
				continue;

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
