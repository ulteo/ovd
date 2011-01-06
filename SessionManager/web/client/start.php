<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

define('AUTH_FAILED', 'auth_failed');
define('IN_MAINTENANCE', 'in_maintenance');
define('INTERNAL_ERROR', 'internal_error');
define('INVALID_USER', 'invalid_user');
define('SERVICE_NOT_AVAILABLE', 'service_not_available');
define('UNAUTHORIZED_SESSION_MODE', 'unauthorized_session_mode');
define('USER_WITH_ACTIVE_SESSION', 'user_with_active_session');

function throw_response($response_code_) {
	header('Content-Type: text/xml; charset=utf-8');

	$dom = new DomDocument('1.0', 'utf-8');

	$response_node = $dom->createElement('response');
	$response_node->setAttribute('code', $response_code_);
	$dom->appendChild($response_node);

	Logger::debug('main', "(client/start) throw_response($response_code_)");

	echo $dom->saveXML();

	die();
}

$sessionManagement = SessionManagement::getInstance();
if (! $sessionManagement->initialize()) {
	Logger::error('main', '(client/start) SessionManagement initialization failed');
	throw_response(INTERNAL_ERROR);
}

if (! $sessionManagement->parseClientRequest(@file_get_contents('php://input'))) {
	Logger::error('main', '(client/start) Client does not send a valid XML');
	throw_response(INTERNAL_ERROR);
}

if (! $sessionManagement->authenticate()) {
	Logger::error('main', '(client/start) Authentication failed');
	throw_response(AUTH_FAILED);
}

$user = $sessionManagement->user;

$default_settings = $user->getSessionSettings('session_settings_defaults');
$session_mode = $default_settings['session_mode'];
$timeout = $default_settings['timeout'];
$allow_shell = $default_settings['allow_shell'];
$multimedia = $default_settings['multimedia'];
$redirect_client_drives = $default_settings['redirect_client_drives'];
$redirect_client_printers = $default_settings['redirect_client_printers'];
$enable_profiles = $default_settings['enable_profiles'];
$auto_create_profile = $default_settings['auto_create_profile'];
$start_without_profile = $default_settings['start_without_profile'];
$enable_sharedfolders = $default_settings['enable_sharedfolders'];
$start_without_all_sharedfolders = $default_settings['start_without_all_sharedfolders'];

$advanced_settings = array();
$buf = $prefs->get('general', 'session_settings_defaults');
foreach ($buf['advanced_settings_startsession'] as $v)
	$advanced_settings[] = $v;

$remote_desktop_settings = $user->getSessionSettings('remote_desktop_settings');
$remote_desktop_enabled = (($remote_desktop_settings['enabled'] == 1)?true:false);
$persistent = $remote_desktop_settings['persistent'];
$desktop_icons = $remote_desktop_settings['desktop_icons'];

$remote_applications_settings = $user->getSessionSettings('remote_applications_settings');
$remote_applications_enabled = (($remote_applications_settings['enabled'] == 1)?true:false);

if (isset($_SESSION['mode']))
	$session_mode = $_SESSION['mode'];

$locale = $user->getLocale();

