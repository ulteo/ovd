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

if (isset($_POST['action']) && ($_POST['action'] == 'add')) {
	if ((isset($_POST['name'])) && (isset($_POST['description']))) {
		$g = new UsersGroup(NULL,$_POST['name'], $_POST['description'], $_POST['published']);
		$res = $g->insertDB();
		if (!$res){
			// problem with insertion
			echo '(ERR#006) add fail <br />';
		}
		else {
			echo 'OK';
		}
	}
}

if (isset($_POST['action']) && ($_POST['action'] == 'del')){
	if ((isset($_POST["id"])&& is_numeric($_POST["id"]))) {
		$g = new UsersGroup();
		$g->fromDB($_POST["id"]);
		if ($g->isOK()){
			if ($g->removeDB())
				echo 'OK';
			else
				echo '(ERR#007) remove error';
		}
		else{
			var_dump($a);echo "<br>";
			echo "(ERR#008) UsersGroup is not ok<br>";
		}
	}
	else{
		echo "(ERR#009) id of UsersGroup is not an int<br>";
		var_dump($_POST);echo "<br>";
		// problem
	}
}

if (isset($_POST['action']) && ($_POST['action'] == 'mod')){
	if ((isset($_POST['name'])) && (isset($_POST['id'])) ) {
		$g = new UsersGroup($_POST["id"],$_POST['name'],$_POST['description'],$_POST['published']);
		if ($g->updateDB()){
			echo 'OK';
		}
		else{
			echo '(ERR#010) update fail<br>';
		}
	}
	else{
		echo "(ERR#011) param problem<br>";
	}
}
