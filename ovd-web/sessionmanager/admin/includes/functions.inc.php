<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Laurent CLOUET <laurent@ulteo.com>
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
 require_once(dirname(__FILE__).'/core.inc.php');

function usersgroup_appsgroup_add($ug){
	if (is_object($ug)){
		$res = "";
		$all_appgroup = getAllAppsGroups();
		$my_apps_groups = $ug->appsGroups();
		if (is_array($all_appgroup) && count($all_appgroup) >0){
			$aff = false;
			foreach ($all_appgroup as $ag1){
				if ( is_array($my_apps_groups) && in_array($ag1,$my_apps_groups) == false  ) {
					$aff = true;
					break;
				}
			}
			if ($aff){
				$res .= '<select id="usersgroup_appsgroup_add_id_appsgroup_'.$ug->id.'"  name="appsgroups">';
				foreach ($all_appgroup as $ag1){
					if ( is_array($my_apps_groups) &&  in_array($ag1,$my_apps_groups) == false  ) {
						$res .= '<option value="'.$ag1->id.'" >'.$ag1->name.'</option>';
					}
				}
				$res .= '</select>';
				$res .= '<input type="button" id="usersgroup_appsgroup_add_button_'.$ug->id.'" name="usersgroup_appsgroup_add_button" onclick="usersgroup_add_to_appsgroup(\''.$ug->id.'\'); return false"  value="'._('Add').'" />';
			}
		}
		return $res;
	}
	else
		return false;
}

function usersgroup_appsgroup_del($ug){
	if (is_object($ug)){
		$my_apps_groups = $ug->appsGroups();
		$res = "";
		if ( is_array($my_apps_groups) && count($my_apps_groups)>0){
			$res .= '<select id="usersgroup_appsgroup_del_id_appsgroup_'.$ug->id.'"  name="appsgroups">';
			foreach ($my_apps_groups as $apps_group){
				$res .= '<option value="'.$apps_group->id.'" >'.$apps_group->name.'</option>';
			}
			$res .= '</select>';
			$res .= '<input type="button" id="usersgroup_appsgroup_del_button_'.$ug->id.'" name="usersgroup_appsgroup_del_button_'.$ug->id.'" onclick="usersgroup_remove_from_appsgroup(\''.$ug->id.'\'); return false"  value="'._('Del').'" />';
		}
		return $res;
	}
	else
		return false;
}

function user_usersgroup_add($u){
	if (is_object($u)){
		$res = "";
		$all_usergroup = get_all_usergroups();
		$my_groups = $u->usersGroups();
		if (is_array($all_usergroup) && count($all_usergroup) >0){
			$group_to_add = array();
			foreach ($all_usergroup as $ug1){
				if ( is_array($my_groups) && in_array($ug1,$my_groups) == false  ) {
					$group_to_add []= $ug1;
				}
			}
			if (count($group_to_add)>0){
				$res .= '<select id="user_add_to_usersgroup_uid_'.$u->getAttribute('uid').'"  name="usersgroup">';
				foreach ($group_to_add as $ug1){
					if ( is_array($my_groups) &&  in_array($ug1,$my_groups) == false  ) {
						$res .= '<option value="'.$ug1->id.'" >'.$ug1->name.'</option>';
					}
				}
				$res .= '</select>';
				$res .= '<input type="button" id="user_usersgroup_add_button_'.$u->getAttribute('uid').'" name="user_usersgroup_add_button" onclick="user_add_to_usersgroup(\''.$u->getAttribute('login').'\',\''.$u->getAttribute('uid').'\'); return false"  value="'._('Add').'" />';
			}
		}
		return $res;
	}
	else
		return false;
}

