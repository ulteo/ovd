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

if (isset($_POST['join'])) {
	$session = new Session($_POST['join']);

	$view_only = 'Yes';
	if (isset($_POST['active']))
		$view_only = 'No';

	$token = $session->create_token('invite', array('view_only' => $view_only));

	redirect('http://'.$session->server.'/index.php?token='.$token);
} elseif (isset($_POST['mass_action']) && $_POST['mass_action'] == 'kill') {
	if (isset($_POST['kill_sessions']) && is_array($_POST['kill_sessions'])) {
		foreach ($_POST['kill_sessions'] as $session) {
			$session = Abstract_Session::load($session);
			$session->orderDeletion();
		}
	}

	redirect($_SERVER['HTTP_REFERER']);
} elseif (isset($_POST['action']) && $_POST['action'] == 'kill') {
	$session = Abstract_Session::load($_POST['session']);
	$session->orderDeletion();

	redirect($_SERVER['HTTP_REFERER']);
} elseif (isset($_GET['info'])) {
	$session = Abstract_Session::load($_GET['info']);

	//FIX ME ?
// 	if (!$session->is_valid())
// 		redirect('sessions.php');

	require_once('header.php');

	echo '<h1>'._('Sessions').'</h1>';

	echo '<h2>'._('Informations').'</h2>';

	echo '<ul>';
	echo '<li><strong>User:</strong> '.$session->getAttribute('user_displayname').'</li>';
	echo '<li><strong>Started:</strong> FIX ME?</li>';
	echo '<li><strong>Status:</strong> '.$session->stringStatus().'</li>';
	echo '</ul>';

	if ($session->getAttribute('status') == 2) {
		echo '<h2>'._('Connect to or observe this session').'</h2>';
		echo '<form id="joinsession" action="sessions.php" method="post" onsubmit="popupOpen2(this)">';
		echo '	<input type="hidden" id="desktop_size" value="auto" />';
		echo '	<input type="hidden" id="session_debug_true" value="0" />';
		echo '	<input type="hidden" name="join" value="'.$session->id.'" />';
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

	require_once('footer.php');
}

else {
	require_once('header.php');

	echo '<h1>'._('Sessions').'</h1>';

	$sessions = Sessions::getAll();
	if (count($sessions) > 0) {
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
			echo '		<td><input type="checkbox" name="kill_sessions[]" value="'.$session->id.'" /></td><form></form>';
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
	} else
		echo _('No active session');

	require_once('footer.php');
}
