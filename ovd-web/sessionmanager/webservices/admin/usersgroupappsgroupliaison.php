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
	if (isset($_POST["usersgroup"]) && isset($_POST["appsgroup"])) {
		$l = new UsersGroupApplicationsGroupLiaison($_POST["usersgroup"],$_POST["appsgroup"]);
		if ($l->onDB()){
			echo 'add: error liaison already in DB';
		}
		else {
			if ($l->insertDB())
				echo 'OK';
			else
				echo '(ERR#012) add: insertDB fail';
		}
		
	}
}

if (isset($_POST["action"]) && $_POST["action"] == "del"){
	if ( isset($_POST["usersgroup"]) && isset($_POST["appsgroup"])) {
		$l = new UsersGroupApplicationsGroupLiaison($_POST["usersgroup"],$_POST["appsgroup"]);
		if ($l->onDB()){
			if ($l->removeDB())
				echo 'OK';
			else
				echo '(ERR#014) removeDB false';
		}
		else {
			echo '(ERR#013) liaison not in data base';
		}
	}
}

if (isset($_POST["action"]) && $_POST["action"] == "mod"){
	if ( isset($_POST["usersgroup"]) && isset($_POST["appsgroup_old"]) && isset($_POST["appsgroup_new"])) {
		$l = new UsersGroupApplicationsGroupLiaison($_POST["usersgroup"],$_POST["appsgroup_old"]);
		if ($l->onDB()){
			$l->updateGroupDB($_POST["appsgroup_new"]);
		}
		else {
			echo '(ERR#014) liaison not in data base';
		}
	}
}
