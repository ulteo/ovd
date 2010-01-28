<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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

include_once(dirname(__FILE__).'/check.php');

function parse_login_XML($xml_) {
	if (! $xml_ || strlen($xml_) == 0)
		return false;

	$dom = new DomDocument('1.0', 'utf-8');

	$buf = @$dom->loadXML($xml_);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$session_node = $dom->getElementsByTagname('session')->item(0);
	if (is_null($session_node))
		return false;

	if (! $session_node->hasAttribute('mode'))
		return false;

	// It's not a login process to handle the session mode... should be moved somewhere else...
	$_SESSION['mode'] = $session_node->getAttribute('mode');

	$user_node = $dom->getElementsByTagname('user')->item(0);
	if (is_null($user_node))
		return false;

	if (! $user_node->hasAttribute('login'))
		return false;

	// Maybe we should authenticate the user? see do_login();
	$_SESSION['login'] = $user_node->getAttribute('login');

	return true;
}

function parse_session_create_XML($xml_) {
	if (! $xml_ || strlen($xml_) == 0)
		return false;

	$dom = new DomDocument('1.0', 'utf-8');

	$buf = @$dom->loadXML($xml_);
	if (! $buf)
		return false;

	if (! $dom->hasChildNodes())
		return false;

	$node = $dom->getElementsByTagname('session')->item(0);
	if (is_null($node))
		return false;

	if (! $node->hasAttribute('id'))
		return false;

	return true;
}

$plugins = new Plugins();
$plugins->doLoad();

$plugins->doInit();

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$system_in_maintenance = $prefs->get('general', 'system_in_maintenance');
if ($system_in_maintenance == '1')
	die_error(_('The system is in maintenance mode'), __FILE__, __LINE__, true);

$default_settings = $prefs->get('general', 'session_settings_defaults');
$session_mode = $default_settings['session_mode'];
$windows_keymap = $default_settings['windows_keymap'];
$desktop_size = 'auto';
$quality = $default_settings['quality'];
$desktop_timeout = $default_settings['timeout'];
$timeout_message = $default_settings['session_timeout_msg'];
$start_app = '';
$persistent = $default_settings['persistent'];
$shareable = $default_settings['shareable'];
$desktop_icons = $default_settings['desktop_icons'];
$allow_shell = $default_settings['allow_shell'];
$app_with_desktop = $default_settings['app_with_desktop'];
$debug = 0;

$default_settings = $prefs->get('general', 'web_interface_settings');
$allow_proxy = $default_settings['allow_proxy'];
$popup = $default_settings['use_popup'];

$advanced_settings = array();
$buf = $prefs->get('general', 'session_settings_defaults');
foreach ($buf['advanced_settings_startsession'] as $v)
	$advanced_settings[] = $v;

$buf = $prefs->get('general', 'web_interface_settings');
foreach ($buf['advanced_settings_startsession'] as $v)
	$advanced_settings[] = $v;

if (! is_array($advanced_settings))
	$advanced_settings = array();

// The "user" node of this XML should be handled by do_login();
$ret = parse_login_XML(@file_get_contents('php://input'));

if (isset($_SESSION['mode']))
	$session_mode = $_SESSION['mode'];

if (! isset($_SESSION['login'])) {
	$ret = do_login();
	if (! $ret)
		die_error(_('Authentication failed'),__FILE__,__LINE__);
}

if (! isset($_SESSION['login']))
	die_error(_('Authentication failed'),__FILE__,__LINE__);

$user_login = $_SESSION['login'];

$userDB = UserDB::getInstance();

$user = $userDB->import($user_login);
if (! is_object($user))
	die_error('User importation failed',__FILE__,__LINE__);

$language = $user->getLocale();

$protocol_vars = array('session_mode', 'language', 'windows_keymap', 'quality', 'timeout', 'application', 'document', 'persistent', 'shareable', 'desktop_icons', 'app_with_desktop', 'popup', 'debug', 'start_app');
foreach ($protocol_vars as $protocol_var) {
	if (in_array($protocol_var, $advanced_settings) && isset($_REQUEST[$protocol_var]) && $_REQUEST[$protocol_var] != '')
		$$protocol_var = $_REQUEST[$protocol_var];
}

