<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Julien LANGLOIS <julien@ulteo.com>
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
require_once(dirname(__FILE__).'/includes/page_template.php');

if (isset($_REQUEST['action'])) {
  if ($_REQUEST['action']=='manage') {
    if (isset($_REQUEST['id']))
      show_manage($_REQUEST['id']);
  }

  if ($_REQUEST['action']=='add') {
    if ($_REQUEST['type'] == 'static')
      $id = action_add();
    elseif ($_REQUEST['type'] == 'dynamic')
      $id = action_add_dynamic();
    if ($id !== false)
      redirect('usersgroup.php?action=manage&id='.$id);
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
      redirect('usersgroup.php?action=manage&id='.$_REQUEST['id']);
    }
  }
  elseif ($_REQUEST['action']=='set_default') {
    if (isset($_REQUEST['id'])) {
      $req_id = $_REQUEST['id'];

      action_set_default($req_id);
      redirect();
    }
  }
  elseif ($_REQUEST['action']=='unset_default') {
    if (isset($_REQUEST['id'])) {
      $req_id = $_REQUEST['id'];

      action_unset_default($req_id);
      redirect();
    }
  }

  redirect();
}

if (! isset($_GET['view']))
  $_GET['view'] = 'all';

if ($_GET['view'] == 'all')
  show_default();

function action_add() {
  if (! (isset($_REQUEST['name']) && isset($_REQUEST['description'])))
    return false;

  if ($_REQUEST['name'] == '') {
    popup_error(_('You must define a name to your usergroup'));
    return false;
  }

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

  return $g->getUniqueID();
}

function action_add_dynamic() {
  if (! (isset($_REQUEST['name']) && isset($_REQUEST['description'])))
    return false;

  if ($_REQUEST['name'] == '') {
    popup_error(_('You must define a name to your usergroup'));
    return false;
  }

  $userGroupDB = UserGroupDB::getInstance();

  $rules = array();
  foreach ($_POST['rules'] as $rule) {
    if ($rule['value'] == '') {
      popup_error(_('You must give a value to each rule of your usergroup'));
      return false;
    }

    $buf = new UserGroup_Rule(NULL);
    $buf->attribute = $rule['attribute'];
    $buf->type = $rule['type'];
    $buf->value = $rule['value'];

    $rules[] = $buf;
  }

  $g = new UsersGroup_dynamic(NULL,$_REQUEST['name'], $_REQUEST['description'], 1, $rules, $_REQUEST['validation_type']);
  $res = $userGroupDB->add($g);
  if (!$res)
    die_error('Unable to create dynamic user group '.$res,__FILE__,__LINE__);
  return $g->getUniqueID();
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

function action_set_default($id_) {
  try {
    $prefs = new Preferences_admin();
  }
  catch (Exception $e) {
    // Error header sauvergarde
    return False;
  }

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserGroupDB',$mods_enable))
    die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);

  $mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();

  $group = $userGroupDB->import($id_);
  if (! is_object($group)) {
    popup_error('No such group id "'.$id_.'"');
    return False;
  }

  $mods_enable = $prefs->set('general', 'user_default_group', $id_);
  if (! $prefs->backup()) {
    Logger::error('main', 'usersgroup.php action_default: Unable to save $prefs');
    return False;
  }

  return True;
}

function action_unset_default($id_) {
  try {
    $prefs = new Preferences_admin();
  }
  catch (Exception $e) {
    // Error header sauvergarde
    return False;
  }

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserGroupDB',$mods_enable))
    die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);

  $mod_usergroup_name = 'UserGroupDB_'.$prefs->get('UserGroupDB','enable');
  $userGroupDB = new $mod_usergroup_name();

  $group = $userGroupDB->import($id_);
  if (! is_object($group)) {
    popup_error('No such group id "'.$id_.'"');
    return False;
  }

  $default_id = $prefs->get('general', 'user_default_group');
  if ($id_ != $default_id) {
    popup_error('Group id "'.$id_.'" is not the default group');
    return False;
  }

  $mods_enable = $prefs->set('general', 'user_default_group', NULL);
  if (! $prefs->backup()) {
    Logger::error('main', 'usersgroup.php action_default: Unable to save $prefs');
    return False;
  }

  return True;
}


