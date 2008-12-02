<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
require_once(dirname(__FILE__).'/../../admin/includes/core.inc.php');

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$mods_enable = $prefs->get('general','module_enable');
if (!in_array('UserDB',$mods_enable)){
	die_error('Module UserDB must be enabled',__FILE__,__LINE__);
}
$mod_user_name = 'admin_UserDB_'.$prefs->get('UserDB','enable');
$userDB = new $mod_user_name();

if (isset($_POST['action']) && $_POST['action'] == 'add'){
	if (isset($_POST['uid']) && isset($_POST['login']) && isset($_POST['displayName']) && isset($_POST['gid']) && isset($_POST['homeDir'])  && isset($_POST['fileserver_uid']) && isset($_POST['password']) ){
		$u = new User();
		$u->setAttribute('uid',$_POST['uid']);
		$u->setAttribute('login',$_POST['login']);
		$u->setAttribute('displayname',$_POST['displayName']);
		$u->setAttribute('gid',$_POST['gid']);
		$u->setAttribute('homeDir',$_POST['homeDir']);
		$u->setAttribute('fileserver',$_POST['fileserver']);
		$u->setAttribute('fileserver_uid',$_POST['fileserver_uid']);
		$u->setAttribute('password',$_POST['password']);
		$res = $userDB->add($u);
		if ($res) {
			echo 'OK';
		}
		else {
			var_dump($res);echo '<br>';
			echo '(ERR#015) problem with user creation<br>';
		}
	}
}
else if (isset($_POST['action']) && ($_POST['action'] == "del")) {
	if (isset($_POST["login"])){
		$u = $userDB->import($_POST["login"]);
		$res = $userDB->remove($u);
		if ($res){
			echo 'OK';
		}
		else{
			echo "(ERR#016) User remove failed<br>";
		}
	}
	else{
		echo "params not ok<br>";
		var_dump($_POST);echo "<br>";
		echo '(ERR#017)';
	}
}
else if (isset($_POST['action']) && ($_POST['action'] == "mod" )){
	if (isset($_POST['uid']) && isset($_POST['login']) && isset($_POST['displayName']) && isset($_POST['gid']) && isset($_POST['homeDir']) && isset($_POST['fileserver_uid']) && isset($_POST['fileserver'])  && isset($_POST['password'])) {
		$u = new User();
		$u->setAttribute('uid',$_POST['uid']);
		$u->setAttribute('login',$_POST['login']);
		$u->setAttribute('displayname',$_POST['displayName']);
		$u->setAttribute('gid',$_POST['gid']);
		$u->setAttribute('homeDir',$_POST['homeDir']);
		$u->setAttribute('fileserver',$_POST['fileserver']);
		$u->setAttribute('fileserver_uid',$_POST['fileserver_uid']);
		$u->setAttribute('password',$_POST['password']);

		$res = $userDB->update($u);
		if ($res){
			echo 'OK';
		}
		else {
			echo '(ERR#018) update fails<br>';
		}
	}
}