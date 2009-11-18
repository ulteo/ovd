<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
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

	$invite = new Invite(gen_string(5));
	$invite->session = $session->id;
	$invite->settings = array(
		'invite_email'	=>	'admin',
		'view_only'		=>	($view_only == 'Yes')?1:0,
		'access_id'		=>	((isset($_POST['access_id']) && $_POST['access_id'] != '')?$_POST['access_id']:'desktop')
	);
	$invite->email = 'none';
	$invite->valid_until = (time()+(60*30));
	Abstract_Invite::save($invite);

	$token = new Token(gen_string(5));
	$token->type = 'invite';
	$token->link_to = $invite->id;
	$token->valid_until = (time()+(60*30));
	Abstract_Token::save($token);

	$server = Abstract_Server::load($session->server);

	redirect($server->getBaseURL(true).'/index.php?token='.$token->id);
} elseif (isset($_POST['mass_action']) && $_POST['mass_action'] == 'kill') {
	if (isset($_POST['kill_sessions']) && is_array($_POST['kill_sessions'])) {
		foreach ($_POST['kill_sessions'] as $session) {
			$session = Abstract_Session::load($session);

			if (is_object($session))
				$session->orderDeletion();
		}
	}

	redirect($_SERVER['HTTP_REFERER']);
} elseif (isset($_POST['action']) && $_POST['action'] == 'kill') {
	$session = Abstract_Session::load($_POST['session']);

	if (is_object($session))
		$session->orderDeletion();

	redirect($_SERVER['HTTP_REFERER']);
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
		$apps = $applicationDB->getList(false);
	}

//FIX ME?
	$session->getStatus();

	page_header();

	echo '<h1>'._('Sessions').'</h1>';

	echo '<h2>'._('Information').'</h2>';

	echo '<ul>';
	echo '<li><strong>'._('Server:').'</strong> <a href="servers.php?action=manage&fqdn='.$session->getAttribute('server').'">'.$session->getAttribute('server').'</a></li>';
	if (isset($session->settings['windows_server']))
		echo '<li><strong>'._('Windows Server:').'</strong> <a href="servers.php?action=manage&fqdn='.$session->settings['windows_server'].'">'.$session->settings['windows_server'].'</a></li>';
	echo '<li><strong>'._('User:').'</strong> <a href="users.php?action=manage&id='.$session->getAttribute('user_login').'">'.$session->getAttribute('user_displayname').'</a></li>';
	echo '<li><strong>'._('Type:').'</strong> ';
	if ($session->getAttribute('mode') == 'desktop')
		echo _('Desktop');
	elseif ($session->getAttribute('mode') == 'portal')
		echo _('Portal');
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

	if ($show_apps && $session->hasAttribute('applications')) {
		echo '<h2>'._('Running applications').'</h2>';

		if (count($session->getAttribute('applications')) == 0) {
			echo _('No application running');
		} else {
			echo '<ul>';
			echo '<table>';
			foreach ($session->getAttribute('applications') as $access_id => $app_id) {
				if (is_null($app_id))
					continue;

				$myapp = $apps[$app_id];
				if (! is_object($myapp))
					continue;

				echo '<tr><td>';
				echo '<img src="media/image/cache.php?id='.$myapp->getAttribute('id').'" alt="" title="" /> <a href="applications.php?action=manage&id='.$myapp->getAttribute('id').'">'.$myapp->getAttribute('name').'</a>';
				echo '</td><td>';
				if ($session->getAttribute('mode') == 'portal' && $session->getAttribute('status') == 2) {
					echo '<form action="sessions.php" method="post" onsubmit="popupOpen2(this)">';
					echo '	<input type="hidden" id="desktop_size" value="auto" />';
					echo '	<input type="hidden" id="session_debug_true" value="0" />';
					echo '	<input type="hidden" name="join" value="'.$session->id.'" />';
					echo '	<input type="hidden" name="access_id" value="'.$access_id.'" />';
					echo '	<input type="submit" name="passive" value="'._('Observe this application').'" />';
					echo '	<input type="submit" name="active" value="'._('Join this application').'" />';
					echo '</form>';
				}
				echo '</td></tr>';
			}
			echo '</table>';
			echo '</ul>';
		}
	}

	if ($session->getAttribute('mode') == 'desktop' && $session->getAttribute('status') == 2) {
		echo '<h2>'._('Connect to or observe this session').'</h2>';
		echo '<form action="sessions.php" method="post" onsubmit="popupOpen2(this)">';
		echo '	<input type="hidden" id="desktop_size" value="auto" />';
		echo '	<input type="hidden" id="session_debug_true" value="0" />';
		echo '	<input type="hidden" name="join" value="'.$session->id.'" />';
		echo '	<input type="hidden" name="access_id" value="desktop" />';
		echo '	<input type="submit" name="passive" value="'._('Observe this session').'" />';
		echo '	<input type="submit" name="active" value="'._('Join this session').'" />';
		echo '</form>';
	}

	echo '<h2>'._('Kill this session').'</h2>';
	echo '<form action="sessions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to kill this session?').'\');">';
	echo '	<input type="hidden" name="action" value="kill" />';
	echo '	<input type="hidden" name="session" value="'.$session->id.'" />';
	echo '	<input type="submit" value="'._('Kill this session').'" />';
	echo '</form>';

	echo '</div>';
	page_footer();
}

else {
	page_header();

	echo '<h1>'._('Sessions').'</h1>';

	$sessions = Sessions::getAll();
	if (is_array($sessions) && count($sessions) > 0) {
		echo '<form action="sessions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to kill selected sessions?').'\');">';
		echo '	<input type="hidden" name="mass_action" value="kill" />';
		echo '<table class="main_sub sortable" id="sessions_list_table" border="0" cellspacing="1" cellpadding="3">';
		echo '	<tr class="title">';
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
			echo '		<td><input class="input_checkbox" type="checkbox" name="kill_sessions[]" value="'.$session->id.'" /></td><form></form>';
			echo '		<td><a href="sessions.php?info='.$session->id.'">'.$session->id.'</td>';
			echo '		<td><a href="servers.php?action=manage&fqdn='.$session->server.'">'.$session->server.'</td>';
			echo '		<td><a href="users.php?action=manage&id='.$session->getAttribute('user_login').'">'.$session->getAttribute('user_displayname').'</td>';
			echo '		<td>'.$session->stringStatus().'</td>';
			echo '		<td>';
			echo '		<form action="sessions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to kill this session?').'\');">';
			echo '			<input type="hidden" name="action" value="kill" />';
			echo '			<input type="hidden" name="session" value="'.$session->id.'" />';
			echo '			<input type="submit" value="'._('Kill').'" />';
			echo '		</form>';
			echo '		</td>';
			echo '	</tr>';
		}
		$css_class = 'content'.(($i++%2==0)?1:2);
		echo '<tfoot>';
		echo '	<tr class="'.$css_class.'">';
		echo '		<td colspan="5"><a href="javascript:;" onclick="markAllRows(\'sessions_list_table\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'sessions_list_table\'); return false">'._('Unmark all').'</a></td>';
		echo '<td><input type="submit" name="kill" value="'._('Kill').'" /></td>';
		echo '	</tr>';
		echo '</tfoot>';
		echo '</table>';
		echo '</form>';
	} else {
		echo _('No active session');
		echo '<br /><br />';
	}

	echo '</div>';
	page_footer();
}
