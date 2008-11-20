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
		$g = new AppsGroup(NULL,$_POST['name'], $_POST['description'], $_POST['published']);
		$res = $g->insertDB();
		if (!$res){
			echo '(ERR#022) Add fail';
			// problem with insertion
		}
		else {
			echo 'OK';
		}
		
	}
}

if (isset($_POST['action']) && ($_POST['action'] == 'del')){
	if ((isset($_POST["id"])&& is_numeric($_POST["id"]))) {
		$g = new AppsGroup();
		$g->fromDB($_POST["id"]);
		if ($g->isOK()){
			$res = $g->removeDB();
			if ($res)
				echo 'OK';
			else
				echo "(ERR#023) remove ".$res;
		}
		else{
			var_dump($a);echo "<br />";
			echo "AppsGroup is not ok<br>";
		}
	}
	else{
		var_dump($_POST);echo "<br />";
		echo "(ERR#024) id of AppsGroup is not an int<br />";
	}
}

if (isset($_POST['action']) && ($_POST['action'] == 'mod')){
	if ((isset($_POST['name'])) && (isset($_POST['id']))) {
		$g = new AppsGroup($_POST["id"],$_POST['name'],$_POST['description'],$_POST['published']);
		if ($g->updateDB($_POST["id"])){
			echo 'OK';
		}
		else{
			echo '(ERR#026) update fail<br />';
		}
	}
	else{
		echo "(ERR#025) param problem<br />";
	}
}
