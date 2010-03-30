<?php
/**
 * Copyright (C) 2010 Ulteo SAS
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
require_once(dirname(__FILE__).'/includes/core-minimal.inc.php');

if (! isset($_GET['id'])) {
	Logger::error('main', '(icon) Missing parameter : id');
	die();
}

if (! isset($_SESSION['login'])) {
	$ret = do_login();
	if (! $ret) {
		Logger::error('main', '(applications) Authentication failed');
		die();
	}
}

if (! isset($_SESSION['login'])) {
	Logger::error('main', '(applications) Authentication failed');
	die();
}

$user_login = $_SESSION['login'];

$userDB = UserDB::getInstance();

$user = $userDB->import($user_login);
if (! is_object($user)) {
	Logger::error('main', '(applications) User importation failed');
	die();
}

$applicationDB = ApplicationDB::getInstance();

$app = $applicationDB->import($_GET['id']);
if (! is_object($app)) {
	Logger::error('main', '(icon) Import failed for Application \''.$_GET['id'].'\'');
	die();
}

$path = $app->getIconPath();
if (! file_exists($path)) {
	Logger::error('main', '(icon) No icon found for Application \''.$_GET['id'].'\'');
	die();
}

header('Content-Type: image/png');
echo @file_get_contents($path);
die();
