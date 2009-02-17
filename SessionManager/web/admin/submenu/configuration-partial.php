<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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

if (isset($_GET['mode']) && $_GET['mode'] == 'mysql') {
	echo '<div class="container" style="background: #fff; border-top: 1px solid #ccc; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;">';
	echo _('Database settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=general">'._('System settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=application_server_settings">'._('Server settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-profile.php">'._('Profile settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=session_settings_defaults">'._('Session settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=web_interface_settings">'._('Web interface settings').'</a>';
	echo '</div>';
} elseif (isset($_GET['mode']) && $_GET['mode'] == 'general') {
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=mysql">'._('Database settings').'</a>';
	echo '</div>';
	echo '<div class="container" style="background: #fff; border-top: 1px solid #ccc; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;">';
	echo _('System settings');
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=application_server_settings">'._('Server settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-profile.php">'._('Profile settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=session_settings_defaults">'._('Session settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=web_interface_settings">'._('Web interface settings').'</a>';
	echo '</div>';
} elseif (isset($_GET['mode']) && $_GET['mode'] == 'application_server_settings') {
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=mysql">'._('Database settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=general">'._('System settings').'</a>';
	echo '</div>';
	echo '<div class="container" style="background: #fff; border-top: 1px solid #ccc; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;">';
	echo _('Server settings');
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-profile.php">'._('Profile settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=session_settings_defaults">'._('Session settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=web_interface_settings">'._('Web interface settings').'</a>';
	echo '</div>';
} elseif (isset($_GET['mode']) && $_GET['mode'] == 'session_settings_defaults') {
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=mysql">'._('Database settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=general">'._('System settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=application_server_settings">'._('Server settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-profile.php">'._('Profile settings').'</a>';
	echo '</div>';
	echo '<div class="container" style="background: #fff; border-top: 1px solid #ccc; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;">';
	echo _('Session settings');
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=web_interface_settings">'._('Web interface settings').'</a>';
	echo '</div>';
} elseif (isset($_GET['mode']) && $_GET['mode'] == 'web_interface_settings') {
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=mysql">'._('Database settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=general">'._('System settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=application_server_settings">'._('Server settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-profile.php">'._('Profile settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=session_settings_defaults">'._('Session settings').'</a>';
	echo '</div>';
	echo '<div class="container" style="background: #fff; border-top: 1px solid #ccc; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;">';
	echo _('Web interface settings');
	echo '</div>';
} else {
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=mysql">'._('Database settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=general">'._('System settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=application_server_settings">'._('Server settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-profile.php">'._('Profile settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=session_settings_defaults">'._('Session settings').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="configuration-partial.php?mode=web_interface_settings">'._('Web interface settings').'</a>';
	echo '</div>';
}
