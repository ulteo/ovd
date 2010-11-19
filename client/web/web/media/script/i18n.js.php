<?php
/**
 * Copyright (C) 2010 Ulteo SAS
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

header('Content-Type: text/javascript');

echo "var i18n = new Hash();\n";
echo "i18n.set('auth_failed', '"._('Authentication failed: please double-check your password and try again')."');\n";
echo "i18n.set('in_maintenance', '"._('The system is on maintenance mode, please contact your administrator for more information')."');\n";
echo "i18n.set('internal_error', '"._('An internal error occured, please contact your administrator')."');\n";
echo "i18n.set('invalid_user', '"._('You specified an invalid login, please double-check and try again')."');\n";
echo "i18n.set('service_not_available', '"._('The service is not available, please contact your administrator for more information')."');\n";
echo "i18n.set('unauthorized_session_mode', '"._('You are not authorized to launch a session in this mode')."');\n";
echo "i18n.set('user_with_active_session', '"._('You already have an active session')."');\n";

echo "i18n.set('session_expire_in_3_minutes', '"._('Your session is going to end in 3 minutes, please save all your data now!')."');\n";

echo "i18n.set('session_close_unexpected', '"._('Server: session closed unexpectedly')."');\n";
echo "i18n.set('session_end_ok', '"._('Your session has ended, you can now close the window')."');\n";
echo "i18n.set('session_end_unexpected', '"._('Your session has ended unexpectedly')."');\n";
echo "i18n.set('error_details', '"._('error details')."');\n";
echo "i18n.set('close_this_window', '"._('Close this window')."');\n";
echo "i18n.set('start_another_session', '"._('Click <a href="javascript:;" onclick="hideEnd(); showLogin(); return false;">here</a> to start a new session')."');\n";

echo "i18n.set('suspend', '"._('suspend')."');\n";
echo "i18n.set('resume', '"._('resume')."');\n";
