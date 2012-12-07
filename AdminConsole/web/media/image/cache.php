<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008
 * Author Julien LANGLOIS <julien@ulteo.com> 2008, 2012
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008
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

if (!isset($_REQUEST['id'])) {
	header('HTTP/1.1 400 Bad Request');
	die();
}

$r = $_SESSION['service']->application_icon_get($_REQUEST['id']);
if ($r === null) {
	header('HTTP/1.1 500 Internal Error');
	die();
}

$buf = base64_decode($r);
header('Content-Type: image/png');
die($buf);
