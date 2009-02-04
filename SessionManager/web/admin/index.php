<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
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
require_once(dirname(__FILE__).'/includes/core.inc.php');

if (!file_exists(SESSIONMANAGER_CONFFILE_SERIALIZED)) {
	// TODO installation
	redirect('configuration.php?action=init');
}

require_once('header.php');
?>
<table style="width: 100%;" border="0" cellspacing="3" cellpadding="5">
	<tr>
		<td style="width: 30%;">
<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Users and Usergroups</h2>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="width: 30%;">
<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Servers and Sessions</h2>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="padding-right: 20px;">
<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Configuration</h2>
</div>
</div>
		</td>
	</tr>
	<tr>
		<td style="height: 10px;" colspan="5">
		</td>
	</tr>
	<tr>
		<td>
<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Applications and Appgroups</h2>
</div>
</div>
		</td>
		<td style="width: 20px;">
		</td>
		<td style="padding-right: 20px;" colspan="3">
<div class="container rounded" style="background: #fff; width: 99%; margin-left: auto; margin-right: auto;">
<div>
	<h2>Status</h2>

	<ul>
<?php
	$active_sessions = Sessions::getAll();
	$count_active_sessions = count($active_sessions);

	$online_servers = Servers::getOnline();
	$count_online_servers = count($online_servers);
	$offline_servers = Servers::getOffline();
	$count_offline_servers = count($offline_servers);
	$broken_servers = Servers::getBroken();
	$count_broken_servers = count($broken_servers);

	echo '<li>';
	echo $count_active_sessions.' ';
	if ($count_active_sessions > 1)
		echo _('active sessions');
	else
		echo _('active session');
	echo '</li>';

	echo '<li>';
	echo $count_online_servers.' ';
	if ($count_online_servers > 1)
		echo _('online servers');
	else
		echo _('online server');
	echo '</li>';

	echo '<li>';
	echo $count_offline_servers.' ';
	if ($count_offline_servers > 1)
		echo _('offline servers');
	else
		echo _('offline server');
	echo '</li>';

	echo '<li>';
	echo $count_broken_servers.' ';
	if ($count_broken_servers > 1)
		echo _('broken servers');
	else
		echo _('broken server');
	echo '</li>';
?>
	</ul>
</div>
</div>
		</td>
	</tr>
</table>
<?php
require_once('footer.php');
