<?php
/**
 * Copyright (C) 2010 Ulteo SAS
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

require_once(dirname(__FILE__).'/includes/core.inc.php');

$client_headers = getallheaders();
foreach ($client_headers as $k => $v) {
	if (in_array($k, array('Forward-Cookie'))) {
		$ret = $k.': '.$v;
		preg_match('@Forward-Cookie: (.*)=(.*);@', $ret, $matches);
		if (count($matches) == 3) {
			$_SESSION['sessionmanager'] = array();
			$_SESSION['sessionmanager']['session_var'] = $matches[1];
			$_SESSION['sessionmanager']['session_id'] = $matches[2];
		}
	}
}

$_SESSION['xml'] = $_POST['xml'];

setcookie('ovd-client[sessionmanager_host]', $_POST['sessionmanager_host'], (time()+(60*60*24*7)));
setcookie('ovd-client[use_local_credentials]', 1, (time()+(60*60*24*7)));
setcookie('ovd-client[session_mode]', $_POST['mode'], (time()+(60*60*24*7)));
setcookie('ovd-client[session_language]', $_POST['language'], (time()+(60*60*24*7)));
setcookie('ovd-client[session_keymap]', $_POST['keymap'], (time()+(60*60*24*7)));
setcookie('ovd-client[debug]', $_POST['debug'], (time()+(60*60*24*7)));
