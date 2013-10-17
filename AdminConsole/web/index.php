<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2010
 * Author Omar AKHAM <jeremy@ulteo.com> 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
 * Contributor Stanislav IEVLEV <stanislav.ievlev@gmail.com> 2010
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


$show_servers = isAuthorized('viewServers') || isAuthorized('manageServers');
$show_users = isAuthorized('viewUsers') || isAuthorized('manageUsers');
$show_usersgroups = isAuthorized('viewUsersGroups') || isAuthorized('manageUsersGroups');
$show_applications = isAuthorized('viewApplications') || isAuthorized('manageApplications');
$show_applicationsgroups = isAuthorized('viewApplicationsGroups') || isAuthorized('manageApplicationsGroups');
$show_publications = isAuthorized('viewPublications') || isAuthorized('managePublications');
$show_configuration = isAuthorized('viewConfiguration') || isAuthorized('manageConfiguration');
$show_status = isAuthorized('viewStatus');

page_header();
?>
<h1 style="margin-left: 10px;"><?php echo _('Index'); ?></h1>
<table style="width: 100%;" border="0" cellspacing="3" cellpadding="5">
	<tr>
		<td style="width: 30%; text-align: inherit; vertical-align: top;">
<div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2><?php echo _('Users and User Groups'); ?></h2>

	<ul>
		<?php
			if ($show_users)
				echo '<li><a href="users.php">'._('Users').'</a></li>';
			if ($show_usersgroups)
				echo '<li><a href="usersgroup.php">'._('User Groups').'</a></li>';
		?>
	</ul>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="width: 30%; text-align: inherit; vertical-align: top;">
<div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2><?php echo _('Servers'); ?></h2>
<?php
if ($show_servers) {
?>
	<ul>
		<li><a href="servers.php"><?php echo _('Servers'); ?></a></li>
		<li><a href="servers.php?view=unregistered"><?php echo _('Unregistered Servers'); ?></a></li>
	</ul>
<?php
}
?>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="padding-right: 20px; text-align: inherit; vertical-align: top;">
<div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2><?php echo _('Configuration'); ?></h2>
<?php
if ($show_configuration) {
?>
	<ul>
		<li><a href="configuration-sumup.php"><?php echo _('General Configuration'); ?></a></li>
	</ul>
<?php
}
?>
</div>
</div>
		</td>
	</tr>
	<tr>
		<td style="height: 10px;" colspan="5">
		</td>
	</tr>
	<tr>
		<td style="text-align: inherit; vertical-align: top;">
<div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2><?php echo _('Applications and Application Groups'); ?></h2>

	<ul>
		<?php
		if ($show_applications) {
			echo '<li><a href="applications.php">'._('Applications').'</a></li>';
			echo '<li><a href="applications_webapp.php">'._('Web Applications').'</a></li>';
		}
		if ($show_applicationsgroups)
			echo '<li><a href="appsgroup.php">'._('Application Groups').'</a><br /><br /></li>';
		if ($show_publications) {
			echo '<li><a href="publications.php">'._('Publications').'</a></li>';
			echo '<li><a href="wizard.php">'._('Publication Wizard').'</a></li>';
		}
		?>
	</ul>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="padding-right: 20px; text-align: inherit; vertical-align: top;">
<div class="container rounded" style="background: #eee; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2><?php echo _('System'); ?></h2>

	<span style="text-align: center; margin-left: auto; margin-right: auto;">
	<?php

		if (array_key_exists('system_in_maintenance', $_SESSION['configuration']) &&  $_SESSION['configuration']['system_in_maintenance'] == '1') {
			echo '<span class="msg_error">'._('The system is in maintenance mode').'</span><br /><br />';

			if (isAuthorized('manageServers'))
				echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to switch the system to production mode?').'\');"><input type="hidden" name="name" value="System" /><input type="hidden" name="action" value="change" /><input type="hidden" name="switch_to" value="production" /><input style="background: #05a305; color: #fff; font-weight: bold;" type="submit" value="'._('Switch the system to production mode').'" /></form>';
		} else {
			echo '<span class="msg_ok">'._('The system is in production mode').'</span><br /><br />';

			if (isAuthorized('manageServers'))
				echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to switch the system to maintenance mode?').'\');"><input type="hidden" name="name" value="System" /><input type="hidden" name="action" value="change" /><input type="hidden" name="switch_to" value="maintenance" /><input type="submit" value="'._('Switch the system to maintenance mode').'" /></form>';
		}

	?>
	</span>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="padding-right: 20px; text-align: inherit; vertical-align: top;">
<div class="container rounded" style="background: #eee; width: 99%; margin-left: auto; margin-right: auto;">
<div>
	<h2><?php echo _('Status'); ?></h2>
<?php
if ($show_status) {
	echo '<ul>';
	
	$count_active_sessions = 0;
	$r = $_SESSION['service']->sessions_count();
	if (! is_null($r) && array_key_exists(Session::SESSION_STATUS_ACTIVE, $r)) {
		$count_active_sessions = $r[Session::SESSION_STATUS_ACTIVE];
	}
	
	$count_online_servers = 0;
	$count_offline_servers = 0;
	$count_broken_servers = 0;

	$servers = $_SESSION['service']->servers_list();
	if (is_null($servers)) {
		$servers = array();
	}
	
	foreach($servers as $server) {
		switch($server->status) {
			case Server::SERVER_STATUS_ONLINE:
				$count_online_servers++;
				break;
			case Server::SERVER_STATUS_PENDING:
			case Server::SERVER_STATUS_OFFLINE:
				$count_offline_servers++;
			case Server::SERVER_STATUS_BROKEN:
			default:
				$count_broken_servers++;
		}
	}

	echo '<li>';
	echo $count_active_sessions.' ';
	echo '<a href="sessions.php">';
	if ($count_active_sessions > 1)
		echo _('active sessions');
	else
		echo _('active session');
	echo '</a>';
	echo '</li>';

	echo '<li>';
	echo $count_online_servers.' ';
	echo '<a href="servers.php">';
	if ($count_online_servers > 1)
		echo _('online servers');
	else
		echo _('online server');
	echo '</a>';
	echo '</li>';

	echo '<li>';
	echo $count_offline_servers.' ';
	echo '<a href="servers.php">';
	if ($count_offline_servers > 1)
		echo _('offline servers');
	else
		echo _('offline server');
	echo '</a>';
	echo '</li>';

	echo '<li>';
	echo $count_broken_servers.' ';
	echo '<a href="servers.php">';
	if ($count_broken_servers > 1)
		echo _('broken servers');
	else
		echo _('broken server');
	echo '</a>';
	echo '</li>';
	echo '</ul>';
}
?>
</div>
</div>
		</td>
	</tr>
</table>
<?php
page_footer();
