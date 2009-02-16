<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

if (isset($_REQUEST['action'])) {
  if ($_REQUEST['action']=='manage') {
    if (isset($_REQUEST['id']))
      show_manage($_REQUEST['id']);
  }

  if ($_REQUEST['action']=='add') {
    $id = action_add();
    show_manage($id);
  }
  elseif ($_REQUEST['action']=='del') {
    if (isset($_REQUEST['id'])) {
      $req_ids = $_REQUEST['id'];
      if (!is_array($req_ids))
        $req_ids = array($req_ids);
      foreach ($req_ids as $req_id)
        action_del($req_id);
    }
  }
  elseif ($_REQUEST['action']=='modify') {
    if (isset($_REQUEST['id'])) {
      action_modify($_REQUEST['id']);
      show_manage($_REQUEST['id']);
    }
  }
}

if (! isset($_GET['view']))
  $_GET['view'] = 'all';

if ($_GET['view'] == 'all')
  show_default();

function action_add() {
  if (! (isset($_REQUEST['name']) && isset($_REQUEST['description'])))
    return false;

  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserGroupDB',$mods_enable))
    die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);

  $mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();
  if (! $userGroupDB->isWriteable())
      return false;

  $g = new UsersGroup(NULL,$_REQUEST['name'], $_REQUEST['description'], 1);
  $res = $userGroupDB->add($g);
  if (!$res)
    die_error('Unable to create user group '.$res,__FILE__,__LINE__);

  return $g->id;
}

function action_del($id) {
  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserGroupDB',$mods_enable))
    die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);

  $mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();
  if (! $userGroupDB->isWriteable())
      return false;

  $group = $userGroupDB->import($id);
  if (! is_object($group))
    die_error('Group "'.$id.'" is not OK',__FILE__,__LINE__);

  if (! $userGroupDB->remove($group))
    die_error('Unable to remove group "'.$id.'" is not OK',__FILE__,__LINE__);

  return true;
}

function action_modify($id) {
  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserGroupDB',$mods_enable))
    die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);

  $mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();
  if (! $userGroupDB->isWriteable())
      return false;

  $group = $userGroupDB->import($id);
  if (! is_object($group))
    die_error('Group "'.$id.'" is not OK',__FILE__,__LINE__);

  $has_change = false;

  if (isset($_REQUEST['description'])) {
    $group->description = $_REQUEST['description'];
    $has_change = true;
  }

  if (isset($_REQUEST['published'])) {
    $group->published = (bool)$_REQUEST['published'];
    $has_change = true;
  }

  if (! $has_change)
    return false;

  if (! $userGroupDB->update($group))
    die_error('Unable to update group "'.$id.'"',__FILE__,__LINE__);

  return true;
}

