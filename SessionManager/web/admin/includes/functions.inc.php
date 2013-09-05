<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2013
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
 * Author David LECHEVALIER <david@ulteo.com> 2012
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

function init_db($prefs_) {
	// prefs must be valid
	Logger::debug('main', 'init_db');

	$modules_enable = $prefs_->get('general', 'module_enable');
	foreach ($modules_enable as $module_name) {
		if (! is_null($prefs_->get($module_name,'enable'))) {
			$enable = $prefs_->get($module_name,'enable');
			if (is_string($enable)) {
				$mod_name = $module_name.'_'.$enable;
				$ret_eval = call_user_func(array($mod_name, 'init'), $prefs_);
				if ($ret_eval !== true) {
					Logger::error('main', 'init_db init module \''.$mod_name.'\' failed');
					return false;
				}
			}
			elseif (is_array($enable)) {
				foreach ($enable as $sub_module) {
					$mod_name = $module_name.'_'.$sub_module;
					$ret_eval = call_user_func(array($mod_name, 'init'), $prefs_);
					if ($ret_eval !== true) {
						Logger::error('main', 'init_db init module \''.$mod_name.'\' failed');
						return false;
					}
				}
			}
		}
	}
	Logger::debug('main', 'init_db modules inited');

	// Init of Abstract
	Abstract_Server::init($prefs_);
	Abstract_Session::init($prefs_);
	Abstract_Token::init($prefs_);
	Abstract_News::init($prefs_);
	Abstract_Liaison::init($prefs_);
	Abstract_Task::init($prefs_);
	Abstract_ReportServer::init($prefs_);
	Abstract_ReportSession::init($prefs_);
	Abstract_User_Preferences::init($prefs_);
	Abstract_UserGroup_Preferences::init($prefs_);
	Abstract_UserGroup_Rule::init($prefs_);
	Abstract_VDI::init($prefs_);
	Abstract_Network_Folder::init($prefs_);
	Abstract_AdminAction::init($prefs_);
	
	return true;
}

function init($host_, $database_, $prefix_, $user_, $password_) {
	$p = new Preferences_admin();
	$sql_conf = array();
	$sql_conf['host'] = $host_;
	$sql_conf['database'] = $database_;
	$sql_conf['user'] = $user_;
	$sql_conf['password'] = $password_;
	$sql_conf['prefix'] = $prefix_;
	$p->set('general','sql', $sql_conf);
	$ret = $p->isValid();
	if ($ret !== true) {
		echo 'error isValid : '.$ret."\n";
		return false;
	}
	$p->backup();
	return true;
}

function get_classes_startwith_admin($start_name) {
	$files = glob(ADMIN_CLASSES_DIR.'/'.$start_name.'*.class.php');

	$ret = array();
	foreach ($files as $file) {
	  $classname = basename($file);
	  $classname = substr($classname, 0, strlen($classname) - strlen('.class.php'));

	  $ret[] = $classname;
	}
	return $ret;
}

function validate_ip($ip_) {
	return preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $ip_);
}

function validate_fqdn($fqdn_) {
	return preg_match("/(?=^.{1,254}$)(^(?:(?!\d+\.|-)[a-zA-Z0-9_\-]{1,63}(?<!-)\.?)+(?:[a-zA-Z]{2,})$)/", $fqdn_);
}
