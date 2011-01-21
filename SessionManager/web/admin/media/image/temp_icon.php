<?php
/**
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

if (! isset($_REQUEST['tempnam'])) {
	header('HTTP/1.1 400 Bad Request');
	die();
}

$prefs = new Preferences_admin();
if (! $prefs) {
	header('HTTP/1.1 500 Internal Error');
	die();
}

$mods_enable = $prefs->get('general','module_enable');
if (!in_array('ApplicationDB',$mods_enable)){
	header('HTTP/1.1 500 Internal Error');
	die();
}

if (! file_exists('/tmp/ico'.substr($_REQUEST['tempnam'], 3))) {
	header('HTTP/1.1 404 Not Found');
	die();
}

header('Content-Type: image/png');
echo @file_get_contents('/tmp/ico'.substr($_REQUEST['tempnam'], 3));
@unlink('/tmp/ico'.substr($_REQUEST['tempnam'], 3));