function user_usersgroup_del($u){
	if (is_object($u)){
		$my_groups = $u->usersGroups();
		$res = "";
		if ( is_array($my_groups) && count($my_groups)>0){
			$res .= '<select id="user_del_to_usersgroup_uid_'.$u->getAttribute('uid').'"  name="usersgroup">';
			foreach ($my_groups as $group){
				$res .= '<option value="'.$group->id.'" >'.$group->name.'</option>';
			}
			$res .= '</select>';
			$res .= '<input type="button" id="user_usersgroup_del_button_'.$u->getAttribute('uid').'" name="user_usersgroup_del_button_'.$u->getAttribute('uid').'" onclick="user_remove_from_usersgroup(\''.$u->getAttribute('login').'\',\''.$u->getAttribute('uid').'\'); return false"  value="'._('Del').'" />';
		}
		return $res;
	}
	else
		return false;
}

function application_appsgroup_add($a){
	if (is_object($a)){
		$res = "";
		$all_appgroup = getAllAppsGroups();
		$my_groups = $a->groups();
		if (is_array($all_appgroup) && count($all_appgroup) >0){
			$aff = array();
			foreach ($all_appgroup as $ag1){
				if (in_array($ag1,$my_groups) == false ) {
					$aff []= $ag1;
				}
			}
			if (count($aff)>0){
				$res .= '<select id="application_add_to_appsgroup_id_'.$a->getAttribute('id').'"  name="appsgroup">';
				foreach ($aff as $ag1){
					$res .= '<option value="'.$ag1->id.'" >'.$ag1->name.'</option>';
				}
				$res .= '</select>';
				$res .= '<input type="button" id="application_add_to_appsgroup_button_'.$a->getAttribute('id').'" name="application_add_to_appsgroup_button" onclick="application_add_to_appsgroup(\''.$a->getAttribute('id').'\',$(\'application_add_to_appsgroup_id_'.$a->getAttribute('id').'\').value); return false"  value="'._('Add').'" />';
			}
		}
		return $res;
	}
	else
		return false;
}

function application_appsgroup_del($a){
	if (is_object($a)){
		$my_groups = $a->groups();
		$res = "";
		if ( is_array($my_groups) && count($my_groups)>0){
			$res .= '<select id="application_del_to_appsgroup_id_'.$a->getAttribute('id').'"  name="usersgroup">';
			foreach ($my_groups as $group){
				$res .= '<option value="'.$group->id.'" >'.$group->name.'</option>';
			}
			$res .= '</select>';
			$res .= '<input type="button" id="application_del_to_appsgroup_del_button_'.$a->getAttribute('id').'" name="application_del_to_appsgroup_del_button'.$a->getAttribute('id').'" onclick="application_remove_from_appsgroup(\''.$a->getAttribute('id').'\',$(\'application_del_to_appsgroup_id_'.$a->getAttribute('id').'\').value); return false"  value="'._('Del').'" />';
		}
		return $res;
	}
}

function getAllAppsGroups(){
	Logger::debug('main','MAINMINIMAL::getAllAppsGroups');
	$sql2 = MySQL::getInstance();
	$res = $sql2->DoQuery('SELECT @1,@2,@3,@4 FROM @5', 'id', 'name', 'description', 'published', APPSGROUP_TABLE);
	if ($res !== false){
		$result = array();
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row){
			$g = new AppsGroup($row['id'],$row['name'],$row['description'],$row['published']);
			$result []= $g;
		}
		return $result;
	}
	else {
		// not the right argument
		return NULL;
	}
}

function get_all_sourceslist_mirrors(){
	$sql2 = MySQL::getInstance();
	$res = $sql2->DoQuery('SELECT @1 FROM @2','element', SOURCES_LIST_TABLE);
	if ($res !== false){
		$result = array();
		$rows = $sql2->FetchAllResults($res);
		foreach ($rows as $row){
			$result []= $row['element'];
		}
		return $result;
	}
	else {
		// not the right argument
		return NULL;
	}
}

