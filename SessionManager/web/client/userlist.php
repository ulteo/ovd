<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008
 * Author Laurent CLOUET <laurent@ulteo.com> 2010
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

$prefs = Preferences::getInstance();
if (! $prefs) {
	Logger::error('main', '(userlist) get Preferences failed');
	die();
}

$web_interface_settings = $prefs->get('general', 'web_interface_settings');
if (! array_key_exists('show_list_users', $web_interface_settings) || $web_interface_settings['show_list_users'] == 0) {
	Logger::debug('main', '(userlist) Show list users is disabled, aborting');
	die();
}

$userDB = UserDB::getInstance();

$users = array();
if ($userDB->canShowList()) {
	$users = $userDB->getList();
	
	if (! is_array($users))
		$users = array();
}

$dom = new DomDocument('1.0', 'utf-8');

$users_node = $dom->createElement('users');
foreach ($users as $user) {
	$user_node = $dom->createElement('user');
	$user_node->setAttribute('login', $user->getAttribute('login'));
	$user_node->setAttribute('displayname', $user->getAttribute('displayname'));
	$users_node->appendChild($user_node);
}
$dom->appendChild($users_node);

header('Content-Type: text/xml; charset=utf-8');
echo $dom->saveXML();
die();