$protocol_vars = array('session_mode', 'language', 'timeout', 'persistent');
foreach ($protocol_vars as $protocol_var) {
	if (in_array($protocol_var, $advanced_settings) && isset($_REQUEST[$protocol_var]) && $_REQUEST[$protocol_var] != '') {
		switch ($protocol_var) {
			case 'session_mode':
				if (! in_array('session_mode', $advanced_settings) && $_REQUEST['session_mode'] != $session_mode)
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

$other_vars = array('timezone');
foreach ($other_vars as $other_var) {
	if (isset($_REQUEST[$other_var]) && $_REQUEST[$other_var] != '')
		$$other_var = $_REQUEST[$other_var];
}

switch ($session_mode) {
	case Session::MODE_DESKTOP:
		if (! isset($remote_desktop_enabled) || $remote_desktop_enabled === false)
			throw_response(UNAUTHORIZED_SESSION_MODE);
		break;
	case Session::MODE_APPLICATIONS:
		if (! isset($remote_applications_enabled) || $remote_applications_enabled === false)
			throw_response(UNAUTHORIZED_SESSION_MODE);
		break;
	default:
		throw_response(UNAUTHORIZED_SESSION_MODE);
		break;
}

Logger::debug('main', '(client/start) Now checking for old session');

$ev = new SessionStart(array('user' => $user));

$sessions = Abstract_Session::getByUser($user->getAttribute('login'));
if ($sessions > 0) {
	foreach ($sessions as $session) {
		if ($session->mode == Session::MODE_DESKTOP && $session->isSuspended()) {
			$old_session_id = $session->id;

			$user_login_aps = $session->settings['aps_access_login'];
			$user_password_aps = $session->settings['aps_access_password'];
			if (array_key_exists('fs_access_login', $session->settings) && array_key_exists('fs_access_password', $session->settings)) {
				$user_login_fs = $session->settings['fs_access_login'];
				$user_password_fs = $session->settings['fs_access_password'];
			}
		} elseif ($session->isAlive()) {
			Logger::error('main', '(client/start) User \''.$user->getAttribute('login').'\' already have an active session');
			throw_response(USER_WITH_ACTIVE_SESSION);
		} else {
			Logger::error('main', '(client/start) User \''.$user->getAttribute('login').'\' already have an active session');
			throw_response(USER_WITH_ACTIVE_SESSION);
		}
	}
}

if (isset($old_session_id)) {
	$session = Abstract_Session::load($old_session_id);

	$session_type = 'resume';

	$session->setStatus(Session::SESSION_STATUS_READY);

	$ret = true;

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
	if ((isset($enable_profiles) && $enable_profiles == 1) || (isset($enable_sharedfolders) && $enable_sharedfolders == 1)) {
		if (array_key_exists(Server::SERVER_ROLE_FS, $sessionManagement->credentials)) {
			$user_login_fs = $sessionManagement->credentials[Server::SERVER_ROLE_FS]['login'];
			$user_password_fs = $sessionManagement->credentials[Server::SERVER_ROLE_FS]['password'];
		}
	}

	if (! $sessionManagement->buildServersList()) {
		Logger::error('main', '(client/start) No "'.Server::SERVER_ROLE_APS.'" server found for User "'.$user->getAttribute('login').'", aborting');
		throw_response(SERVICE_NOT_AVAILABLE);
	}
	$servers = $sessionManagement->servers;

	$random_server = false;
	if ($session_mode == Session::MODE_DESKTOP && (isset($remote_desktop_settings) && array_key_exists('desktop_type', $remote_desktop_settings))) {
		$random_server = $sessionManagement->getDesktopServer($remote_desktop_settings['desktop_type']);
		if (! $random_server) {
			Logger::error('main', '(client/start) No desktop server found for User "'.$user->getAttribute('login').'", aborting');
			throw_response(SERVICE_NOT_AVAILABLE);
		}
	} else
		$random_server = array_rand($servers[Server::SERVER_ROLE_APS]);

	if (isset($enable_profiles) && $enable_profiles == 1) {
		$fileservers = Abstract_Server::load_available_by_role(Server::SERVER_ROLE_FS);
		if (count($fileservers) > 0) {
			$netfolders = $user->getProfiles();

			if (! is_array($netfolders)) {
				Logger::error('main', '(client/start) User::getProfiles() failed');
				throw_response(INTERNAL_ERROR);
			}

			$profile_available = false;
			if (count($netfolders) == 1) {
				Logger::debug('main', '(client/start) User "'.$user->getAttribute('login').'" already have a profile, using it');

				$netfolder = array_pop($netfolders);

				foreach ($fileservers as $fileserver) {
					if ($fileserver->fqdn != $netfolder->server)
						continue;

					$profile_available = true;
					$profile_server = $netfolder->server;
					$profile_name = $netfolder->id;

					$servers[Server::SERVER_ROLE_FS][$profile_server] = array();
				}
			} else {
				Logger::debug('main', '(client/start) User "'.$user->getAttribute('login').'" does not have a profile for now, checking for auto-creation');

				if (isset($auto_create_profile) && $auto_create_profile == 1) {
					Logger::debug('main', '(client/start) User "'.$user->getAttribute('login').'" profile will be auto-created, and used');

					$profileDB = ProfileDB::getInstance();

					$fileserver = array_pop($fileservers);
					$profile = new Profile();
					$profile->server = $fileserver->getAttribute('fqdn');

					if (! $profileDB->addToServer($profile, $fileserver)) {
						Logger::error('main', '(client/start) Auto-creation of profile for User "'.$user->getAttribute('login').'" failed (step 1)');
						throw_response(INTERNAL_ERROR);
					}

					if (! $profile->addUser($user)) {
						Logger::error('main', '(client/start) Auto-creation of profile for User "'.$user->getAttribute('login').'" failed (step 2)');
						throw_response(INTERNAL_ERROR);
					}

					$profile_available = true;
					$profile_server = $profile->server;
					$profile_name = $profile->id;

					$servers[Server::SERVER_ROLE_FS][$profile_server] = array();
				} else {
					Logger::debug('main', '(client/start) Auto-creation of profile for User "'.$user->getAttribute('login').'" disabled, checking for session without profile');

					if (isset($start_without_profile) && $start_without_profile == 1) {
						Logger::debug('main', '(client/start) User "'.$user->getAttribute('login').'" can start a session without a valid profile, proceeding');

						$profile_available = false;
					} else {
						Logger::error('main', '(client/start) User "'.$user->getAttribute('login').'" does not have a valid profile, aborting');

						throw_response(INTERNAL_ERROR);
					}
				}
			}
		} else {
			Logger::debug('main', '(client/start) FileServer not available for User "'.$user->getAttribute('login').'", checking for session without profile');

			if (isset($start_without_profile) && $start_without_profile == 1) {
				Logger::debug('main', '(client/start) User "'.$user->getAttribute('login').'" can start a session without a valid profile, proceeding');

				$profile_available = false;
			} else {
				Logger::error('main', '(client/start) User "'.$user->getAttribute('login').'" does not have a valid profile, aborting');

				throw_response(INTERNAL_ERROR);
			}
		}
	}

	if (isset($enable_sharedfolders) && $enable_sharedfolders == 1) {
		$sharedfolders = $user->getSharedFolders();
		$netshares = array();
		$sharedfolders_available = false;
		if (is_array($sharedfolders) && count($sharedfolders) > 0) {
			foreach ($sharedfolders as $sharedfolder) {
				$sharedfolder_server = Abstract_Server::load($sharedfolder->server);
				if (! $sharedfolder_server || ! $sharedfolder_server->isOnline()) {
					Logger::error('main', '(client/start) Server "'.$sharedfolder->server.'" for shared folder "'.$sharedfolder->id.'" is not available');

					if (isset($start_without_all_sharedfolders) && $start_without_all_sharedfolders == 1) {
						Logger::debug('main', '(client/start) User "'.$user->getAttribute('login').'" can start a session without all shared folders available, proceeding');

						continue;
					} else {
						Logger::error('main', '(client/start) User "'.$user->getAttribute('login').'" does not have all shared folders available, aborting');

						throw_response(INTERNAL_ERROR);
					}
				}

				$netshares[] = $sharedfolder;

				$servers[Server::SERVER_ROLE_FS][$sharedfolder->server] = array();
			}

			if (count($netshares) > 0)
				$sharedfolders_available = true;
		}
	}

	$random_session_id = gen_unique_string();

	$session_type = 'start';

	$session = new Session($random_session_id);
	$session->server = $random_server;
	$session->mode = $session_mode;
	$session->type = $session_type;
	$session->status = Session::SESSION_STATUS_CREATING;
	$session->user_login = $user->getAttribute('login');
	$session->user_displayname = $user->getAttribute('displayname');
	$session->servers = $servers;
	$save_session = Abstract_Session::save($session);
	if (! $save_session) {
		Logger::error('main', '(client/start) failed to save session \''.$session->id.'\' for user \''.$user->getAttribute('login').'\'');
		throw_response(INTERNAL_ERROR);
	}
	$session->setStatus(Session::SESSION_STATUS_CREATED);

	$ret = true;

	Logger::info('main', '(client/start) Creating new session for '.$user->getAttribute('login').' ('.$session->id.')');
}

if ($ret === false)
	throw_response(INTERNAL_ERROR);

$default_args = array(
	'user_login'				=>	$user->getAttribute('login'),
	'user_displayname'			=>	$user->getAttribute('displayname'),
	'locale'					=>	$locale,
	'timeout'					=>	$timeout,
	'multimedia'				=>	$multimedia,
	'redirect_client_drives'	=>	$redirect_client_drives,
	'redirect_client_printers'	=>	$redirect_client_printers
);

$optional_args = array();
if (isset($timezone))
	$optional_args['timezone'] = $timezone;
if (isset($_REQUEST['start_apps']) && is_array($_REQUEST['start_apps'])) {
	$start_apps = $_REQUEST['start_apps'];

	$applicationDB = ApplicationDB::getInstance();

	foreach ($start_apps as $start_app) {
		$app = $applicationDB->import($start_app['id']);

		if (! is_object($app)) {
			Logger::error('main', '(client/start) No such application for id \''.$start_app['id'].'\'');
			throw_response(SERVICE_NOT_AVAILABLE);
		}

		$apps = $user->applications();

		$ok = false;
		foreach ($apps as $user_app) {
			if ($user_app->getAttribute('id') == $start_app['id']) {
				$ok = true;
				break;
			}
		}

		if ($ok === false) {
			Logger::error('main', '(client/start) Application not available for user \''.$user->getAttribute('login').'\' id \''.$start_app['id'].'\'');
			throw_response(SERVICE_NOT_AVAILABLE);
		}
	}
}
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
	$mounts = array();

	if (isset($profile_available) && $profile_available === true) {
		if (! array_key_exists($profile_server, $mounts))
			$mounts[$profile_server] = array();

		$mounts[$profile_server][] = $profile_name;
	}

	if (isset($sharedfolders_available) && $sharedfolders_available === true) {
		foreach ($netshares as $netshare) {
			if (! array_key_exists($netshare->server, $mounts))
				$mounts[$netshare->server] = array();

			$mounts[$netshare->server][] = $netshare->id;
		}
	}

	foreach ($mounts as $k => $v) {
		$server = Abstract_Server::load($k);
		if (! $server)
			continue;

		if (! $server->orderFSAccessEnable($user_login_fs, $user_password_fs, $v)) {
			Logger::error('main', '(client/start) Cannot enable FS access for User "'.$user->getAttribute('login').'" on Server "'.$server->fqdn.'"');
			throw_response(INTERNAL_ERROR);
		}
	}

	if ($session->mode == Session::MODE_DESKTOP) {
		$server = Abstract_Server::load($session->server);
		if (! $server)
			continue;

		if (! is_array($server->roles) || ! array_key_exists(Server::SERVER_ROLE_APS, $server->roles))
			continue;

		if ($session->mode == Session::MODE_DESKTOP && isset($remote_desktop_settings) && array_key_exists('allow_external_applications', $remote_desktop_settings) && $remote_desktop_settings['allow_external_applications'] == 1 && count($session->servers[Server::SERVER_ROLE_APS]) > 1) {
			$external_apps_token = new Token(gen_unique_string());
			$external_apps_token->type = 'external_apps';
			$external_apps_token->link_to = $session->id;
			$external_apps_token->valid_until = 0;
			Abstract_Token::save($external_apps_token);
		}

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
		if (isset($external_apps_token))
			$session_node->setAttribute('external_apps_token', $external_apps_token->id);
		foreach (array('desktop_icons', 'locale', 'timezone') as $parameter) {
			if (! isset($$parameter))
				continue;

			$parameter_node = $dom->createElement('parameter');
			$parameter_node->setAttribute('name', $parameter);
			$parameter_node->setAttribute('value', $$parameter);
			$session_node->appendChild($parameter_node);
		}
		$user_node = $dom->createElement('user');
		$user_node->setAttribute('login', $user_login_aps);
		$user_node->setAttribute('password', $user_password_aps);
		$user_node->setAttribute('displayName', $user->getAttribute('displayname'));
		$session_node->appendChild($user_node);

		if (isset($profile_available) && $profile_available === true) {
			$profile_fileserver = Abstract_Server::load($profile_server);
			$profile_node = $dom->createElement('profile');
			$profile_node->setAttribute('server', $profile_fileserver->external_name);
			$profile_node->setAttribute('dir', $profile_name);
			$profile_node->setAttribute('login', $user_login_fs);
			$profile_node->setAttribute('password', $user_password_fs);
			$session_node->appendChild($profile_node);
		}

		if (isset($netshares) && count($netshares) > 0) {
			$sharedfolders_node = $dom->createElement('sharedfolders');
			$session_node->appendChild($sharedfolders_node);

			foreach ($netshares as $netshare) {
				$netshare_fileserver = Abstract_Server::load($netshare->server);
				$sharedfolder_node = $dom->createElement('sharedfolder');
				$sharedfolder_node->setAttribute('server', $netshare_fileserver->external_name);
				$sharedfolder_node->setAttribute('dir', $netshare->id);
				$sharedfolder_node->setAttribute('name', $netshare->name);
				$sharedfolder_node->setAttribute('login', $user_login_fs);
				$sharedfolder_node->setAttribute('password', $user_password_fs);
				$sharedfolders_node->appendChild($sharedfolder_node);
			}
		}

		foreach ($user->applications() as $application) {
			if ($application->getAttribute('type') != $server->getAttribute('type'))
				continue;

			if (! in_array($application->getAttribute('id'), $available_applications))
				continue;

			$application_node = $dom->createElement('application');
			$application_node->setAttribute('id', $application->getAttribute('id'));
			$application_node->setAttribute('name', $application->getAttribute('name'));
			if (! $application->getAttribute('static'))
				$application_node->setAttribute('mode', 'local');
			else
				$application_node->setAttribute('mode', 'static');

			$session_node->appendChild($application_node);
		}

		if (isset($start_apps) && is_array($start_apps)) {
			$start_node = $dom->createElement('start');
			foreach ($start_apps as $start_app) {
				$application_node = $dom->createElement('application');
				$application_node->setAttribute('id', $start_app['id']);
				if (! is_null($start_app['arg']))
					$application_node->setAttribute('arg', $start_app['arg']);
				$start_node->appendChild($application_node);
			}
			$session_node->appendChild($start_node);
		}

		$dom->appendChild($session_node);

		$sessionManagement->appendToSessionCreateXML($dom);

		$xml = $dom->saveXML();

		$session_create_xml = query_url_post_xml($server->getBaseURL().'/aps/session/create', $xml);
		$ret = $sessionManagement->parseSessionCreate($session_create_xml);
		if (! $ret) {
			Logger::critical('main', '(client/start) Unable to create Session \''.$session->id.'\' for User \''.$session->user_login.'\' on Server \''.$server->fqdn.'\', aborting');
			$session->orderDeletion();

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
		foreach ($session->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
			$server = Abstract_Server::load($fqdn);
			if (! $server)
				continue;

			if (! is_array($server->roles) || ! array_key_exists(Server::SERVER_ROLE_APS, $server->roles))
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
			foreach (array('desktop_icons', 'locale', 'timezone') as $parameter) {
				if (! isset($$parameter))
					continue;

				$parameter_node = $dom->createElement('parameter');
				$parameter_node->setAttribute('name', $parameter);
				$parameter_node->setAttribute('value', $$parameter);
				$session_node->appendChild($parameter_node);
			}
			$user_node = $dom->createElement('user');
			$user_node->setAttribute('login', $user_login_aps);
			$user_node->setAttribute('password', $user_password_aps);
			$user_node->setAttribute('displayName', $user->getAttribute('displayname'));
			$session_node->appendChild($user_node);

			if (isset($profile_available) && $profile_available === true) {
				$profile_fileserver = Abstract_Server::load($profile_server);
				$profile_node = $dom->createElement('profile');
				$profile_node->setAttribute('server', $profile_fileserver->external_name);
				$profile_node->setAttribute('dir', $profile_name);
				$profile_node->setAttribute('login', $user_login_fs);
				$profile_node->setAttribute('password', $user_password_fs);
				$session_node->appendChild($profile_node);
			}

			if (isset($netshares) && count($netshares) > 0) {
				$sharedfolders_node = $dom->createElement('sharedfolders');
				$session_node->appendChild($sharedfolders_node);

				foreach ($netshares as $netshare) {
					$netshare_fileserver = Abstract_Server::load($netshare->server);
					$sharedfolder_node = $dom->createElement('sharedfolder');
					$sharedfolder_node->setAttribute('server', $netshare_fileserver->external_name);
					$sharedfolder_node->setAttribute('dir', $netshare->id);
					$sharedfolder_node->setAttribute('name', $netshare->name);
					$sharedfolder_node->setAttribute('login', $user_login_fs);
					$sharedfolder_node->setAttribute('password', $user_password_fs);
					$sharedfolders_node->appendChild($sharedfolder_node);
				}
			}

			foreach ($user->applications() as $application) {
				if ($application->getAttribute('type') != $server->getAttribute('type'))
					continue;

				if (! in_array($application->getAttribute('id'), $available_applications))
					continue;

				$application_node = $dom->createElement('application');
				$application_node->setAttribute('id', $application->getAttribute('id'));
				$application_node->setAttribute('name', $application->getAttribute('name'));
				if (! $application->getAttribute('static'))
					$application_node->setAttribute('mode', 'local');
				else
					$application_node->setAttribute('mode', 'static');

				$session_node->appendChild($application_node);
			}

			if (isset($start_apps) && is_array($start_apps) && ($session->mode == Session::MODE_DESKTOP && isset($remote_desktop_settings) && array_key_exists('allow_external_applications', $remote_desktop_settings) && $remote_desktop_settings['allow_external_applications'] == 1)) {
				$start_node = $dom->createElement('start');
				foreach ($start_apps as $start_app) {
					$application_node = $dom->createElement('application');
					$application_node->setAttribute('id', $start_app['id']);
					if (! is_null($start_app['arg']))
						$application_node->setAttribute('arg', $start_app['arg']);
					$start_node->appendChild($application_node);
				}
				$session_node->appendChild($start_node);
			}

			$dom->appendChild($session_node);

			$sessionManagement->appendToSessionCreateXML($dom);

			$xml = $dom->saveXML();

			$session_create_xml = query_url_post_xml($server->getBaseURL().'/aps/session/create', $xml);
			$ret = $sessionManagement->parseSessionCreate($session_create_xml);
			if (! $ret) {
				Logger::critical('main', '(client/start) Unable to create Session \''.$session->id.'\' for User \''.$session->user_login.'\' on Server \''.$server->fqdn.'\', aborting');
				$session->orderDeletion();

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
}

$_SESSION['session_id'] = $session->id;

header('Content-Type: text/xml; charset=utf-8');
$dom = new DomDocument('1.0', 'utf-8');

$session_node = $dom->createElement('session');
$session_node->setAttribute('id', $session->id);
$session_node->setAttribute('mode', $session->mode);
$session_node->setAttribute('multimedia', $multimedia);
$session_node->setAttribute('redirect_client_drives', $redirect_client_drives);
$session_node->setAttribute('redirect_client_printers', $redirect_client_printers);
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

if (isset($profile_available) && $profile_available === true) {
	$profile_node = $dom->createElement('profile');
	$profile_node->setAttribute('server', $profile_server);
	$profile_node->setAttribute('dir', $profile_name);
	$profile_node->setAttribute('login', $user_login_fs);
	$profile_node->setAttribute('password', $user_password_fs);
	$session_node->appendChild($profile_node);
}

if (isset($sharedfolders_available) && $sharedfolders_available === true) {
	$sharedfolders_node = $dom->createElement('sharedfolders');
	foreach ($netshares as $netshare) {
		$sharedfolder_node = $dom->createElement('sharedfolder');
		$sharedfolder_node->setAttribute('server', $netshare->server);
		$sharedfolder_node->setAttribute('dir', $netshare->id);
		$sharedfolder_node->setAttribute('name', $netshare->name);
		$sharedfolder_node->setAttribute('login', $user_login_fs);
		$sharedfolder_node->setAttribute('password', $user_password_fs);
		$sharedfolders_node->appendChild($sharedfolder_node);
	}
	$session_node->appendChild($sharedfolders_node);
}

if ($session->mode == Session::MODE_DESKTOP) {
	$server = Abstract_Server::load($session->server);
	if (! $server)
		continue;

	if (! is_array($server->roles) || ! array_key_exists(Server::SERVER_ROLE_APS, $server->roles))
		continue;

	$server_applications = $server->getApplications();
	if (! is_array($server_applications))
		$server_applications = array();

	$available_applications = array();
	foreach ($server_applications as $server_application)
		$available_applications[] = $server_application->getAttribute('id');

	$server_node = $dom->createElement('server');
	$server_node->setAttribute('fqdn', $server->getAttribute('external_name'));
	$server_node->setAttribute('login', $user_login_aps);
	$server_node->setAttribute('password', $user_password_aps);
	foreach ($user->applications() as $application) {
		if ($application->getAttribute('type') != $server->getAttribute('type'))
			continue;

		if (! in_array($application->getAttribute('id'), $available_applications))
			continue;

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
} elseif ($session->mode == Session::MODE_APPLICATIONS) {
	$defined_apps = array();
	foreach ($session->servers[Server::SERVER_ROLE_APS] as $fqdn => $data) {
		$server = Abstract_Server::load($fqdn);
		if (! $server)
			continue;

		if (! is_array($server->roles) || ! array_key_exists(Server::SERVER_ROLE_APS, $server->roles))
			continue;

		$server_applications = $server->getApplications();
		if (! is_array($server_applications))
			$server_applications = array();

		$available_applications = array();
		foreach ($server_applications as $server_application)
			$available_applications[] = $server_application->getAttribute('id');

		$server_node = $dom->createElement('server');
		$server_node->setAttribute('fqdn', $server->getAttribute('external_name'));
		$server_node->setAttribute('login', $user_login_aps);
		$server_node->setAttribute('password', $user_password_aps);

		foreach ($user->applications() as $application) {
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
$dom->appendChild($session_node);

echo $dom->saveXML();
exit(0);