function get_publications($usersgroup_id_=NULL,$appsgroup_id_=NULL) {
	$ret = array();
	$l = new UsersGroupApplicationsGroupLiaison($usersgroup_id_,$appsgroup_id_);
	if (is_null($usersgroup_id_) && is_null($appsgroup_id_)) {
		echo "usersgroup_id_ null appsgroup_id_  null<br>";
		$l_all = $l->all();
		foreach ($l_all as $element_group) {
			if  (is_array($element_group)) {
				$r = array();
				$ug = new UsersGroup();
				$ug->fromDB($element_group['element']);
				$ag = new AppsGroup();
				$ag->fromDB($element_group['group']);
				if ( $ag->published && $ug->published) {
					$r['appsgroup'] = $ag;
					$r['usersgroup'] = $ug;
					$ret[] = $r;
				}
			}
		}
	}else if (is_null($usersgroup_id_) && !is_null($appsgroup_id_)) {
		echo "usersgroup_id_ null appsgroup_id_ $appsgroup_id_<br>";
		$l_elements = $l->elements();
		$ag = new AppsGroup();
		$ag->fromDB($appsgroup_id_);
		foreach ($l_elements as $ug_id) {
			$r = array();
			$ug = new UsersGroup();
			$ug->fromDB($ug_id);
			if ( $ag->published && $ug->published) {
				$r['appsgroup'] = $ag;
				$r['usersgroup'] = $ug;
				$ret[] = $r;
			}
		}
	}else if (!is_null($usersgroup_id_) && is_null($appsgroup_id_)) {
		echo "usersgroup_id_ $usersgroup_id_ appsgroup_id_ null<br>";
		$l_groups = $l->groups();
		$ug = new UsersGroup();
		$ug->fromDB($usersgroup_id_);
		foreach ($l_groups as $ag_id) {
			$r = array();
			$ag = new AppsGroup();
			$ag->fromDB($ag_id);
			if ( $ag->published && $ug->published) {
				$r['appsgroup'] = $ag;
				$r['usersgroup'] = $ug;
				$ret[] = $r;
			}
		}
	
	}else {
		echo "usersgroup_id_ $usersgroup_id_ appsgroup_id_ $appsgroup_id_<br>";
		if ($l->onDB()) {
			$ug = new UsersGroup();
			$ug->fromDB($usersgroup_id_);
			$ag = new AppsGroup();
			$ag->fromDB($appsgroup_id_);
			if ( $ag->published && $ug->published) {
				$r['appsgroup'] = $ag;
				$r['usersgroup'] = $ug;
				$ret[] = $r;
			}
		}
// 		else {
// 		}
	}
	
	return $ret;
	
}


