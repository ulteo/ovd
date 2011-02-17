<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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

if (in_admin() && ! isset($_SESSION['admin_login']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
	$_SESSION['redirect'] = base64_encode($_SERVER['REQUEST_URI']);

	if (basename(dirname($_SERVER['PHP_SELF'])) != 'admin')
		redirect('../login.php');
	else
		redirect('login.php');
}

$menu = array();

$menu['main'] = 
	array('id' => 'main',
		  'name' => _('Index'),
		  'page' => 'index.php',
		  'parent' => array(),
		  'always_display' => true);

$menu['servers'] = 
	array('id' => 'servers',
		  'name' => _('Servers'),
		  'page' => 'servers.php',
		  'parent' => array());

$menu['users'] =
	array('id' => 'users',
		  'name' => _('Users'),
		  'page' => 'users.php',
		  'parent' => array());

$menu['applications'] = 
	array('id' => 'applications',
		  'name' => _('Applications'),
		  'page' => 'applications.php',
		  'parent' => array());

$menu['configuration'] = 
	array('id' => 'configuration',
		  'name' => _('Configuration'),
		  'page' => 'configuration-sumup.php',
		  'parent' => array());

$menu['status'] = 
	array('id' => 'status',
		  'name' => _('Status'),
		  'page' => 'sessions.php',
		  'parent' => array());

if (isAuthorized('viewServers')) {
	$menu['servers_child'] =
		array('id' => 'servers_child',
			  'name' => _('Servers'),
			  'page' => 'servers.php',
			  'parent' => array('servers'));

	$menu['servers_unregistered'] = 
		array('id' => 'servers_unregistered',
			  'name' => _('Unregistered servers'),
			  'page' => 'servers.php?view=unregistered',
			  'parent' => array('servers'));

	$menu['tasks'] = 
		array('id' => 'tasks',
			  'name' => _('Tasks'),
			  'page' => 'tasks.php',
			  'parent' => array('servers'));
}

if (isAuthorized('viewSharedFolders')) {
	if (Preferences::moduleIsEnabled('SharedFolderDB')) {
		$menu['sharedfolders'] = 
			array('id' => 'sharedfolders',
				'name' => _('Shared folders'),
				'page' => 'sharedfolders.php',
				'parent' => array('servers'));
	}
}

if (isAuthorized('viewSharedFolders')) { // it should be viewProfile
	if (Preferences::moduleIsEnabled('ProfileDB')) {
		$menu['profile'] = 
			array('id' => 'profiles',
				'name' => _('Profiles'),
				'page' => 'profiles.php',
				'parent' => array('servers'));
	}
}

if (isAuthorized('viewUsers'))
	$menu['user_child'] = 
		array('id' => 'user_child',
			  'name' => _('Users'),
			  'page' => 'users.php',
			  'parent' => array('users'));

if (isAuthorized('viewUsersGroups'))
	$menu['users_groups'] = 
		array('id' => 'users_groups',
			  'name' => _('Users Groups'),
			  'page' => 'usersgroup.php',
			  'parent' => array('users'));

if (isAuthorized('viewApplications'))
	$menu['applications_child'] = 
		array('id' => 'applications_child',
			  'name' => _('Applications'),
			  'page' => 'applications.php',
			  'parent' => array('applications'));

if (isAuthorized('viewApplicationsGroups'))
	$menu['applications_groups'] = 
		array('id' => 'applications_groups',
			  'name' => _('Applications Groups'),
			  'page' => 'appsgroup.php',
			  'parent' => array('applications'));

if (isAuthorized('viewApplications')) {
	$menu['mime_types'] = 
		array('id' => 'mime_types',
			  'name' => _('Mime-Types'),
			  'page' => 'mimetypes.php',
			  'parent' => array('applications'));
	$menu['applications_static'] = 
		array('id' => 'applications_static',
			  'name' => _('Static applications'),
			  'page' => 'applications_static.php',
			  'parent' => array('applications'));
}

if (isAuthorized('viewPublications'))
	$menu['publications'] = 
		array('id' => 'publications',
			  'name' => _('Publications'),
			  'page' => 'publications.php',
			  'parent' => array('applications', 'users'));

if (isAuthorized('managePublications'))
	$menu['publications_wizard'] = 
		array('id' => 'publications_wizard',
			  'name' => _('Publication wizard'),
			  'page' => 'wizard.php',
			  'parent' => array('applications', 'users'));

if (isAuthorized('viewConfiguration')) {
	$menu['configuration_sumup'] = 
		array('id' => 'configuration_sumup',
			  'name' => _('Sum up'),
			  'page' => 'configuration-sumup.php',
			  'parent' => array('configuration'));

	$menu['configuration_db'] = 
		array('id' => 'configuration_db',
			  'name' => _('Database settings'),
			  'page' => 'configuration-partial.php?mode=sql',
			  'parent' => array('configuration'));

	$menu['configuration_general'] = 
		array('id' => 'configuration_general',
			  'name' => _('System settings'),
			  'page' => 'configuration-partial.php?mode=general',
			  'parent' => array('configuration'));

	$menu['configuration_server'] = 
		array('id' => 'configuration_server',
			  'name' => _('Server settings'),
			  'page' => 'configuration-partial.php?mode=slave_server_settings',
			  'parent' => array('configuration'));

	$menu['configuration_profiles'] = 
		array('id' => 'configuration_profiles',
			  'name' => _('Domain integration settings'),
			  'page' => 'configuration-profile.php',
			  'parent' => array('configuration'));
	
	$menu['configuration_auth'] = 
		array('id' => 'configuration_auth',
			  'name' => _('Authentication settings'),
			  'page' => 'configuration-partial.php?mode=auth',
			  'parent' => array('configuration'));

	$menu['configuration_sessions'] = 
		array('id' => 'configuration_sessions',
			  'name' => _('Session settings'),
			  'page' => 'configuration-partial.php?mode=session_settings',
			  'parent' => array('configuration'));

	$menu['configuration_events'] = 
		array('id' => 'configuration_events',
			  'name' => _('Events settings'),
			  'page' => 'configuration-partial.php?mode=events',
			  'parent' => array('configuration'));

	$menu['configuration_webui'] = 
		array('id' => 'configuration_webui',
			  'name' => _('Web interface settings'),
			  'page' => 'configuration-partial.php?mode=web_interface_settings',
			  'parent' => array('configuration'));
	
	$menu['configuration_changepassword'] = 
		array('id' => 'configuration_changepassword',
			  'name' => _('Change Administrator password'),
			  'page' => 'password.php?action=change',
			  'parent' => array('configuration'));
}

if (isAuthorized('viewStatus')) {
	$menu['sessions'] = 
		array('id' => 'sessions_child',
			  'name' => _('Sessions'),
			  'page' => 'sessions.php',
			  'parent' => array('status'));

	$menu['logs'] = 
		array('id' => 'logs',
			  'name' => _('Logs'),
			  'page' => 'logs.php',
			  'parent' => array('status'));

	$menu['reporting'] = 
		array('id' => 'reporting',
			  'name' => _('Reporting'),
			  'page' => 'reporting.php',
			  'parent' => array('status'));
}

if (isAuthorized('viewNews'))
	$menu['news'] =
		array('id' => 'news',
			  'name' => _('News'),
			  'page' => 'news.php',
			  'parent' => array('status'));

if (isAuthorized('viewSummary'))
	$menu['sumup'] = 
		array('id' => 'sumup',
			  'name' => _('Summary'),
			  'page' => 'summary.php',
			  'parent' => array('status'));

// extra modules
foreach (glob(dirname(dirname(__FILE__)).'/*/menu.inc.php') as $path) {
	$mod_name = basename(dirname($path));
	
	include_once($path);
	global $mod_menu;
	foreach($mod_menu as $item) {
		$item['mod'] = $mod_name;
		$item['mod_id'] = $item['id'];
		$item['id'] = $mod_name.'_'.$item['id'];
		$item['page'] = $mod_name.'/'.$item['page'];
		for ($i=0; $i<count($item['parent']); $i++) {
			if (str_startswith($item['parent'][$i], '@'))
				$item['parent'][$i] = substr($item['parent'][$i], 1);
			else
				$item['parent'][$i] = $mod_name.'_'.$item['parent'][$i];
		}
		
		$menu[$item['id']] = $item;
	}
}

if (defined('SESSIONMANAGER_ADMIN_DEBUG') && SESSIONMANAGER_ADMIN_DEBUG === true && ! isset($_SESSION['admin_ovd_user'])) {
	$menu['debug'] = 
		array('id' => 'debug',
			  'name' => _('Debug Tools'),
			  'page' => 'configuration.php',
			  'parent' => array());
	
	$menu['configuration_debug'] =
		array('id' => 'configuration_debug',
			  'name' => _('Configuration'),
			  'page' => 'configuration.php',
			  'parent' => array('debug'));
	
	$menu['checkup'] =
		array('id' => 'checkup',
			  'name' => _('Checkup'),
			  'page' => 'checkup.php',
			  'parent' => array('debug'));
}

$menu['logout'] =
	array('id' => 'logout',
		  'name' => _('Logout').(isset($_SESSION['admin_ovd_user'])?' ('.$_SESSION['admin_login'].')':''),
		  'page' => 'logout.php',
		  'parent' => array(),
		  'always_display' => true);


function page_header($params_=array()) {
  $title = 'Open Virtual Desktop - '._('Administration');

  if (isset($_SESSION['errormsg'])) {
    $errors = array_unique($_SESSION['errormsg']);
    unset($_SESSION['errormsg']);
  }
  else
    $errors = array();

  if (isset($_SESSION['infomsg'])) {
    $infos = array_unique($_SESSION['infomsg']);
    unset($_SESSION['infomsg']);
  }
  else
    $infos = array();

  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
  echo '<html xmlns="http://www.w3.org/1999/xhtml">';
  echo '<head>';
  echo '<title>'.$title.'</title>';

  //echo '<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />';
  echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';

  echo '<link rel="shortcut icon" type="image/png" href="'.ROOT_ADMIN_URL.'/media/image/favicon.ico" />';
  echo '<link rel="stylesheet" type="text/css" href="'.ROOT_ADMIN_URL.'/media/style/common.css" />';

  echo '<link rel="stylesheet" type="text/css" href="'.ROOT_ADMIN_URL.'/media/script/lib/nifty/niftyCorners.css" />';
  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" charset="utf-8">';
  echo '		NiftyLoad = function() {';
  echo '			Nifty("div.rounded");';
  echo '		}';
  echo '</script>';

  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/lib/prototype/prototype.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/lib/scriptaculous/slider.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/common-regular.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/sortable.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/common.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/ajax/configuration.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/ajax/usergroup.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/media/script/ajax/add_del_rows.js" charset="utf-8"></script>';

  if (is_array($params_) && isset($params_['js_files']) && is_array($params_['js_files']))
    foreach ($params_['js_files'] as $js_file)
      echo '<script type="text/javascript" src="'.ROOT_ADMIN_URL.'/'.$js_file.'" charset="utf-8"></script>';

  echo '</head>';
  echo '<body>';
  echo '<div id="infoBulle" style="position: absolute; border: 1px solid black; background: #fec; padding: 5px; display: none; margin-right: 11px; max-width: 600px; z-index: 5000;"></div>';
  echo '<div id="mainWrap">';
  echo '<div id="headerWrap">';

  echo '<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  echo '<td style="min-width: 10%; text-align: left;" class="menu">';
  page_menu();
  echo '</td>';

  //echo '<td style="text-align: center; width: 100%;" class="title centered">';
  //echo '<h1 class="centered">'.$title.'</h1>';
  //echo '</td>';

  echo '<td style="width: 100%; text-align: right; padding-right: 10px; border-bottom: 1px solid #ccc;" class="logo">';
  echo '<a href="index.php"><img src="'.ROOT_ADMIN_URL.'/media/image/header.png" alt="'.$title.'" title="'.$title.'" /></a>';
  echo '</td>';

  echo '</tr>';
  echo '</table>';

  echo '</div>';

  echo '<div class="spacer"></div>';

  echo '<div id="pageWrap">';
  echo '<br />';
  echo '<div class="spacer"></div>';

  echo '<div id="adminContent">';

  if (count($errors) > 0) {
    echo '<div id="adminError">';
    echo '<span class="msg_error">';
    if (count($errors) > 1) {
      echo '<ul>';
      foreach ($errors as $error_msg)
	echo '<li>'.$error_msg.'</li>';
      echo '</ul>';
    } else
      echo $errors[0];
    echo '</span>';
    echo '</div>';
  }

  if (count($infos) > 0) {
    echo '<div id="adminInfo">';
    echo '<span class="msg_ok">';
    if (count($infos) > 1) {
      echo '<ul>';
      foreach ($infos as $info_msg)
	echo '<li>'.$info_msg.'</li>';
      echo '</ul>';
    } else
      echo $infos[0];
    echo '</span>';
    echo '</div>';
  }

  page_sub_menu();
}

function page_footer() {
  echo '</td>';
  echo '</tr>';
  echo '</table>';

  echo '</div>';

  echo '<div class="spacer"></div>';
  echo '<br />';
  echo '</div>';

  echo '<div class="spacer"></div>';

  echo '<div id="footerWrap">'._('powered by');
  echo ' <a href="http://www.ulteo.com"><img src="'.ROOT_ADMIN_URL.'/media/image/ulteo.png" width="22" height="22" alt="Ulteo" title="Ulteo" /> Ulteo</a> OVD v'.OVD_SM_VERSION.'&nbsp;&nbsp;&nbsp;';
  echo '</div>';
  echo '</div>';
  echo '</body>';
  echo '</html>';
}

function get_current_page() {
	$buf = strpos($_SERVER['REQUEST_URI'], ROOT_ADMIN_URL);
	if ($buf === FALSE)
		return '';
	
	return substr($_SERVER['REQUEST_URI'], $buf + strlen(ROOT_ADMIN_URL) + 1);
}

function get_menu_entry() {
	global $menu;
	$menu2 = $menu; // bug in php 5.1.6 (redhat 5.2)

	$page = get_current_page();
	
	$buffer_id = Null;
	$buffer_len = 0;
	foreach($menu2 as $id => $entrie) {
		if (count($entrie['parent']) == 0)
			continue;

		if (! str_startswith($page, $entrie['page']))
			continue;

		if ( strlen($entrie['page']) > $buffer_len) {
			$buffer_id = $id;
			$buffer_len = strlen($entrie['page']);
		}
	}

	if ($buffer_id == Null)
		$buffer_id = 'main';
	
	return $buffer_id;
}

function get_target($id_) {
	global $menu;
	$menu2 = $menu; // bug in php 5.1.6 (redhat 5.2)
	
	foreach($menu2 as $id => $entrie) {
		if (! in_array($id_, $entrie['parent']))
			continue;

		return $entrie['page'];
	}

	return $menu2[$id_]['page'];
}

function get_nb_child($id_) {
	global $menu;
	$menu2 = $menu; // bug in php 5.1.6 (redhat 5.2)
	$nb = 0;

	foreach($menu2 as $id => $entrie) {
		if (in_array($id_, $entrie['parent']))
			$nb++;
	}

	return $nb;
}


function page_menu(){
	global $menu;
	$menu2 = $menu; // bug in php 5.1.6 (redhat 5.2)

	$position = get_menu_entry();
	$parent = $menu2[$position]['parent'];
	if ($parent == Null)
		$parent = $position;
	elseif (is_array($parent))
		$parent = $parent[0];

	echo '<table border="0" cellspacing="0" cellpadding="10">';
	echo '<tr>';

	$first = true;
	foreach($menu2 as $id => $entrie) {
		if (count($entrie['parent'])>0)
			continue;
		
		if (! isset($entrie['always_display']) && get_nb_child($id) == 0)
			continue;
		
		echo '<td style="min-width: 60px; height: 81px; text-align: center; vertical-align: middle;';
		if ($id == $parent) {
			if ($first !== true)
				echo ' border-left: 1px solid  #ccc;';
			echo ' background: #eee; border-right: 1px solid #ccc; border-bottom: 0px;';
		} else
			echo ' border-bottom: 1px solid #ccc;';
		
		if (isset($entrie['mod']))
			$img = ''.$entrie['mod'].'/media/image/menu_'.$entrie['mod_id'].'.png';
		else
			$img = 'media/image/menu/'.$id.'.png';
		
		echo '" class="menu"><a href="'.ROOT_ADMIN_URL.'/'.get_target($id).'"><img src="'.ROOT_ADMIN_URL.'/'.$img.'" width="32" height="32" alt="'.$entrie['name'].'" title="'.$entrie['name'].'" /><br />';
		echo '<span class="menulink';
		if ($id == $parent)
			echo '_active';
		
		echo '">'.$entrie['name'].'</span></a></td>'."\n";

		$first = false;
	}
	echo '</tr>';
	echo '</table>';
}

function page_sub_menu() {
	echo '<table style="width: 98.9%; margin-left: 10px; margin-right: 10px;" border="0" cellspacing="0" cellpadding="0">';
	echo '<tr>';

	global $menu;
	$menu2 = $menu; // bug in php 5.1.6 (redhat 5.2)

	$position = get_menu_entry();
	$parent = $menu2[$position]['parent'];
	if (is_array($parent)) {
		if (count($parent) > 0)
			$parent = $parent[0];
		else
			$parent = NULL;
	}

	if (! is_null($parent)) {
		echo '<td style="width: 150px; text-align: center; vertical-align: top; background: url(\''.ROOT_ADMIN_URL.'/media/image/submenu_bg.png\') repeat-y right;">';

		foreach($menu2 as $id => $entrie) {
			if (is_array($entrie['parent'])) {
				if (! in_array($parent, $entrie['parent']))
					continue;
			} else {
				if ($parent != $entrie['parent'])
					continue;
			}

			if ($id == $position)
				echo '<div class="container" style="background: #fff; border-top: 1px solid #ccc; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;">';
			else
				echo '<div class="container">';

			echo '<a href="'.ROOT_ADMIN_URL.'/'.$entrie['page'].'">'.$entrie['name'].'</a>';
			echo '</div>';
		}

		echo '</td>';
	}

	echo '<td style="text-align: left; vertical-align: top; background: #fff; border-top: 1px solid  #ccc; border-right: 1px solid  #ccc; border-bottom: 1px solid  #ccc;';
	if (is_null($parent))
		echo ' border-left: 1px solid #ccc;';
	echo '">';
	echo '<div class="container">';
}
