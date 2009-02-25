<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julient@ulteo.com>
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


// Menu Items
$items = array('index.php'		=>	_('Index'),
	       'servers.php'		=>	_('Servers'),
	       // 	'sessions.php'		=>	_('Sessions'),
	       'users.php'		=>	_('Users'),
	       // 	'usersgroup.php'	=>	_('Users groups'),
	       'applications.php'	=>	_('Applications'),
	       // 	'appsgroup.php'	=>	_('Appgroups'),
	       // 	'publications.php'	=>	_('Publications'),
	       'configuration-sumup.php'	=>	_('Configuration'),
	       // 	'logs.php'		=>	_('Logs'),
	       'sessions.php'		=>	_('Status'),
		 'report.php'		=>	_('Reports'),
		 'logout.php'		=>	_('Logout')
	       );

$sub_items =
  array('servers.php'		=>
	array('servers.php' => _('Servers'),
	      'servers.php?view=unregistered' => _('Unregistered servers')),

	'users.php'		=>
	array('users.php' => _('Users'),
	      'usersgroup.php' => _('Users Groups'),
	      'publications.php' => _('Publications'),
	      'wizard.php' => _('Publication wizard')),

	'applications.php'	=>
	array('applications.php' => _('Applications'),
	      'appsgroup.php' => _('Applications Groups'),
	      'publications.php' => _('Publications'),
	      'wizard.php' => _('Publication wizard')),


	'configuration-sumup.php'	=>
	array('configuration-partial.php?mode=mysql'		=> _('Database settings'),
	      'configuration-partial.php?mode=general'		=> _('System settings'),
	      'configuration-partial.php?mode=application_server_settings' => _('Server settings'),
	      'configuration-profile.php' => _('Profile settings'),
	      'configuration-partial.php?mode=session_settings_defaults' => _('Session settings'),
	      'configuration-partial.php?mode=events' => _('Events settings'),
	      'configuration-partial.php?mode=web_interface_settings' => _('Web interface settings'),
	      'configuration-sumup.php' => _('Sum up')),

	'sessions.php'		=>
	array('sessions.php' => _('Sessions'),
	      'logs.php' => _('Logs'),
	      'sumup.php' => _('Summary')),
	);

function page_header() {
  $base_url = str_replace('/admin', '', dirname($_SERVER['PHP_SELF'])).'/';
  $title = 'Open Virtual Desktop - '._('Administration');

  if (isset($_SESSION['errormsg'])) {
    $errors = $_SESSION['errormsg'];
    unset($_SESSION['errormsg']);
  }
  else
    $errors = array();

  echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
  echo '<html xmlns="http://www.w3.org/1999/xhtml">';
  echo '<head>';
  echo '<title>'.$title.'</title>';

  //echo '<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />';
  echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';

  echo '<link rel="shortcut icon" type="image/png" href="'.$base_url.'media/image/favicon.ico" />';
  echo '<link rel="stylesheet" type="text/css" href="'.$base_url.'media/style/common.css" />';
  echo '<link rel="stylesheet" type="text/css" href="'.$base_url.'admin/media/style/common.css" />';

  echo '<link rel="stylesheet" type="text/css" href="'.$base_url.'media/script/lib/nifty/niftyCorners.css" />';
  echo '<script type="text/javascript" src="'.$base_url.'media/script/lib/nifty/niftyCorners.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" charset="utf-8">';
  echo '		NiftyLoad = function() {';
  echo '			Nifty("div.rounded");';
  echo '		}';
  echo '</script>';

  echo '<script type="text/javascript" src="'.$base_url.'media/script/lib/prototype/prototype.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.$base_url.'media/script/lib/scriptaculous/scriptaculous.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.$base_url.'media/script/lib/scriptaculous/slider.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.$base_url.'media/script/common.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.$base_url.'media/script/sortable.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.$base_url.'admin/media/script/common.js" charset="utf-8"></script>';
  echo '<script type="text/javascript" src="'.$base_url.'admin/media/script/ajax/configuration.js" charset="utf-8"></script>';

  echo '</head>';
  echo '<body>';
  echo '<div id="infoBulle" style="position: absolute; border: 1px solid black; background: #fec; padding: 5px; display: none; margin-right: 11px; max-width: 600px;"></div>';
  echo '<div id="mainWrap">';
  echo '<div id="headerWrap">';

  echo '<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  echo '<td style="min-width: 10%; text-align: right;" class="menu">';
  page_menu();

  echo '</td>';
  //echo '<td style="text-align: center; width: 100%;" class="title centered">';
  //echo '<h1 class="centered">'.$title.'</h1>';
  echo '</td>';

  echo '<td style="text-align: right; padding-right: 10px; border-bottom: 1px solid #ccc;" class="logo">';
  echo '<a href="index.php"><img src="'.$base_url.'/media/image/header.png" alt="logo" title="'.$title.'" /></a>';
  echo '</td>';

  echo '</tr>';
  echo '</table>';
  echo '</div>';

  echo '<div class="spacer"></div>';

  echo '<div id="pageWrap">';
  echo '<br />';
  echo '<div class="spacer"></div>';

  // Useless ?
  if (isset($_GET['error']) && $_GET['error'] != '')
    echo '<p class="msg_error">'.$_GET['error'].'</p><br /><br  />';

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

  page_sub_menu();
}

