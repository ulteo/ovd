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

  $old_profile = $prefs->get('Profile', 'mode');
  $new_profile = $new_prefs->get('Profile', 'mode');

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
  $prefs->set('Profile', 'mode', $name);
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
    popup_error('Active Directory configuration is invalid for Users');
    return False;
  }

  $mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();
  if (! $userGroupDB->prefsIsValid($prefs)) {
    // error
    popup_error('Active Directory configuration is invalid for UserGroups');
    return False;
  }

  do_auto_clean_db($prefs);

  if (! $prefs->backup()) {
    popup_error('Unable to save configuration');
    return False;
  }

  $_SESSION['save_succed'] = true;
  return True;
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
    do_save($prefs, $name);
    redirect($_SERVER["PHP_SELF"]);
    die();
  }

  $has_previous = $name;
}

$mode = $prefs->get('Profile', 'mode');
if (isset($has_previous))
  $mode = $has_previous;
elseif ($mode == NULL) {
  $mode = 'Configuration_mode_internal';
}

$classes = get_classes_startwith_2('Configuration_mode_');
$profiles = array();
foreach($classes as $c) {
  $b = new $c();
  $profiles[$c] = $b->getPrettyName();
}

$c = new $mode();

$green = false;
if (isset($_SESSION['save_succed'])) {
  unset($_SESSION['save_succed']);
  $green = true;
}


require_once('header.php');

echo '<form method="post">';
echo '<input type="hidden" name="config_previous" value="'.$mode.'" />';
echo '<select name="config" onChange="this.form.submit();">';
foreach($profiles as $profile => $name) {
  $s = ($profile == $mode)?'selected="selected"':'';
  var_dump($s);

  echo '<option value="'.$profile.'" '.$s.'>'.$name.'</option>';
}
echo '</select>';
if ($green)
  echo ' <span style="color:green; font-weight: bold;">'._('Save succefully').'</span>';
echo '<br/>';

  echo $c->display($prefs);

echo '<input type="submit" value="Save"/>';
echo '</form>';

require_once('footer.php');
die();
