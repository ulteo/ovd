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
require_once(dirname(__FILE__).'/includes/core.inc.php');

$plugins = new Plugins();
$plugins->doLoad();

$plugins->doInit();

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$default_settings = $prefs->get('general', 'session_settings_defaults');
$desktop_locale = $default_settings['language'];
$desktop_size = 'auto';
$desktop_quality = $default_settings['quality'];
$desktop_timeout = $default_settings['timeout'];
$timeout_message = $default_settings['session_timeout_msg'];
$start_app = '';
$persistent = $default_settings['persistent'];
$shareable = $default_settings['shareable'];
$desktop_icons = $default_settings['desktop_icons'];
$debug = 0;

$advanced_settings = $prefs->get('general', 'session_settings_defaults');
$advanced_settings = $advanced_settings['advanced_settings_startsession'];
if (!is_array($advanced_settings))
	$advanced_settings = array();

if (in_array('language', $advanced_settings) && isset($_REQUEST['desktop_locale']) && $_REQUEST['desktop_locale'] != '')
	$desktop_locale = $_REQUEST['desktop_locale'];

if (in_array('quality', $advanced_settings) && isset($_REQUEST['desktop_quality']) && $_REQUEST['desktop_quality'] != '')
	$desktop_quality = $_REQUEST['desktop_quality'];

if (in_array('timeout', $advanced_settings) && isset($_REQUEST['desktop_timeout']) && $_REQUEST['desktop_timeout'] != '')
	$desktop_timeout = $_REQUEST['desktop_timeout'];

if (in_array('application', $advanced_settings) && isset($_REQUEST['start_app']) && $_REQUEST['start_app'] != '')
	$start_app = $_REQUEST['start_app'];

if (in_array('persistent', $advanced_settings) && isset($_REQUEST['persistent']) && $_REQUEST['persistent'] != '')
	$persistent = $_REQUEST['persistent'];

if (in_array('shareable', $advanced_settings) && isset($_REQUEST['shareable']) && $_REQUEST['shareable'] != '')
	$shareable = $_REQUEST['shareable'];

if (in_array('desktop_icons', $advanced_settings) && isset($_REQUEST['desktop_icons']) && $_REQUEST['desktop_icons'] != '')
	$desktop_icons = $_REQUEST['desktop_icons'];

if (in_array('debug', $advanced_settings) && isset($_REQUEST['debug']) && $_REQUEST['debug'] != '')
	$debug = $_REQUEST['debug'];

$mods_enable = $prefs->get('general', 'module_enable');
if (!in_array('UserDB', $mods_enable))
	die_error('Module UserDB must be enabled',__FILE__,__LINE__);

$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
$userDB = new $mod_user_name();

$use_sso = $prefs->get('general', 'user_authenticate_sso');
if ($use_sso) {
	$user_authenticate_trust = $prefs->get('general', 'user_authenticate_trust');

	$user_login = $_SERVER[$user_authenticate_trust];
} else {
	if (!isset($_SESSION['login']))
		die_error('You must be authenticated to start a session',__FILE__,__LINE__);

	$user_login = $_SESSION['login'];
}

$user = $userDB->import($user_login);
if (!is_object($user))
	die_error(_('User importation failed'),__FILE__,__LINE__);

Logger::debug('main', '(startsession) Now checking for old session');

$already_online = 0;
$sessions = Sessions::getByUser($user->getAttribute('login'));
if ($sessions > 0) {
	foreach ($sessions as $session)
		if ($session->isSuspended()) {
			$old_session_id = $session->id;
			$old_session_server = $session->server;
		} elseif ($session->isAlive())
			$already_online = 1;
}

if (in_array('server', $advanced_settings) && isset($_REQUEST['force']) && $_REQUEST['force'] != '')
	$random_server = $_REQUEST['force'];