function page_footer() {
  $base_url = str_replace('/admin', '', dirname($_SERVER['PHP_SELF'])).'/';

  echo '</td>';
  echo '</tr>';
  echo '</table>';

  echo '</div>';

  echo '<div class="spacer"></div>';
  echo '<br />';
  echo '</div>';

  echo '<div class="spacer"></div>';

  echo '<div id="footerWrap">'._('powered by');
  echo '<a href="http://www.ulteo.com"><img src="'.$base_url.'media/image/ulteo.png" width="22" height="22" alt="Ulteo" title="Ulteo" /> Ulteo</a>&nbsp;&nbsp;&nbsp;';
  echo '</div>';
  echo '</div>';
  echo '</body>';
  echo '</html>';
}

function get_menu_entry() {
  return basename($_SERVER['REQUEST_URI']);
}

function get_parent_menu_entry() {
  global $items;
  global $sub_items;

  $in_menu = get_menu_entry();

  $len = 0;
  $parent = NULL;
  $son = NULL;

  foreach($sub_items as $k => $v) {
    foreach($v as $kk => $vv) {
      if (str_startswith($in_menu, $kk) &&
	  strlen($kk) > $len) {
	$parent = $k;
	$son = $kk;
      }
    }
  }

  return array($parent, $son);
}

function page_menu(){
  global $items;

  $in_menu = get_menu_entry();

  $parent = get_parent_menu_entry();
  if ($parent == NULL)
    $root = $in_menu;
  else
    $root = $parent;


  $i = 0;
  echo '<table border="0" cellspacing="0" cellpadding="10">';
  echo '<tr>';
  foreach($items as $k => $v) {
    echo '<td style="min-width: 60px; height: 81px;text-align: center; vertical-align: middle;';
    if ($root == $k){
      echo ' background: #eee; border-right: 1px solid #ccc;';
      echo ' border-left: 1px solid  #ccc;';
    } else
      echo ' border-bottom: 1px solid #ccc;';

    echo '" class="menu"><a href="'.$k.'"><img src="media/image/menu/'.$k.'.png" width="32" height="32" alt="'.$v.'" title="'.$v.'" /><br />';
    echo '<span class="menulink';

    if ($in_menu == $k)
      echo '_active';

    echo '">'.$v.'</span></a></td>'."\n";
  }
  echo '</tr>';
  echo '</table>';
}

function page_sub_menu() {
  global $sub_items;


  $in_menu = get_menu_entry();

  list($parent, $son) = get_parent_menu_entry();
  if ($parent == NULL)
    $root = $in_menu;
  else
    $root = $parent;

  if (! isset($sub_items[$root]))
    return;

  echo '<table style="width: 98.5%; margin-left: 10px; margin-right: 10px;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  echo '<td style="width: 150px; text-align: center; vertical-align: top; background: url(\'media/image/submenu_bg.png\') repeat-y right;">';

  foreach($sub_items[$root] as $key => $value) {
    if ($son == $key) {
echo '<div class="container" style="background: #fff; border-top: 1px solid #ccc; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;">';
      echo $value;
    } else {
      echo '<div class="container">';
      echo '<a href="'.$key.'">'.$value.'</a>';
    }
    echo '</div>';
  }

  echo '</td>';
  echo '<td style="text-align: left; vertical-align: top;">';
  echo '<div class="container" style="background: #fff; border-top: 1px solid  #ccc; border-right: 1px solid  #ccc; border-bottom: 1px solid  #ccc;">';
}
