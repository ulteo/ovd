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

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$mods_enable = $prefs->get('general','module_enable');
if (! in_array('UserDB',$mods_enable))
	die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);

$mod_user_name = 'admin_UserDB_'.$prefs->get('UserDB','enable');
$userDB = new $mod_user_name();

$mod_usergroup_name = 'admin_UserGroupDB_'.$prefs->get('UserGroupDB','enable');
$userGroupDB = new $mod_usergroup_name();


if (isset($_REQUEST['action'])) {
  if ($_REQUEST['action']=='manage') {
    if (isset($_REQUEST['id']))
      show_manage($_REQUEST['id'], $userDB, $userGroupDB);
  }

  if ($userDB->isWriteable()) {
    if ($_REQUEST['action']=='add')
      add_user($userDB);

    elseif ($_REQUEST['action']=='del') {
      if (isset($_REQUEST['id']))
	del_user($userDB, $_REQUEST['id']);
    }
    elseif ($_REQUEST['action']=='modify') {
      if (isset($_REQUEST['id'])) {
	modify_user($userDB, $_REQUEST['id']);
	show_manage($_REQUEST['id'], $userDB, $userGroupDB);
      }
    }
  }
}

show_default($userDB);

function add_user($userDB) {
  $minimun_attributes =  array_unique(array_merge(array('login', 'displayname', 'uid',  'password'), get_needed_attributes_user_from_module_plugin()));
  if (!isset($_REQUEST['login']) or !isset($_REQUEST['displayname']) or !isset($_REQUEST['password']))
    return false;

  $u = new User();
  foreach ($minimun_attributes as $attributes) {
    $u->setAttribute($attributes ,$_REQUEST[$attributes]);
  }
  if (!isset($_REQUEST['uid']) || ($_REQUEST['uid'] == ''))
    $u->setAttribute('uid', str2num($_REQUEST['login']));
  else
    $u->setAttribute('uid', $_REQUEST['uid']);

  $res = $userDB->add($u);
  if (! $res)
    die_error('Unable to create user '.$res, __FILE__, __LINE__);

  return true;
}

function del_user($userDB, $login) {
  $u = $userDB->import($login);
  $res = $userDB->remove($u);
  if (! $res)
    die_error('Unable to delete user '.$res,__FILE__,__LINE__);

  return true;
}

function modify_user($userDB, $login) {
  $u = $userDB->import($login);
  if (! is_object($u))
    die_error('Unable to import user "'.$login.'"',__FILE__,__LINE__);

  foreach($u->getAttributesList() as $attr)
    if (isset($_REQUEST[$attr]))
      $u->setAttribute($attr, $_REQUEST[$attr]);

  $res = $userDB->update($u);
  if (! $res)
    die_error('Unable to modify user '.$res,__FILE__,__LINE__);

  return true;
}

function show_manage($login, $userDB, $userGroupDB) {
  $u = $userDB->import($login);
  if (! is_object($u))
    die_error('Unable to import user "'.$login.'"',__FILE__,__LINE__);

  $userdb_rw = $userDB->isWriteable();
  $usergroupdb_rw = $userGroupDB->isWriteable();

  $keys = array();
  foreach($u->getAttributesList() as $attr)
    if (! in_array($attr, array('uid', 'login', 'displayname')))
      $keys[]= $attr;

  // Users Group
  $groups_mine = $u->usersGroups();
  if (is_null($groups_mine))
    die_error(_('Error while requesting usergroups'),__FILE__,__LINE__);

  $groups_all = get_all_usergroups();
  $groups_available = array();
  foreach($groups_all as $group)
    if (! in_array($group, $groups_mine))
      $groups_available[]= $group;

  // Sessions
  $sessions = Sessions::getByUser($login);
  $has_sessions = count($sessions);


  include_once('header.php');
  echo '<div id="users_div">';
  echo '<h1>'.$u->getAttribute('displayname').'</h1>';

  echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
  echo '<tr class="title">';
  echo '<th>'._('Login').'</th>';
  echo '<th>'._('Uid').'</th>';
  foreach($keys as $key)
    if ($key != 'password')
      echo '<th>'.$key.'</th>';
  echo '</tr>';

  echo '<tr class="content1">';
   echo '<td>'.$u->getAttribute('login').'</td>';
  echo '<td>'.$u->getAttribute('uid').'</td>';
  foreach($keys as $key)
    if ($key != 'password')
      echo '<td>'.$u->getAttribute($key).'</td>';

  echo '</table>';

  if ($userdb_rw) {
    echo '<div>';
    echo '<h2>'._('Settings').'</h2>';

    echo '<div>';
    echo '<form action="" onsubmit="return confirm(\''._('Are you sure you want to delete this user?').'\');">';
    echo '<input type="submit" value="'._('Delete this user').'"/>';
    echo '<input type="hidden" name="action" value="del" />';
    echo '<input type="hidden" name="id" value="'.$login.'" />';
    echo '</form>';
    echo '</div>';
    echo '<br/><br/>';

    echo '<div>';
    echo '<form action="users.php" method="post">';
    echo '<input type="hidden" name="action" value="modify" />';
    echo '<input type="hidden" name="id" value="'.$login.'" />';
    echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';

    $count = 0;
    $content = 'content'.(($count++%2==0)?1:2);
    echo '<tr class="'.$content.'">';
    echo '<th>'._('Display name').'</th>';
    echo '<td><input type="text" name="displayname" value="'.$u->getAttribute('displayname').'" /></td>';
    echo '</tr>';

    $content = 'content'.(($count++%2==0)?1:2);
    echo '<tr class="'.$content.'">';
    echo '<th>'._('Uid').'</th>';
    echo '<td><input type="text" name="uid" value="'.$u->getAttribute('uid').'" /></td>';
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
    echo '<input type="submit" name="add" value="'._('Save the modifications').'" />';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</form>';
    echo '</div>';
  }

  // User groups part
  if (count($groups_all)>0) {
    echo '<div>';
    echo '<h2>'._('Users groups with this user').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';

    foreach ($groups_mine as $group) {
      echo '<tr><td>';
      echo '<a href="usersgroup.php?action=manage&id='.$group->id.'">'.$group->name.'</a>';
      echo '</td>';
      if ($usergroupdb_rw) {
        echo '<td><form action="actions.php" method="get" onsubmit="return confirm(\''._('Are you sure you want to delete this user from this group?').'\');">';
        echo '<input type="hidden" name="name" value="User_UserGroup" />';
        echo '<input type="hidden" name="action" value="del" />';
        echo '<input type="hidden" name="group" value="'.$group->id.'" />';
        echo '<input type="hidden" name="element" value="'.$login.'" />';
        echo '<input type="submit" value="'._('Delete from this group').'" />';
        echo '</form></td>';
      }
      echo '</tr>';
    }

    if ((count ($groups_available) >0) && $usergroupdb_rw) {
      echo '<tr><form action="actions.php" method="get"><td>';
      echo '<input type="hidden" name="action" value="add" />';
      echo '<input type="hidden" name="name" value="User_UserGroup" />';
      echo '<input type="hidden" name="element" value="'.$login.'" />';
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
      echo '</td><td><input type="submit" value="'._('Informations about this session').'" /></td>';
      echo '</td>';
      echo '</tr></form>';
    }
    echo '</table>';
    echo '</div>';
  }

  include_once('footer.php');
  die();
}

