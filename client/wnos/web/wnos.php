<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

define('WNOS_CONF_FILE', '/etc/ulteo/wnos/config.inc.php');
define('LOCALE_DIR', '/usr/share/locale');

require_once(WNOS_CONF_FILE);
require_once(dirname(__FILE__) . '/functions.inc.php');

// Flush PHP output cache
while (ob_get_level())
	ob_end_clean();

header('Content-Type: text/plain; charset=utf-8');

if (! defined('OPTION_LANGUAGE_DEFAULT')) {
	define('OPTION_LANGUAGE_DEFAULT', 'en_EN');
}
setlocale(LC_ALL, OPTION_LANGUAGE_DEFAULT);
$domain = 'uovdclient';
bindtextdomain($domain, LOCALE_DIR);
textdomain($domain);
$tr = array(
	'auth_failed' => _('Authentication failed: please double-check your password and try again'),
	'in_maintenance' => _('The system is on maintenance mode, please contact your administrator for more information'),
	'internal_error' => _('An internal error occured, please contact your administrator'),
	'invalid_user' => _('You specified an invalid login, please double-check and try again'),
	'service_not_available' => _('The service is not available, please contact your administrator for more information'),
	'unauthorized_session_mode' => _('You are not authorized to launch a session in this mode'),
	'user_with_active_session' => _('You already have an active session'),
	'unable_to_reach_sm' => _('Unable to reach the Session Manager'),
	'loading_ovd' => _('Connecting to the session manager'),
	'wait_aps' => _('Waiting server for session'),
	'session_end_unexpected' => _('Your session has ended unexpectedly'),
	'no_sessionmanager_host' => _('Usage: missing "sessionmanager_host" parameter'),
);

if (! defined('OPTION_SM_HOST')) {
	error($tr['no_sessionmanager_host']);
}
$sessionmanager_url = 'https://'.OPTION_SM_HOST.'/ovd/client';

switch ($_GET['command']) {
	case 'sysinit': 
		perform_sysinit($tr, $sessionmanager_url); 
		break;
	case 'signon': 
		perform_signon($tr, $sessionmanager_url);
		break;
		
	case 'signoff': 
		perform_signoff($tr, $sessionmanager_url); 
		break;
		
	case 'logout': 
		perform_logout($tr, $sessionmanager_url); 
		break;
}
