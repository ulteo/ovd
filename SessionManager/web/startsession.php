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

define('INTERNAL_ERROR', 'internal_error');
define('INVALID_USER', 'invalid_user');
define('UNAUTHORIZED_SESSION_MODE', 'unauthorized_session_mode');
define('USER_WITH_ACTIVE_SESSION', 'user_with_active_session');

function throw_response($response_code_) {
	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument('1.0', 'utf-8');

	$response_node = $dom->createElement('response');
	$response_node->setAttribute('code', $response_code_);
	$dom->appendChild($response_node);

	echo $dom->saveXML();

	die();
}

function parse_client_XML($xml_) {
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

	if (! $session_node->hasAttribute('language'))
		return false;

	$_SESSION['mode'] = $session_node->getAttribute('mode');

	if ($session_node->hasAttribute('language'))
		$_REQUEST['language'] = $session_node->getAttribute('language');

	$user_node = $dom->getElementsByTagname('user')->item(0);
	if (is_null($user_node))
		return false;

	if (! $user_node->hasAttribute('login') || ! $user_node->hasAttribute('password'))
		return false;

	$_POST['login'] = $user_node->getAttribute('login');
	$_POST['password'] = $user_node->getAttribute('password');

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

$ret = parse_client_XML(@file_get_contents('php://input'));

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
	throw_response(INVALID_USER);

$default_settings = $user->getSessionSettings();
$session_mode = $default_settings['session_mode'];
$desktop_size = 'auto';
$desktop_timeout = $default_settings['timeout'];
$timeout_message = $default_settings['session_timeout_msg'];
$start_app = '';
$start_app_args = '';
$persistent = $default_settings['persistent'];
//$shareable = $default_settings['shareable'];
$desktop_icons = $default_settings['desktop_icons'];
$allow_shell = $default_settings['allow_shell'];
$multimedia = $default_settings['multimedia'];
$redirect_client_printers = $default_settings['redirect_client_printers'];
$auto_create_profile = $default_settings['auto_create_profile'];
$start_without_profile = $default_settings['start_without_profile'];
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

$remote_desktop_settings = $prefs->get('general', 'remote_desktop_settings');
$remote_applications_settings = $prefs->get('general', 'remote_applications_settings');

$enabled_session_modes = array();
$sessmodes = array('desktop', 'applications');
foreach ($sessmodes as $sessmode) {
	$buf = $prefs->get('general', 'remote_'.$sessmode.'_settings');
	if (! $buf)
		continue;

	if ($buf['enabled'] == 1)
		$enabled_session_modes[] = $sessmode;
}

if (isset($_SESSION['mode'])) {
	if (! in_array('session_mode', $advanced_settings) && $_SESSION['mode'] != $session_mode)
		throw_response(UNAUTHORIZED_SESSION_MODE);

	if (in_array('session_mode', $advanced_settings) && ! in_array($_SESSION['mode'], $enabled_session_modes))
		throw_response(UNAUTHORIZED_SESSION_MODE);

	$session_mode = $_SESSION['mode'];
}

$locale = $user->getLocale();

$protocol_vars = array('session_mode', 'language', 'timeout', 'persistent', /*'shareable', */'desktop_icons', 'popup', 'debug');
foreach ($protocol_vars as $protocol_var) {
	if (in_array($protocol_var, $advanced_settings) && isset($_REQUEST[$protocol_var]) && $_REQUEST[$protocol_var] != '') {
		switch ($protocol_var) {
			case 'session_mode':
				if (! in_array('session_mode', $advanced_settings) && $_REQUEST['session_mode'] != $session_mode)
					throw_response(UNAUTHORIZED_SESSION_MODE);

				if (in_array('session_mode', $advanced_settings) && ! in_array($_REQUEST['session_mode'], $enabled_session_modes))
					throw_response(UNAUTHORIZED_SESSION_MODE);

				$session_mode = $_REQUEST['session_mode'];
				break;
			case 'language':
				$locale = locale2unix($_REQUEST['language']);
				break;
			default:
				$$protocol_var = $_REQUEST[$protocol_var];
				break;
		}
	}
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
$sessions = Abstract_Session::getByUser($user->getAttribute('login'));
if ($sessions > 0) {
	foreach ($sessions as $session) {
		/*if ($session->isSuspended()) {
			$old_session_id = $session->id;
			$old_session_server = $session->server;
		} else*/if ($session->isAlive()) {
			$already_online = 1;

			$buf = $prefs->get('general', 'session_settings_defaults');
			$buf = $buf['action_when_active_session'];

			if ($buf == 0)
				throw_response(USER_WITH_ACTIVE_SESSION);
			/*elseif ($buf == 1) {
				$invite = new Invite(gen_unique_string());
				$invite->session = $session->id;
				$invite->settings = array(
					'invite_email'	=>	$user->getAttribute('displayname'),
					'view_only'		=>	0,
					'access_id'		=>	Session::MODE_DESKTOP
				);
				$invite->email = 'none';
				$invite->valid_until = (time()+(60*30));
				Abstract_Invite::save($invite);

				$token = new Token(gen_unique_string());
				$token->type = 'invite';
				$token->link_to = $invite->id;
				$token->valid_until = (time()+(60*30));
				Abstract_Token::save($token);

				$server = Abstract_Server::load($session->server);

				redirect($server->getBaseURL(true).'/index.php?token='.$token->id);
			}*/

			throw_response(USER_WITH_ACTIVE_SESSION);
		} else
			throw_response(USER_WITH_ACTIVE_SESSION);
	}
}

$user_login = $user->getAttribute('login').'_OVD'; //hardcoded
$user_password = gen_string(3, 'abcdefghijklmnopqrstuvwxyz').gen_string(2, '0123456789').gen_string(3, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');

$buf_servers = $user->getAvailableServers();
if (is_null($buf_servers) || count($buf_servers) == 0) {
	$ev->setAttribute('ok', false);
	$ev->setAttribute('error', _('No available server'));
	$ev->emit();
	Logger::error('main', '(startsession) no server found for \''.$user->getAttribute('login').'\' -> abort');
	die_error(_('You don\'t have access to a server for now'),__FILE__,__LINE__);
}

$servers = array();
foreach ($buf_servers as $buf_server)
	$servers[] = $buf_server->fqdn;
$random_server = false;
if (isset($remote_desktop_settings) && array_key_exists('desktop_type', $remote_desktop_settings)) {
	switch ($remote_desktop_settings['desktop_type']) {
		case 'linux':
			foreach ($servers as $k => $server) {
				$server = Abstract_Server::load($server);
				if (! $server)
					continue;

				if ($server->getAttribute('type') == 'linux') {
					$random_server = $servers[$k];
					break;
				}
			}
			if (! $random_server) {
				Logger::error('main', '(startsession) no "linux" desktop server found for \''.$user->getAttribute('login').'\' -> abort');
				die_error(_('You don\'t have access to a "linux" desktop server for now'),__FILE__,__LINE__);
			}
			break;
		case 'windows':
			foreach ($servers as $k => $server) {
				$server = Abstract_Server::load($server);
				if (! $server)
					continue;

				if ($server->getAttribute('type') == 'windows') {
					$random_server = $servers[$k];
					break;
				}
			}
			if (! $random_server) {
				Logger::error('main', '(startsession) no "windows" desktop server found for \''.$user->getAttribute('login').'\' -> abort');
				die_error(_('You don\'t have access to a "windows" desktop server for now'),__FILE__,__LINE__);
			}
			break;
		case 'any':
		default:
			$random_server = $servers[0];
			break;
	}
} else
	$random_server = $servers[0];

$fileservers = Servers::getAvailableByRole(Servers::$role_fs);
if (count($fileservers) > 0) {
	$netfolders = $user->getNetworkFolders();

	if (! is_array($netfolders))
		throw_response(INTERNAL_ERROR);

	$profile_available = false;
	if (count($netfolders) == 1) {
		Logger::debug('main', '(startsession) User "'.$user_login.'" already have a profile, using it');

		$netfolder = array_pop($netfolders);

		foreach ($fileservers as $fileserver) {
			if ($fileserver->fqdn != $netfolder->server)
				continue;

			$profile_available = true;

			if (! $netfolder->delUser($user)) {
				Logger::error('main', '(startsession) Access creation for User "'.$user_login.'" profile failed (step 1)');
				throw_response(INTERNAL_ERROR);
			}
			if (! $fileserver->delUserFromNetworkFolder($netfolder->path, $user_login)) {
				Logger::error('main', '(startsession) Access creation for User "'.$user_login.'" profile failed (step 2)');
				throw_response(INTERNAL_ERROR);
			}

			if (! $netfolder->addUser($user)) {
				Logger::error('main', '(startsession) Access creation for User "'.$user_login.'" profile failed (step 3)');
				throw_response(INTERNAL_ERROR);
			}
			if (! $fileserver->addUserToNetworkFolder($netfolder->path, $user_login, $user_password)) {
				Logger::error('main', '(startsession) Access creation for User "'.$user_login.'" profile failed (step 4)');
				throw_response(INTERNAL_ERROR);
			}

			$profile_server = $netfolder->server;
			$profile_name = $netfolder->path;
		}
	} else {
		Logger::debug('main', '(startsession) User "'.$user_login.'" does not have a profile for now, checking for auto-creation');

		if (isset($auto_create_profile) && $auto_create_profile == 1) {
			Logger::debug('main', '(startsession) User "'.$user_login.'" profile will be auto-created, and used');

			$fileserver = array_pop($fileservers);
			$profile = new NetworkFolder();
			$profile->path = md5($user->getAttribute('login'));
			$profile->server = $fileserver->getAttribute('fqdn');
			Abstract_NetworkFolder::save($profile);

			if (! $fileserver->createNetworkFolder($profile->path)) {
				Logger::error('main', '(startsession) Auto-creation of profile for User "'.$user_login.'" failed (step 1)');
				throw_response(INTERNAL_ERROR);
			}

			if (! $profile->addUser($user)) {
				Logger::error('main', '(startsession) Auto-creation of profile for User "'.$user_login.'" failed (step 2)');
				throw_response(INTERNAL_ERROR);
			}
			if (! $fileserver->addUserToNetworkFolder($profile->path, $user_login, $user_password)) {
				Logger::error('main', '(startsession) Auto-creation of profile for User "'.$user_login.'" failed (step 3)');
				throw_response(INTERNAL_ERROR);
			}

			$profile_available = true;
			$profile_server = $profile->server;
			$profile_name = $profile->path;
		} else {
			Logger::debug('main', '(startsession) Auto-creation of profile for User "'.$user_login.'" disabled, checking for session without profile');

			if (isset($start_without_profile) && $start_without_profile == 1) {
				Logger::debug('main', '(startsession) User "'.$user_login.'" can start a session without a valid profile, proceeding');

				$profile_available = false;
			} else {
				Logger::error('main', '(startsession) User "'.$user_login.'" does not have a valid profile, aborting');

				throw_response(INTERNAL_ERROR);
			}
		}
	}
} else {
	Logger::debug('main', '(startsession) FileServer not available for User "'.$user_login.'", checking for session without profile');

	if (isset($start_without_profile) && $start_without_profile == 1) {
		Logger::debug('main', '(startsession) User "'.$user_login.'" can start a session without a valid profile, proceeding');

		$profile_available = false;
	} else {
		Logger::error('main', '(startsession) User "'.$user_login.'" does not have a valid profile, aborting');

		throw_response(INTERNAL_ERROR);
	}
}

/*if (isset($old_session_id) && isset($old_session_server)) {
	$session = Abstract_Session::load($old_session_id);

	$session_type = 'resume';

	$ret = true;

	Logger::info('main', '(startsession) Resuming session for '.$user->getAttribute('login').' ('.$old_session_id.' => '.$old_session_server.')');
} else {*/
	$random_session_id = gen_unique_string();

	$session_type = 'start';

	$session = new Session($random_session_id);
	$session->server = $random_server;
	$session->mode = $session_mode;
	$session->type = $session_type;
	$session->status = -1;
	$session->user_login = $user->getAttribute('login');
	$session->user_displayname = $user->getAttribute('displayname');
	$session->servers = $servers;

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
	'locale'			=>	$locale
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
if (isset($start_app_args) && $start_app_args != '')
	$optional_args['start_app_args'] = $start_app_args;
if (isset($popup))
	$optional_args['popup'] = (int)$popup;
if (isset($debug) && $debug != '0')
	$optional_args['debug'] = 1;
if (isset($persistent) && $persistent != '0')
	$optional_args['persistent'] = 1;
/*if (isset($shareable) && $shareable != '0')
	$optional_args['shareable'] = 1;*/
if (isset($desktop_icons) && $desktop_icons != '0')
	$optional_args['desktop_icons'] = 1;
if (isset($allow_shell) && $allow_shell != '0')
	$optional_args['allow_shell'] = 1;
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

$session->settings['aps_access_login'] = $user_login;
$session->settings['aps_access_password'] = $user_password;

$save_session = Abstract_Session::save($session);
if ($save_session === true) {
	Logger::info('main', '(startsession) session \''.$session->id.'\' actually saved on DB for user \''.$user->getAttribute('login').'\'');
}
else {
	Logger::error('main', '(startsession) failed to save session \''.$session->id.'\' for user \''.$user->getAttribute('login').'\'');
	die_error(_('Internal error'), __FILE__, __LINE__);
}

$token = new Token(gen_unique_string());
$token->type = $session_type;
$token->link_to = $session->id;
$token->valid_until = (time()+(60*5));
$save_token = Abstract_Token::save($token);
if ($save_token === false) {
	Logger::error('main', '(startsession) failed to save token \''.$token.'\' for session \''.$session->id.'\'');
	die_error(_('Internal error'), __FILE__, __LINE__);
}

$ev->setAttributes(array(
	'ok'	=> true,
	'server'	=>	$session->server,
	'resume'	=>	$session->isSuspended(),
	'token'	=>	$token->id,
	'sessid'	=>	$session->id
));
$ev->emit();

if ($session->mode == Session::MODE_DESKTOP) {
	$server = Abstract_Server::load($session->server);
	if (! $server)
		continue;

	$server_applications = $server->getApplications();
	if (! is_array($server_applications))
		$server_applications = array();

	$available_applications = array();
	foreach ($server_applications as $server_application)
		$available_applications[] = $server_application->getAttribute('id');

	$dom = new DomDocument('1.0', 'utf-8');

	$session_node = $dom->createElement('session');
	$session_node->setAttribute('id', $session->id);
	$session_node->setAttribute('mode', Session::MODE_DESKTOP);
	foreach (array('desktop_icons', 'locale') as $parameter) {
		$parameter_node = $dom->createElement('parameter');
		$parameter_node->setAttribute('name', $parameter);
		$parameter_node->setAttribute('value', $$parameter);
		$session_node->appendChild($parameter_node);
	}
	$user_node = $dom->createElement('user');
	$user_node->setAttribute('login', $user_login);
	$user_node->setAttribute('password', $user_password);
	$user_node->setAttribute('displayName', $user->getAttribute('displayname'));
	$session_node->appendChild($user_node);

	if (isset($profile_available) && $profile_available === true) {
		$profile_node = $dom->createElement('profile');
		$profile_node->setAttribute('server', $profile_server);
		$profile_node->setAttribute('dir', $profile_name);
		$profile_node->setAttribute('login', $user_login);
		$profile_node->setAttribute('password', $user_password);
		$session_node->appendChild($profile_node);
	}

	foreach ($user->applications() as $application) {
		if ($application->getAttribute('static'))
			continue;

		if ($application->getAttribute('type') != $server->getAttribute('type'))
			continue;

		if (! in_array($application->getAttribute('id'), $available_applications))
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
}

if ($session->mode == Session::MODE_APPLICATIONS || ($session->mode == Session::MODE_DESKTOP && isset($remote_desktop_settings) && array_key_exists('allow_external_applications', $remote_desktop_settings) && $remote_desktop_settings['allow_external_applications'] == 1)) {
	foreach ($session->servers as $server) {
		$server = Abstract_Server::load($server);
		if (! $server)
			continue;

		if ($session->mode == Session::MODE_DESKTOP && isset($remote_desktop_settings) && array_key_exists('allow_external_applications', $remote_desktop_settings) && $remote_desktop_settings['allow_external_applications'] == 1 && $server->fqdn == $session->server)
			continue;

		$server_applications = $server->getApplications();
		if (! is_array($server_applications))
			$server_applications = array();

		$available_applications = array();
		foreach ($server_applications as $server_application)
			$available_applications[] = $server_application->getAttribute('id');

		$dom = new DomDocument('1.0', 'utf-8');

		$session_node = $dom->createElement('session');
		$session_node->setAttribute('id', $session->id);
		$session_node->setAttribute('mode', Session::MODE_APPLICATIONS);
		foreach (array('desktop_icons', 'locale') as $parameter) {
			$parameter_node = $dom->createElement('parameter');
			$parameter_node->setAttribute('name', $parameter);
			$parameter_node->setAttribute('value', $$parameter);
			$session_node->appendChild($parameter_node);
		}
		$user_node = $dom->createElement('user');
		$user_node->setAttribute('login', $user_login);
		$user_node->setAttribute('password', $user_password);
		$user_node->setAttribute('displayName', $user->getAttribute('displayname'));
		$session_node->appendChild($user_node);

		if (isset($profile_available) && $profile_available === true) {
			$profile_node = $dom->createElement('profile');
			$profile_node->setAttribute('server', $profile_server);
			$profile_node->setAttribute('dir', $profile_name);
			$profile_node->setAttribute('login', $user_login);
			$profile_node->setAttribute('password', $user_password);
			$session_node->appendChild($profile_node);
		}

		foreach ($user->applications() as $application) {
			if ($application->getAttribute('static'))
				continue;

			if ($application->getAttribute('type') != $server->getAttribute('type'))
				continue;

			if (! in_array($application->getAttribute('id'), $available_applications))
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
	}
}

$_SESSION['session_id'] = $session->id;

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$session_node = $dom->createElement('session');
$session_node->setAttribute('id', $session->id);
$session_node->setAttribute('mode', $session->mode);
$session_node->setAttribute('multimedia', $multimedia);
$session_node->setAttribute('redirect_client_printers', $redirect_client_printers);
$user_node = $dom->createElement('user');
$user_node->setAttribute('displayName', $user->getAttribute('displayname'));
$session_node->appendChild($user_node);
if ($session->mode == Session::MODE_DESKTOP) {
	$server = Abstract_Server::load($session->server);
	if (! $server)
		continue;

	$server_applications = $server->getApplications();
	if (! is_array($server_applications))
		$server_applications = array();

	$available_applications = array();
	foreach ($server_applications as $server_application)
		$available_applications[] = $server_application->getAttribute('id');

	$server_node = $dom->createElement('server');
	$server_node->setAttribute('fqdn', $server->getAttribute('external_name'));
	$server_node->setAttribute('login', $user_login);
	$server_node->setAttribute('password', $user_password);
	foreach ($user->applications() as $application) {
		if ($application->getAttribute('static'))
			continue;

		if ($application->getAttribute('type') != $server->getAttribute('type'))
			continue;

		if (! in_array($application->getAttribute('id'), $available_applications))
			continue;

		$application_node = $dom->createElement('application');
		$application_node->setAttribute('id', $application->getAttribute('id'));
		$application_node->setAttribute('name', $application->getAttribute('name'));
		$application_node->setAttribute('server', $server->getAttribute('external_name'));
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
} elseif ($session->mode == Session::MODE_APPLICATIONS) {
	foreach ($session->servers as $server) {
		$server = Abstract_Server::load($server);
		if (! $server)
			continue;

		$server_applications = $server->getApplications();
		if (! is_array($server_applications))
			$server_applications = array();

		$available_applications = array();
		foreach ($server_applications as $server_application)
			$available_applications[] = $server_application->getAttribute('id');

		$server_node = $dom->createElement('server');
		$server_node->setAttribute('fqdn', $server->getAttribute('external_name'));
		$server_node->setAttribute('login', $user_login);
		$server_node->setAttribute('password', $user_password);
		foreach ($user->applications() as $application) {
			if ($application->getAttribute('static'))
				continue;

			if ($application->getAttribute('type') != $server->getAttribute('type'))
				continue;

			if (! in_array($application->getAttribute('id'), $available_applications))
				continue;

			$application_node = $dom->createElement('application');
			$application_node->setAttribute('id', $application->getAttribute('id'));
			$application_node->setAttribute('name', $application->getAttribute('name'));
			$application_node->setAttribute('server', $server->getAttribute('external_name'));
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
}
$dom->appendChild($session_node);

echo $dom->saveXML();
exit(0);
