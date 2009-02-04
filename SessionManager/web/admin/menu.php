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
$items = array(
	'servers.php'		=>	_('Servers'),
	'sessions.php'		=>	_('Sessions'),
	'users.php'		=>	_('Users'),
	'usersgroup.php'	=>	_('Usergroups'),
	'applications.php'	=>	_('Applications'),
	'appsgroup.php'	=>	_('Appgroups'),
	'publications.php'	=>	_('Publications'),
	'configuration.php'	=>	_('Configuration'),
	'logs.php'		=>	_('Logs'),
	'logout.php'		=>	_('Logout')
);

$in_menu = basename('http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']);

$i = 0;
echo '<table border="0" cellspacing="0" cellpadding="10">';
echo '<tr>';
foreach($items as $k => $v) {
	echo '<td style="min-width: 60px; height: 81px;text-align: center; vertical-align: middle;';

	if ($in_menu == $k) {
		echo ' background: #eee; border-right: 1px solid #ccc;';
		if ($k != 'servers.php')
			echo ' border-left: 1px solid  #ccc;';
	} else {
		echo ' border-bottom: 1px solid #ccc;';
	}

	echo '" class="menu"><a href="'.$k.'"><img src="media/image/menu/'.$k.'.png" width="32" height="32" alt="'.$v.'" title="'.$v.'" /><br />';
	echo '<span class="menulink';

	if ($in_menu == $k)
		echo '_active';

	echo '">'.$v.'</span></a></td>'."\n";
}
echo '</tr>';
echo '</table>';
