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

if (isset($_POST["action"]) && $_POST["action"] == "add"){
	if (isset($_POST["user"]) && isset($_POST["group"])) {
		$l = new UsersGroupLiaison($_POST["user"],$_POST["group"]);
		if ($l->onDB()){
			// nothing to do
			echo 'error liaison already in db<br>';
		}
		else {
			// add to do
			if ($l->insertDB())
				echo 'OK';
			else
				echo 'liaison add insert fail';
		}
		
	}
}

if (isset($_POST["action"]) && $_POST["action"] == "del"){
	if ( isset($_POST["user"]) && isset($_POST["group"])) {
		$l = new UsersGroupLiaison($_POST["user"],$_POST["group"]);
		if ($l->onDB()){
			// del to do
			if ($l->removeDB())
				echo 'OK';
			else
				echo 'liaison del fail';
		}
		else {
			// nothing to do
			echo '(ERR#020) liaison not in DB';
			
		}
		
	}
}

if (isset($_POST["action"]) && $_POST["action"] == "mod"){
	if ( isset($_POST["user"]) && isset($_POST["group_old"]) && isset($_POST["group_new"])) {
		$l = new UsersGroupLiaison($_POST["user"],$_POST["group_old"]);
		if ($l->onDB()){
			$l->updateGroupDB($_POST["group_new"]);
		}
		else {
			echo '(ERR#021) liaison not in data base<br>';
		}
	}
}