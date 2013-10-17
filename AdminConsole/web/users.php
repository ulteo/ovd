<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2008, 2009, 2010, 2011, 2012, 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

if (! checkAuthorization('viewUsers'))
	redirect();



if (isset($_REQUEST['action'])) {
  if ($_REQUEST['action']=='manage') {
    if (isset($_REQUEST['id']))
      show_manage($_REQUEST['id']);
  }
}

if (! isset($_GET['view']))
	$_GET['view'] = 'all';

if ($_GET['view'] == 'all')
	show_default();

function show_default() {
	$usersList = new UsersList($_REQUEST);
	$us = $usersList->search();
	$searchDiv = $usersList->getForm();

  $total_us = count($us);

  $users_list_empty = (is_null($us) or count($us)==0);
	$userdb_rw = userdb_is_writable();

	$can_manage_users = isAuthorized('manageUsers');

  page_header();
  echo '<div id="users_div">';
  echo '<h1>'._('Users').'</h1>';

  echo $searchDiv;

  echo '<div id="users_list_div">';

  if ($users_list_empty)
    echo _('No available users').'<br />';
  else {
    echo '<table class="main_sub sortable" id="user_list_table" border="0" cellspacing="1" cellpadding="5">';
    echo '<thead>';
    echo '<tr class="title">';
    if ($userdb_rw and $can_manage_users and $total_us > 1)
      echo '<th class="unsortable"></th>';
    echo '<th>'._('Login').'</th>';
    echo '<th>'._('Display name').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $count = 0;
    foreach($us as $u){
      $content = 'content'.(($count++%2==0)?1:2);

      echo '<tr class="'.$content.'">';
      if ($userdb_rw and $can_manage_users and $total_us > 1)
        echo '<td><input class="input_checkbox" type="checkbox" name="checked_users[]" value="'.htmlspecialchars($u->getAttribute('login')).'" /></td>';
      echo '<td><a href="users.php?action=manage&id='.urlencode($u->getAttribute('login')).'">';
      echo $u->getAttribute('login');
      echo '</a></td>';
      echo '<td>'.$u->getAttribute('displayname').'</td>';

      if ($userdb_rw and $can_manage_users) {
	echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this user?').'\');">';
	echo '<input type="submit" value="'._('Delete').'"/>';
	echo '<input type="hidden" name="name" value="User" />';
	echo '<input type="hidden" name="action" value="del" />';
	echo '<input type="hidden" name="checked_users[]" value="'.htmlspecialchars($u->getAttribute('login')).'" />';
	echo '</form></td>';
      }
      echo '</tr>';
    }
    echo '</tbody>';
    if ($userdb_rw and $can_manage_users and $total_us > 1) {
      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tfoot>';
      echo '<tr class="'.$content.'">';
      echo '<td colspan="3">';
      echo '<a href="javascript:;" onclick="markAllRows(\'user_list_table\'); return false">'._('Mark all').'</a>';
      echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'user_list_table\'); return false">'._('Unmark all').'</a>';
      echo '</td>';
      echo '<td>';
      echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete the selected users?').'\') && updateMassActionsForm(this, \'user_list_table\');;">';
      echo '<input type="hidden" name="name" value="User" />';
      echo '<input type="hidden" name="action" value="del" />';
      echo '<input type="submit" value="'._('Delete').'"/><br />';
      echo '</form>';
      echo '</td>';
      echo '</tr>';
      echo '</tfoot>';
    }
    echo '</table>';
  }

  if ($userdb_rw and $can_manage_users) {
    echo '<h2>'._('Add').'</h2>';
    echo '<div id="user_add">';
    echo '<form action="actions.php" method="post">';
    echo '<input type="hidden" name="action" value="add" />';
    echo '<input type="hidden" name="name" value="User" />';

    echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';

    $content_color = 1;
    $minimun_attributes = array('login' => _('Login'), 'displayname' => _('Display name'),  'password' => _('Password'));
    foreach ($minimun_attributes as $minimun_attribute => $minimun_attribute_msg) {
      echo '<tr class="content'.$content_color.'">';
      echo '<th>'.$minimun_attribute_msg.'</th>';
      echo '<td><input type="'.(($minimun_attribute=='password')?'password':'text').'" name="'.$minimun_attribute.'" value="" /></td>';
      echo '</tr>';
      $content_color = (($content_color++)%2)+1;
    }

    echo '<tr class="content'.$content_color.'">';
    echo '<td colspan="2">';
    echo '<input type="submit" name="add" value="'._('Add').'" />';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</form>';
    echo '</div>';
    
    echo '<br />';
    echo '<h2>'._('Populate').'</h2>';
    echo '<div id="user_populate">';
    echo '<p>';
    echo _('This feature is useful to create default accounts. It is mainly used when you want to test the system quickly. It is not recommended to use those default accounts on a production system because it is very easy to delete them by mistake using this "populate" feature.');
    echo '</p>';
    
    echo '<form action="actions.php" method="post">';
    echo '<input type="hidden" name="name" value="User" />';
    echo '<input type="hidden" name="action" value="populate" />';
    
    echo '<table border="0" cellspacing="1" cellpadding="3">';
    echo '<tr>';
    echo '<td>'._('Overwrite existing: ').'</td>';
    echo '<td><select name="override">';
    echo '<option value="1">'._('Yes').'</option>';
    echo '<option value="0" selected="selected">'._('No').'</option>';
    echo '</select></td>';
    echo '</tr><tr>';
    echo '<td>'._('Password management:').'</td>';
    echo '<td><select name="password" id="password_management_select" onchange="';
    echo '  if( $(\'password_str\').style.visibility== \'hidden\' )';
    echo '    $(\'password_str\').style.visibility = \'\';';
    echo '  else';
    echo '    $(\'password_str\').style.visibility = \'hidden\';';
    echo '">';
    echo '<option value="login">'._('Same as username').'</option>';
    echo '<option value="custom">'._('Custom:').'</option>';
    echo '</select></td>';
    echo '<td><input type="text" name="password_str" value="" id="password_str" style="visibility: hidden"/></td>';
    echo '</tr><tr>';
    echo '<td></td><td><input type="submit" value="'._('Populate').'"/></td>';
    echo '</tr></table>';
    echo '</form>';
    echo '</div>';
  }


  echo '</div>';
  echo '</div>';
  page_footer();
  die();
}

