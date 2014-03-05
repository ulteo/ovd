<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
 * Author David LECHEVALIER <david@ulteo.com> 2012-2014
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012-2013
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
 * Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2013
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

define('AUTH_FAILED', 'auth_failed');
define('IN_MAINTENANCE', 'in_maintenance');
define('INTERNAL_ERROR', 'internal_error');
define('INVALID_USER', 'invalid_user');
define('SERVICE_NOT_AVAILABLE', 'service_not_available');
define('UNAUTHORIZED', 'unauthorized');
define('USER_WITH_ACTIVE_SESSION', 'user_with_active_session');

function throw_response($response_code_) {
	Logger::error('main', '(client/start) throw_response(\''.$response_code_.'\')');

	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument('1.0', 'utf-8');

	$response_node = $dom->createElement('response');
	$response_node->setAttribute('code', $response_code_);
	$dom->appendChild($response_node);

	echo $dom->saveXML();

	die();
}

$prefs = Preferences::getInstance();
if (! $prefs)
	throw_response(INTERNAL_ERROR);

$system_in_maintenance = $prefs->get('general', 'system_in_maintenance');
if ($system_in_maintenance == '1') {
	Logger::error('main', 'SessionManagement::__construct - The system is on maintenance mode');
	throw_response(IN_MAINTENANCE);
}

try {
	$sessionManagement = SessionManagement::getInstance();
}
catch (Exception $err) {
	throw_response(INTERNAL_ERROR);
}

if (! $sessionManagement->initialize()) {
	Logger::error('main', '(client/start) SessionManagement initialization failed');
	throw_response(INTERNAL_ERROR);
}

if (! array_key_exists('from_Client_start_XML', $_SESSION))
	$_SESSION['from_Client_start_XML'] = @file_get_contents('php://input');

if (! $sessionManagement->parseClientRequest($_SESSION['from_Client_start_XML'])) {
	unset($_SESSION['from_Client_start_XML']);
	Logger::error('main', '(client/start) Client does not send a valid XML');
	throw_response(INTERNAL_ERROR);
}

if (! $sessionManagement->authenticate()) {
	unset($_SESSION['from_Client_start_XML']);
	Logger::error('main', '(client/start) Authentication failed');
	throw_response(AUTH_FAILED);
}

unset($_SESSION['from_Client_start_XML']);

if (class_exists("PremiumManager") && PremiumManager::is_premium()) {
	if (!PremiumManager::can_start_session()) {
		throw_response(SERVICE_NOT_AVAILABLE);
	}
}

$user = $sessionManagement->user;

$default_settings = $user->getSessionSettings('session_settings_defaults');
$session_mode = $default_settings['session_mode'];
$timeout = $default_settings['timeout'];
$allow_shell = $default_settings['allow_shell'];
$multimedia = $default_settings['multimedia'];
$redirect_client_drives = $default_settings['redirect_client_drives'];
$redirect_client_printers = $default_settings['redirect_client_printers'];
if (class_exists("PremiumManager") && PremiumManager::is_premium()) {
	$redirect_smartcards_readers = $default_settings['redirect_smartcards_readers'];
} else {
	$redirect_smartcards_readers = 0;
}
$rdp_bpp = $default_settings['rdp_bpp'];
$enhance_user_experience = $default_settings['enhance_user_experience'];
$persistent = $default_settings['persistent'];
$followme = $default_settings['followme'];

$advanced_settings = array();
foreach ($default_settings['advanced_settings_startsession'] as $v)
	$advanced_settings[] = $v;

$remote_desktop_settings = $user->getSessionSettings('remote_desktop_settings');
$remote_desktop_enabled = (($remote_desktop_settings['enabled'] == 1)?true:false);
$desktop_icons = $remote_desktop_settings['desktop_icons'];
$authorize_no_desktop = ($remote_desktop_settings['authorize_no_desktop'] == 1);

$remote_applications_settings = $user->getSessionSettings('remote_applications_settings');
$remote_applications_enabled = (($remote_applications_settings['enabled'] == 1)?true:false);

if (isset($sessionManagement->mode)) {
	if (! in_array('session_mode', $advanced_settings) && $sessionManagement->mode != $session_mode)
		throw_response(UNAUTHORIZED);
	$session_mode = $sessionManagement->mode;
}

if (!$user->can_use_session()) {
	Logger::info('main', '(client/start) User '.$user->getAttribute('login').' cannot start/recover a session because of time restriction policy');
	throw_response(UNAUTHORIZED);
}

$locale = $user->getLocale();
if (isset($sessionManagement->language)) {
	$locale = locale2unix($sessionManagement->language);
}