function show_default($userDB) {
  $us = $userDB->getList();  // in admin, getList is always present (even if canShowList is false)

  $users_list_empty = (is_null($us) or count($us)==0);
  $userdb_rw = $userDB->isWriteable();

  include_once('header.php');

  echo '<div id="users_div">';
  echo '<h1>'._('Users').'</h1>';
  echo '<a href="usersgroup.php">'._('Users groups management').'</a>';
  echo '<div id="users_list_div">';
  echo '<h2>'._('List of users').'</h2>';

  if ($users_list_empty)
    echo _('No available user').'<br />';
  else {
    echo '<table class="main_sub sortable" id="user_list_table" border="0" cellspacing="1" cellpadding="5">';
    echo '<tr class="title">';
    echo '<th>'._('Login').'</th>';
    echo '<th>'._('Display name').'</th>';
    echo '<th>'._('Uid').'</th>';
    echo '</tr>';

    $count = 0;
    foreach($us as $u){
      $content = 'content'.(($count++%2==0)?1:2);

      $keys = array();
      foreach($u->getAttributesList() as $attr)
	if (! in_array($attr, array('uid', 'login', 'displayname','password')))
	  $keys[]= $attr;

      $extra = array();
      foreach($keys as $key)
	$extra[]= '<b>'.$key.':</b> '.$u->getAttribute($key);

      echo '<tr class="'.$content.'">';
      echo '<td><a href="users.php?action=manage&id='.$u->getAttribute('login').'">';
      echo $u->getAttribute('login');
      echo '</a></td>';
      echo '<td>'.$u->getAttribute('displayname').'</td>';
      echo '<td>'.$u->getAttribute('uid').'</td>';
      echo '<td>'.implode(",", $extra).'</td>';

      echo '<td><form action="users.php">';
      echo '<input type="submit" value="'._('Manage').'"/>';
      echo '<input type="hidden" name="action" value="manage" />';
      echo '<input type="hidden" name="id" value="'.$u->getAttribute('login').'" />';
      echo '</form></td>';

      if ($userdb_rw) {
	echo '<td><form action="" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this user?').'\');">';
	echo '<input type="submit" value="'._('Delete').'"/>';
	echo '<input type="hidden" name="action" value="del" />';
	echo '<input type="hidden" name="id" value="'.$u->getAttribute('login').'" />';
	echo '</form></td>';
      }
    }
    echo '</table>';
  }

  if ($userdb_rw) {
    echo '<h2>'._('Add').'</h2>';
    echo '<div id="user_add">';
    echo '<form action="" method="post">';
    echo '<input type="hidden" name="action" value="add" />';

    echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';

    $content_color = 1;
    $minimun_attributes =  array_unique(array_merge(array('login', 'displayname', 'uid',  'password'), get_needed_attributes_user_from_module_plugin()));
    foreach ($minimun_attributes as $minimun_attribute) {
      echo '<tr class="content'.$content_color.'">';
      echo '<th>'._($minimun_attribute).'</th>';
      echo '<td><input type="'.$minimun_attribute.'" name="'.$minimun_attribute.'" value="" /></td>';
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
  }


  echo '</div>';
  echo '</div>';
  include_once('footer.php');
  die();
}
