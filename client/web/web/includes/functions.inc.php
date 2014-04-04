<?php
/**
 * Copyright (C) 2010-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012, 2014
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2013
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

require_once(dirname(__FILE__).'/core.inc.php');


function return_error($errno_, $errstr_) {
	$dom = new DomDocument('1.0', 'utf-8');
	$node = $dom->createElement('error');
	$node->setAttribute('id', $errno_);
	$node->setAttribute('message', $errstr_);
	$dom->appendChild($node);
	return $dom->saveXML();
}


function get_available_languages() {
	return array(
// 		array('id' => 'af', 'english_name' => 'Afrikaans'),
// 		array('id' => 'sq', 'english_name' => 'Albanian'),
		array('id' => 'ar-ae', 'english_name' => 'Arabic', 'local_name' => 'العربية'),
		array('id' => 'eu-es', 'english_name' => 'Basque', 'local_name' => 'Euskara'),
 		array('id' => 'bg', 'english_name' => 'Bulgarian', 'local_name' => 'Български'),
// 		array('id' => 'be', 'english_name' => 'Belarusian'),
		array('id' => 'zh-cn', 'english_name' => 'Chinese', 'local_name' => '中文'),
// 		array('id' => 'hr', 'english_name' => 'Croatian'),
		array('id' => 'ca-es', 'english_name' => 'Catalan', 'local_name' => 'Català'),
// 		array('id' => 'cs', 'english_name' => 'Czech', 'local_name' => 'Česky'),
		array('id' => 'da-dk', 'english_name' => 'Danish', 'local_name' => 'Dansk'),
		array('id' => 'nl', 'english_name' => 'Dutch', 'local_name' => 'Nederlands'),
		array('id' => 'en-us', 'english_name' => 'English (US)'),
		array('id' => 'en-gb', 'english_name' => 'English (GB)'),
// 		array('id' => 'et', 'english_name' => 'Estonian'),
// 		array('id' => 'fo', 'english_name' => 'Faeroese'),
		array('id' => 'fi', 'english_name' => 'Finnish', 'local_name' => 'Suomi'),
// 		array('id' => 'fr-be', 'english_name' => 'French (Belgium)', 'local_name' => 'Français (Belgique)'),
// 		array('id' => 'fr-ca', 'english_name' => 'French (Canada)', 'local_name' => 'Français'),
// 		array('id' => 'fr-ch', 'english_name' => 'French (Switzerland)', 'local_name' => 'Français (Suisse)'),
		array('id' => 'fr', 'english_name' => 'French (France)', 'local_name' => 'Français'),
// 		array('id' => 'fr-lu', 'english_name' => 'French (Luxembourg)', 'local_name' => 'Français'),
		array('id' => 'de', 'english_name' => 'German', 'local_name' => 'Deutsch'),
		array('id' => 'el-gr', 'english_name' => 'Greek', 'local_name' => 'Ελληνικά'),
		array('id' => 'fa-ir', 'english_name' => 'Persian', 'local_name' => 'فارسی'),
		array('id' => 'he-il', 'english_name' => 'Hebrew', 'local_name' => 'עברית'),
// 		array('id' => 'hi', 'english_name' => 'Hindi'),
		array('id' => 'hu', 'english_name' => 'Hungarian', 'local_name' => 'Magyar'),
		array('id' => 'is', 'english_name' => 'Icelandic', 'local_name' => 'Íslenska'),
		array('id' => 'id', 'english_name' => 'Indonesian', 'local_name' => 'Bahasa Indonesia'),
		array('id' => 'it', 'english_name' => 'Italian', 'local_name' => 'Italiano'),
		array('id' => 'ja-jp', 'english_name' => 'Japanese', 'local_name' => '日本語'),
// 		array('id' => 'ko', 'english_name' => 'Korean', 'local_name' => '한국어'),
// 		array('id' => 'lv', 'english_name' => 'Latvian'),
// 		array('id' => 'lt', 'english_name' => 'Lithuanian', 'local_name' => 'Lietuvių'),
// 		array('id' => 'mt', 'english_name' => 'Maltese'),
		array('id' => 'nb-no', 'english_name' => 'Norwegian (Bokmal)', 'local_name' => 'Norsk (Bokmål)'),
// 		array('id' => 'no', 'english_name' => 'Norwegian (Nynorsk)'),
		array('id' => 'pl', 'english_name' => 'Polish', 'local_name' => 'Polski'),
// 		array('id' => 'pt', 'english_name' => 'Portuguese', 'local_name' => 'Português'),
		array('id' => 'pt-br', 'english_name' => 'Portuguese (Brazil)', 'local_name' => 'Português (Brasil)'),
		array('id' => 'ro', 'english_name' => 'Romanian', 'local_name' => 'Română'),
		array('id' => 'ru', 'english_name' => 'Russian', 'local_name' => 'Русский'),
		array('id' => 'sk', 'english_name' => 'Slovak', 'local_name' => 'Slovenčina'),
// 		array('id' => 'sl', 'english_name' => 'Slovenian'),
// 		array('id' => 'sb', 'english_name' => 'Sorbian'),
		array('id' => 'es', 'english_name' => 'Spanish (Spain)', 'local_name' => 'Español (España)'),
		array('id' => 'sv-se', 'english_name' => 'Swedish', 'local_name' => 'Svenska'),
// 		array('id' => 'th', 'english_name' => 'Thai'),
// 		array('id' => 'tn', 'english_name' => 'Tswana'),
// 		array('id' => 'tr', 'english_name' => 'Turkish', 'local_name' => 'Türkçe'),
// 		array('id' => 'uk', 'english_name' => 'Ukrainian', 'local_name' => 'Українська'),
// 		array('id' => 've', 'english_name' => 'Venda'),
// 		array('id' => 'vi', 'english_name' => 'Vietnamese', 'local_name' => 'Tiếng Việt'),
	);
}

function get_available_keymaps() {
	return array(
		array('id' => 'ar', 'name' => 'Arabic'),
		array('id' => 'da', 'name' => 'Danish'),
		array('id' => 'de', 'name' => 'German'),
		array('id' => 'en-us', 'name' => 'English (US)'),
		array('id' => 'en-gb', 'name' => 'English (GB)'),
		array('id' => 'es', 'name' => 'Spanish'),
		array('id' => 'fi', 'name' => 'Finnish'),
		array('id' => 'fr', 'name' => 'French'),
		array('id' => 'fr-be', 'name' => 'French (Belgium)'),
		array('id' => 'hr', 'name' => 'Croatian'),
		array('id' => 'it', 'name' => 'Italian'),
		array('id' => 'ja', 'name' => 'Japanese'),
		array('id' => 'lt', 'name' => 'Lithuanian'),
		array('id' => 'lv', 'name' => 'Latvian'),
		array('id' => 'no', 'name' => 'Norwegian (Nynorsk)'),
		array('id' => 'pl', 'name' => 'Polish'),
		array('id' => 'pt', 'name' => 'Portuguese'),
		array('id' => 'pt-br', 'name' => 'Portuguese (Brazil)'),
		array('id' => 'ru', 'name' => 'Russian'),
		array('id' => 'sl', 'name' => 'Slovenian'),
		array('id' => 'sv', 'name' => 'Swedish'),
		array('id' => 'tr', 'name' => 'Turkish')
	);
}

function language_is_supported($languages_list, $lang) {
	foreach ($languages_list as $available_language) {
		if ($available_language['id'] == $lang)
			return true;
	}
	
	return false;
}

// parse list of comma separated language tags and sort it by the quality value
function detectBrowserLanguage($languages_list) {
	$languages = array();
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$languagesQ = array();
		$languageList = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$languageRanges = explode(',', trim($languageList));
		foreach ($languageRanges as $languageRange) {
			if (preg_match('/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/', trim($languageRange), $match)) {
				if (language_is_supported($languages_list, strtolower($match[1]))) {
					if (!isset($match[2])) {
						$match[2] = '1.0';
					} else {
						$match[2] = (string) floatval($match[2]);
					}
					if (!isset($languagesQ[$match[2]])) {
						$languagesQ[$match[2]] = array();
					}
					$languagesQ[$match[2]][] = strtolower($match[1]);
				}
			}
		}
		krsort($languagesQ);
		foreach ($languagesQ as $langQ) {
			foreach ($langQ as $lang) {
				$languages[] = $lang;
			}
		}
	}
	
	return $languages;
}

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


function get_available_translations($lang) {
	setlocale(LC_ALL, locale2unix($lang));
	$domain = 'uovdclient';
	bindtextdomain($domain, LOCALE_DIR);
	textdomain($domain);
	
	$translations = array(
		'close'							=>	_('Close'),

		'session_manager'				=>	_('Session Manager'),
		'login'							=>	_('Login'),
		'login_detected'		=>	_('Login (detected)'),
		'password'						=>	_('Password'),
		'use_local_credentials'			=>	_('Use local credentials'),
		'use_local_credentials_yes'		=>	_('Yes'),
		'use_local_credentials_no'		=>	_('No'),
		'mode'							=>	_('Mode'),
		'mode_desktop'					=>	_('Desktop'),
		'mode_portal'					=>	_('Portal'),
		'rdp_mode'						=>	_('Type'),
		'fullscreen'					=>	_('Fullscreen'),
		'fullscreen_yes'				=>	_('Yes'),
		'fullscreen_no'					=>	_('No'),
		'language'						=>	_('Language'),
		'keyboard_layout'				=>	_('Keyboard layout'),
		'debug'							=>	_('Debug'),
		'debug_yes'						=>	_('Yes'),
		'debug_no'						=>	_('No'),
		
		'keyboard_config' => _('Keyboard method'),
		'keyboard_config_scancode' => _('Scancode'),
		'keyboard_config_unicode' => _('Unicode'),
		'keyboard_config_unicode_lime' => _('Unicode use local IME'),

		'advanced_settings'				=>	_('Advanced settings'),
		'connect'						=>	_('Connect'),

		'system_compatibility_check_1'	=>	_('Checking for system compatibility'),
		'system_compatibility_check_2'	=>	_('If this is your first time here, a Java security window will show up and you will have to accept it to use the service.'),
		'system_compatibility_check_3'	=>	_('You are advised to check the "<em>Always trust content from this publisher</em>" checkbox.'),

		'system_compatibility_error_1'	=>	_('System compatibility error'),
		'system_compatibility_error_2'	=>	_('Java is either not available on your system or in your web browser.'),
		'system_compatibility_error_3'	=>	_('Please install the Java extension for your web browser or contact your administrator.'),
		'system_compatibility_error_4'	=>	_('You have not accepted the Java security window.'),
		'system_compatibility_error_5'	=>	_('You <strong>cannot</strong> have access to this service.'),

		'loading_ovd'					=>	_('Loading Open Virtual Desktop'),
		'unloading_ovd'					=>  _('Disconnecting Open Virtual Desktop'),
		'welcome'						=>	_('Welcome!'),
		'suspend'						=>	_('Suspend'),
		'logout'						=>	_('Logout'),

		'desktop_fullscreen_text1'		=>	_('The Ulteo OVD session runs in a separate window'),
		'desktop_fullscreen_text2'		=>	str_replace(
									array('[A]', '[/A]'),
									array('<a href="javascript:;">', '</a>'),
									_('Click [A]here[/A] to switch back to your session')
								),

		'my_apps'						=>	_('My applications'),
		'running_apps'					=>	_('Running applications'),
		'my_files'						=>	_('My files')
	);

	$js_translations = array(
		'sessionmanager_host_example'		=>	str_replace('%EXAMPLE%', 'sm.test.demo', _('Example: %EXAMPLE%')),
		
		'no_sessionmanager_host'		=>	_('Usage: missing "sessionmanager_host" parameter'),
		'no_login_parameter'			=>	_('Usage: missing "login" parameter'),
		'no_password_parameter'			=>	_('Usage: missing "password" parameter'),
		'unable_to_reach_sm'			=>	_('Unable to reach the Session Manager'),

		'auth_failed'					=>	_('Authentication failed: please double-check your password and try again'),
		'in_maintenance'				=>	_('The system is in maintenance mode, please contact your administrator for more information'),
		'internal_error'				=>	_('An internal error occurred, please contact your administrator'),
		'invalid_user'					=>	_('You specified an invalid login, please double-check and try again'),
		'service_not_available'			=>	_('The service is not available, please contact your administrator for more information'),
		'unauthorized'					=>	_('You are not authorized to launch a session. Please contact your administrator for more information'),
		'user_with_active_session'		=>	_('You already have an active session'),

		'window_onbeforeunload'			=>	_('You will be disconnected from your OVD session.'),

		'session_expire_in_3_minutes'	=>	_('Your session is going to end in 3 minutes, please save all your data now!'),
		'session_time_restriction_expire'=>	_('Your session is going to be disconnected in %MINUTES% minutes because of the logon time restriction policy'),

		'session_close_unexpected'		=>	_('Server: session closed unexpectedly'),
		'session_end_ok'				=>	_('Your session has ended, you can now close the window'),
		'session_end_unexpected'		=>	_('Your session has ended unexpectedly'),
		'error_details'					=>	_('error details'),
		'close_this_window'				=>	_('Close this window'),
		'start_another_session'			=>	str_replace(
									array('[A]', '[/A]'),
									array('<a href="javascript:;">', '</a>'),
									_('Click [A]here[/A] to start a new session')
								),

		'suspend'						=>	_('suspend'),
		'resume'						=>	_('resume'),
		'want_logout'						=>	_('Are you sure you want to logout (# apps running) ?')
	);

	return array($translations, $js_translations);
}

function unparse_url($parsed_url) { 
	$scheme   = array_key_exists('scheme', $parsed_url)?$parsed_url['scheme'].'://':'';
	$host     = array_key_exists('host', $parsed_url)?$parsed_url['host']:'';
	$port     = array_key_exists('port', $parsed_url)?':'.$parsed_url['port']:'';
	$creds    = array_key_exists('user', $parsed_url)?$parsed_url['user']:'';
	if (strlen($creds) > 0) {
		if (array_key_exists('pass', $parsed_url))
			$creds.= ':'.$parsed_url['pass'];
		
		$creds.= '@';
	}
	
	$path     = array_key_exists('path', $parsed_url)?$parsed_url['path']:'';
	$query    = array_key_exists('query', $parsed_url)?'?'.$parsed_url['query']:'';
	$fragment = array_key_exists('fragment', $parsed_url)?'#'.$parsed_url['fragment']:'';
	
	return $scheme.$creds.$host.$port.$path.$query.$fragment;
}


function get_ie_version() {
	if (($msiepos = stripos($_SERVER['HTTP_USER_AGENT'], 'msie')) !== false) {
		$ie_version = floatval(substr($_SERVER['HTTP_USER_AGENT'], $msiepos+5, strpos($_SERVER['HTTP_USER_AGENT'], ';', $msiepos+5)));
	} else {
		$ie_version = PHP_INT_MAX;
	}
	return $ie_version;
}

function get_users_list() {
	if (! defined('SESSIONMANAGER_HOST'))
		return false;

	global $sessionmanager_url;

	$sm = new SessionManager($sessionmanager_url);
	$ret = $sm->query('userlist');

	$dom = new DomDocument('1.0', 'utf-8');
	$buf = @$dom->loadXML($ret);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$users_node = $dom->getElementsByTagname('users')->item(0);
	if (is_null($users_node))
		return false;

	$users = array();
	foreach ($users_node->childNodes as $user_node) {
		if ($user_node->hasAttribute('login'))
			$users[$user_node->getAttribute('login')] = ((strlen($user_node->getAttribute('displayname')) > 32)?substr($user_node->getAttribute('displayname'), 0, 32).'...':$user_node->getAttribute('displayname'));
	}
	natcasesort($users);

	if (count($users) == 0)
		return false;

	return $users;
}

function short_list_field($id, $disabled, $selected, $items) {
	foreach ($items as $key => $val) {
		if (substr($val, -8) === "_gettext") {
			$gettext = $val;
			$label = "";
		} else {
			$gettext = "";
			$label = $val;
		}
		echo "<input class=\"input_radio\" type=\"radio\" value=\"${key}\" name=\"${id}\" id=\"${id}_${key}\"".
			($selected == $key ? " checked=\"checked\"" : "").
			($disabled ? " disabled=\"disabled\"" : "").
			"><label for=\"${id}_${key}\" id=\"${gettext}\">${label}</label>";
	}
}

function long_list_field($id, $disabled, $selected, $items) {
	echo "<select id=\"${id}\" ".($disabled ? ' disabled="disabled"' : "").">";
	foreach ($items as $key => $val) {
		if (substr($val, -8) === "_gettext") {
			$gettext = $val;
			$label = "";
		} else {
			$gettext = "";
			$label = $val;
		}
		echo "<option id=\"${gettext}\" value=\"${key}\" ".($selected == $key ? "selected=\"selected\"" : "").">${val}</option>";
	}
	echo "</select>";
}