$client = 'unknown';
$other_vars = array('timezone', 'client');
foreach ($other_vars as $other_var) {
	if (isset($_REQUEST[$other_var]) && $_REQUEST[$other_var] != '')
		$$other_var = $_REQUEST[$other_var];
}

Logger::debug('main', '(startsession) Now checking for old session');

$ev = new SessionStart(array('user' => $user));

$already_online = 0;
/*$sessions = Sessions::getByUser($user->getAttribute('login'));
if ($sessions > 0) {
	foreach ($sessions as $session) {
		if ($session->isSuspended()) {
			$old_session_id = $session->id;
			$old_session_server = $session->server;
		} elseif ($session->isAlive()) {
			$already_online = 1;

			$buf = $prefs->get('general', 'session_settings_defaults');
			$buf = $buf['action_when_active_session'];

			if ($buf == 0)
				die_error(_('You already have an active session'), __FILE__, __LINE__, true);
			elseif ($buf == 1) {
				$invite = new Invite(gen_string(5));
				$invite->session = $session->id;
				$invite->settings = array(
					'invite_email'	=>	$user->getAttribute('displayname'),
					'view_only'		=>	0,
					'access_id'		=>	'desktop'
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
			}
		} else
			die_error(_('You already have a session, please contact your administrator'), __FILE__, __LINE__, true);
	}
}*/

$serv_tmp = $user->getAvailableServers();
if (count($serv_tmp) == 0) {
	$ev->setAttribute('ok', false);
	$ev->setAttribute('error', _('No available server'));
	$ev->emit();
	Logger::error('main', '(startsession) no server found for \''.$user->getAttribute('login').'\' -> abort');
	die_error(_('You don\'t have access to a server for now'),__FILE__,__LINE__);
}

$random_server = array_shift($serv_tmp);
$random_server = $random_server->fqdn;

/*if (isset($old_session_id) && isset($old_session_server)) {
	$session = Abstract_Session::load($old_session_id);

	$session_type = 'resume';

	$ret = true;

	Logger::info('main', '(startsession) Resuming session for '.$user->getAttribute('login').' ('.$old_session_id.' => '.$old_session_server.')');
} else {*/
	$random_session_id = gen_string(5);

	$session_type = 'start';

	$session = new Session($random_session_id);
	$session->server = $random_server;
	$session->mode = $session_mode;
	$session->type = $session_type;
	$session->status = -1;
	$session->user_login = $user->getAttribute('login');
	$session->user_displayname = $user->getAttribute('displayname');

	$ret = true;

	Logger::info('main', '(startsession) Creating new session for '.$user->getAttribute('login').' ('.$random_session_id.' => '.$random_server.')');
//}

if ($ret === false)
	die_error(_('No available session'),__FILE__,__LINE__);

$fs = $prefs->get('plugins', 'FS');
if (is_null($fs))
	die_error(_('No available filesystem'),__FILE__,__LINE__);
$module_fs = $fs;

$default_args = array(
	'client'			=>	$client,
	'user_login'		=>	$user->getAttribute('login'),
	'user_displayname'	=>	$user->getAttribute('displayname'),
	'locale'			=>	locale2unix($language),
	'windows_keymap'	=>	$windows_keymap,
	'quality'			=>	$quality
);

$optional_args = array();
if (isset($timezone))
	$optional_args['timezone'] = $timezone;
if (isset($desktop_timeout) && $desktop_timeout != -1) {
	$optional_args['timeout'] = (time()+$desktop_timeout);
	$optional_args['timeout_message'] = $timeout_message;
}
if (isset($start_app) && $start_app != '') {
	$applicationDB = ApplicationDB::getInstance();
	$app = $applicationDB->import($start_app);

	if (! is_object($app)) {
		Logger::error('main', '(startsession) No such application for id \''.$start_app.'\'');
		die_error(_('Application does not exist'), __FILE__, __LINE__);
	}

	$apps = $user->applications();

	$ok = false;
	foreach ($apps as $user_app) {
		if ($user_app->getAttribute('id') == $start_app) {
			$ok = true;
			break;
		}
	}

	if ($ok === false) {
		Logger::error('main', '(startsession) Application not available for user \''.$user->getAttribute('login').'\' id \''.$start_app.'\'');
		die_error(_('Application not available'), __FILE__, __LINE__);
	}

	$optional_args['start_app_id'] = $start_app;
}
if (isset($open_doc) && $open_doc != '')
	$optional_args['open_doc'] = $open_doc;
