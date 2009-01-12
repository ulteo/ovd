<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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

$server = $_SERVER['SERVER_NAME'];
$session = $_SESSION['session'];

if (!isset($session) || $session == '')
	die('CRITICAL ERROR'); // That's odd !

$_SESSION['width'] = @$_REQUEST['width'];
$_SESSION['height'] = @$_REQUEST['height'];

if (isset($_SESSION['mode']) && $_SESSION['mode'] == 'start' && get_from_file(SESSION_PATH.'/'.$session.'/infos/status') == 0) {
	put_to_file(SESSION_PATH.'/'.$session.'/parameters/geometry', $_SESSION['width'].'x'.$_SESSION['height']);

	foreach ($_SESSION['parameters'] as $k => $v)
		put_to_file(SESSION_PATH.'/'.$session.'/parameters/'.$k, $v);

	@unlink(SESSION_PATH.'/'.$session.'/parameters/module_fs');
	@mkdir(SESSION_PATH.'/'.$session.'/parameters/module_fs', 0750);
	foreach ($_SESSION['parameters']['module_fs'] as $k => $v)
		put_to_file(SESSION_PATH.'/'.$session.'/parameters/module_fs/'.$k, $v);

	$buf = '';
	foreach ($_SESSION['parameters']['desktopfiles'] as $desktopfile)
		$buf .= $desktopfile."\n";
	put_to_file(SESSION_PATH.'/'.$session.'/parameters/menu', $buf);

	@touch(SESSION_PATH.'/'.$session.'/infos/keepmealive');

	put_to_file(SESSION_PATH.'/'.$session.'/infos/status', 1);
} elseif (isset($_SESSION['mode']) && $_SESSION['mode'] == 'resume' && get_from_file(SESSION_PATH.'/'.$session.'/infos/status') == 10) {
	@touch(SESSION_PATH.'/'.$session.'/infos/keepmealive');

	put_to_file(SESSION_PATH.'/'.$session.'/infos/status', 11);
}

Logger::info('main', 'Session starting');
