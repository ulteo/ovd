<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
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

if (! checkAuthorization('viewUsersGroups'))
	redirect('index.php');


$schedules = array(
	3600	=>	_('1 hour'),
	86400	=>	_('1 day'),
	604800	=>	_('1 week')
);

if (isset($_REQUEST['action'])) {
  if ($_REQUEST['action']=='manage') {
    if (isset($_REQUEST['id']))
      show_manage($_REQUEST['id']);
  }
  elseif ($_REQUEST['action']=='search') {
    show_default();
  }

  redirect();
}

if (! isset($_GET['view']))
  $_GET['view'] = 'all';

if ($_GET['view'] == 'all')
  show_default();

function show_default() {
  global $schedules;

  $userGroupDB = UserGroupDB::getInstance();
  $userDB = UserDB::getInstance();
  $usersgroupsList = new UsersGroupsList($_REQUEST);
  $groups = $usersgroupsList->search();
  if (is_array($groups)) {
    usort($groups, "usergroup_cmp");
  }
  $searchDiv = $usersgroupsList->getForm();

  $has_group = ! (is_null($groups) or (count($groups) == 0));

  $can_manage_usersgroups = isAuthorized('manageUsersGroups');

  page_header();

  echo '<div id="usersgroup_div" >';
  echo '<h1>'._('User groups').'</h1>';

  echo $searchDiv;
  
  echo '<div id="usersgroup_list">';

  if (! $has_group)
    echo _('No available user group').'<br />';
  else {
     $all_static = true;
     foreach($groups as $group){
       if ($group->type != 'static' || $userGroupDB->isWriteable()) {
         $all_static = false;
         break; // no need to continue;
       }
     }
    echo '<table class="main_sub sortable" id="usergroups_list" border="0" cellspacing="1" cellpadding="5">';
    echo '<thead>';
    echo '<tr class="title">';
    if ( (!$all_static || $userGroupDB->isWriteable()) and $can_manage_usersgroups and count($groups) > 1) {
      echo '<th class="unsortable"></th>'; // masse action
    }
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Description').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '<th>'._('Type').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $count = 0;
    foreach($groups as $group){
      $content = 'content'.(($count++%2==0)?1:2);
      if ($group->published)
        $publish = '<span class="msg_ok">'._('Enabled').'</span>';
      else
        $publish = '<span class="msg_error">'._('Blocked').'</span>';

      echo '<tr class="'.$content.'">';
      if ($can_manage_usersgroups) {
        if ($group->type != 'static' || $userGroupDB->isWriteable() and count($groups) > 1) {
          echo '<td><input class="input_checkbox" type="checkbox" name="checked_groups[]" value="'.$group->getUniqueID().'" /></td>';
        }
        else if ( !$all_static and count($groups) > 1) {
          echo '<td></td>';
        }
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

      if (($group->type != 'static' || $userGroupDB->isWriteable()) and $can_manage_usersgroups) {
        echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
        echo '<input type="hidden" name="name" value="UserGroup" />';
        echo '<input type="submit" value="'._('Delete').'"/>';
        echo '<input type="hidden" name="action" value="del" />';
        echo '<input type="hidden" name="checked_groups[]" value="'.$group->getUniqueID().'" />';
        echo '</form></td>';
      }
      else if ( !$all_static and $can_manage_usersgroups) {
        echo '<td></td>';
      }
      echo '</tr>';
    }
    echo '</tbody>';
    $content = 'content'.(($count++%2==0)?1:2);
    if ( (!$all_static || $userGroupDB->isWriteable()) and $can_manage_usersgroups and count($groups) > 1) {
      echo '<tfoot>';
      echo '<tr class="'.$content.'">';
      echo '<td colspan="6"><a href="javascript:;" onclick="markAllRows(\'usergroups_list\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'usergroups_list\'); return false">'._('Unmark all').'</a></td>';
	  echo '<td>';
	  echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete these groups?').'\') && updateMassActionsForm(this, \'usergroups_list\');">';
	  echo '<input type="hidden" name="name" value="UserGroup" />';
	  echo '<input type="hidden" name="action" value="del" />';
	  echo '<input type="submit" value="'._('Delete').'"/>';
	  echo '</form>';
	  echo '</td>';
      echo '</tr>';
      echo '</tfoot>';
    }
    echo '</table>';
  }

  echo '</div>';

  $usergroup_types = array();
  if ($userGroupDB->isWriteable()) {
    $usergroup_types = array('static' => _('Static'));
  }
  if (Preferences::moduleIsEnabled('UserGroupDBDynamic') || Preferences::moduleIsEnabled('UserGroupDBDynamicCached')) {
    $usergroup_types['dynamic'] = _('Dynamic');
  }


	if ($can_manage_usersgroups && $usergroup_types != array()) {
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
			echo $name;
		}

		foreach ($usergroup_types as $type => $name) {
			$count = 2;
			echo '<form action="actions.php" method="post">';
			echo '<table id="table_'.$type.'"';
			if ( $type != $first_type)
				echo ' style="display: none" ';
			else
				echo ' style="display: visible" ';
			echo ' border="0" class="main_sub" cellspacing="1" cellpadding="5" >';
			echo '<input type="hidden" name="name" value="UserGroup" />';
			echo '<input type="hidden" name="action" value="add" />';
			echo '<input type="hidden" name="type" value="'.$type.'" />';
			echo '<tr class="content'.(($count++%2==0)?1:2).'">';
			echo '<th>'._('Name').'</th>';
			echo '<td><input type="text" name="name_group" value="" /></td>';
			echo '</tr>';

			echo '<tr class="content'.(($count++%2==0)?1:2).'">';
			echo '<th>'._('Description').'</th>';
			echo '<td><input type="text" name="description_group" value="" /></td>';
			echo '</tr>';
		
			if (str_startswith($type, 'dynamic')) {
				echo '<tr class="content'.(($count++%2==0)?1:2).'">';
				echo '<th>'._('Cached').'</th>';
				echo '<td>';
				echo '<input type="radio" name="cached" value="0" checked="checked" onclick="$(\'schedule_select\').hide();" /> '._('No');
				if (Preferences::moduleIsEnabled('UserGroupDBDynamicCached')) {
					echo '<input type="radio" name="cached" value="1" onclick="$(\'schedule_select\').show();" /> '._('Yes');
					echo ' <span id="schedule_select" style="display: none;"><br />'._('Time between two updates:').' <select name="schedule">';
					foreach ($schedules as $interval => $text)
						echo '<option value="'.$interval.'">'.$text.'</option>';
					echo '</select></span>';
				}
				echo '</td>';
				echo '</tr>';
				echo '<tr class="content'.(($count++%2==0)?1:2).'">';
				echo '<th>'._('Validation type').'</th>';
				echo '<td><input type="radio" name="validation_type" value="and" checked="checked" /> '._('All').' <input type="radio" name="validation_type" value="or" /> '._('At least one').'</td>';
				echo '</tr>';

				echo '<tr class="content'.(($count++%2==0)?1:2).'">';
				echo '<th>'._('Filters').'</th>';
				echo '<td>';

				$i = 0;
				$filter_attributes = $userDB->getAttributesList();
				foreach ($filter_attributes as $key1 => $value1) {
					if ( $value1 == 'password')
						unset($filter_attributes[$key1]);
				}
				$filter_types = UserGroup_Rule::$types;
				echo '<table border="0" cellspacing="1" cellpadding="3">';
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
	} //if ($can_manage_usersgroups)

  echo '</div>';
  page_footer();
  die();
}

function show_manage($id) {
  global $schedules;
  
  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $userGroupDB = UserGroupDB::getInstance();

  $group = $userGroupDB->import($id);
  
  if (! is_object($group)) {
    die_error(_('Failed to load usergroup'));
  }
  
  $usergroupdb_rw = $userGroupDB->isWriteable();

  $policy = $group->getPolicy();
  $policy_rule_enable = 0;
  $policy_rules_disable = 0;
  foreach($policy as $key => $value) {
	  if ($value === true)
		  $policy_rule_enable++;
	  else
		  $policy_rules_disable++;
  }

  $buffer = $prefs_policy = $prefs->get('general', 'policy');
  $default_policy = $prefs_policy['default_policy'];

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

  $userDB = UserDB::getInstance();
  $applicationsGroupDB = ApplicationsGroupDB::getInstance();

  if ($group->isDefault() == false) {
    $users = $group->usersLogin();
    sort($users);
    $has_users = (count($users) > 0);

  if ($usergroupdb_rw) {
    $usersList = new UsersList($_REQUEST);
    $users_all = $usersList->search();
    $search_form = $usersList->getForm(array('action' => 'manage', 'id' => $id, 'search_user' => true));
    if (is_null($users_all))
      $users_all = array();
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
    }
    else {
      $users_available = array();
      $users_all = array();
      foreach($users as $a_login) {
        $users_all [] = $userDB->import($a_login);
      }
      usort($users_all, "user_cmp");
    }
  }
  else {
    $users = array();
    $users_available = array();
    $users_all = array();
    $search_form = null;
  }

  // Default usergroup
  $is_default_group = ($prefs->get('general', 'user_default_group') == $id);

  // Publications
  $groups_apps = array();
  foreach ( Abstract_Liaison::load('UsersGroupApplicationsGroup',  $id, NULL) as $group_a) {
    $obj = $applicationsGroupDB->import($group_a->group);

    if (is_object($obj))
	$groups_apps[]= $obj;
  }

  $groups_apps_all = $applicationsGroupDB->getList();
  $groups_apps_available = array();
  foreach($groups_apps_all as $group_apps) {
    if (! in_array($group_apps, $groups_apps))
      $groups_apps_available[]= $group_apps;
  }

  $can_manage_usersgroups = isAuthorized('manageUsersGroups');
  $can_manage_publications = isAuthorized('managePublications');
  $can_manage_sharedfolders = isAuthorized('manageServers');
  
  $prefs_to_get_for_a_group = array('session_settings_defaults', 'remote_desktop_settings',  'remote_applications_settings');
  $prefs_of_a_group = array();
  $unuse_settings = array();
  $session_prefs = array();
  
  foreach ($prefs_to_get_for_a_group as $prefs_to_get_for_a_group_value) {
    $prefs_of_a_group[$prefs_to_get_for_a_group_value] = array();
    $unuse_settings[$prefs_to_get_for_a_group_value] = array();
    
    $session_prefs[$prefs_to_get_for_a_group_value] = $prefs->getElements('general', $prefs_to_get_for_a_group_value);
    $prefs_of_a_group_unsort = Abstract_UserGroup_Preferences::loadByUserGroupId($group->getUniqueID(), 'general', $prefs_to_get_for_a_group_value);
 
    foreach ($session_prefs[$prefs_to_get_for_a_group_value] as $k4 => $v4) {  // we should use the ones from the group ($prefs_of_a_group_unsort) but we can display then if they are in $session_prefs
      if (array_key_exists($k4, $prefs_of_a_group_unsort)) {
        $prefs_of_a_group[$prefs_to_get_for_a_group_value][$k4] = $prefs_of_a_group_unsort[$k4];
      }
      else {
        $unuse_settings[$prefs_to_get_for_a_group_value][$k4] = $v4;
      }
    }
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


 	if ($can_manage_usersgroups) {
		echo '<div>';
		echo '<h2>'._('Settings').'</h1>';

		if ($group->type == 'static' and $can_manage_usersgroups and $usergroupdb_rw) {
			echo '<form action="actions.php" method="post">';
			if ($is_default_group) {
				echo '<input type="submit" value="'._('Remove from default').'"/>';
				echo '<input type="hidden" name="action" value="unset_default" />';
			} else {
				echo '<input type="submit" value="'._('Define as default').'"/>';
				echo '<input type="hidden" name="action" value="set_default" />';
			}

			echo '<input type="hidden" name="name" value="UserGroup" />';
			echo '<input type="hidden" name="id" value="'.$group->getUniqueID().'" />';
			echo '</form>';
			echo '<br/>';
		}

		if ($usergroupdb_rw || ($group->type != 'static')) {
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
			echo '<input type="submit" value="'._('Delete this group').'"/>';
			echo '<input type="hidden" name="name" value="UserGroup" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="checked_groups[]" value="'.$id.'" />';
			echo '</form>';
			echo '<br/>';

			echo '<form action="actions.php" method="post">';
			echo '<input type="hidden" name="name" value="UserGroup" />';
			echo '<input type="hidden" name="action" value="modify" />';
			echo '<input type="hidden" name="id" value="'.$id.'" />';
			echo '<input type="hidden" name="published" value="'.$status_change_value.'" />';
			echo '<input type="submit" value="'.$status_change.'"/>';
			echo '</form>';
			echo '<br/>';

			echo '<form action="actions.php" method="post">';
			echo '<input type="hidden" name="name" value="UserGroup" />';
			echo '<input type="hidden" name="action" value="modify" />';
			echo '<input type="hidden" name="id" value="'.$id.'" />';
			echo '<input type="text" name="name_group"  value="'.$group->name.'" size="50" /> ';
			echo '<input type="submit" value="'._('Update the name').'"/>';
			echo '</form>';
			echo '<br/>';
	
			echo '<form action="actions.php" method="post">';
			echo '<input type="hidden" name="name" value="UserGroup" />';
			echo '<input type="hidden" name="action" value="modify" />';
			echo '<input type="hidden" name="id" value="'.$id.'" />';
			echo '<input type="text" name="description"  value="'.$group->description.'" size="50" /> ';
			echo '<input type="submit" value="'._('Update the description').'"/>';
			echo '</form>';
		}
    
		if ($group->type == 'dynamiccached') {
			echo '<br />';
			echo '<form action="actions.php" method="post">';
			echo '<input type="hidden" name="name" value="UserGroup" />';
			echo '<input type="hidden" name="action" value="modify" />';
			echo '<input type="hidden" name="id" value="'.$id.'" />';

			echo ' <select name="schedule">';
			foreach ($schedules as $interval => $text) {
				echo '<option value="'.$interval.'"';
				if ($group->schedule == $interval)
					echo ' selected="selected"';
				echo '>'.$text.'</option>';
			}
			echo '</select>';
			echo '<input type="submit" value="'._('Update the schedule').'"/>';
			echo '</form>';
		}

		echo '</div>';
		echo '<br/>';
	}


  if (str_startswith($group->type,'dynamic')) {
    echo '<div>';
    echo '<h2>'._('Rules').'</h1>';

	if ($can_manage_usersgroups) {
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="UserGroup" />';
		echo '<input type="hidden" name="action" value="modify_rules" />';
		echo '<input type="hidden" name="id" value="'.$id.'" />';
	}
echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
echo '<tr class="content1">';
echo '<th>'._('Validation type').'</th>';
echo '<td><input type="radio" name="validation_type" value="and"';
if ($group->validation_type == 'and')
	echo ' checked="checked"';
echo ' /> '._('All').' <input type="radio" name="validation_type" value="or"';
if ($group->validation_type == 'or')
	echo ' checked="checked"';
echo ' /> '._('At least one').'</td>';
echo '</tr>';

echo '<tr class="content2">';
echo '<th>'._('Filters').'</th>';
echo '<td>';

$i = 0;
$filter_attributes = $userDB->getAttributesList();
foreach ($filter_attributes as $key1 => $value1) {
	if ($value1 == 'password')
		unset($filter_attributes[$key1]);
}

$filter_types = UserGroup_Rule::$types;
echo '<table border="0" cellspacing="1" cellpadding="3">';
$i = 0;
foreach ($group->rules as $rule) {
	echo '<tr>';
	echo '<td><select name="rules['.$i.'][attribute]">';
	foreach ($filter_attributes as $filter_attribute) {
		echo '<option value="'.$filter_attribute.'"';
		if ($rule->attribute == $filter_attribute)
			echo ' selected="selected"';
		echo '>'.$filter_attribute.'</option>';
	}
	echo '</select></td>';
	echo '<td><select name="rules['.$i.'][type]">';
	foreach ($filter_types as $filter_type) {
		echo '<option value="'.$filter_type.'"';
		if ($rule->type == $filter_type)
			echo ' selected="selected"';
		echo '>'.$filter_type.'</option>';
	}
	echo '</select></td>';
	echo '<td><input type="text" name="rules['.$i.'][value]" value="'.$rule->value.'" /></td>';
	if ($can_manage_usersgroups) {
		echo '<td>';

		echo '<input';
		if (($i == 0 && count($group->rules) == 1) || $i == count($group->rules))
			echo ' style="display: none;"';
		echo ' type="button" onclick="del_field(this.parentNode.parentNode); return false;" value="-" />';

		echo '<input';
		if ($i+1 != count($group->rules))
			echo ' style="display: none;"';
		echo ' type="button" onclick="add_field(this.parentNode.parentNode); return false;" value="+" />';

		echo '</td>';
	}
	echo '</tr>';

	$i++;
}
echo '</table>';

echo '</td>';
echo '</tr>';
echo '</table>';
echo '<br />';
	if ($can_manage_usersgroups) {
		echo '<input type="submit" value="'._('Update rules').'" />';
		echo '</form>';
	}

    echo '</div>';
	echo '<br />';
  }

  // Users list
if ((count($users_all) > 0 || count($users) > 0) || ($group->isDefault())) {
    echo '<div>';
    echo '<h2>'._('List of users in this group').'</h2>';
    if ($group->isDefault()) {
      echo _('All available users are in this group.');
    }
    else {
      echo '<table border="0" cellspacing="1" cellpadding="3">';

      if (count($users) > 0) {
        foreach($users as $user) {
          echo '<tr>';
          echo '<td><a href="users.php?action=manage&id='.$user.'">'.$user.'</td>';
          echo '<td>';
          if ($usergroupdb_rw && $group->type == 'static' && !$group->isDefault() and $can_manage_usersgroups) {
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

    if ((count ($users_available) >0) && $usergroupdb_rw && $group->type == 'static' and $can_manage_usersgroups) {
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

    if ($usergroupdb_rw && $group->type == 'static' and $can_manage_usersgroups) {
      echo '<br/>';
      echo $search_form;
    }
    echo '</div>';
    echo '<br/>';
  }
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
		if ($can_manage_publications) {
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this publication?').'\');">';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="name" value="Publication" />';
			echo '<input type="hidden" name="group_u" value="'.$id.'" />';
			echo '<input type="hidden" name="group_a" value="'.$groups_app->id.'" />';
			echo '<input type="submit" value="'._('Delete this publication').'" />';
			echo '</form>';
			echo '</td>';
		}
	echo '</tr>';
      }
    }

    if (count ($groups_apps_available) >0 and $can_manage_publications) {
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


	// Policy of this group
	echo '<div>';
	echo '<h2>'._('Policy of this group').'</h2>';
	echo '<table border="0" cellspacing="1" cellpadding="3">';

	foreach($policy as $key => $value) {
		if ($value === false)
			continue;

		$extends_from_default = (in_array($key,$default_policy));
		$buffer = ($extends_from_default===true?' ('._('extend from default').')':'');

		echo '<tr>';
		echo '<td>'.$key.' '.$buffer.'</td>';
		if ($can_manage_usersgroups && ! $extends_from_default) {
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this rule?').'\');">';
			echo '<input type="hidden" name="name" value="UserGroup_PolicyRule" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="id" value="'.$group->getUniqueID().'" />';
			echo '<input type="hidden" name="element" value="'.$key.'" />';
			echo '<input type="submit" value="'._('Delete this rule').'" />';
			echo '</form>';
			echo '</td>';
		}
		echo '</tr>';
	}
	if ($can_manage_usersgroups && count($policy_rules_disable)>0 && (array_search(false, $policy) !== false)) {
		echo '<tr><form action="actions.php" method="post"><td>';
		echo '<input type="hidden" name="name" value="UserGroup_PolicyRule" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="hidden" name="id" value="'.$group->getUniqueID().'" />';
		echo '<select name="element">';

		foreach($policy as $key => $value) {
			if ($value === true)
				continue;

			echo '<option value="'.$key.'" >'.$key.'</option>';
		}
		echo '</select>';
		echo '</td><td><input type="submit" value="'._('Add this rule').'" /></td>';
		echo '</form></tr>';
	}

	echo '</table>';
	echo '</div>';
	echo '<br/>';

    if (Preferences::moduleIsEnabled('SharedFolderDB')) {
		$sharedfolderdb = SharedFolderDB::getInstance();
		$all_sharedfolders = $sharedfolderdb->getList();

		if (count($all_sharedfolders) > 0) {
			$available_sharedfolders = array();
			$used_sharedfolders = $sharedfolderdb->importFromUsergroup($group->getUniqueID());
			foreach ($all_sharedfolders as $sharedfolder) {
				if (in_array($sharedfolder->id, array_keys($used_sharedfolders)))
					continue;

				$available_sharedfolders[] = $sharedfolder;
			}

			echo '<br />';
			echo '<div>';
			echo '<h2>'._('Shared folders').'</h1>';

			echo '<table border="0" cellspacing="1" cellpadding="3">';
			foreach ($used_sharedfolders as $sharedfolder) {
				echo '<tr>';
				echo '<td><a href="sharedfolders.php?action=manage&amp;id='.$sharedfolder->id.'">'.$sharedfolder->name.'</a></td>';
				if ($can_manage_sharedfolders) {
					echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this shared folder access?').'\');">';
					echo '<input type="hidden" name="name" value="SharedFolder_ACL" />';
					echo '<input type="hidden" name="action" value="del" />';
					echo '<input type="hidden" name="sharedfolder_id" value="'.$sharedfolder->id.'" />';
					echo '<input type="hidden" name="usergroup_id" value="'.$group->getUniqueID().'" />';
					echo '<input type="submit" value="'._('Delete access to this shared folder').'" />';
					echo '</form></td>';
				}
				echo '</tr>';
			}

			if (count($available_sharedfolders) > 0 && $can_manage_sharedfolders) {
				echo '<tr><form action="actions.php" method="post"><td>';
				echo '<input type="hidden" name="name" value="SharedFolder_ACL" />';
				echo '<input type="hidden" name="action" value="add" />';
				echo '<input type="hidden" name="usergroup_id" value="'.$group->getUniqueID().'" />';
				echo '<select name="sharedfolder_id">';
				foreach($available_sharedfolders as $sharedfolder)
					echo '<option value="'.$sharedfolder->id.'" >'.$sharedfolder->name.'</option>';
				echo '</select>';
				echo '</td><td><input type="submit" value="'._('Add access to this shared folder').'" /></td>';
				echo '</form></tr>';
			}
			echo '</table>';
			echo '</div>';
		}
		
		echo '<br />';
	}
	echo '<div>'; // Session settings configuration
	echo '<h2>';
	echo _('Session settings configuration');
	echo '</h2>';
	
	if ($prefs_of_a_group != array()) {
		foreach ($prefs_of_a_group as $container => $prefs_of_a_group_value) {
			echo '<fieldset class="prefssessionusergroup">';
			echo '<legend>'.$prefs->getPrettyName($container).'</legend>';
			
			echo '<form action="actions.php" method="post">';
			$key_name = 'general';
			echo '<input type="hidden" name="container" value="'.$container.'" />';
			// from admin/functions.inc.php
			$color=0;
			if (count($prefs_of_a_group_value) != 0) {
				echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3" style="margin-bottom: 10px;">'; // TODO
				echo '<tr  class="title">';
				echo '<th>'._('Name').'</th>';
				echo '<th>'._('Default value').'</th>';
				echo '<th>'._('Value').'</th>';
				echo '<th>'._('Action').'</th>';
				echo '<tr>';
				
				foreach ($prefs_of_a_group_value as $element_key => $usersgroup_preferences) {
					$config_element = $usersgroup_preferences->toConfigElement();
					echo '<tr class="content'.($color % 2 +1).'">';
					echo '<td style="width: 250px;">';
					echo '<span onmouseover="showInfoBulle(\''.str_replace("'", "&rsquo;", $config_element->description_detailed).'\'); return false;" onmouseout="hideInfoBulle(); return false;">'.$config_element->label.'</span>';
					
					echo '<td>';
					$default_element = $session_prefs[$container][$config_element->id];
					$default_element->setFormSeparator('NaN'); // it must be different of ___
					$default_element->setPath(array('key_name' => $key_name, 'container' => $container, 'element_id' => $config_element->id));
					echo $default_element->toHTML(true);
					echo '</td>';
					
					echo '</td>';
					echo '<td style="padding: 3px;">';
					print_element($key_name, $container, $element_key, $config_element);
					echo '</td>';
					
					echo '<td>';
					echo '<input type="button" value="'._('Remove this overriden setting').'" onclick="usergroup_settings_remove(\''.$group->getUniqueID().'\',\''.$container.'\',\''.$config_element->id.'\'); return false;"/>';
					echo '</td>';
					
					echo '</tr>';
					$color++;
				}
			
				// end from
				echo '<tr class="content'.($color % 2 +1).'">';
				echo '<td colspan="3"></td>';
				echo '<td>';
				echo '<input type="hidden" name="name" value="UserGroup_settings" />';
				echo '<input type="hidden" name="container" value="'.$container.'" />';
				echo '<input type="hidden" name="unique_id" value="'.$group->getUniqueID().'" />';
				echo '<input type="hidden" name="action" value="modify" />';
				echo '<input type="submit" value="'._('Save settings').'" />';
				
				echo '</td>';
				echo '</tr>';
				echo '</table>';
				echo '</form>';
			}
			
			if ($unuse_settings[$container] != array()) {
				echo '<form action="actions.php" method="post">';
					echo '<input type="hidden" name="name" value="UserGroup_settings" />';
					echo '<input type="hidden" name="container" value="'.$container.'" />';
					echo '<input type="hidden" name="unique_id" value="'.$group->getUniqueID().'" />';
					echo '<input type="hidden" name="action" value="add" />';
					
				echo '<select name="element_id">';
				foreach ($unuse_settings[$container] as $setting_name => $setting_content) {
					echo '<option value="'.$setting_name.'" >'.$setting_content->label.'</option>';
				}
				echo '</select>';
				echo ' ';
				echo '<input type="submit" value="'._('Add this setting').'" />';
				echo '</form>';
			}
			echo '</fieldset>';
		}
	}
	
	echo '</div>'; // Session settings configuration
	echo "\n\n\n";

  echo '</div>';
  page_footer();
  die();
}