switch ($session_mode) {
	case Session::MODE_DESKTOP:
		if (! isset($remote_desktop_enabled) || $remote_desktop_enabled === false)
			throw_response(UNAUTHORIZED);
		break;
	case Session::MODE_APPLICATIONS:
		if (! isset($remote_applications_enabled) || $remote_applications_enabled === false)
			throw_response(UNAUTHORIZED);
		break;
	default:
		throw_response(UNAUTHORIZED);
		break;
}

Logger::debug('main', '(client/start) Now checking for old session');

$ev = new SessionStart(array('user' => $user));

$createNow = true;
$sessions = Abstract_Session::getByUser($user->getAttribute('login'));
if ($sessions > 0) {
	foreach ($sessions as $session) {
		$same_mode = ($session->mode == $session_mode);
		if (! $same_mode) {
			Logger::error('main', '(client/start) User \''.$user->getAttribute('login').'\' do not specify the right session mode');
			throw_response(USER_WITH_ACTIVE_SESSION);
		}
		
		switch ($session->status) {
			case Session::SESSION_STATUS_CREATING:
			case Session::SESSION_STATUS_CREATED:
			case Session::SESSION_STATUS_INIT:
			case Session::SESSION_STATUS_READY:
			case Session::SESSION_STATUS_INACTIVE:
				break;
				
			case Session::SESSION_STATUS_ACTIVE:
				if (! $followme) {
					Logger::error('main', '(client/start) User \''.$user->getAttribute('login').'\' is not authorized to use followme feature');
					throw_response(USER_WITH_ACTIVE_SESSION);
				}
				
				break;
				
			case Session::SESSION_STATUS_WAIT_DESTROY:
			case Session::SESSION_STATUS_DESTROYING:
				if (array_key_exists('stop_time', $session->settings) && ($session->settings['stop_time'] + DESTROYING_DURATION) < time()) {
					$session->orderDeletion(false, Session::SESSION_END_STATUS_ERROR);
					Abstract_Session::delete($session);
					continue;
				}
				
				$createNow = false;
				break;
			
			case Session::SESSION_STATUS_DESTROYED:
				$session->orderDeletion(false, Session::SESSION_END_STATUS_ERROR);
				Abstract_Session::delete($session);
				continue;
			
			case Session::SESSION_STATUS_ERROR:
			default: # Unknown status
				$session->orderDeletion(false, Session::SESSION_END_STATUS_ERROR);
				Abstract_Session::delete($session);
				continue;
		}
				
		
		$old_session_id = $session->id;
		$client_id = gen_unique_string();
		$session->client_id = $client_id;
		
		$user_login_aps = $session->settings['aps_access_login'];
		$user_password_aps = $session->settings['aps_access_password'];
		
		if (array_key_exists('fs_access_login', $session->settings) && array_key_exists('fs_access_password', $session->settings)) {
			$user_login_fs = $session->settings['fs_access_login'];
			$user_password_fs = $session->settings['fs_access_password'];
		}
		
		if (array_key_exists('webapps_access_login', $session->settings) && array_key_exists('webapps_access_password', $session->settings)) {
			$user_login_webapps = $session->settings['webapps_access_login'];
			$user_password_webapps = $session->settings['webapps_access_password'];
		}
		
		if (array_key_exists('webapps-url', $session->settings)) {
			$webapps_url = $session->settings['webapps-url'];
		}
	}
}

