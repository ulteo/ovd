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

// if ($_SESSION['ajax']==true) {
// 	require_once('ajax/index.php');
// 	die();
// }

$server = $_SERVER['SERVER_NAME'];
$session = $_SESSION['session'];

if (!isset($session) || $session == '')
	die('CRITICAL ERROR'); // That's odd !

$_SESSION['width'] = @$_REQUEST['width'];
$_SESSION['height'] = @$_REQUEST['height'];

if (isset($_SESSION['mode']) && $_SESSION['mode'] == 'start' && get_from_file(SESSION_PATH.'/'.$session.'/runasap') == 0) {
	put_to_file(SESSION_PATH.'/'.$session.'/geometry', $_SESSION['width'].'x'.$_SESSION['height']);

	put_to_file(SESSION_PATH.'/'.$session.'/u_uid', $_SESSION['user_id']);
	//strange?
	put_to_file(SESSION_PATH.'/'.$session.'/uu', $_SESSION['user_id']+70000);
	put_to_file(SESSION_PATH.'/'.$session.'/user_login', $_SESSION['user_login']);
	put_to_file(SESSION_PATH.'/'.$session.'/nick', $_SESSION['user_displayname']);
	put_to_file(SESSION_PATH.'/'.$session.'/locale', $_SESSION['locale']);
	put_to_file(SESSION_PATH.'/'.$session.'/ex', time()+$_SESSION['timeout']);
	put_to_file(SESSION_PATH.'/'.$session.'/app', $_SESSION['start_app']);
	put_to_file(SESSION_PATH.'/'.$session.'/module_fs', $_SESSION['module_fs']['type']);

	$buf = '';
	foreach ($_SESSION['desktopfile'] as $desktopfile)
		$buf .= $desktopfile."\n";
	put_to_file(SESSION_PATH.'/'.$session.'/menu', $buf);

	if ($_SESSION['module_fs']['type'] == 'cifs' || $_SESSION['module_fs']['type'] == 'cifs_no_sfu') {
 		put_to_file(SESSION_PATH.'/'.$session.'/fileserver', $_SESSION['module_fs']['user_fileserver']);
 		put_to_file(SESSION_PATH.'/'.$session.'/uu', $_SESSION['module_fs']['fileserver_uid']);
		put_to_file(SESSION_PATH.'/'.$session.'/cifs_homebase', dirname($_SESSION['module_fs']['user_homedir']));
		put_to_file(SESSION_PATH.'/'.$session.'/cifs_homedir', basename($_SESSION['module_fs']['user_homedir']));
		put_to_file(SESSION_PATH.'/'.$session.'/cifs_login', $_SESSION['module_fs']['cifs_login']);
		put_to_file(SESSION_PATH.'/'.$session.'/cifs_password', $_SESSION['module_fs']['cifs_password']);
	}

	if ($_SESSION['module_fs']['type'] == 'httpsave') {
		put_to_file(SESSION_PATH.'/'.$session.'/doc', $_SESSION['module_fs']['document_name']);
		put_to_file(SESSION_PATH.'/'.$session.'/doc_from', $_SESSION['module_fs']['document_from']);
		put_to_file(SESSION_PATH.'/'.$session.'/doc_to', $_SESSION['module_fs']['document_to']);
	}

	if ($_SESSION['module_fs']['type'] == 'nfs' || $_SESSION['module_fs']['type'] == 'ulteo_nfs') {
		put_to_file(SESSION_PATH.'/'.$session.'/fileserver', $_SESSION['module_fs']['user_fileserver']);
		put_to_file(SESSION_PATH.'/'.$session.'/uu', $_SESSION['module_fs']['fileserver_uid']);
		put_to_file(SESSION_PATH.'/'.$session.'/remote_home', $_SESSION['module_fs']['user_homedir']);
		put_to_file(SESSION_PATH.'/'.$session.'/homebase', $_SESSION['module_fs']['homebase']);
	}

	@touch(SESSION_PATH.'/'.$session.'/keepmealive');

	if ($_SESSION['persistent'] == 1)
		@touch(SESSION_PATH.'/'.$session.'/persistent');

	put_to_file(SESSION_PATH.'/'.$session.'/runasap', 1);
} elseif (isset($_SESSION['mode']) && $_SESSION['mode'] == 'resume' && get_from_file(SESSION_PATH.'/'.$session.'/runasap') == 10) {
	@touch(SESSION_PATH.'/'.$session.'/keepmealive');

	put_to_file(SESSION_PATH.'/'.$session.'/runasap', 11);
}

Logger::info('main', 'Session starting');
