<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

function locale2unix($locale_) {
	if (preg_match('/[a-z]+_[A-Z]+\.[a-zA-Z-0-9]+/', $locale_))
		return $locale_;

	$locale = strtolower($locale_);
	$locales = array(
		'ar'	=>	'ar_AE',
		'en'	=>	'en_US',
		'ja'	=>	'ja_JP',
	);

	if (! preg_match('/[a-zA-Z-_]/', $locale))
		$locale = $locales['en'];

	if (strlen($locale) == 2) {
		if (array_key_exists($locale, $locales))
			$locale = $locales[$locale];
		else
			$locale = $locale.'_'.strtoupper($locale);
	}
	elseif (strlen($locale) == 5)
		$locale = substr($locale, 0, 2).'_'.strtoupper(substr($locale, -2));

	$locale .= '.UTF-8';

	return $locale;
}

if (array_key_exists('lang', $_REQUEST))
	setlocale(LC_ALL, locale2unix($_REQUEST['lang']));
$domain = 'uovdclient';
bindtextdomain($domain, LOCALE_DIR);
textdomain($domain);

header('Content-Type: text/xml; charset=utf-8');

$dom = new DomDocument('1.0', 'utf-8');

$root = $dom->createElement('translations');
$dom->appendChild($root);

$translations = array(
	'close'							=>	_('Close'),

	'session_manager'				=>	_('Session Manager'),
	'login'							=>	_('Login'),
	'password'						=>	_('Password'),
	'use_local_credentials'			=>	_('Use local credentials'),
	'use_local_credentials_yes'		=>	_('Yes'),
	'use_local_credentials_no'		=>	_('No'),
	'mode'							=>	_('Mode'),
	'mode_desktop'					=>	_('Desktop'),
	'mode_portal'					=>	_('Portal'),
	'fullscreen'					=>	_('Fullscreen'),
	'fullscreen_yes'				=>	_('Yes'),
	'fullscreen_no'					=>	_('No'),
	'language'						=>	_('Language'),
	'keyboard_layout'				=>	_('Keyboard layout'),
	'debug'							=>	_('Debug'),
	'debug_yes'						=>	_('Yes'),
	'debug_no'						=>	_('No'),
	

	'advanced_settings'				=>	_('Advanced settings'),
	'connect'						=>	_('Connect'),

	'system_compatibility_check_1'	=>	_('Checking for system compatibility'),
	'system_compatibility_check_2'	=>	_('If this is your first time here, a Java security window will show up and you have to accept it to use the service.'),
	'system_compatibility_check_3'	=>	_('You are advised to check the "<em>Always trust content from this publisher</em>" checkbox.'),

	'system_compatibility_error_1'	=>	_('System compatibility error'),
	'system_compatibility_error_2'	=>	_('Java is not available either on your system or in your web browser.'),
	'system_compatibility_error_3'	=>	_('Please install Java extension for your web browser or contact your administrator.'),
	'system_compatibility_error_4'	=>	_('You have not accepted the Java security window.'),
	'system_compatibility_error_5'	=>	_('You <strong>cannot</strong> have access to this service.'),

	'loading_ovd'					=>	_('Loading Open Virtual Desktop'),
	'welcome'						=>	_('Welcome!'),
	'suspend'						=>	_('Suspend'),
	'logout'						=>	_('Logout'),

	'desktop_fullscreen_text1'		=>	_('The Ulteo OVD session runs in a separated window'),
	'desktop_fullscreen_text2'		=>	str_replace(array('[A]', '[/A]'), 
								array('<a href="javascript:;" onclick="$(\'ulteoapplet\').switchBackFullscreenWindow(); return false;">', '</a>'),
								_('Click [A]here[/A] to switch back to your session')),

	'my_apps'						=>	_('My applications'),
	'running_apps'					=>	_('Running applications'),
	'my_files'						=>	_('My files')
);

foreach ($translations as $id => $string) {
	$node = $dom->createElement('translation');
	$node->setAttribute('id', $id);
	$node->setAttribute('string', $string);
	$root->appendChild($node);
}

$js_translations = array(
	'sessionmanager_host_example'		=>	_('Example: sm.ulteo.com'),
	
	'no_sessionmanager_host'		=>	_('Usage: missing "sessionmanager_host" parameter'),
	'no_login_parameter'			=>	_('Usage: missing "login" parameter'),
	'no_password_parameter'			=>	_('Usage: missing "password" parameter'),
	'unable_to_reach_sm'			=>	_('Unable to reach the Session Manager'),

	'auth_failed'					=>	_('Authentication failed: please double-check your password and try again'),
	'in_maintenance'				=>	_('The system is on maintenance mode, please contact your administrator for more information'),
	'internal_error'				=>	_('An internal error occured, please contact your administrator'),
	'invalid_user'					=>	_('You specified an invalid login, please double-check and try again'),
	'service_not_available'			=>	_('The service is not available, please contact your administrator for more information'),
	'unauthorized_session_mode'		=>	_('You are not authorized to launch a session in this mode'),
	'user_with_active_session'		=>	_('You already have an active session'),

	'window_onbeforeunload'			=>	_('You will be disconnected from your OVD session.'),

	'session_expire_in_3_minutes'	=>	_('Your session is going to end in 3 minutes, please save all your data now!'),

	'session_close_unexpected'		=>	_('Server: session closed unexpectedly'),
	'session_end_ok'				=>	_('Your session has ended, you can now close the window'),
	'session_end_unexpected'		=>	_('Your session has ended unexpectedly'),
	'error_details'					=>	_('error details'),
	'close_this_window'				=>	_('Close this window'),
	'start_another_session'			=>	_('Click <a href="javascript:;" onclick="hideEnd(); showLogin(); return false;">here</a> to start a new session'),

	'suspend'						=>	_('suspend'),
	'resume'						=>	_('resume')
);

foreach ($js_translations as $id => $string) {
	$node = $dom->createElement('js_translation');
	$node->setAttribute('id', $id);
	$node->setAttribute('string', $string);
	$root->appendChild($node);
}

echo $dom->saveXML();
die();
