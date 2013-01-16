<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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

require_once(dirname(__FILE__).'/../../includes/core.inc.php');

function finish() {
	echo '<script type="text/javascript">';
	echo 'parent.startSession();';
	echo 'parent.hideIFrame();';
	echo '</script>';

	return;
}

if (! is_array($_GET) || count($_GET) == 0 || ! array_key_exists('CAS_server_url', $_GET)) {
	$_SESSION['ovd-client']['from_SM_start_XML'] = 'ERROR';
	finish();
	die();
}
$CAS_server_url = urldecode($_GET['CAS_server_url']);
unset($_GET['CAS_server_url']);

$protocol =  'https';
/*if (! array_key_exists('HTTPS', $_SERVER) || $_SERVER['HTTPS'] != 'on')
	$protocol = 'http';*/
$host = $_SERVER['HTTP_HOST'];
$port = $_SERVER['SERVER_PORT'];
$path = dirname($_SERVER['SCRIPT_NAME']);

$CAS_callback_url = $protocol.'://'.$host;
if ($port != 443)
	$CAS_callback_url .= ':'.$port;
$CAS_callback_url .= $path.'/callback.php';

require_once('CAS.php');

if (array_key_exists('sessionmanager', $_SESSION['ovd-client'])) {
	if (! array_key_exists('phpCAS', $_SESSION))
		$_SESSION['phpCAS'] = array();

	if (! array_key_exists('service_cookies', $_SESSION['phpCAS']))
		$_SESSION['phpCAS']['service_cookies'] = array();

	$sm = $_SESSION['ovd-client']['sessionmanager'];
	foreach($sm->get_cookies() as $k => $v) {
		$cookie = array(
			'domain' => parse_url($sm->get_base_url(), PHP_URL_HOST),
			'path' => '/',
			'secure' => false,
			'name' => $k,
			'value' => $v,
		);
		
		$_SESSION['phpCAS']['service_cookies'][]= $cookie;
	}
}

phpCAS::proxy(CAS_VERSION_2_0, parse_url($CAS_server_url, PHP_URL_HOST), parse_url($CAS_server_url, PHP_URL_PORT), parse_url($CAS_server_url, PHP_URL_PATH), false);

phpCAS::setNoCasServerValidation();

phpCAS::setPGTStorageFile(CAS_PGT_STORAGE_FILE_FORMAT_PLAIN, session_save_path());
phpCAS::setFixedCallbackURL($CAS_callback_url); //HTTPS required, and Apache's CRT must be added in Tomcat's keystore (CAS server)

phpCAS::forceAuthentication();

if (! phpCAS::serviceWeb($_SESSION['ovd-client']['sessionmanager_url'].'/start.php', $errno, $output)) {
	$_SESSION['ovd-client']['from_SM_start_XML'] = 'ERROR';
	finish();
	die();
}

$_SESSION['ovd-client']['from_SM_start_XML'] = $output;

finish();

die();
