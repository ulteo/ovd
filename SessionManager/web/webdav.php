<?php
require_once('includes/core.inc.php');

function Unauthorized() {
	header('WWW-Authenticate: Basic realm="Ulteo WebDAV"');
	header('HTTP/1.0 401 Unauthorized');
	die('401 Unauthorized');
}

function ParseURL() {
	$matches = array();
	preg_match('@/webdav\.php/([^/]+)/([^/]+)/@', $_SERVER['REQUEST_URI'], $matches);
	if (! is_array($matches) || ! array_key_exists(1, $matches) || ! array_key_exists(2, $matches))
		return Unauthorized();

	$usergroup_id = $matches[1];
	$sharedfolder_id = $matches[2];

	$sharedfolder = Abstract_UserGroup_SharedFolder::load($sharedfolder_id);
	if (! $sharedfolder)
		return Unauthorized();

	if ($sharedfolder->usergroup_id != $usergroup_id)
		return Unauthorized();

	return $sharedfolder;
}

function AuthenticationBasicHTTP() {
	if (! isset($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_USER']))
		return Unauthorized();

	if (! isset($_SERVER['PHP_AUTH_PW']) || empty($_SERVER['PHP_AUTH_PW']))
		return Unauthorized();

	$login = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	$dav_user = Abstract_DAV_User::load($login);
	if (! $dav_user)
		return Unauthorized();

	if ($password != $dav_user->password)
		return Unauthorized();

	$sharedfolder = ParseURL();
	if (! $sharedfolder)
		return Unauthorized();

	$userGroupDB = UserGroupDB::getInstance();

	$usergroup = $userGroupDB->import($sharedfolder->usergroup_id);

	if (! is_object($usergroup))
		return Unauthorized();

	$usergroup_users = $usergroup->usersLogin();
	if (! in_array($login, $usergroup_users))
		return Unauthorized();

	return true;
}

if (! AuthenticationBasicHTTP())
	return Unauthorized();

require_once('HTTP/WebDAV/Server/Filesystem.php');
$server = new HTTP_WebDAV_Server_Filesystem();

$prefs = Preferences::getInstance();

if (! $prefs)
	Unauthorized();
$mysql_conf = $prefs->get('general', 'mysql');

$server->db_host = $mysql_conf['host'];
$server->db_user = $mysql_conf['user'];
$server->db_passwd = $mysql_conf['password'];
$server->db_name = $mysql_conf['database'];
$server->db_prefix = $mysql_conf['prefix'].'dav_';

$server->ServeRequest(SHAREDFOLDERS_DIR);
