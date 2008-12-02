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
	if (isset($_POST["application"]) && isset($_POST["group"])) {
		$l = new AppsGroupLiaison($_POST["application"],$_POST["group"]);
		if ($l->onDB()){
			// nothing to do
			echo '(ERR#004)error liaison already on DB';
		}
		else {
			// add to do
			if ($l->insertDB())
				echo 'OK';
			else
				echo '(ERR#003)problem insert';
		}
		
	}
}
if (isset($_POST["action"]) && $_POST["action"] == "del"){
	if ( isset($_POST["application"]) && isset($_POST["group"])) {
		$l = new AppsGroupLiaison($_POST["application"],$_POST["group"]);
		if ($l->onDB()){
			// del to do
			
			if ($l->removeDB())
				echo 'OK';
			else
				echo '(ERR#002)problem remove';
		}
		else {
			// nothing to do
			echo '(ERR#001)error liason not in DB';
			
		}
		
	}
}

