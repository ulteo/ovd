<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

if (isset($_GET["usersgroup"]) && isset($_GET["visible"]) && $_GET["visible"] == "add" ){
	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);
	$mods_enable = $prefs->get('general','module_enable');
	if (! in_array('UserDB',$mods_enable))
		die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);

	$mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
	$userGroupDB = new $mod_usergroup_name();

	$ug = $userGroupDB->import($_GET["usersgroup"]);
	if (is_object($ug)) {
		echo usersgroup_appsgroup_add($ug);
	}
}
if (isset($_GET["usersgroup"]) && isset($_GET["visible"]) && $_GET["visible"] == "del" ){
	$prefs = Preferences::getInstance();
	if (! $prefs)
		die_error('get Preferences failed',__FILE__,__LINE__);
	$mods_enable = $prefs->get('general','module_enable');
	if (! in_array('UserDB',$mods_enable))
		die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);

	$mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
	$userGroupDB = new $mod_usergroup_name();

	$ug = $userGroupDB->import($_GET["usersgroup"]);
	if (is_object($ug)) {
		echo usersgroup_appsgroup_del($ug);
	}
}
if (isset($_GET["user"]) && isset($_GET["visible"]) && $_GET["visible"] == "add" ){
	$prefs = Preferences::getInstance();
	if (! $prefs) {
		return_error();
		die();
	}
	$mods_enable = $prefs->get('general','module_enable');
	if (!in_array('UserDB',$mods_enable)){
		die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
	}
	$mod_user_name = 'UserDB_'.$prefs->get('UserDB','enable');
	$userDB = new $mod_user_name();

	$ug = $userDB->import($_GET["user"]);
	if ($userDB->isOK($ug)){
		echo user_usersgroup_add($ug);
	}
}
if (isset($_GET["user"]) && isset($_GET["visible"]) && $_GET["visible"] == "del" ){
	$prefs = Preferences::getInstance();
	if (! $prefs) {
		return_error();
		die();
	}
	$mods_enable = $prefs->get('general','module_enable');
	if (!in_array('UserDB',$mods_enable)){
		die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
	}
	$mod_user_name = 'UserDB_'.$prefs->get('UserDB','enable');
	$userDB = new $mod_user_name();

	$ug = $userDB->import($_GET["user"]);
	if ($userDB->isOK($ug)){
		echo user_usersgroup_del($ug);
	}
}
if (isset($_GET["application"]) && isset($_GET["visible"]) && $_GET["visible"] == "add" ){
	$prefs = Preferences::getInstance();
	if (! $prefs) {
		return_error();
		die();
	}
	$mods_enable = $prefs->get('general','module_enable');
	if (!in_array('ApplicationDB',$mods_enable)){
		die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
	}
	$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB','enable');
	$applicationDB = new $mod_app_name();
	$a = $applicationDB->import($_GET["application"]);
	if ($applicationDB->isOK($a)) {
		echo application_appsgroup_add($a);
	}
}
if (isset($_GET["application"]) && isset($_GET["visible"]) && $_GET["visible"] == "del" ){
	$prefs = Preferences::getInstance();
	if (! $prefs) {
		return_error();
		die();
	}
	$mods_enable = $prefs->get('general','module_enable');
	if (!in_array('ApplicationDB',$mods_enable)){
		die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
	}
	$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB','enable');
	$applicationDB = new $mod_app_name();
	$a = $applicationDB->import($_GET["application"]);
	if ($applicationDB->isOK($a)) {
		echo application_appsgroup_del($a);
	}
}