if (isset($popup))
	$optional_args['popup'] = (int)$popup;
if (isset($debug) && $debug != '0')
	$optional_args['debug'] = 1;
if (isset($persistent) && $persistent != '0')
	$optional_args['persistent'] = 1;
if (isset($shareable) && $shareable != '0')
	$optional_args['shareable'] = 1;
if (isset($desktop_icons) && $desktop_icons != '0')
	$optional_args['desktop_icons'] = 1;
if (isset($allow_shell) && $allow_shell != '0')
	$optional_args['allow_shell'] = 1;
if (isset($app_with_desktop) && $app_with_desktop != '0')
	$optional_args['app_with_desktop'] = 1;
if (isset($allow_proxy) && $allow_proxy != '0') {
	if (isset($_REQUEST['proxy_host']) && $_REQUEST['proxy_host'] != '') {
		$optional_args['enable_proxy'] = 1;
		if (isset($_REQUEST['proxy_type']))
			$optional_args['proxy_type'] = $_REQUEST['proxy_type'];
		if (isset($_REQUEST['proxy_host']))
			$optional_args['proxy_host'] = $_REQUEST['proxy_host'];
		if (isset($_REQUEST['proxy_port']))
			$optional_args['proxy_port'] = $_REQUEST['proxy_port'];
		if (isset($_REQUEST['proxy_username']))
			$optional_args['proxy_username'] = $_REQUEST['proxy_username'];
		if (isset($_REQUEST['proxy_password']))
			$optional_args['proxy_password'] = $_REQUEST['proxy_password'];
	}
}

/*if (count($user->applications('windows')) > 0) {
	$windows_server = $user->getAvailableServer('windows');
	if (! is_object($windows_server)) {
		Logger::error('main', '(startsession) No windows server available for user \''.$user->getAttribute('login').'\'');
		die_error(_('You don\'t have access to a windows server for now'), __FILE__, __LINE__);
	}
	$optional_args['windows_server'] = $windows_server->fqdn;

	$optional_args['windows_manage_session'] = false;

	$user_profile_mode = $prefs->get('UserDB', 'enable');
	$prefs_ldap = $prefs->get('UserDB', 'ldap');
	if ($user_profile_mode == 'activedirectory') {
		$prefs_ad = $prefs->get('UserDB', 'activedirectory');
		$optional_args['windows_login'] = $user->getAttribute('real_login').'@'.$prefs_ad['domain'];
	} elseif ($user_profile_mode == 'ldap' && $prefs_ldap['ad'] == 1) {
		$buf = $prefs_ldap['suffix'];
		$suffix = suffix2domain($buf);
		if (! is_string($suffix)) {
			Logger::error('main', 'LDAP suffix is invalid for AD usage : '.$buf);
			die_error('LDAP suffix is invalid for AD usage', __FILE__, __LINE__);
		}
		$optional_args['windows_login'] = $user->getAttribute('login').'@'.$suffix;
	} else {
		$optional_args['windows_manage_session'] = true;
		$optional_args['windows_login'] = $user->getAttribute('login');
	}

	if ($optional_args['windows_manage_session'] === true) {
		$optional_args['windows_password'] = gen_string(8);

		$buf = $windows_server->orderWindowsSessionCreation($session->id, &$optional_args['windows_login'], $optional_args['windows_password'], $user->getAttribute('displayname'));
		if (! $buf) {
			Logger::error('main', '(startsession) Unable to create windows session for user \''.$user->getAttribute('login').'\' on server \''.$optional_args['windows_server'].'\'');
			die_error(_('You don\'t have access to a windows server for now'), __FILE__, __LINE__);
		}
	} else
		$optional_args['windows_password'] = $_SESSION['password'];
}*/

$plugins->doStartsession(array(
	'fqdn'	=>	$session->server,
	'session'	=>	$session->id
));

$plugins_args = array();
foreach ($plugins->plugins as $plugin) {
	foreach ($plugin->redir_args as $k => $v)
		if ($k != 'session')
			$plugins_args[$k] = $v;

	if (substr(get_class($plugin), 0, 3) == 'FS_')
		$plugins_args['home_dir_type'] = $plugin->getHomeDirType();
}

$data = array();
foreach ($default_args as $k => $v)
	$data[$k] = $v;
foreach ($optional_args as $k => $v)
	$data[$k] = $v;