else {
	$serv_tmp = $user->getAvailableServer('linux'); // FIXME : a session server  != linux server...
	if (is_object($serv_tmp))
		$random_server = $serv_tmp->fqdn;

	if ((!isset($random_server)) || is_null($random_server) || $random_server == '')
		die_error(_('No available server'),__FILE__,__LINE__);

	if (@gethostbyname($random_server) == $random_server) {
		$buf = $prefs->get('general', 'application_server_settings');
		$fqdn_private_address = $buf['fqdn_private_address'];
		if (isset($fqdn_private_address[$random_server]))
			$random_server = $fqdn_private_address[$random_server];
	}
}

if (isset($old_session_id) && isset($old_session_server)) {
	$session = Abstract_Session::load($old_session_id);

	$session_mode = 'resume';

	$ret = true;

	Logger::info('main', '(startsession) Resuming session ('.$old_session_id.' => '.$old_session_server.')');
} else {
	$random_session_id = gen_string(5);

	$session = new Session($random_session_id);
	$session->server = $random_server;
	$session->status = -1;
	$session->user_login = $user->getAttribute('login');
	$session->user_displayname = $user->getAttribute('displayname');

	$session_mode = 'start';

	$ret = true;

	Logger::info('main', '(startsession) Creating new session ('.$random_session_id.' => '.$random_server.')');
}

if ($ret === false)
	die_error(_('No available session'),__FILE__,__LINE__);

$fs = $prefs->get('plugins', 'FS');
// for now we can use one FS at the same time
//if (!is_array($fs) || count($fs) == 0)
if (is_null($fs))
	die_error(_('No available filesystem'),__FILE__,__LINE__);
//$module_fs = $fs[0];
$module_fs = $fs;

$default_args = array(
// 	'user_id'			=>	$user->getAttribute('uid'),
	'user_login'		=>	$user->getAttribute('login'),
	'user_displayname'	=>	$user->getAttribute('displayname'),
	'locale'			=>	$desktop_locale,
	'quality'			=>	$desktop_quality,
	'home_dir_type'	=>	$module_fs
);

$optional_args = array();
if (isset($desktop_timeout) && $desktop_timeout != -1) {
	$optional_args['timeout'] = (time()+$desktop_timeout);
	$optional_args['timeout_message'] = $timeout_message;
}
if (isset($start_app) && $start_app != '')
	$optional_args['start_app'] = $start_app;
if (isset($debug) && $debug != '0')
	$optional_args['debug'] = 1;
if (isset($persistent) && $persistent != '0')
	$optional_args['persistent'] = 1;
if (isset($shareable) && $shareable != '0')
	$optional_args['shareable'] = 1;
if (isset($desktop_icons) && $desktop_icons != '0')
	$optional_args['desktop_icons'] = 1;

if ($prefs->get('UserDB', 'enable') == 'activedirectory') {
	$config_ad = $prefs->get('UserDB', 'activedirectory');
	$windows_server = $user->getAvailableServer('windows');
	if (is_object($windows_server)) {
		$optional_args['windows_server'] = $windows_server->fqdn;
		$optional_args['windows_login'] = $user->getAttribute('real_login').'@'.$config_ad['domain'];
		$optional_args['windows_password'] = $_SESSION['password'];
	} else
		Logger::error('main', '(startsession) No windows server available for user \''.$user->getAttribute('login').'\'');
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
}

$data = array();
foreach ($default_args as $k => $v)
	$data[$k] = $v;
foreach ($optional_args as $k => $v)
	$data[$k] = $v;
foreach ($plugins_args as $k => $v)
	$data[$k] = $v;

$session->setAttribute('settings', $data);
Abstract_Session::save($session);

$token = new Token(gen_string(5));
$token->type = $session_mode;
$token->link_to = $session->id;
$token->valid_until = (time()+(60*5));
Abstract_Token::save($token);

$buf = Abstract_Server::load($session->server);

$redir = 'http://'.$buf->getAttribute('external_name').'/index.php?token='.$token->id;

$ev = Events::getEvent('SessionStartEvent', array(
	'server'	=>	$buf->getAttribute('external_name'),
	'resume'	=>	$session->isSuspended(),
	'token'	=>	$token->id,
	'user'	=>	$user,
	'sessid'	=>	$session->id
));
$ev->emit();

$report = new Reporting($session->id);
$report->session_begin($token->id, $user);

redirect($redir);