if (isset($old_session_id)) {
	$session = Abstract_Session::load($old_session_id);
	$session_type = 'resume';
//
	$prepare_servers = array();
	if ($session->mode == Session::MODE_APPLICATIONS) {
		foreach ($session->servers[Server::SERVER_ROLE_WEBAPPS] as $server_id => $data) {
			$prepare_servers[] = $server_id;
		}
	}

	$count_prepare_servers = 0;
	foreach ($prepare_servers as $prepare_server) {
		$count_prepare_servers++;

		$server = Abstract_Server::load($prepare_server);
		if (! $server)
			continue;

		if (! array_key_exists(Server::SERVER_ROLE_WEBAPPS, $server->getRoles()))
			continue;

		$dom = new DomDocument('1.0', 'utf-8');
		$session_node = $dom->createElement('session');
		$session_node->setAttribute('id', $session->id);
		$session_node->setAttribute('mode', Session::MODE_APPLICATIONS);
		$user_node = $dom->createElement('user');
		$user_node->setAttribute('login', $user_login_webapps);
		$user_node->setAttribute('password', $user_password_webapps);
		$user_node->setAttribute('USER_LOGIN', $_POST['login']);
		$user_node->setAttribute('USER_PASSWD', $_POST['password']);
		$user_node->setAttribute('displayName', $user->getAttribute('displayname'));
		$session_node->appendChild($user_node);
		
		$applications_node = $dom->createElement('applications');
		foreach ($session->getPublishedApplications() as $application) {
			if ($application->getAttribute('type') != 'webapp')
				continue;

			$application_node = $dom->createElement('application');
			$application_node->setAttribute('id', $application->getAttribute('id'));
			$application_node->setAttribute('type', 'webapp');
			$application_node->setAttribute('name', $application->getAttribute('name'));
			$applications_node->appendChild($application_node);
		}
		$session_node->appendChild($applications_node);
		
		$dom->appendChild($session_node);

		$sessionManagement->appendToSessionCreateXML($dom);

		$xml = $dom->saveXML();

		$ret_xml = query_url_post_xml($server->getBaseURL().'/webapps/session/create', $xml);
		$ret = $sessionManagement->parseSessionCreate($ret_xml);
		if (! $ret) {
			Logger::critical('main', '(client/start) Unable to create Session \''.$session->id.'\' for User \''.$session->user_login.'\' on Server \''.$server->fqdn.'\', aborting');
			$session->orderDeletion(true, Session::SESSION_END_STATUS_ERROR);

			throw_response(INTERNAL_ERROR);
		}

		$ret_dom = new DomDocument('1.0', 'utf-8');
		$ret_buf = @$ret_dom->loadXML($ret_xml);
		$node = $ret_dom->getElementsByTagname('session')->item(0);
		$webapps_url = $node->getAttribute('webapps-scheme').'://'.$server->getExternalName().':'.$node->getAttribute('webapps-port');
		$session->settings['webapps-url'] = $webapps_url;
	}

//
	$session->setStatus(Session::SESSION_STATUS_READY);
	Logger::info('main', '(client/start) Resuming session for '.$user->getAttribute('login').' ('.$old_session_id.' => '.$session->server.')');
} else {
	if (! $sessionManagement->generateCredentials()) {
		Logger::error('main', '(client/start) Unable to generate access credentials for User "'.$user->getAttribute('login').'", aborting');
		throw_response(SERVICE_NOT_AVAILABLE);
	}
	if (array_key_exists(Server::SERVER_ROLE_APS, $sessionManagement->credentials)) {
		$user_login_aps = $sessionManagement->credentials[Server::SERVER_ROLE_APS]['login'];
		$user_password_aps = $sessionManagement->credentials[Server::SERVER_ROLE_APS]['password'];
	}
	if (array_key_exists(Server::SERVER_ROLE_FS, $sessionManagement->credentials)) {
		$user_login_fs = $sessionManagement->credentials[Server::SERVER_ROLE_FS]['login'];
		$user_password_fs = $sessionManagement->credentials[Server::SERVER_ROLE_FS]['password'];
	}
	if (array_key_exists(Server::SERVER_ROLE_WEBAPPS, $sessionManagement->credentials)) {
		$user_login_webapps = $sessionManagement->credentials[Server::SERVER_ROLE_WEBAPPS]['login'];
		$user_password_webapps = $sessionManagement->credentials[Server::SERVER_ROLE_WEBAPPS]['password'];
	}

	// Desktop server choosing if session mode desktop
	if ($session_mode == Session::MODE_DESKTOP) {
		$ret = $sessionManagement->getDesktopServer();
		if ($ret !== true) {
			Logger::error('main', '(client/start) No desktop server found for User "'.$user->getAttribute('login').'", aborting');
			throw_response(SERVICE_NOT_AVAILABLE);
		}
	}
	
	if (! $sessionManagement->buildServersList()) {
		Logger::error('main', '(client/start) Unable to build servers list for User "'.$user->getAttribute('login').'", aborting');
		throw_response(SERVICE_NOT_AVAILABLE);
	}
	$servers = $sessionManagement->servers;

	// Hack: we must set the session server to the desktop server if 
	// we want to have the desktop server as ... desktop server
	$random_server = false;
	if ($session_mode == Session::MODE_DESKTOP) {
		if (! $sessionManagement->desktop_server) {
			Logger::error('main', '(client/start) No desktop server found for User "'.$user->getAttribute('login').'", aborting');
			throw_response(SERVICE_NOT_AVAILABLE);
		}
		
		$random_server = $sessionManagement->desktop_server->id;
	}
	else if (! empty($servers[Server::SERVER_ROLE_APS])) {
		$random_server = array_rand($servers[Server::SERVER_ROLE_APS]);
	}
	else if (! empty($servers[Server::SERVER_ROLE_WEBAPPS])) {
		$random_server = array_rand($servers[Server::SERVER_ROLE_WEBAPPS]);
	}
	else {
		Logger::error('main', '(client/start) No server for this session');
		$random_server = '';
	}

	$random_session_id = gen_unique_string();
	$client_id = gen_unique_string();

	$session_type = 'start';

	$session = new Session($random_session_id);
	$session->client_id = $client_id;
	$session->server = $random_server;
	$session->mode = $session_mode;
	$session->type = $session_type;
	$session->status = Session::SESSION_STATUS_CREATING;
	$session->user_login = $user->getAttribute('login');
	$session->user_displayname = $user->getAttribute('displayname');
	$session->servers = $servers;
	$session->start_time = time();
	$save_session = Abstract_Session::save($session);
	if (! $save_session) {
		Logger::error('main', '(client/start) failed to save session \''.$session->id.'\' for user \''.$user->getAttribute('login').'\'');
		throw_response(INTERNAL_ERROR);
	}
	$session->setStatus(Session::SESSION_STATUS_CREATED);
	$session->setPublishedApplications($sessionManagement->applications);

	Logger::info('main', '(client/start) Creating new session for '.$user->getAttribute('login').' ('.$session->id.')');
}

