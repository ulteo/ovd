<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2010, 2012
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2010
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
require_once(dirname(__FILE__).'/core-minimal.inc.php');

function get_root_admin_url() {
	// Retrieve the admin root URL
	$root_admin_dir = dirname(__FILE__);
	$root_admin_url = @$_SERVER['REQUEST_URI'];
	
	$len1 = count(explode(DIRECTORY_SEPARATOR, $root_admin_dir));
	$len2 = count(explode(DIRECTORY_SEPARATOR, realpath(@$_SERVER['SCRIPT_FILENAME'])));
	if ($len1 > $len2) {
		// Error: not possible !
		return $root_admin_url;
	}
	
	for ($i=$len2 - $len1; $i>0; $i--) {
		$pos = strrpos ($root_admin_url, '/');
		if ($pos === False)
			// Error: not possible !
			return $root_admin_url;
		
		$root_admin_url = substr($root_admin_url, 0, $pos);
	}
	
	return $root_admin_url;
}

define('ROOT_ADMIN_URL', get_root_admin_url());
define('CURRENT_ADMIN_PAGE', substr($_SERVER['REQUEST_URI'], strlen(ROOT_ADMIN_URL)+1));

if (! array_key_exists('service', $_SESSION) or ! array_key_exists('admin_login', $_SESSION) or ! array_key_exists('admin_password', $_SESSION)) {
	if (basename($_SERVER['PHP_SELF']) == 'login.php') {
		die_error(_('Unable to initialize communication with Session Manager'));
		// todo : add trace or more info panel
	}
	
	$_SESSION['redirect'] = base64_encode($_SERVER['REQUEST_URI']);
	redirect('login.php');
}

try {
	// It seems the SoapClient object is not serialized correctly in $_SESSION object.
	// So create a new instance for each connection ...
	$service = new SessionManager($_SESSION['admin_login'], $_SESSION['admin_password'], (! array_key_exists('no_ssl', $_SESSION)));
	
	// At the moment, get the configuration at each connection.
	// In the future, this part will be cached for a time range to avoid requesting the Session Manager each time ...
	$configuration = $service->getInitialConfiguration();
}
catch (Exception $e) {
	unset($_SESSION['admin_login']);
	unset($_SESSION['admin_password']);
	
	if ($e->faultcode == 'auth_failed') {
		$_SESSION['admin_error'] = _('You have been disconnected from the Session Manager');
		redirect('login.php');
	}
	
	die_error(_('Unable to initialize communication with Session Manager'));
}

$_SESSION['service'] = $service;
$_SESSION['configuration'] = $configuration;

if (! array_key_exists('system_inited', $_SESSION['configuration']) or  $_SESSION['configuration']['system_inited'] !== true) {
	if ($_SERVER['REQUEST_METHOD'] == 'GET' && CURRENT_ADMIN_PAGE != 'configuration.php?action=init') {
		redirect('configuration.php?action=init');
	}
}

if (array_key_exists('admin_language', $_SESSION['configuration'])) {
	$lang = $_SESSION['configuration']['admin_language'];
	if ($lang != 'auto') {
		set_language($lang);
	}
}

if (array_key_exists('system_in_maintenance', $_SESSION['configuration']) &&
    $_SESSION['configuration']['system_in_maintenance'] == '1') {
	popup_error(_('The system is in maintenance mode'));
	if (isset($_SESSION['infomsg'][0]) &&
	    $_SESSION['infomsg'][0] == _('The system is in production mode'))
		$_SESSION['infomsg'] = array();
}
elseif (array_key_exists('system_in_maintenance', $_SESSION['configuration']) &&
        $_SESSION['configuration']['system_in_maintenance'] == '0') {
	popup_info(_('The system is in production mode'));
	if (isset($_SESSION['errormsg'][0]) &&
	    $_SESSION['errormsg'][0] == _('The system is in maintenance mode'))
		$_SESSION['errormsg'] = array();
}
