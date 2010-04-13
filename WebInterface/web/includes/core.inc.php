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

define('WEBINTERFACE_CONF_FILE', '/etc/ulteo/webinterface/config.inc.php');

if (file_exists(WEBINTERFACE_CONF_FILE))
	include_once(WEBINTERFACE_CONF_FILE);

require_once(dirname(__FILE__).'/functions.inc.php');

session_start();

$sessionmanager_url = NULL;
if (defined('SESSIONMANAGER_URL'))
	$sessionmanager_url = SESSIONMANAGER_URL;
elseif (array_key_exists('webinterface', $_SESSION) && array_key_exists('sessionmanager_url', $_SESSION['webinterface']))
	$sessionmanager_url = $_SESSION['webinterface']['sessionmanager_url'];

if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$buf = explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
	$buf = explode(';', $buf[0]);
	$user_language = $buf[0];
} else
	$user_language = 'en-us';