function show_default() {
  $groups = get_all_usergroups();
  $has_group = ! (is_null($groups) or (count($groups) == 0));

  include_once('header.php');
//   echo '<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">';

  echo '<table style="width: 98.5%; margin-left: 10px; margin-right: 10px;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  echo '<td style="width: 150px; text-align: center; vertical-align: top; background: url(\'media/image/submenu_bg.png\') repeat-y right;">';
  include_once(dirname(__FILE__).'/submenu/usergroups.php');
  echo '</td>';
  echo '<td style="text-align: left; vertical-align: top;">';
  echo '<div class="container" style="background: #fff; border-top: 1px solid  #ccc; border-right: 1px solid  #ccc; border-bottom: 1px solid  #ccc;">';

  echo '<div id="usersgroup_div" >';
  echo '<h1>'._('Users groups').'</h1>';

  echo '<div id="usersgroup_list">';

  if (! $has_group)
    echo _('No users group available').'<br />';
  else {
    echo '<form action="usersgroup.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete these groups?').'\');">';
    echo '<input type="hidden" name="action" value="del" />';
    echo '<table class="main_sub sortable" id="usergroups_list" border="0" cellspacing="1" cellpadding="5">';
    echo '<tr class="title">';
    echo '<th class="unsortable"></th>';
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Description').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '</tr>';

    $count = 0;
    foreach($groups as $group){
      $content = 'content'.(($count++%2==0)?1:2);
      if ($group->published)
	$publish = '<span class="msg_ok">'._('Enabled').'</span>';
      else
	$publish = '<span class="msg_error">'._('Blocked').'</span>';

      echo '<tr class="'.$content.'">';
      echo '<td><input type="checkbox" name="id[]" value="'.$group->id.'" /></td><form></form>';
      echo '<td><a href="?action=manage&id='.$group->id.'">'.$group->name.'</a></td>';
      echo '<td>'.$group->description.'</td>';
      echo '<td class="centered">'.$publish.'</td>';

      echo '<td><form action="">';
      echo '<input type="submit" value="'._('Manage').'"/>';
      echo '<input type="hidden" name="action" value="manage" />';
      echo '<input type="hidden" name="id" value="'.$group->id.'" />';
      echo '</form></td>';

      echo '<td><form action="" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
      echo '<input type="submit" value="'._('Delete').'"/>';
      echo '<input type="hidden" name="action" value="del" />';
      echo '<input type="hidden" name="id" value="'.$group->id.'" />';
      echo '</form></td>';
      echo '</tr>';
    }
    $content = 'content'.(($count++%2==0)?1:2);
    echo '<tr class="'.$content.'">';
    echo '<td colspan="5"><a href="javascript:;" onclick="markAllRows(\'usergroups_list\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'usergroups_list\'); return false">'._('Unmark all').'</a></td>';
    echo '<td><input type="submit" value="'._('Delete').'"/></td>';
    echo '</table>';
    echo '</form>';
  }
  echo '</div>';

  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserGroupDB',$mods_enable))
    die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);

  $mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();
  if ($userGroupDB->isWriteable()) {
    echo '<div>';
    echo '<h2>'._('Create a new group').'</h2>';
    echo '<form action="" method="post">';
    echo '<input type="hidden" name="action" value="add" />';
    echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';

    echo '<tr class="content1">';
    echo '<th>'._('Name').'</th>';
    echo '<td><input type="text" name="name" value="" /></td>';
    echo '</tr>';

    echo '<tr class="content2">';
    echo '<th>'._('Description').'</th>';
    echo '<td><input type="text" name="description" value="" /></td>';
    echo '</tr>';
    /*
    echo '<tr class="content2">';
    echo '<th>'._('Status').'</th>';
    echo '<td>';
    echo '<input type="radio" name="published" value="1" checked />'._('Enable');
    echo '<input type="radio" name="published" value="0"  />'._('Block');
    echo '</td>';
    echo '</tr>';
    */
    echo '<tr class="content1">';
    echo '<td class="centered" colspan="2"><input type="submit" value="'._('Add').'" /></td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';
    echo '</div>';
  }

  echo '</div>';
  echo '</div>';
  echo '</div>';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
  include_once('footer.php');
}

function show_manage($id) {
  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserGroupDB',$mods_enable))
    die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);

  $mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();
  $usergroupdb_rw = $userGroupDB->isWriteable();

  $group = $userGroupDB->import($id);

  if (! is_object($group))
    die_error('Group "'.$id.'" is not OK',__FILE__,__LINE__);

  if ($group->published) {
    $status = '<span class="msg_ok">'._('Enabled').'</span>';
    $status_change = _('Block');
    $status_change_value = 0;

  } else {
    $status = '<span class="msg_error">'._('Blocked').'</span>';
    $status_change = _('Enable');
    $status_change_value = 1;
  }

  $users = $group->usersLogin();
  $has_users = (count($users) > 0);

  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserDB',$mods_enable))
    die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);

  $mod_user_name = 'admin_UserDB_'.$prefs->get('UserDB','enable');
  $userDB = new $mod_user_name();

  $users_all = $userDB->getList();
  $users_available = array();
  foreach($users_all as $user) {
    $found = false;
    foreach($users as $user2) {
      if ($user2 == $user->getAttribute('login'))
	$found = true;
    }

    if (! $found)
      $users_available[]= $user->getAttribute('login');
  }

  // Publications
  $groups_apps = array();
  foreach ( Abstract_Liaison::load('UsersGroupApplicationsGroup',  $id, NULL) as $group_a) {
    $obj = new AppsGroup();
    $obj->fromDB($group_a->group);

    if (is_object($obj))
	$groups_apps[]= $obj;
  }

  $groups_apps_all = getAllAppsGroups();
  $groups_apps_available = array();
  foreach($groups_apps_all as $group_apps) {
    if (! in_array($group_apps, $groups_apps))
      $groups_apps_available[]= $group_apps;
  }


  include_once('header.php');
