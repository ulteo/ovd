<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2014
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
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

require_once(dirname(dirname(__FILE__)).'/includes/core.inc.php');
require_once(dirname(dirname(__FILE__)).'/includes/page_template.php');

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

  $usersgroupsList = new UsersGroupsList($_REQUEST);
  $groups = $usersgroupsList->search();
  if (is_array($groups)) {
    uasort($groups, "usergroup_cmp");
  }
  $searchDiv = $usersgroupsList->getForm();

  $has_group = ! (is_null($groups) or (count($groups) == 0));

  $can_manage_usersgroups = isAuthorized('manageUsersGroups');

  page_header();

  echo '<div id="usersgroup_div" >';
  echo '<h1>'._('User Groups').'</h1>';

  echo $searchDiv;
  
  echo '<div id="usersgroup_list">';

  if (! $has_group)
    echo _('No available User Groups').'<br />';
  else {
     $all_static = true;
     foreach($groups as $group){
       if ($group->type != 'static' || usergroupdb_is_writable()) {
         $all_static = false;
         break; // no need to continue;
       }
     }
    echo '<table class="main_sub sortable" id="usergroups_list" border="0" cellspacing="1" cellpadding="5">';
    echo '<thead>';
    echo '<tr class="title">';
    if ( (!$all_static || usergroupdb_is_writable()) and $can_manage_usersgroups and count($groups) > 1) {
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
        if ($group->type != 'static' || usergroupdb_is_writable() and count($groups) > 1) {
          echo '<td><input class="input_checkbox" type="checkbox" name="checked_groups[]" value="'.$group->id.'" /></td>';
        }
        else if ( !$all_static and count($groups) > 1) {
          echo '<td></td>';
        }
      }
      echo '<td><a href="?action=manage&id='.$group->id.'">'.$group->name.'</a></td>';
      echo '<td>'.$group->description.'</td>';
      echo '<td class="centered">'.$publish.'</td>';
      echo '<td class="centered">'.$group->type.'</td>';


      if (($group->type != 'static' || usergroupdb_is_writable()) and $can_manage_usersgroups) {
        echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
        echo '<input type="hidden" name="name" value="UserGroup" />';
        echo '<input type="submit" value="'._('Delete').'"/>';
        echo '<input type="hidden" name="action" value="del" />';
        echo '<input type="hidden" name="checked_groups[]" value="'.$group->id.'" />';
        echo '</form></td>';
      }
      else if ( !$all_static and $can_manage_usersgroups) {
        echo '<td></td>';
      }
      echo '</tr>';
    }
    echo '</tbody>';
    $content = 'content'.(($count++%2==0)?1:2);
    if ( (!$all_static || usergroupdb_is_writable()) and $can_manage_usersgroups and count($groups) > 1) {
      echo '<tfoot>';
      echo '<tr class="'.$content.'">';
      echo '<td colspan="5"><a href="javascript:;" onclick="markAllRows(\'usergroups_list\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'usergroups_list\'); return false">'._('Unmark all').'</a></td>';
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
  if (usergroupdb_is_writable()) {
    $usergroup_types = array('static' => _('Static'));
  }
  if (is_module_enabled('UserGroupDBDynamic') || is_module_enabled('UserGroupDBDynamicCached')) {
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
				if (is_module_enabled('UserGroupDBDynamicCached')) {
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
				$filter_attributes =  array('login', 'displayname');
				foreach ($filter_attributes as $key1 => $value1) {
					if ( $value1 == 'password')
						unset($filter_attributes[$key1]);
				}
				$filter_types = array('equal', 'not_equal', 'contains', 'not_contains', 'startswith', 'not_startswith', 'endswith', 'not_endswith');
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
  
	$group = $_SESSION['service']->users_group_info($id);
  
  if (! is_object($group)) {
    die_error(_('Failed to load User Group'));
  }
  
  $usergroupdb_rw = usergroupdb_is_writable();

  $policy = $group->getAttribute('policy');
  $policy_rule_enable = 0;
  $policy_rules_disable = 0;
  foreach($policy as $key => $value) {
	  if ($value === true)
		  $policy_rule_enable++;
	  else
		  $policy_rules_disable++;
  }

  $default_policy = $group->getAttribute('default_policy');

  if ($group->published) {
    $status = '<span class="msg_ok">'._('Enabled').'</span>';
    $status_change = _('Block');
    $status_change_value = 0;

  } else {
    $status = '<span class="msg_error">'._('Blocked').'</span>';
    $status_change = _('Enable');
    $status_change_value = 1;
  }

  if ($group->isDefault() == false) {
	$users = array();
	if ($group->hasAttribute('users')) {
		$users = $group->getAttribute('users');
	}
	
	$users_partial_list = $group->getAttribute('users_partial_list');
	$usersList = new UsersList($_REQUEST);
	if ($users_partial_list) {
		if ($usersList->is_empty_filter()) {
			$usersList->set_external_result($users, true);
		}
		else {
			$users2 = $usersList->search($id);
			if (is_null($users2)) {
				die_error(_('Error while requesting users'),__FILE__,__LINE__);
			}
			
			$users = array();
			foreach($users2 as $user) {
				$users[$user->getAttribute('login')] = $user->getAttribute('displayname');
			}
		}
	}
	
	asort($users);

    $has_users = (count($users) > 0);

  if ($usergroupdb_rw) {
    $users_all = $usersList->search();
    if (is_null($users_all))
      $users_all = array();
      $users_available = array();
      foreach($users_all as $user) {
	if (! array_key_exists($user->getAttribute('login'), $users))
		$users_available[]= $user->getAttribute('login');
      }
    }
    else {
      $users_available = array();
      $users_all = $users;
      uasort($users_all, "user_cmp");
    }

	$search_form = $usersList->getForm();
  }
  else {
    $users = array();
    $users_available = array();
    $users_all = array();
    $search_form = null;
  }

  // Default usergroup
  $is_default_group = $group->isDefault();

  // Publications
  $groups_apps = array();
	if ($group->hasAttribute('applicationsgroups')) {
		$groups_apps = $group->getAttribute('applicationsgroups');
	}

  $groups_apps_all = $_SESSION['service']->applications_groups_list();
  $groups_apps_available = array();
  foreach($groups_apps_all as $group_apps) {
    if (! array_key_exists($group_apps->id, $groups_apps))
      $groups_apps_available[]= $group_apps;
  }

  // Scripts
  $groups_scripts_all = $_SESSION['service']->scripts_groups_list($id);
  $all_scripts = $_SESSION['service']->scripts_list();

  $scripts_available = array();
  foreach($all_scripts as $script) {
    if (! array_key_exists($script->id, $groups_scripts_all))
      $scripts_available[]= $script;
  }
  
	// Servers publications
	$servers_groups_list = $_SESSION['service']->servers_groups_list();
	$servers_groups_published = array();
	if ($group->hasAttribute('serversgroups')) {
		$servers_groups_published = $group->getAttribute('serversgroups');
	}
  
  $can_manage_scripts = isAuthorized('manageScripts');

  $can_manage_usersgroups = isAuthorized('manageUsersGroups');
  $can_manage_publications = isAuthorized('managePublications');
  $can_manage_sharedfolders = isAuthorized('manageServers');
  
  $prefs_of_a_group = array();
  $unuse_settings = array();
  $session_prefs = array();

		$settings = $_SESSION['service']->users_group_settings_get($id);
		$prefs = new Preferences_admin(null, false);
		$prefs->load($settings);

		$group_settings = array();
		if ($group->hasAttribute('settings')) {
			$group_settings = $group->getAttribute('settings');
		}
		
		$categs = $prefs->getSubKeys('general');
		foreach($categs as $categ) {
			$categ_prefs = $prefs->get_elements('general', $categ);
			
			$session_prefs[$categ] = array();
			$prefs_of_a_group[$categ] = array();
			$unuse_settings[$categ] = array();
			
			foreach($categ_prefs as $setting_id => $p) {
				$session_prefs[$categ][$setting_id] = $p;
				$uid = 'general'.'.'.$categ.'.'.$setting_id;
				if (array_key_exists($uid, $group_settings)) {
					array_push($prefs_of_a_group[$categ], $setting_id);
				}
				else {
					array_push($unuse_settings[$categ], $setting_id);
				}
			}
		}


  page_header();
  echo '<div id="users_div">';
  echo '<h1><a href="?">'._('User Group Management').'</a> - '.$group->name.'</h1>';

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
			echo '<input type="hidden" name="id" value="'.$group->id.'" />';
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

  // User list
if ($group->isDefault() || (count($users_all) > 0 || !$usersList->is_empty_filter() || count($users) > 0)) {
    echo '<div>';
    echo '<h2>'._('List of users in this group').'</h2>';

	if (! is_null($search_form)) {
		echo $search_form;
	}

    if ($group->isDefault()) {
      echo _('All available users are in this group.');
    }
    else {
      echo '<table border="0" cellspacing="1" cellpadding="3">';

      if (count($users) > 0) {
        foreach($users as $user_login => $user_displayname) {
          echo '<tr>';
          echo '<td><a href="users.php?action=manage&id='.$user_login.'">'.$user_login.'</td>';
          echo '<td>';
          if ($usergroupdb_rw && $group->type == 'static' && !$group->isDefault() and $can_manage_usersgroups) {
            echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this user?').'\');">';
            echo '<input type="hidden" name="action" value="del" />';
            echo '<input type="hidden" name="name" value="User_UserGroup" />';
            echo '<input type="hidden" name="group" value="'.$id.'" />';
            echo '<input type="hidden" name="element" value="'.$user_login.'" />';
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
      foreach($groups_apps as $groups_app_id => $groups_app_name) {
	echo '<tr>';
	echo '<td><a href="appsgroup.php?action=manage&id='.$groups_app_id.'">'.$groups_app_name.'</td>';
		if ($can_manage_publications) {
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this publication?').'\');">';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="name" value="Publication" />';
			echo '<input type="hidden" name="group_u" value="'.$id.'" />';
			echo '<input type="hidden" name="group_a" value="'.$groups_app_id.'" />';
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

  // Scripts part
  if (count($groups_scripts_all) > 0) {
    echo '<div>';
    echo '<h2>'._('List of scripts for this group').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';


    if (count($groups_scripts_all) > 0) {
      foreach($groups_scripts_all as $script) {

	echo '<tr>';
	echo '<td><a href="script.php?action=manage&amp;id='.$script["id"].'">'.$script["name"].'</a></td>';
		if ($can_manage_scripts) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this user from this group?').'\');">';
			echo '<input type="hidden" name="name" value="Script_UserGroup" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="group" value="'.$id.'" />';
			echo '<input type="hidden" name="element" value="'.$script["id"].'" />';
			echo '<input type="submit" value="'._('Delete from this group').'" />';
			echo '</form></td>';
		}
	echo '</tr>';
      }
    }

    if ((count($scripts_available) > 0) and $can_manage_scripts) {
      echo '<tr><form action="actions.php" method="post"><td>';
      echo '<input type="hidden" name="action" value="add" />';
      echo '<input type="hidden" name="name" value="Script_UserGroup" />';
      echo '<input type="hidden" name="group" value="'.$id.'" />';
      echo '<select name="element">';
      foreach($scripts_available as $script)
        echo '<option value="'.$script->id.'" >'.$script->name.'</option>';
      echo '</select>';
      echo '</td><td><input type="submit" value="'._('Add this script').'" /></td>';
      echo '</form></tr>';
    }
    echo '</table>';
    echo '</div>';
  }


	// Servers publications part
	if (count($servers_groups_list)>0) {
		echo '<div>';
		echo '<h2>'._('List of published Server Groups for this group').'</h1>';
		echo '<table border="0" cellspacing="1" cellpadding="3">';

		if (count($servers_groups_published)>0) {
			foreach($servers_groups_published as $servers_group_id => $servers_group_name) {
				echo '<tr>';
				echo '<td><a href="serversgroup.php?action=manage&id='.$servers_group_id.'">'.$servers_group_name.'</td>';
				if ($can_manage_publications) {
					echo '<td>';
					echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this publication?').'\');">';
					echo '<input type="hidden" name="action" value="del" />';
					echo '<input type="hidden" name="name" value="UsersGroupServersGroup" />';
					echo '<input type="hidden" name="users_group" value="'.$id.'" />';
					echo '<input type="hidden" name="servers_group" value="'.$servers_group_id.'" />';
					echo '<input type="submit" value="'._('Delete this publication').'" />';
					echo '</form>';
					echo '</td>';
				}
				
				echo '</tr>';
			}
		}
		
		if (count($servers_groups_list) > count($servers_groups_published) and $can_manage_publications) {
			echo '<tr><form action="actions.php" method="post"><td>';
			echo '<input type="hidden" name="action" value="add" />';
			echo '<input type="hidden" name="name" value="UsersGroupServersGroup" />';
			echo '<input type="hidden" name="users_group" value="'.$id.'" />';
			echo '<select name="servers_group">';
			foreach($servers_groups_list as $servers_group) {
				if (array_key_exists($servers_group->id, $servers_groups_published)) {
					continue;
				}
				
				echo '<option value="'.$servers_group->id.'" >'.$servers_group->name.'</option>';
			}
			
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

		$extends_from_default = ($default_policy[$key] === $value);
		$buffer = ($extends_from_default===true?' ('._('extend from default').')':'');

		echo '<tr>';
		echo '<td>'.$key.' '.$buffer.'</td>';
		if ($can_manage_usersgroups && ! $extends_from_default) {
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this rule?').'\');">';
			echo '<input type="hidden" name="name" value="UserGroup_PolicyRule" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="id" value="'.$group->id.'" />';
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
		echo '<input type="hidden" name="id" value="'.$group->id.'" />';
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

    if (is_module_enabled('SharedFolderDB')) {
		$all_sharedfolders = $_SESSION['service']->shared_folders_list();
		if (is_null($all_sharedfolders)) {
			$all_sharedfolders = array();
		}

		if (count($all_sharedfolders) > 0) {
			$available_sharedfolders = array();
			
			$used_sharedfolders = array();
			if ($group->hasAttribute('shared_folders')) {
				$data = $group->getAttribute('shared_folders');
				
				$mods_by_share = array();
				foreach($data as $mode => $used_sharedfolders2) {
					foreach($used_sharedfolders2 as $share_id => $share_name) {
						$used_sharedfolders[$share_id] = $share_name;
						
						$mods_by_share[$share_id] = $mode;
					}
				}
			}
			
			foreach ($all_sharedfolders as $sharedfolder) {
				if (array_key_exists($sharedfolder->id, $used_sharedfolders))
					continue;

				$available_sharedfolders[] = $sharedfolder;
			}

			echo '<br />';
			echo '<div>';
			echo '<h2>'._('Shared Folders').'</h1>';

			echo '<table border="0" cellspacing="1" cellpadding="3">';
			foreach ($used_sharedfolders as $sharedfolder_id => $sharedfolder_name) {
				echo '<tr>';
				echo '<td><a href="sharedfolders.php?action=manage&amp;id='.$sharedfolder_id.'">'.$sharedfolder_name.'</a></td>';
				echo '<td>'.$mods_by_share[$sharedfolder_id].'</td>';
				if ($can_manage_sharedfolders) {
					echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this shared folder access?').'\');">';
					echo '<input type="hidden" name="name" value="SharedFolder_ACL" />';
					echo '<input type="hidden" name="action" value="del" />';
					echo '<input type="hidden" name="sharedfolder_id" value="'.$sharedfolder_id.'" />';
					echo '<input type="hidden" name="usergroup_id" value="'.$group->id.'" />';
					echo '<input type="submit" value="'._('Delete access to this shared folder').'" />';
					echo '</form></td>';
				}
				echo '</tr>';
			}

			if (count($available_sharedfolders) > 0 && $can_manage_sharedfolders) {
				echo '<tr><form action="actions.php" method="post"><td>';
				echo '<input type="hidden" name="name" value="SharedFolder_ACL" />';
				echo '<input type="hidden" name="action" value="add" />';
				echo '<input type="hidden" name="usergroup_id" value="'.$group->id.'" />';
				echo '<select name="sharedfolder_id">';
				foreach($available_sharedfolders as $sharedfolder)
					echo '<option value="'.$sharedfolder->id.'" >'.$sharedfolder->name.'</option>';
				echo '</select>';
				echo '</td><td>';
				echo '<select name="mode">';
				echo '<option value="rw" >'._('Read-write').'</option>';
				echo '<option value="ro" >'._('Read only').'</option>';
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
	echo _('Session Settings configuration');
	echo '</h2>';
	
	if (count($session_prefs)>0) {
		$key_name = 'general';
		
		foreach ($session_prefs as $container => $container_prefs) {
			echo '<fieldset class="prefssessionusergroup">';
			echo '<legend>'.$prefs->getPrettyName($key_name.'.'.$container).'</legend>';
			
			echo '<form action="actions.php" method="post">';
			echo '<input type="hidden" name="container" value="'.$container.'" />';
			// from admin/functions.inc.php
			$color=0;

			echo '<table class="main_sub" id="settings_table_'.$container.'" border="0" cellspacing="1" cellpadding="3" style="margin-bottom: 10px;'.((count($prefs_of_a_group[$container])==0)?' display: none;':'').' ">';
			echo '<thead>';
			echo '<tr  class="title">';
			echo '<th>'._('Name').'</th>';
			echo '<th>'._('Group value').'</th>';
			echo '<th>'._('Action').'</th>';
			echo '</tr>';
			echo '</thead><tbody>';
			
			foreach ($container_prefs as $element_key => $config_element) {
				if (! in_array($element_key, $prefs_of_a_group[$container])) {
					continue;
				}
				
				print_overriden_pref($key_name, $container, $config_element, $group_settings[$key_name.'.'.$container.'.'.$element_key], 'content'.(($color++) % 2 +1));
			}
			
			// end from
			echo '</tbody><tfoot>';
			echo '<tr class="content'.($color % 2 +1).'" id="foot_'.$container.'" style="display: none;">';
			echo '<td colspan="2"></td>';
			echo '<td>';
			echo '<input type="hidden" name="name" value="UserGroup_settings" />';
			echo '<input type="hidden" name="container" value="'.$container.'" />';
			echo '<input type="hidden" name="unique_id" value="'.$group->id.'" />';
			echo '<input type="hidden" name="action" value="modify" />';
			echo '<input type="submit" value="'._('Save settings').'" style="background-color: red; font-weight:bold;"/>';
			
			echo '</td>';
			echo '</tr></tfoot>';
			echo '</table>';
			
			echo '</form>';
			
			echo '<form '.((count($unuse_settings[$container])==0)?'style="display: none;"':'').' onsubmit="configuration_add(\''.$container.'\', this.settings.value); return false;">';
			echo '<select id="settings_select_'.$container.'" name="settings" onchange="configuration_add(\''.$container.'\', this.value); return false;">';
			foreach ($session_prefs[$container] as $setting_name => $setting_content) {
				if (in_array($setting_name, $prefs_of_a_group[$container])) {
					continue;
				}
				
				echo '<option value="'.$setting_name.'" >'.$setting_content->label.'</option>';
			}
			echo '</select>';
			echo ' ';
			echo '<input type="submit" value="'._('Add this setting').'" />';
			echo '</form>';
			
			echo '<div style="display: none;">'; // Content for javascript purpose
			echo '<select id="cache_select_'.$container.'">';
			foreach ($session_prefs[$container] as $setting_name => $setting_content) {
				echo '<option value="'.$setting_name.'" >'.$setting_content->label.'</option>';
			}
			echo '</select>';
			
			echo '<table id="cache_table_'.$container.'"><tbody>';
			foreach ($session_prefs[$container] as $element_key => $config_element) {
				if (in_array($element_key, $prefs_of_a_group[$container])) {
					continue;
				}
				
				print_overriden_pref($key_name, $container, $config_element);
			}
			
			echo '</tbody></table>';
			echo '</div>';
			
			echo '<div>';
			foreach ($session_prefs[$container] as $element_key => $config_element) {
				$uid = $key_name.'.'.$container.'.'.$element_key;
				$setting = $settings[$uid];
				
				$extra = array();
				if (array_key_exists('global', $setting['values'])) {
					array_push($extra, array('title' => _('Global value'), 'value' => $setting['values']['global']));
				}
				
				print_pref_values($key_name, $container, $config_element, $setting['value'], $extra);
			}
			
			echo '</div>';
			
			echo '<script>';
			echo 'Event.observe(window, \'load\', function() {';
			echo 'configuration_change_monitor(\''.$container.'\')';
			echo '});';
			echo '</script>';
			
			echo '</fieldset>';
		}
	}
	
	echo '</div>'; // Session Settings configuration
	echo "\n\n\n";

  echo '</div>';
  page_footer();
  die();
}
