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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

header('Content-Type: text/plain');

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error(_('get Preferences failed'),__FILE__,__LINE__);

$mods_enable = $prefs->get('general', 'module_enable');
if (!in_array('UserDB', $mods_enable))
	die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);

$mod_user_name = 'UserDB_'.$prefs->get('UserDB', 'enable');
$userDB = new $mod_user_name();

if ($userDB->canShowList())
	$list_users = $userDB->getList();

foreach ($list_users as $list_user)
	echo $list_user->getAttribute('login')."\n";