foreach ($plugins_args as $k => $v)
	$data[$k] = $v;

$session->setAttribute('settings', $data);
$session->setAttribute('start_time', time());
$save_session = Abstract_Session::save($session);
if ($save_session === true) {
	Logger::info('main', '(startsession) session \''.$session->id.'\' actually saved on DB for user \''.$user->getAttribute('login').'\'');
}
else {
	Logger::error('main', '(startsession) failed to save session \''.$session->id.'\' for user \''.$user->getAttribute('login').'\'');
	die_error(_('Internal error'), __FILE__, __LINE__);
}

$token = new Token(gen_string(5));
$token->type = $session_type;
$token->link_to = $session->id;
$token->valid_until = (time()+(60*5));
$save_token = Abstract_Token::save($token);
if ($save_token === false) {
	Logger::error('main', '(startsession) failed to save token \''.$token.'\' for session \''.$session->id.'\'');
	die_error(_('Internal error'), __FILE__, __LINE__);
}

$server = Abstract_Server::load($session->server);

$ev->setAttributes(array(
	'ok'	=> true,
	'server'	=>	$session->server,
	'resume'	=>	$session->isSuspended(),
	'token'	=>	$token->id,
	'sessid'	=>	$session->id
));
$ev->emit();

$user_login = $user->getAttribute('login').'_OVD'; //hardcoded
$user_password = gen_string(8);

$dom = new DomDocument('1.0', 'utf-8');

$session_node = $dom->createElement('session');
$session_node->setAttribute('id', $session->id);
foreach (array('desktop_icons') as $parameter) {
// continue;
	$parameter_node = $dom->createElement('parameter');
	$parameter_node->setAttribute('name', $parameter);
	$parameter_node->setAttribute('value', true);
	$session_node->appendChild($parameter_node);
}
$user_node = $dom->createElement('user');
$user_node->setAttribute('login', $user_login);
$user_node->setAttribute('password', $user_password);
$user_node->setAttribute('displayName', $user->getAttribute('displayname'));
$session_node->appendChild($user_node);

foreach ($user->applications() as $application) {
	if ($application->getAttribute('static'))
		continue;

	$application_node = $dom->createElement('application');
	$application_node->setAttribute('id', $application->getAttribute('id'));
	$application_node->setAttribute('mode', 'local');
	$application_node->setAttribute('desktopfile', $application->getAttribute('desktopfile'));

	$session_node->appendChild($application_node);
}

$dom->appendChild($session_node);

$xml = $dom->saveXML();

$ret = parse_session_create_XML(query_url_post_xml($server->getBaseURL().'/aps/session/create', $xml));
if (! $ret) {
	header('Content-Type: text/xml; charset=utf-8');
	$dom = new DomDocument('1.0', 'utf-8');

	$node = $dom->createElement('error');
	$node->setAttribute('id', 1);
	$node->setAttribute('message', 'Server does not send a valid XML');
	$dom->appendChild($node);

	echo $dom->saveXML();
	exit(1);
}

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$session_node = $dom->createElement('session');
$session_node->setAttribute('id', $session->id);
$session_node->setAttribute('mode', $session->mode);
foreach (array($session->server) as $server) {
	$server = Abstract_Server::load($session->server);
	if (! $server)
		continue;

	$server_node = $dom->createElement('server');
	$server_node->setAttribute('fqdn', $server->getAttribute('external_name'));
	$server_node->setAttribute('login', $user_login);
	$server_node->setAttribute('password', $user_password);
	foreach ($user->applications() as $application) {
		if ($application->getAttribute('static'))
			continue;

		$application_node = $dom->createElement('application');
		$application_node->setAttribute('id', $application->getAttribute('id'));
		$application_node->setAttribute('name', $application->getAttribute('name'));
		$application_node->setAttribute('command', $application->getAttribute('executable_path'));
		foreach (explode(';', $application->getAttribute('mimetypes')) as $mimetype) {
			if ($mimetype == '')
				continue;

			$mimetype_node = $dom->createElement('mime');
			$mimetype_node->setAttribute('type', $mimetype);
			$application_node->appendChild($mimetype_node);
		}
		$server_node->appendChild($application_node);
	}
	$session_node->appendChild($server_node);
}
$dom->appendChild($session_node);

echo $dom->saveXML();
exit(0);
