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

if (isset($_POST['action']) && ($_POST['action'] == "add" )){
	if ( isset($_POST['name']) && isset($_POST['description']) && isset($_POST['type']) && isset($_POST['executable_path']) ){
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$mods_enable = $prefs->get('general','module_enable');
		if (!in_array('ApplicationDB',$mods_enable)){
			die_error('Module ApplicationDB must be enabled',__FILE__,__LINE__);
		}
		$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
		$applicationDB = new $mod_app_name();

		$a = new Application(NULL,$_POST['name'], $_POST['description'], $_POST['type'], $_POST['executable_path'], $_POST['package'], $_POST['icon_path'],$_POST['published']);
		$res = $applicationDB->add($a);
		if ($res)
			echo 'OK';
		else
			echo '(ERR#027) problem with insertion';
	}
}

if (isset($_POST['action']) && ($_POST['action'] == "del" )){
	if (isset($_POST["id"])){
		if (is_numeric($_POST["id"])){
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed',__FILE__,__LINE__);
			$mods_enable = $prefs->get('general','module_enable');
			if (!in_array('ApplicationDB',$mods_enable)){
				die_error('Module ApplicationDB must be enabled',__FILE__,__LINE__);
			}
			$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
			$applicationDB = new $mod_app_name();

			$a = $applicationDB->import($_POST["id"]);
			$res = $applicationDB->remove($a);
			if ($res)
				echo 'OK';
			else
				echo "(ERR#029) remove ".$res."<br>";
		}
		else{
			echo "id of application is not an int<br>";
			var_dump($_POST);echo "<br>";
			// problem
		}
	}
	else{
		echo "(ERR#030) params not ok<br>";
		var_dump($_POST);echo "<br>";
	}
}

if (isset($_POST['action']) && ($_POST['action'] == "mod" )){
	if ( isset($_POST['id']) && isset($_POST['name']) && isset($_POST['description']) && isset($_POST['type']) && isset($_POST['executable_path']) ){
		$prefs = Preferences::getInstance();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);
		$mods_enable = $prefs->get('general','module_enable');
		if (!in_array('ApplicationDB',$mods_enable)){
			die_error('Module ApplicationDB must be enabled',__FILE__,__LINE__);
		}
		$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
		$applicationDB = new $mod_app_name();

		$a = new Application($_POST['id'],$_POST['name'], $_POST['description'], $_POST['type'], $_POST['executable_path'], $_POST['package'], $_POST['icon_path'],$_POST['published']);

		$res = $applicationDB->update($a);
		if ($res){
			echo 'OK';
		}
		else {
			echo '(ERR#031) update fails<br>';
		}
	}
}
