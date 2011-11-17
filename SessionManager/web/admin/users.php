<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2008, 2009, 2010, 2011
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

if (! checkAuthorization('viewUsers'))
	redirect();

$userDB = UserDB::getInstance();
$userGroupDB = UserGroupDB::getInstance();


if (isset($_REQUEST['action'])) {
  if ($_REQUEST['action']=='manage') {
    if (isset($_REQUEST['id']))
      show_manage($_REQUEST['id'], $userDB, $userGroupDB);
  }
}

if (! isset($_GET['view']))
	$_GET['view'] = 'all';

if ($_GET['view'] == 'all')
	show_default($userDB);

function show_default($userDB) {
	$usersList = new UsersList($_REQUEST);
	$us = $usersList->search();
	$searchDiv = $usersList->getForm();

  $total_us = count($us);

  $users_list_empty = (is_null($us) or count($us)==0);
  $userdb_rw = $userDB->isWriteable();

	$can_manage_users = isAuthorized('manageUsers');

  page_header();
  echo '<div id="users_div">';
  echo '<h1>'._('Users').'</h1>';

  echo $searchDiv;

  echo '<div id="users_list_div">';

  if ($users_list_empty)
    echo _('No available user').'<br />';
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
      echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete selected users?').'\') && updateMassActionsForm(this, \'user_list_table\');;">';
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
    $minimun_attributes = array('login', 'displayname',  'password');
    foreach ($minimun_attributes as $minimun_attribute) {
      echo '<tr class="content'.$content_color.'">';
      echo '<th>'._($minimun_attribute).'</th>';
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

function show_manage($login, $userDB, $userGroupDB) {
  $u = $userDB->import($login);
  if (! is_object($u))
    die_error('Unable to import user "'.$login.'"',__FILE__,__LINE__);

  $userdb_rw = $userDB->isWriteable();
  $usergroupdb_rw = $userGroupDB->isWriteable();

  $keys = array();
  foreach($u->getAttributesList() as $attr)
    if (! in_array($attr, array('login', 'displayname')))
      $keys[]= $attr;

  // Users Group
  $groups_mine = $u->usersGroups();
  if (is_null($groups_mine))
    die_error(_('Error while requesting usergroups'),__FILE__,__LINE__);
  usort($groups_mine, 'usergroup_cmp');

  $groups_available = array();
	if ($usergroupdb_rw) {
		$groups_all = $userGroupDB->getList(true);
		foreach($groups_all as $group)
			if (! in_array($group, $groups_mine))
				$groups_available[]= $group;
	}

  // Sessions
  $sessions = Abstract_Session::getByUser($login);
  $has_sessions = count($sessions);

	$can_manage_users = isAuthorized('manageUsers');
	$can_manage_usersgroups = isAuthorized('manageUsersGroups');

  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);
  
  $prefs_to_get_for_a_user = array('session_settings_defaults', 'remote_desktop_settings',  'remote_applications_settings');
  $prefs_of_a_user = array();
  $unuse_settings = array();
  $session_prefs = array();
  
  foreach ($prefs_to_get_for_a_user as $prefs_to_get_for_a_user_value) {
    $prefs_of_a_user[$prefs_to_get_for_a_user_value] = array();
    $unuse_settings[$prefs_to_get_for_a_user_value] = array();
    
    $session_prefs[$prefs_to_get_for_a_user_value] = $prefs->getElements('general', $prefs_to_get_for_a_user_value);
    $prefs_of_a_user_unsort = Abstract_User_Preferences::loadByUserLogin($u->getAttribute('login'), 'general', $prefs_to_get_for_a_user_value);
 
    foreach ($session_prefs[$prefs_to_get_for_a_user_value] as $k4 => $v4) {
      if (array_key_exists($k4, $prefs_of_a_user_unsort)) {
        $prefs_of_a_user[$prefs_to_get_for_a_user_value][$k4] = $prefs_of_a_user_unsort[$k4];
      }
      else {
        $unuse_settings[$prefs_to_get_for_a_user_value][$k4] = $v4;
      }
    }
  }

  page_header();

  echo '<div id="users_div">';
  echo '<h1>'.$u->getAttribute('displayname').'</h1>';

  echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
  echo '<tr class="title">';
  echo '<th>'._('Login').'</th>';
  foreach($keys as $key)
    if ($key != 'password')
      echo '<th>'.$key.'</th>';
  echo '<th>'._('Locale').'</th>';
  echo '</tr>';

  echo '<tr class="content1">';
   echo '<td>'.$u->getAttribute('login').'</td>';
  foreach($keys as $key)
    if ($key != 'password') {
      if (is_array($u->getAttribute($key)))
        $buf = implode(", ", $u->getAttribute($key));
      else
        $buf = $u->getAttribute($key);
      echo '<td>'.$buf.'</td>';
    }
  echo '<td>'.$u->getLocale().'</td>';
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

    foreach($keys as $key) {
      if ($key == 'password')
	continue;

      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tr class="'.$content.'">';
      echo '<th>'.$key.'</th>';
      echo '<td><input type="text" name="'.$key.'" value="'.$u->getAttribute($key).'" /></td>';
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
  if (count($groups_mine)>0 or count($groups_all)>0) {
    echo '<div>';
    echo '<h2>'._('User groups with this user').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';

    foreach ($groups_mine as $group) {
      echo '<tr><td>';
      if ($can_manage_usersgroups) {
        echo '<a href="usersgroup.php?action=manage&id='.$group->getUniqueID().'">'.$group->name.'</a>';
      }
      else {
        echo $group->name;
      }
      echo '</td>';
      if ($usergroupdb_rw and $can_manage_usersgroups and ($group->isDefault() == false)) {
        echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this user from this group?').'\');">';
        echo '<input type="hidden" name="name" value="User_UserGroup" />';
        echo '<input type="hidden" name="action" value="del" />';
        echo '<input type="hidden" name="group" value="'.$group->getUniqueID().'" />';
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
        echo '<option value="'.$group->getUniqueID().'" >'.$group->name.'</option>';
      echo '</select>';
      echo '</td><td><input type="submit" value="'._('Add to this group').'" /></td>';
      echo '</form></tr>';
    }
    echo '</table>';
    echo "</div>\n";
  }

  $apps_s = $u->applications();
  if (is_array($apps_s) && count($apps_s) > 0) {
    echo '<br />';
    echo '<h2>'._('Published applications').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';
    foreach ($apps_s as $aaa) {
      echo '<tr>';
      echo '<td><img src="media/image/cache.php?id='.$aaa->getAttribute('id').'" alt="" title="" /></td>';
      echo '<td><a href="applications.php?action=manage&id='.$aaa->getAttribute('id').'">'.$aaa->getAttribute('name').'</a></td>';
      echo '</tr>';
    }
    echo '</table>';
  }


  echo '<h2>';
  echo _('Session settings configuration');
  echo '</h2>';
  
  if ($prefs_of_a_user != array()) {
    foreach ($prefs_of_a_user as $container => $prefs_of_a_user_value) {
      echo '<fieldset class="prefssessionusergroup">';
      echo '<legend>'.$prefs->getPrettyName($container).'</legend>';
      
      echo '<form action="actions.php" method="post">';
      $key_name = 'general';
      echo '<input type="hidden" name="container" value="'.$container.'" />';
      // from admin/functions.inc.php
      $color=0;
      if (count($prefs_of_a_user_value) != 0) {
        echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3" style="margin-bottom: 10px;">'; // TODO
        echo '<tr  class="title">';
        echo '<th>'._('Name').'</th>';
        echo '<th>'._('Default value').'</th>';
        echo '<th>'._('Value').'</th>';
        echo '<th>'._('Action').'</th>';
        echo '<tr>';
        
        foreach ($prefs_of_a_user_value as $element_key => $user_preferences) {
          $config_element = $user_preferences->toConfigElement();
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
          echo '<input type="button" value="'._('Remove this overriden setting').'" onclick="user_settings_remove(\''.$u->getAttribute('login').'\',\''.$container.'\',\''.$config_element->id.'\'); return false;"/>';
          echo '</td>';
          
          echo '</tr>';
          $color++;
        }
      
        // end from
        echo '<tr class="content'.($color % 2 +1).'">';
        echo '<td colspan="3"></td>';
        echo '<td>';
        echo '<input type="hidden" name="name" value="User_settings" />';
        echo '<input type="hidden" name="container" value="'.$container.'" />';
        echo '<input type="hidden" name="unique_id" value="'.$u->getAttribute('login').'" />';
        echo '<input type="hidden" name="action" value="modify" />';
        echo '<input type="submit" value="'._('Save settings').'" />';
        
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</form>';
      }
      
      if ($unuse_settings[$container] != array()) {
        echo '<form action="actions.php" method="post">';
          echo '<input type="hidden" name="name" value="User_settings" />';
          echo '<input type="hidden" name="container" value="'.$container.'" />';
          echo '<input type="hidden" name="unique_id" value="'.$u->getAttribute('login').'" />';
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
  

  if ($has_sessions) {
    echo '<div>';
    echo '<h2>'._('Active sessions').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';
    foreach($sessions as $session) {
      echo '<form action="sessions.php"><tr>';
      echo '<td>';
      $buf = $session->getAttribute('start_time');
      if (! $buf)
        echo _('Not started yet');
      else
        echo @date('d/m/Y H:i:s', $session->getAttribute('start_time'));
      echo '</td>';
      echo '<td><a href="servers.php?action=manage&fqdn='.$session->server.'">'.$session->server.'</td>';
      echo '<td>';
      echo '<input type="hidden" name="info" value="'.$session->id.'" />';
      echo '</td><td><input type="submit" value="'._('Information about this session').'" /></td>';
      echo '</td>';
      echo '</tr></form>';
    }
    echo '</table>';
    echo '</div>';
  }

  echo '</div>';
  page_footer();
  die();
}