function show_default() {
  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserGroupDB',$mods_enable))
    die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);
  
  if (! in_array('UserDB',$mods_enable))
    die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
  
  $userGroupDB = UserGroupDB::getInstance();
  $mod_userdb_name = 'admin_UserDB_'.$prefs->get('UserDB','enable');
  $userDB = new $mod_userdb_name();
  $groups = $userGroupDB->getList(true);
  $has_group = ! (is_null($groups) or (count($groups) == 0));

  page_header();

  echo '<div id="usersgroup_div" >';
  echo '<h1>'._('User groups').'</h1>';

  echo '<div id="usersgroup_list">';

  if (! $has_group)
    echo _('No available user group').'<br />';
  else {
    if ($userGroupDB->isWriteable()) {
      echo '<form action="usersgroup.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete these groups?').'\');">';
      echo '<input type="hidden" name="action" value="del" />';
    }
    echo '<table class="main_sub sortable" id="usergroups_list" border="0" cellspacing="1" cellpadding="5">';
    echo '<tr class="title">';
    if ($userGroupDB->isWriteable()) {
      echo '<th class="unsortable"></th>';
    }
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Description').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '<th>'._('Type').'</th>';
    echo '</tr>';

    $count = 0;
    foreach($groups as $group){
      $content = 'content'.(($count++%2==0)?1:2);
      if ($group->published)
        $publish = '<span class="msg_ok">'._('Enabled').'</span>';
      else
        $publish = '<span class="msg_error">'._('Blocked').'</span>';

      echo '<tr class="'.$content.'">';
      if ($userGroupDB->isWriteable()) {
        echo '<td><input class="input_checkbox" type="checkbox" name="id[]" value="'.$group->getUniqueID().'" /></td><form></form>';
      }
      echo '<td><a href="?action=manage&id='.$group->getUniqueID().'">'.$group->name.'</a></td>';
      echo '<td>'.$group->description.'</td>';
      echo '<td class="centered">'.$publish.'</td>';
      echo '<td class="centered">'.$group->type.'</td>';

      echo '<td><form action="">';
      echo '<input type="submit" value="'._('Manage').'"/>';
      echo '<input type="hidden" name="action" value="manage" />';
      echo '<input type="hidden" name="id" value="'.$group->getUniqueID().'" />';
      echo '</form></td>';

      if ($userGroupDB->isWriteable()) {
        echo '<td><form action="" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
        echo '<input type="submit" value="'._('Delete').'"/>';
        echo '<input type="hidden" name="action" value="del" />';
        echo '<input type="hidden" name="id" value="'.$group->getUniqueID().'" />';
        echo '</form></td>';
      }
      echo '</tr>';
    }
    $content = 'content'.(($count++%2==0)?1:2);
    if ($userGroupDB->isWriteable()) {
      echo '<tfoot>';
      echo '<tr class="'.$content.'">';
      echo '<td colspan="6"><a href="javascript:;" onclick="markAllRows(\'usergroups_list\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'usergroups_list\'); return false">'._('Unmark all').'</a></td>';
      echo '<td><input type="submit" value="'._('Delete').'"/></td>';
      echo '</tr>';
      echo '</tfoot>';
    }
    echo '</table>';
    if ($userGroupDB->isWriteable()) {
      echo '</form>';
    }
  }

  echo '</div>';

  if ($userGroupDB->isWriteable()) {
	$usergroup_types = array('static' => _('Static'), 'dynamic' => _('Dynamic'));

    echo '<div>';
    echo '<h2>'._('Create a new group').'</h2>';

	$first_type = array_keys($usergroup_types);
	$first_type = $first_type[0];
	$usergroup_types2 = $usergroup_types; // bug in php 5.1.6 (redhat 5.2)
	foreach ($usergroup_types as $type => $name) {
		echo '<input class="input_radio" type="radio" name="type" value="'.$type.'" onclick="';
		foreach ($usergroup_types2 as $type2 => $name2) { // bug in php 5.1.6
			if ($type == $type2)
				echo '$(\'table_'.$type2.'\').show(); ';
			else
				echo '$(\'table_'.$type2.'\').hide(); ';
		}
		echo '"';
		if ($type == $first_type)
			echo ' checked="checked"';

		echo ' />';
		echo $type;
	}

	foreach ($usergroup_types as $type => $name) {
		echo '<form action="" method="post">';
		echo '<table id="table_'.$type.'"';
		if ( $type != $first_type)
			echo ' style="display: none" ';
		else
			echo ' style="display: visible" ';
		echo ' border="0" class="main_sub" cellspacing="1" cellpadding="5" >';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="hidden" name="type" value="'.$type.'" />';
		echo '<tr class="content1">';
		echo '<th>'._('Name').'</th>';
		echo '<td><input type="text" name="name" value="" /></td>';
		echo '</tr>';

		echo '<tr class="content2">';
		echo '<th>'._('Description').'</th>';
		echo '<td><input type="text" name="description" value="" /></td>';
		echo '</tr>';

		if ($type == 'dynamic') {
			echo '<tr class="content1">';
			echo '<th>'._('Validation type').'</th>';
			echo '<td><input type="radio" name="validation_type" value="and" checked="checked" /> '._('All').' <input type="radio" name="validation_type" value="or" /> '._('At least one').'</td>';
			echo '</tr>';

			echo '<tr class="content2">';
			echo '<th>'._('Filters').'</th>';
			echo '<td>';

			$i = 0;
			$filter_attributes = $userDB->getAttributesList();
			foreach ($filter_attributes as $key1 => $value1) {
				if ( $value1 == 'password')
					unset($filter_attributes[$key1]);
			}
			$filter_types = UserGroup_Rule::$types;
			echo '<table id="toto" border="0" cellspacing="1" cellpadding="3">';
			echo '<tr>';
			echo '<td><select name="rules[0][attribute]">';
			foreach ($filter_attributes as $filter_attribute)
				echo '<option value="'.$filter_attribute.'">'.$filter_attribute.'</option>';
			echo '</select></td>';
			echo '<td><select name="rules[0][type]">';
			foreach ($filter_types as $filter_type) {
				echo '<option value="'.$filter_type.'">'.$filter_type.'</option>';
			}
			echo '</select></td>';
			echo '<td><input type="text" name="rules[0][value]" value="" /></td>';
			echo '<td><input style="display: none;" type="button" onclick="del_field(this.parentNode.parentNode); return false;" value="-" /><input type="button" onclick="add_field(this.parentNode.parentNode); return false;" value="+" /></td>';
			echo '</tr>';
			echo '</table>';

			echo '</td>';
			echo '</tr>';
		}

		echo '<tr class="content1">';
		echo '<td class="centered" colspan="2"><input type="submit" value="'._('Add').'" /></td>';
		echo '</tr>';
		echo '</table>';
		echo '</form>';
	}
    echo '</div>';
  }

  echo '</div>';
  page_footer();
}

function show_manage($id) {
  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserGroupDB',$mods_enable))
    die_error(_('Module UserGroupDB must be enabled'),__FILE__,__LINE__);

  $userGroupDB = UserGroupDB::getInstance();
  $usergroupdb_rw = true;// TODO  $usergroupdb_rw = $userGroupDB->isWriteable();

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

  // Default usergroup
  $is_default_group = ($prefs->get('general', 'user_default_group') == $id);

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


  page_header();
  echo '<div id="users_div">';
  echo '<h1><a href="?">'._('User groups management').'</a> - '.$group->name.'</h1>';

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


  echo '<div>';
  echo '<h2>'._('Settings').'</h1>';

  echo '<form action="" method="post">';
  if ($is_default_group) {
    echo '<input type="submit" value="'._('Remove from default').'"/>';
    echo '<input type="hidden" name="action" value="unset_default" />';
  } else {
    echo '<input type="submit" value="'._('Define as default').'"/>';
    echo '<input type="hidden" name="action" value="set_default" />';
  }

  echo '<input type="hidden" name="id" value="'.$id.'" />';
  echo '</form>';
  echo '<br/>';

  if ($usergroupdb_rw) {
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
  }
  echo '</div>';
  echo '<br/>';

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
  page_footer();
  die();
}
