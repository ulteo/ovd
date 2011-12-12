<?php
/**
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2009, 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

require_once(dirname(__FILE__).'/includes/core.inc.php');

if (! array_key_exists('ovd-client', $_SESSION) || ! array_key_exists('sessionmanager', $_SESSION['ovd-client'])) {
	if (is_null($sessionmanager_url)) {
		header('Content-Type: text/xml');
		echo return_error(0, 'System not yet initialized');
		die();
	}
	
	$sm = new SessionManager($sessionmanager_url);
}
else
	$sm = $_SESSION['ovd-client']['sessionmanager'];

header('Content-Type: image/png');
die($sm->query('icon.php?id='.$_GET['id']));
