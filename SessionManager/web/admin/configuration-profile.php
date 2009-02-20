<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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

function get_classes_startwith_2($start_name) {
	$files = glob('classes/'.$start_name.'*.class.php');

	$ret = array();
	foreach ($files as $file) {
	  $classname = basename($file);
	  $classname = substr($classname, 0, strlen($classname) - strlen('.class.php'));

	  $ret[] = $classname;
	}
	return $ret;
}

function getProfileMode($prefs) {
  $userDB_mode = $prefs->get('UserDB', 'enable');

  $classes = get_classes_startwith_2('Configuration_mode_');
  foreach($classes as $c) {
    $b = new $c();

    if ($b->careAbout($userDB_mode))
      return $c;
  }

  // Should never be called !!!
  return 'Configuration_mode_internal';
}


function do_auto_clean_db($new_prefs) {
  $prefs = Preferences::getInstance();

  $old_profile = getProfileMode($prefs);
  $new_profile = getProfileMode($new_prefs);

  $old_u = $prefs->get('UserDB', 'enable');
  $new_u = $new_prefs->get('UserDB', 'enable');

  $old_ugrp = $prefs->get('UserGroupDB', 'enable');
  $new_ugrp = $new_prefs->get('UserGroupDB', 'enable');

  $has_changed_u = False;
  $has_changed_ug = False;

  if ($old_profile == $new_profile) {
    $p = new $new_profile();
    list($has_changed_u, $has_changed_ug) = $p->has_change($prefs, $new_prefs);
  }

  // If UserDB module change
  if ($old_u != $new_u || $has_changed_u) {
    // Remove Users from user groups
    Abstract_Liaison::delete('UsersGroup', NULL, NULL) or
      popup_error('Unable to remove Users from UserGroups');
  }

  // If UserGroupDB module change
  if ($old_ugrp != $new_ugrp || $has_changed_ug) {
    // Remove Publications
    Abstract_Liaison::delete('UsersGroupApplicationsGroup', NULL, NULL) or
      popup_error('Unable to remove Publications');
  }
}

function do_save($prefs, $name) {
  $obj = new $name();

  if (! $obj->form_valid($_POST)) {
    popup_error('Invalid form');
    return False;
  }

  $flag = $obj->form_read($_POST, $prefs);
  if ($flag===False) {
    popup_error('form_read return an error');
    return False;
  }


  $mod_user_name = 'admin_UserDB_'.$prefs->get('UserDB','enable');
  //var_dump($mod_user_name);
  $userDB = new $mod_user_name();
  if (! $userDB->prefsIsValid($prefs)) {
    // error
    popup_error('Configuration is invalid for Users');
    return False;
  }

  $mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();
  if (! $userGroupDB->prefsIsValid($prefs)) {
    // error
    popup_error('Configuration is invalid for UserGroups');
    return False;
  }

  do_auto_clean_db($prefs);

  if (! $prefs->backup()) {
    popup_error('Unable to save configuration');
    return False;
  }

  return True;
}

function do_preview($prefs, $name) {
  $obj = new $name();
  $log = array();

  if (! $obj->form_valid($_POST)) {
    $log['Invalid Form'] = false;
    return $log;
  }

  $flag = $obj->form_read($_POST, $prefs);
  if ($flag===False) {
    $log['form_read return an error'] = false;
    return $log;
  }
  
  $mod_user_name = 'admin_UserDB_'.$prefs->get('UserDB','enable');
  $userDB = new $mod_user_name();
  if (! $userDB->prefsIsValid($prefs, &$log)) {
    return $log;
  }

  $mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();
  if (! $userGroupDB->prefsIsValid($prefs, &$log)) {
    return $log;
  }

  return $log;
}

try {
  $prefs = new Preferences_admin();
}
catch (Exception $e) {
  // Error header sauvergarde
}

if (isset($_POST['config'])) {
  $name = $_POST['config'];
  $previous = $_POST['config_previous'];

  if ($name == $previous) {
    $_SESSION['config_profile'] = $name;
    $_SESSION[$name] = $_POST;

    if (isset($_POST['submit_preview'])) {
      $preview = do_preview($prefs, $name);
    }
    else {
      if (do_save($prefs, $name) === True) {
	$_SESSION['config_profile_saved'] = true;
	unset($_SESSION['config_profile']);
	unset($_SESSION[$name]);
      }
       redirect();
    }
  
  }

  $has_previous = $name;
}

$classes = get_classes_startwith_2('Configuration_mode_');
$profiles = array();
foreach($classes as $c) {
  $b = new $c();
  $profiles[$c] = $b->getPrettyName();
}

$mode = getProfileMode($prefs);

if (isset($has_previous))
  $mode = $has_previous;
elseif (isset($_SESSION['config_profile']))
  $mode = $_SESSION['config_profile'];
else
  $mode = getProfileMode($prefs);


$c = new $mode();
if (isset($_SESSION[$mode]))
  $form = $_SESSION[$mode];
else
  $form = $c->config2form($prefs);

$green = false;
if (isset($_SESSION['config_profile_saved'])) {
  unset($_SESSION['config_profile_saved']);
  $green = true;
}


require_once('header.php');

  echo '<table style="width: 98.5%; margin-left: 10px; margin-right: 10px;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  echo '<td style="width: 150px; text-align: center; vertical-align: top; background: url(\'media/image/submenu_bg.png\') repeat-y right;">';
  include_once(dirname(__FILE__).'/submenu/configuration-profile.php');
  echo '</td>';
  echo '<td style="text-align: left; vertical-align: top;">';
  echo '<div class="container" style="background: #fff; border-top: 1px solid  #ccc; border-right: 1px solid  #ccc; border-bottom: 1px solid  #ccc;">';

echo '<form method="post">';
echo '<input type="hidden" name="config_previous" value="'.$mode.'" />';
echo '<select name="config" onChange="this.form.submit();">';
foreach($profiles as $profile => $name) {
  echo '<option value="'.$profile.'"';
  if ($profile == $mode)
    echo ' selected="selected"';
  echo '>'.$name.'</option>';
}
echo '</select>';
if ($green)
  echo ' <span style="color:green; font-weight: bold;">'._('Save succefully').'</span>';
echo '<br/>';

  echo $c->display($form);

echo '<input type="submit" value="Save"/>';
echo ' <input type="submit" name="submit_preview" value="Preview Mode"/>';
echo '</form>';

  echo '</div>';
  echo '</td>';
if (isset($preview)) {
  echo '<td style="width: 30%; vertical-align: top;"><ul>';
  foreach($preview as $msg => $status) {
    $c = ($status)?'green':'red';
    $m = ($status)?_('OK'):_('Error');

    echo '<li style="color:'.$c.';">'.$msg.': <strong>'.$m.'</strong></li>';
  }

  if (count($preview) > 0 && $status)
    echo '<li style="color:'.$c.';">'._('SUCCESS').'</li>';
  echo '</ul>';
  echo '</td>';
 }
  echo '</tr>';
  echo '</table>';

require_once('footer.php');
die();