function show_manage($login) {
	$u = $_SESSION['service']->user_info($login);
  if (! is_object($u))
    die_error('Unable to import user "'.$login.'"',__FILE__,__LINE__);

  $userdb_rw = userdb_is_writable();
  $usergroupdb_rw = usergroupdb_is_writable();

  $keys = array();
  foreach($u->getAttributesList() as $attr)
    if (! in_array($attr, array('login', 'displayname')))
      $keys[]= $attr;

  // Users Group
  $groups_mine = $u->getAttribute('groups');
	$groups_partial_list = $u->getAttribute('groups_partial_list');
	$usersgroupsList = new UsersGroupsList($_REQUEST);
	if ($groups_partial_list) {
		if ($usersgroupsList->is_empty_filter()) {
			$usersgroupsList->set_external_result($groups_mine, true);
		}
		else {
			$groups_mine2 = $usersgroupsList->search($login);
			if (is_null($groups_mine)) {
				die_error(_('Error while requesting User Group data'),__FILE__,__LINE__);
			}
			
			$groups_mine = array();
			foreach($groups_mine2 as $group) {
				$groups_mine[$group->id] = $group->name;
			}
		}
	}
	
  uasort($groups_mine, 'usergroup_cmp');

  $groups_available = array();
	$default_group_id = null;
	if ($usergroupdb_rw) {
		// do not request other groups if we do not display the 'add to' panel ...
		$groups_all = $usersgroupsList->search();
		usort($groups_all, "usergroup_cmp");
		
		foreach($groups_all as $group) {
			if (! array_key_exists($group->id, $groups_mine))
				$groups_available[]= $group;
			
			if ($group->isDefault()) {
				$default_group_id = $group->id;
			}
		}
	}
	uasort($groups_available, 'usergroup_cmp');
	
	// need to be after all search call!
	$searchDiv = $usersgroupsList->getForm();

	$can_manage_users = isAuthorized('manageUsers');
	$can_manage_usersgroups = isAuthorized('manageUsersGroups');
	$can_manage_profiles = isAuthorized('manageSharedFolders');

  
  $prefs_of_a_user = array();
  $unuse_settings = array();
  $session_prefs = array();
  
	$settings = $_SESSION['service']->user_settings_get($login);
	$prefs = new Preferences_admin(null, false);
	$prefs->load($settings);
	
	$user_settings = array();
	if ($u->hasAttribute('settings')) {
		$user_settings = $u->getAttribute('settings');
	}
	
	$categs = $prefs->getSubKeys('general');
	foreach($categs as $categ) {
		$categ_prefs = $prefs->get_elements('general', $categ);
		
		$session_prefs[$categ] = array();
		$prefs_of_a_user[$categ] = array();
		$unuse_settings[$categ] = array();
		
		foreach($categ_prefs as $setting_id => $p) {
			$session_prefs[$categ][$setting_id] = $p;
			$uid = 'general'.'.'.$categ.'.'.$setting_id;
			if (array_key_exists($uid , $user_settings)) {
				array_push($prefs_of_a_user[$categ], $setting_id);
			}
			else {
				array_push($unuse_settings[$categ], $setting_id);
			}
		}
	}
	
	$applications = array();
	if ($u->hasAttribute('applications')) {
		$applications = $u->getAttribute('applications');
		uasort($applications, 'application_cmp');
	}
	

  page_header();

  echo '<div id="users_div">';
  echo '<h1>'.$u->getAttribute('displayname').'</h1>';

  echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
  echo '<tr class="title">';
  echo '<th>'._('Login').'</th>';
  echo '<th>'._('Locale').'</th>';
  echo '</tr>';

  echo '<tr class="content1">';
   echo '<td>'.$u->getAttribute('login').'</td>';
  echo '<td>'.$u->getAttribute('locale').'</td>';
  echo '</tr>';
  echo '</table>';

  if ($userdb_rw and $can_manage_users) {
    echo '<div>';
    echo '<h2>'._('Settings').'</h2>';

    echo '<div>';
    echo '<form action="actions.php" onsubmit="return confirm(\''._('Are you sure you want to delete this user?').'\');">';
    echo '<input type="submit" value="'._('Delete this user').'"/>';
    echo '<input type="hidden" name="name" value="User" />';
    echo '<input type="hidden" name="action" value="del" />';
    echo '<input type="hidden" name="checked_users[]" value="'.htmlspecialchars($login).'" />';
    echo '</form>';
    echo '</div>';
    echo '<br/><br/>';

    echo '<div>';
    echo '<form action="actions.php" method="post">';
    echo '<input type="hidden" name="name" value="User" />';
    echo '<input type="hidden" name="action" value="modify" />';
    echo '<input type="hidden" name="id" value="'.htmlspecialchars($login).'" />';
    echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';

    $count = 0;
    $content = 'content'.(($count++%2==0)?1:2);
    echo '<tr class="'.$content.'">';
    echo '<th>'._('Display name').'</th>';
    echo '<td><input type="text" name="displayname" value="'.htmlspecialchars($u->getAttribute('displayname')).'" /></td>';
    echo '</tr>';

    if ($u->hasAttribute('password')) {
      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tr class="'.$content.'">';
      echo '<th>'._('New password').'</th>';
      echo '<td><input type="password" name="password" value="" /></td>';
      echo '</tr>';
    }

    $content = 'content'.(($count%2==0)?1:2);
    echo '<tr class="'.$content.'">';
    echo '<td colspan="2">';
    echo '<input type="submit" name="modify" value="'._('Save changes').'" />';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</form>';
    echo '</div>';
  }

  // User groups part
  if (count($groups_mine)>0 or !$usersgroupsList->is_empty_filter() or ($usergroupdb_rw and count($groups_all)>0)) {
    echo '<div>';
    echo '<h2>'._('User Groups with this user').'</h2>';

    echo $searchDiv;
    echo '<table border="0" cellspacing="1" cellpadding="3">';

    foreach ($groups_mine as $group_id => $group_name) {
      echo '<tr><td>';
      if ($can_manage_usersgroups) {
        echo '<a href="usersgroup.php?action=manage&id='.$group_id.'">'.$group_name.'</a>';
      }
      else {
        echo $group_name;
      }
      echo '</td>';
      if ($usergroupdb_rw and $can_manage_usersgroups and $group_id != $default_group_id) {
        echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this user from this group?').'\');">';
        echo '<input type="hidden" name="name" value="User_UserGroup" />';
        echo '<input type="hidden" name="action" value="del" />';
        echo '<input type="hidden" name="group" value="'.$group_id.'" />';
        echo '<input type="hidden" name="element" value="'.htmlspecialchars($login).'" />';
        echo '<input type="submit" value="'._('Delete from this group').'" />';
        echo '</form></td>';
      }
      echo '</tr>';
    }

    if ((count ($groups_available) >0) && $usergroupdb_rw and $can_manage_usersgroups) {
      echo '<tr><form action="actions.php" method="post"><td>';
      echo '<input type="hidden" name="action" value="add" />';
      echo '<input type="hidden" name="name" value="User_UserGroup" />';
      echo '<input type="hidden" name="element" value="'.htmlspecialchars($login).'" />';
      echo '<select name="group">';
      foreach($groups_available as $group)
        echo '<option value="'.$group->id.'" >'.$group->name.'</option>';
      echo '</select>';
      echo '</td><td><input type="submit" value="'._('Add to this group').'" /></td>';
      echo '</form></tr>';
    }
    echo '</table>';
    echo "</div>\n";
  }

  $apps_s = array();
  if (count($applications) > 0) {
    echo '<br />';
    echo '<h2>'._('Published Applications').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';
    foreach ($applications as $application_id => $application_name) {
      echo '<tr>';
      echo '<td><img class="icon32" src="media/image/cache.php?id='.$application_id.'" alt="" title="" /></td>';
      echo '<td><a href="applications.php?action=manage&id='.$application_id.'">'.$application_name.'</a></td>';
      echo '</tr>';
    }
    echo '</table>';
  }

		echo '<div>'; // Session settings configuration
		echo '<h2>';
		echo _('Session settings configuration');
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
				
				echo '<table class="main_sub" id="settings_table_'.$container.'" border="0" cellspacing="1" cellpadding="3" style="margin-bottom: 10px;'.((count($prefs_of_a_user[$container])==0)?' display: none;':'').' ">';
				echo '<thead>';
				echo '<tr  class="title">';
				echo '<th>'._('Name').'</th>';
				echo '<th>'._('Value').'</th>';
				echo '<th>'._('Action').'</th>';
				echo '</tr>';
				echo '</thead><tbody>';
				
				foreach ($container_prefs as $element_key => $config_element) {
					if (! in_array($element_key, $prefs_of_a_user[$container])) {
						continue;
					}
					
					print_overriden_pref($key_name, $container, $config_element, $user_settings[$key_name.'.'.$container.'.'.$element_key], 'content'.(($color++) % 2 +1));
				}
				
				// end from
				echo '</tbody><tfoot>';
				echo '<tr class="content'.($color % 2 +1).'" id="foot_'.$container.'" style="display: none;">';
				echo '<td colspan="2"></td>';
				echo '<td>';
				echo '<input type="hidden" name="name" value="User_settings" />';
				echo '<input type="hidden" name="container" value="'.$container.'" />';
				echo '<input type="hidden" name="unique_id" value="'.$u->getAttribute('login').'" />';
				echo '<input type="hidden" name="action" value="modify" />';
				echo '<input type="submit" value="'._('Save settings').'" style="background-color: red; font-weight:bold;"/>';
				
				echo '</td>';
				echo '</tr></tfoot>';
				echo '</table>';
				
				echo '</form>';
				
				echo '<form '.((count($unuse_settings[$container])==0)?'style="display: none;"':'').' onsubmit="configuration_add(\''.$container.'\', this.settings.value); return false;">';
				echo '<select id="settings_select_'.$container.'" name="settings" onchange="configuration_add(\''.$container.'\', this.value); return false;">';
				foreach ($session_prefs[$container] as $setting_name => $setting_content) {
					if (in_array($setting_name, $prefs_of_a_user[$container])) {
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
					if (in_array($element_key, $prefs_of_a_user[$container])) {
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
					
					if (array_key_exists('groups', $setting['values'])) {
						foreach($setting['values']['groups'] as $group_id => $info) {
							array_push($extra, array(
								'title' => str_replace('%NAME%', $info['group'], _('Users group %NAME%')),
								'value' => $info['value']));
						}
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
		
		echo '</div>'; // Session settings configuration
		echo "\n\n\n";

  if ($u->hasAttribute('sessions') && count($u->getAttribute('sessions')) > 0) {
    echo '<div>';
    echo '<h2>'._('Active sessions').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';
    foreach($u->getAttribute('sessions') as $session_id => $session_start_time) {
      echo '<tr>';
      echo '<td>';
      if (! $session_start_time)
        echo _('Not started yet');
      else
        echo @date('d/m/Y H:i:s', $session_start_time);
      echo '</td>';
      echo '<td><a href="sessions.php?info='.$session_id.'">'.$session_id.'</td>';
      echo '<td></td>';
      echo '<td>';
      echo '<form action="sessions.php">';
      echo '<input type="hidden" name="info" value="'.$session_id.'" />';
      echo '<input type="submit" value="'._('Information about this session').'" />';
      echo '</form>';
      echo '</td>';
      echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
  }

	$use_profiles = false;
	if (array_key_exists('general.session_settings_defaults.enable_profiles', $settings)) {
		if ($settings['general.session_settings_defaults.enable_profiles']['value'] == 1) {
			$use_profiles = true;
		}
	}
	
	if ($use_profiles) {
		echo '<div>';
		echo '<h2>'._('Profile').'</h2>';
		
		if (! $u->hasAttribute('profiles')) {
			echo '<p>';
			echo _('This user doesn\'t have a user profile');
			echo '</p>';
			if ($can_manage_profiles) {
				echo '<div>';
				echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to create a profile for this user?').'\');">';
				echo '<input type="hidden" name="name" value="Profile" />';
				echo '<input type="hidden" name="action" value="add" />';
				echo '<input type="hidden" name="users[]" value="'.$u->getAttribute('login').'" />';
				echo '<input type="submit" value="'._('Create a profile').'" />';
				echo '</form>';
				echo '</div>';
			}
			
		}
		else {
			foreach($u->getAttribute('profiles') as $profile_id => $profile_info) {
				echo '<p>';
				echo str_replace(
					'%SERVER%',
					'<a href="servers.php?action=manage&id='.$profile_info['server_id'].'"> '.$profile_info['server_name'].'</a>',
					_('User has profile stored on server %SERVER%.'));
				echo '</p>';
				if ($can_manage_profiles) {
					echo '<div>';
					echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this profile?').'\');">';
					echo '<input type="hidden" name="name" value="Profile" />';
					echo '<input type="hidden" name="action" value="del" />';
					echo '<input type="hidden" name="ids[]" value="'.$profile_id.'" />';
					echo '<input type="submit" value="'._('Delete this profile').'" />';
					echo '</form>';
					echo '</div>';
				}
			}
		}
		echo '</div>';
	}

  echo '</div>';
  page_footer();
  die();
}
