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


if (isset($_POST['join'])) {
	$session = Abstract_Session::load($_POST['join']);

	if (! $session)
		redirect($_SERVER['HTTP_REFERER']);

	$view_only = 'Yes';
	if (isset($_POST['active']))
		$view_only = 'No';

	$invite = new Invite(gen_unique_string());
	$invite->session = $session->id;
	$invite->settings = array(
		'invite_email'	=>	'admin',
		'view_only'		=>	($view_only == 'Yes')?1:0,
		'access_id'		=>	((isset($_POST['access_id']) && $_POST['access_id'] != '')?$_POST['access_id']:Session::MODE_DESKTOP),
		'client'		=>	'browser'
	);
	$invite->email = 'none';
	$invite->valid_until = (time()+(60*30));
	Abstract_Invite::save($invite);

	$token = new Token(gen_unique_string());
	$token->type = 'invite';
	$token->link_to = $invite->id;
	$token->valid_until = (time()+(60*30));
	Abstract_Token::save($token);

	$server = Abstract_Server::load($session->server);

	redirect($server->getBaseURL(true).'/index.php?token='.$token->id);
} elseif (isset($_GET['info'])) {
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

	echo '<h1>'._('Sessions').'</h1>';

	echo '<h2>'._('Information').'</h2>';

	echo '<ul>';
	echo '<li><strong>'._('Servers:').'</strong>';
	foreach ($session->getAttribute('servers') as $server)
		echo ' <a href="servers.php?action=manage&fqdn='.$server.'">'.$server.'</a>';
	echo '</li>';
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
			echo _('No application running');
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
				/*if ($session->getAttribute('mode') == Session::MODE_APPLICATIONS && $session->getAttribute('status') == Session::SESSION_STATUS_ACTIVE) {
					echo '<form action="sessions.php" method="post" onsubmit="popupOpen2(this)">';
					echo '	<input type="hidden" id="desktop_size" value="auto" />';
					echo '	<input type="hidden" id="session_debug_true" value="0" />';
					echo '	<input type="hidden" name="join" value="'.$session->id.'" />';
					echo '	<input type="hidden" name="access_id" value="'.$access_id.'" />';
					echo '	<input type="submit" name="passive" value="'._('Observe this application').'" />';
					echo '	<input type="submit" name="active" value="'._('Join this application').'" />';
					echo '</form>';
				}*/
				echo '</td></tr>';
			}
			echo '</table>';
			echo '</ul>';
		}
	}

	/*if ($session->getAttribute('mode') == Session::MODE_DESKTOP && $session->getAttribute('status') == Session::SESSION_STATUS_ACTIVE) {
		echo '<h2>'._('Connect to or observe this session').'</h2>';
		echo '<form action="sessions.php" method="post" onsubmit="popupOpen2(this)">';
		echo '	<input type="hidden" id="desktop_size" value="auto" />';
		echo '	<input type="hidden" id="session_debug_true" value="0" />';
		echo '	<input type="hidden" name="join" value="'.$session->id.'" />';
		echo '	<input type="hidden" name="access_id" value="'.Session::MODE_DESKTOP.'" />';
		echo '	<input type="submit" name="passive" value="'._('Observe this session').'" />';
		echo '	<input type="submit" name="active" value="'._('Join this session').'" />';
		echo '</form>';
	}*/

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
		echo '<table class="main_sub sortable" id="sessions_list_table" border="0" cellspacing="1" cellpadding="3">';
		echo '	<tr class="title">';
		if (count($sessions) > 1)
			echo '		<th class="unsortable"></th>';
		echo '		<th>'._('Session').'</th>';
		echo '		<th>'._('Server').'</th>';
		echo '		<th>'._('User').'</th>';
		echo '		<th>'._('Status').'</th>';
		echo '	</tr>';

		$i = 0;
		foreach ($sessions as $session) {
			$css_class = 'content'.(($i++%2==0)?1:2);

			echo '	<tr class="'.$css_class.'">';
			if (count($sessions) > 1)
				echo '		<td><input class="input_checkbox" type="checkbox" name="selected_session[]" value="'.$session->id.'" /></td>';
			echo '		<td><a href="sessions.php?info='.$session->id.'">'.$session->id.'</td>';
			echo '		<td><a href="servers.php?action=manage&fqdn='.$session->server.'">'.$session->server.'</td>';
			echo '		<td><a href="users.php?action=manage&id='.$session->getAttribute('user_login').'">'.$session->getAttribute('user_displayname').'</td>';
			echo '		<td>'.$session->stringStatus().'</td>';
			echo '		<td>';
			echo '		<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to kill this session?').'\');">';
			echo '  		<input type="hidden" name="name" value="Session" />';
			echo '			<input type="hidden" name="action" value="del" />';
			echo '			<input type="hidden" name="selected_session[]" value="'.$session->id.'" />';
			echo '			<input type="submit" value="'._('Kill').'" />';
			echo '		</form>';
			echo '		</td>';
			echo '	</tr>';
		}
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