$default_args = array(
	'user_login'				=>	$user->getAttribute('login'),
	'user_displayname'			=>	$user->getAttribute('displayname'),
	'locale'					=>	$locale,
	'timeout'					=>	$timeout,
	'multimedia'				=>	$multimedia,
	'redirect_client_drives'	=>	$redirect_client_drives,
	'redirect_client_printers'	=>	$redirect_client_printers,
	'redirect_smartcards_readers'	=>	$redirect_smartcards_readers,
	'rdp_bpp'			=>	$rdp_bpp,
	'enhance_user_experience'	=>	$enhance_user_experience
);

$optional_args = array();
if (isset($sessionManagement->timezone))
	$optional_args['timezone'] = $sessionManagement->timezone;
if (isset($persistent) && $persistent != '0')
	$optional_args['persistent'] = 1;
if (isset($desktop_icons) && $desktop_icons != '0')
	$optional_args['desktop_icons'] = 1;
if (isset($allow_shell) && $allow_shell != '0')
	$optional_args['allow_shell'] = 1;

$data = array();
foreach ($default_args as $k => $v)
	$data[$k] = $v;
foreach ($optional_args as $k => $v)
	$data[$k] = $v;

$session->setAttribute('settings', $data);
$session->setAttribute('start_time', time());

$session->settings['aps_access_login'] = $user_login_aps;
$session->settings['aps_access_password'] = $user_password_aps;
if (isset($user_login_fs) && isset($user_password_fs)) {
	$session->settings['fs_access_login'] = $user_login_fs;
	$session->settings['fs_access_password'] = $user_password_fs;
}
if (isset($user_login_webapps) && isset($user_password_webapps)) {
	$session->settings['webapps_access_login'] = $user_login_webapps;
	$session->settings['webapps_access_password'] = $user_password_webapps;
}

$session->client_id = $client_id;
$save_session = Abstract_Session::save($session);
if (! $save_session) {
	Logger::error('main', '(client/start) failed to save session \''.$session->id.'\' for user \''.$user->getAttribute('login').'\'');
	throw_response(INTERNAL_ERROR);
}

$ev->setAttributes(array(
	'ok'	=> true,
	'server'	=>	$session->server,
	'resume'	=>	$session->isSuspended(),
	'sessid'	=>	$session->id
));
$ev->emit();

if (! isset($old_session_id)) {
	if ($createNow == false) {
		$session->need_creation = true;
	}
	else {
		if (! $sessionManagement->prepareSession($session)) {
			throw_response(INTERNAL_ERROR);
		}
	}
	
	$save_session = Abstract_Session::save($session);
}

$_SESSION['session_id'] = $session->id;
$_SESSION['client_id'] = $client_id;

$sessionManagement->end();

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$session_node = $dom->createElement('session');
$session_node->setAttribute('id', $session->id);
$session_node->setAttribute('mode', $session->mode);
if ($timeout > 0)
	$session_node->setAttribute('duration', $timeout);
$settings_node = $dom->createElement('settings');
foreach ($session->settings as $setting_k => $setting_v) {
	$setting_node = $dom->createElement('setting');
	$setting_node->setAttribute('name', $setting_k);
	$setting_node->setAttribute('value', $setting_v);
	$settings_node->appendChild($setting_node);
}
$session_node->appendChild($settings_node);
$user_node = $dom->createElement('user');
$user_node->setAttribute('displayName', $user->getAttribute('displayname'));
$session_node->appendChild($user_node);