function init_db($prefs_) {
	// prefs must be valid
	Logger::debug('admin','init_db');
	$mysql_conf = $prefs_->get('general', 'mysql');
	if (!is_array($mysql_conf)) {
		Logger::error('admin','init_db mysql conf not valid');
		return false;
	}
	$APPSGROUP_TABLE = $mysql_conf['prefix'].'gapplication';
	$LIAISON_APPS_GROUP_TABLE = $mysql_conf['prefix'].'apps_group_link';
	$USERSGROUP_TABLE = $mysql_conf['prefix'].'usergroup';
	$LIAISON_USERS_GROUP_TABLE = $mysql_conf['prefix'].'users_group_link';
	$USERSGROUP_APPLICATIONSGROUP_LIAISON_TABLE = $mysql_conf['prefix'].'ug_ag_link';
	$LIAISON_APPLICATION_SERVER_TABLE = $mysql_conf['prefix'].'application_server_link';
	$SOURCES_LIST_TABLE = $mysql_conf['prefix'].'sources_list';
	
	// we create the sql table
	$sql2 = MySQL::newInstance($mysql_conf['host'], $mysql_conf['user'], $mysql_conf['password'], $mysql_conf['database']);
	
	$ret = $sql2->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@2 int(8) NOT NULL,
		@3 varchar(100) NOT NULL,
		PRIMARY KEY  (@2,@3)
		)',$LIAISON_APPLICATION_SERVER_TABLE,'element','group');
	if ( $ret === false) {
		Logger::error('admin','init_db table '.$LIAISON_APPLICATION_SERVER_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('admin','init_db table '.$LIAISON_APPLICATION_SERVER_TABLE.' created');
	
	
	$ret = $sql2->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@2 int(8) NOT NULL,
		@3 int(8) NOT NULL,
		PRIMARY KEY  (@2,@3)
		)',$LIAISON_APPS_GROUP_TABLE,'element','group');
	if ( $ret === false) {
		Logger::error('admin','init_db table '.$LIAISON_APPS_GROUP_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('admin','init_db table '.$LIAISON_APPS_GROUP_TABLE.' created');
	
	$ret = $sql2->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@2 int(8) NOT NULL auto_increment,
		@3 varchar(150) NOT NULL,
		@4 varchar(150) NOT NULL,
		@5 tinyint(1) NOT NULL,
		PRIMARY KEY  (@2)
		)',$APPSGROUP_TABLE,'id','name','description','published');
	if ( $ret === false) {
		Logger::error('admin','init_db table '.$APPSGROUP_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('admin','init_db table '.$APPSGROUP_TABLE.' created');
	
	$ret = $sql2->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@4 int(8) NOT NULL auto_increment,
		@2 varchar(200) NOT NULL,
		@3 varchar(200) NOT NULL,
		PRIMARY KEY  (@4)
		)',$SOURCES_LIST_TABLE,'element','group','id');
	if ( $ret === false) {
		Logger::error('admin','init_db table '.$SOURCES_LIST_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('admin','init_db table '.$SOURCES_LIST_TABLE.' created');
	
	$ret = $sql2->DoQuery(
		'CREATE TABLE IF NOT EXISTS @1 (
		@2 varchar(50) NOT NULL,
		@3 int(8) NOT NULL,
		PRIMARY KEY  (`element`,`group`)
		)',$USERSGROUP_APPLICATIONSGROUP_LIAISON_TABLE,'element','group');
	if ( $ret === false) {
		Logger::error('admin','init_db table '.$USERSGROUP_APPLICATIONSGROUP_LIAISON_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('admin','init_db table '.$USERSGROUP_APPLICATIONSGROUP_LIAISON_TABLE.' created');

	$ret = $sql2->DoQuery(
	'CREATE TABLE IF NOT EXISTS `'.$USERSGROUP_TABLE.'` (
	@2 int(8) NOT NULL auto_increment,
	@3 varchar(150) NOT NULL,
	@4 varchar(150) NOT NULL,
	@5 tinyint(1) NOT NULL,
	PRIMARY KEY  (@2)
	)',$USERSGROUP_TABLE,'id','name','description','published');
	if ( $ret === false) {
		Logger::error('admin','init_db table '.$USERSGROUP_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('admin','init_db table '.$USERSGROUP_TABLE.' created');
	
	$ret = $sql2->DoQuery(
	'CREATE TABLE IF NOT EXISTS @1 (
	@2 varchar(50) NOT NULL,
	@3 int(8) NOT NULL,
	PRIMARY KEY  (`element`,`group`)
	)',$LIAISON_USERS_GROUP_TABLE,'element','group');
	if ( $ret === false) {
		Logger::error('admin','init_db table '.$LIAISON_USERS_GROUP_TABLE.' fail to created');
		return false;
	}
	else
		Logger::debug('admin','init_db table '.$LIAISON_USERS_GROUP_TABLE.' created');

	Logger::debug('admin','init_db all tables created');
	$modules_enable = $prefs_->get('general', 'module_enable');
	foreach ($modules_enable as $module_name) {
		$mod_name = 'admin_'.$module_name.'_'.$prefs_->get($module_name,'enable');
		$ret_eval = eval('return '.$mod_name.'::init($prefs_);');
		if ($ret_eval !== true) {
			Logger::error('admin','init_db init module \''.$mod_name.'\' failed');
			return false;
		}
	}
	Logger::debug('admin','init_db modules inited');
	
	//TODO : do the same for plugins
	
	return true;	
}