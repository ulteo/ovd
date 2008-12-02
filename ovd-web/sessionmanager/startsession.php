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

$lock = new Lock($user->getAttribute('login'));
if ($lock->have_lock()) {
	$session = new Session($lock->session);

	if ($session->session_alive())
		die_error(_('You already have a session active'),__FILE__,__LINE__);
}

$advanced_settings = $prefs->get('general', 'advanced_settings_startsession');
if (!is_array($advanced_settings))
	$advanced_settings = array();

if (in_array('server', $advanced_settings) && isset($_REQUEST['force']) && $_REQUEST['force'] != '')
	$random_server = $_REQUEST['force'];
else {
	$serv_tmp = $user->getAvalaibleServer();
	if (is_object($serv_tmp))
		$random_server = $serv_tmp->fqdn;

	if ((!isset($random_server)) || is_null($random_server) || $random_server == '')
		die_error(_('No available server'),__FILE__,__LINE__);

	if (@gethostbyname($random_server) == $random_server) {
		$fqdn_private_address = $prefs->get('general', 'fqdn_private_address');
		if (isset($fqdn_private_address[$random_server]))
			$random_server = $fqdn_private_address[$random_server];
	}
}

$session = new Session(md5(rand()), $random_server);
$ret = $session->add_session(0);

if ($ret === false)
	die_error(_('No available session'),__FILE__,__LINE__);

$lock->add_lock($session->session, $session->server);

if (in_array('language', $advanced_settings) && isset($_REQUEST['desktop_locale']) && $_REQUEST['desktop_locale'] != '')
	$desktop_locale = $_REQUEST['desktop_locale'];

if (in_array('quality', $advanced_settings) && isset($_REQUEST['desktop_quality']) && $_REQUEST['desktop_quality'] != '')
	$desktop_quality = $_REQUEST['desktop_quality'];

if (in_array('timeout', $advanced_settings) && isset($_REQUEST['desktop_timeout']) && $_REQUEST['desktop_timeout'] != '')
	$desktop_timeout = $_REQUEST['desktop_timeout'];

if (in_array('application', $advanced_settings) && isset($_REQUEST['start_app']) && $_REQUEST['start_app'] != '')
	$start_app = $_REQUEST['start_app'];

if (in_array('debug', $advanced_settings) && isset($_REQUEST['debug']) && $_REQUEST['debug'] != '')
	$debug = $_REQUEST['debug'];

$fs = $prefs->get('plugins', 'FS');
// for now we can use one FS at the same time
//if (!is_array($fs) || count($fs) == 0)
if (is_null($fs))
	die_error(_('No available filesystem'),__FILE__,__LINE__);
//$module_fs = $fs[0];
$module_fs = $fs;

$default_args = array(
	'user_id'			=>	$user->getAttribute('uid'),
	'user_login'		=>	str_replace(' ', '', $user->getAttribute('login')),
	'user_displayname'	=>	str_replace(' ', '', $user->getAttribute('displayname')),
	'locale'			=>	$desktop_locale,
	'quality'			=>	$desktop_quality,
	'timeout'			=>	$desktop_timeout,
	'debug'			=>	$debug,
	'start_app'		=>	$start_app,
	'home_dir_type'	=>	$module_fs
);

$plugins->doStartsession(array(
	'fqdn'	=>	$session->server,
	'session'	=>	$session->session
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
foreach ($plugins_args as $k => $v)
	$data[$k] = $v;

$token = $session->create_token('start', $data);

$redir = 'http://'.$session->server.'/index.php?token='.$token;

redirect($redir);