if (array_key_exists(Server::SERVER_ROLE_FS, $session->servers)) {
	foreach ($session->servers[Server::SERVER_ROLE_FS] as $server_id => $netfolders) {
		$server = Abstract_Server::load($server_id);
		foreach ($netfolders as $netfolder) {
			$uri = 'webdav://'.$server->getExternalName().':1113/ovd/fs/'.$netfolder['dir'].'/';
			
			$netfolder_node = $dom->createElement($netfolder['type']);
			$netfolder_node->setAttribute('rid', $netfolder['rid']);
			$netfolder_node->setAttribute('uri', $uri);
			if ($netfolder['type'] == 'sharedfolder')
				$netfolder_node->setAttribute('name', $netfolder['name']);
			$netfolder_node->setAttribute('login', $user_login_fs);
			$netfolder_node->setAttribute('password', $user_password_fs);
			$session_node->appendChild($netfolder_node);
		}
	}
}

$defined_apps = array();
if (array_key_exists(Server::SERVER_ROLE_APS, $session->servers)) {
	foreach ($session->servers[Server::SERVER_ROLE_APS] as $server_id => $data) {
		if ($session->mode == Session::MODE_DESKTOP && $server_id != $session->server)
			continue;
	
		$server = Abstract_Server::load($server_id);
		if (! $server)
			throw_response(INTERNAL_ERROR);

		if (! array_key_exists(Server::SERVER_ROLE_APS, $server->getRoles()))
			throw_response(INTERNAL_ERROR);

		$server_applications = $server->getApplications();
		if (! is_array($server_applications))
			$server_applications = array();

		$available_applications = array();
		foreach ($server_applications as $server_application)
			$available_applications[] = $server_application->getAttribute('id');

		$server_node = $dom->createElement('server');
		$server_node->setAttribute('type', $server->getAttribute('type'));
		$server_node->setAttribute('fqdn', $server->getExternalName());
		if ($server->getApSRDPPort() != Server::DEFAULT_RDP_PORT)
			$server_node->setAttribute('port', $server->getApSRDPPort());
		$server_node->setAttribute('login', $user_login_aps);
		$server_node->setAttribute('password', $user_password_aps);
	
		foreach ($session->getPublishedApplications() as $application) {
			if ($application->getAttribute('type') != $server->getAttribute('type'))
				continue;

			if (! in_array($application->getAttribute('id'), $available_applications))
				continue;

			if (in_array($application->getAttribute('id'), $defined_apps))
				continue;
		
			$defined_apps[] = $application->getAttribute('id');

			$application_node = $dom->createElement('application');
			$application_node->setAttribute('id', $application->getAttribute('id'));
			$application_node->setAttribute('name', $application->getAttribute('name'));
			foreach ($application->getMimeTypes() as $mimetype) {
				$mimetype_node = $dom->createElement('mime');
				$mimetype_node->setAttribute('type', $mimetype);
				$application_node->appendChild($mimetype_node);
			}
			$server_node->appendChild($application_node);
		}
		$session_node->appendChild($server_node);
	}
}

if (array_key_exists(Server::SERVER_ROLE_WEBAPPS, $session->servers)) {
	foreach ($session->servers[Server::SERVER_ROLE_WEBAPPS] as $server_id => $data) {
		$server = Abstract_Server::load($server_id);
		if (! $server)
			throw_response(INTERNAL_ERROR);

		if (! array_key_exists(Server::SERVER_ROLE_WEBAPPS, $server->getRoles()))
			throw_response(INTERNAL_ERROR);

		$server_node = $dom->createElement('webapp-server');
		$server_node->setAttribute('type', 'webapps');
		$server_node->setAttribute('base-url', $server->getBaseURL());
		$server_node->setAttribute('webapps-url', $webapps_url);
		$server_node->setAttribute('login', $user_login_webapps);
		$server_node->setAttribute('password', $user_password_webapps);

		foreach ($session->getPublishedApplications() as $application) {
			if ($application->getAttribute('type') != 'webapp')
				continue;

			$application_node = $dom->createElement('application');
			$application_node->setAttribute('id', $application->getAttribute('id'));
			$application_node->setAttribute('type', 'webapp');
			$application_node->setAttribute('name', $application->getAttribute('name'));
			$server_node->appendChild($application_node);
		}
		$session_node->appendChild($server_node);
	}
}

$dom->appendChild($session_node);

$buf = $dom->saveXML();

header('Content-Length: '.strlen($buf)); // disable the HTTP/1.1 Content-Length chunked because the gateway does not handle this.

echo $buf;

exit(0);
