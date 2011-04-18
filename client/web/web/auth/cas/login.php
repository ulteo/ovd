<?php
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

if (array_key_exists('session_var', $_SESSION['ovd-client']['sessionmanager']) && array_key_exists('session_id', $_SESSION['ovd-client']['sessionmanager'])) {
	if (! array_key_exists('phpCAS', $_SESSION))
		$_SESSION['phpCAS'] = array();

	if (! array_key_exists('service_cookies', $_SESSION['phpCAS']))
		$_SESSION['phpCAS']['service_cookies'] = array();

	if (! array_key_exists(0, $_SESSION['phpCAS']['service_cookies'])) {
		$_SESSION['phpCAS']['service_cookies'][0]['domain'] = $_SERVER['SERVER_ADDR'];
		$_SESSION['phpCAS']['service_cookies'][0]['path'] = '/';
		$_SESSION['phpCAS']['service_cookies'][0]['secure'] = false;
		$_SESSION['phpCAS']['service_cookies'][0]['name'] = $_SESSION['ovd-client']['sessionmanager']['session_var'];
		$_SESSION['phpCAS']['service_cookies'][0]['value'] = $_SESSION['ovd-client']['sessionmanager']['session_id'];
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
