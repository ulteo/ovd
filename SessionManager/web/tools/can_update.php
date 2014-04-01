<?php
/**
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2014
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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');


if (! defined('STDIN')) {
	echo("This script cannot be used in CGI mode.\n");
	exit(1);
}

// Check if session manager is in maintenance
try {
	$prefs = new Preferences_admin();
}
catch (Exception $e) {
	echo("Internal error while loading settings, unable to continue.\n");
	exit(2);
}

if ($prefs->isValid() !== true) {
	// First install
	exit(0);
}

$system_in_maintenance = $prefs->get('general', 'system_in_maintenance');
if ($system_in_maintenance != '1') {
	echo("The Session Manager is in production mode. It's not possible to perform an upgrade until it's on maintenance mode.\n");
	exit(3);
}


// Check if there still have sessions
$nb_sessions = Abstract_Session::countByStatus();
if ($nb_sessions != 0) {
	echo("There are running sessions on the OVD farm. It's not possible to perform an upgrade until there are running sessions\n");
	exit(4);
}

exit(0);