//   echo '<div class="container rounded" style="background: #fff; width: 98%; margin-left: auto; margin-right: auto;">';

  echo '<table style="width: 98.5%; margin-left: 10px; margin-right: 10px;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  echo '<td style="width: 150px; text-align: center; vertical-align: top; background: url(\'media/image/submenu_bg.png\') repeat-y right;">';
  include_once(dirname(__FILE__).'/submenu/usergroups.php');
  echo '</td>';
  echo '<td style="text-align: left; vertical-align: top;">';
  echo '<div class="container" style="background: #fff; border-top: 1px solid  #ccc; border-right: 1px solid  #ccc; border-bottom: 1px solid  #ccc;">';

  echo '<div id="users_div">';
  echo '<h1><a href="?">'._('Users groups management').'</a> - '.$group->name.'</h1>';

  echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
  echo '<tr class="title">';
  echo '<th>'._('Description').'</th>';
  echo '<th>'._('Status').'</th>';
  echo '</tr>';

  echo '<tr class="content1">';
  echo '<td>'.$group->description.'</td>';
  echo '<td>'.$status.'</td>';
  echo '</tr>';
  echo '</table>';

  if ($usergroupdb_rw) {
    echo '<div>';
    echo '<h2>'._('Settings').'</h1>';
    echo '<form action="" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
    echo '<input type="submit" value="'._('Delete this group').'"/>';
    echo '<input type="hidden" name="action" value="del" />';
    echo '<input type="hidden" name="id" value="'.$id.'" />';
    echo '</form>';
    echo '<br/>';

    echo '<form action="" method="post">';
    echo '<input type="hidden" name="action" value="modify" />';
    echo '<input type="hidden" name="id" value="'.$id.'" />';
    echo '<input type="hidden" name="published" value="'.$status_change_value.'" />';
    echo '<input type="submit" value="'.$status_change.'"/>';
    echo '</form>';
    echo '<br/>';

    echo '<form action="" method="post">';
    echo '<input type="hidden" name="action" value="modify" />';
    echo '<input type="hidden" name="id" value="'.$id.'" />';
    echo '<input type="text" name="description"  value="'.$group->description.'" size="50" /> ';
    echo '<input type="submit" value="'._('Update the description').'"/>';
    echo '</form>';
    echo '</div>';
    echo '<br/>';
  }
  // Users list
  if (count($users_all) > 0) {
    echo '<div>';
    echo '<h2>'._('List of users in this group').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';

    if (count($users) > 0) {
      foreach($users as $user) {
	echo '<tr>';
	echo '<td><a href="users.php?action=manage&id='.$user.'">'.$user.'</td>';
	echo '<td>';
	if ( $usergroupdb_rw) {
		echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this user?').'\');">';
		echo '<input type="hidden" name="action" value="del" />';
		echo '<input type="hidden" name="name" value="User_UserGroup" />';
		echo '<input type="hidden" name="group" value="'.$id.'" />';
		echo '<input type="hidden" name="element" value="'.$user.'" />';
		echo '<input type="submit" value="'._('Delete from this group').'" />';
		echo '</form>';
		echo '</td>';
	}
	echo '</tr>';
      }
    }

    if ((count ($users_available) >0) && $usergroupdb_rw) {
      echo '<tr><form action="actions.php" method="post"><td>';
      echo '<input type="hidden" name="action" value="add" />';
      echo '<input type="hidden" name="name" value="User_UserGroup" />';
      echo '<input type="hidden" name="group" value="'.$id.'" />';
      echo '<select name="element">';
      foreach($users_available as $user)
	echo '<option value="'.$user.'" >'.$user.'</option>';
      echo '</select>';
      echo '</td><td><input type="submit" value="'._('Add to this group').'" /></td>';
      echo '</form></tr>';
    }

    echo '</table>';
    echo '</div>';
    echo '<br/>';
  }

  // Publications part
  if (count($groups_apps_all)>0) {
    echo '<div>';
    echo '<h2>'._('List of publications for this group').'</h1>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';

    if (count($groups_apps)>0) {
      foreach($groups_apps as $groups_app) {
	echo '<tr>';
	echo '<td><a href="appsgroup.php?action=manage&id='.$groups_app->id.'">'.$groups_app->name.'</td>';
	echo '<td>';
	echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this publication?').'\');">';
	echo '<input type="hidden" name="action" value="del" />';
	echo '<input type="hidden" name="name" value="Publication" />';
	echo '<input type="hidden" name="group_u" value="'.$id.'" />';
	echo '<input type="hidden" name="group_a" value="'.$groups_app->id.'" />';
	echo '<input type="submit" value="'._('Delete this publication').'" />';
	echo '</form>';
	echo '</td>';
	echo '</tr>';
      }
    }

    if (count ($groups_apps_available) >0) {
      echo '<tr><form action="actions.php" method="post"><td>';
      echo '<input type="hidden" name="action" value="add" />';
      echo '<input type="hidden" name="name" value="Publication" />';
      echo '<input type="hidden" name="group_u" value="'.$id.'" />';
      echo '<select name="group_a">';
      foreach($groups_apps_available as $group_apps)
	echo '<option value="'.$group_apps->id.'" >'.$group_apps->name.'</option>';
      echo '</select>';
      echo '</td><td><input type="submit" value="'._('Add this publication').'" /></td>';
      echo '</form></tr>';
    }
    echo '</table>';
    echo '</div>';
  }

  echo '</div>';
  echo '</div>';
  echo '</div>';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
  include_once('footer.php');
  die();
}
