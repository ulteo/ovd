<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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

show_default();

function action_add() {
  if (! (isset($_REQUEST['name']) && isset($_REQUEST['description'])))
    return false;

  $g = new AppsGroup(NULL,$_REQUEST['name'], $_REQUEST['description'], 1);
  $res = $g->insertDB();
  if (!$res)
    die_error('Unable to create application group '.$res,__FILE__,__LINE__);

  return $g->id;
}

function action_del($id) {
  $group = new AppsGroup();
  $group->fromDB($id);
  if (! $group->isOK())
    die_error('Group "'.$id.'" is not OK',__FILE__,__LINE__);

  if (! $group->removeDB())
    die_error('Unable to remove group "'.$id.'" is not OK',__FILE__,__LINE__);

  return true;
}

function action_modify($id) {
  $group = new AppsGroup();
  $group->fromDB($id);
  if (! $group->isOK())
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

  if (! $group->updateDB())
    die_error('Unable to update group "'.$id.'"',__FILE__,__LINE__);

  return true;
}


function show_manage($id) {
  $group = new AppsGroup();
  $group->fromDB($id);
  if (! $group->isOK())
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

  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('ApplicationDB',$mods_enable))
    die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
  $mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
  $applicationDB = new $mod_app_name();

  $applications_all = $applicationDB->getList();
  $l = new AppsGroupLiaison(NULL, $id);
  $applications_id = $l->elements();

  $applications = array();
  $applications_available = array();

  foreach($applications_all as $application) {
    if (! in_array($application->getAttribute('id'), $applications_id))
      $applications_available[]= $application;
    else
      $applications[]= $application;
  }

/*
  // Servers
  $servers_all = Servers::getOnline();
  $servers_available = array();
  $servers = array();
  foreach($servers_all as $server) {
    if (in_array($group, $server->appsGroups()))
	$servers[]= $server;
    else
	$servers_available[]= $server;
  }
*/

  // Publications
  $groups_users = array();
  $l = new UsersGroupApplicationsGroupLiaison(NULL, $id);
  foreach ($l->elements() as $group_u) {
    $obj = new UsersGroup();
    $obj->fromDB($group_u);

    if (is_object($obj))
      $groups_users[]= $obj;
  }

  $groups_users_all = get_all_usergroups();
  $groups_users_available = array();
  foreach($groups_users_all as $group_users) {
    if (! in_array($group_users, $groups_users))
      $groups_users_available[]= $group_users;
  }


  include_once('header.php');
  echo '<div>';
  echo '<h1><a href="?">'._('Application groups management').'</a> - '.$group->name.'</h1>';
  echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
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
  echo '<h2>'._('Settings').'</h2>';
  echo '<form action="" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group ?').'\');">';
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
  echo '<br/>';

  echo '<div>';
  echo '<h2>'._('List of applications in this group').'</h2>';
  echo '<table border="0" cellspacing="1" cellpadding="3">';

  if (count($applications) == 0)
    echo '<tr><td colspan="2">'._('No application in this group').'</td></tr>';
  else {
    foreach($applications as $application) {
      $icon_id = ($application->haveIcon())?$application->getAttribute('id'):0;
      echo '<tr>';
      echo '<td><img src="../cache/image/application/'.$icon_id.'.png" alt="'.$application->getAttribute('name').'" title="'.$application->getAttribute('name').'" /> <a href="applications.php?action=manage&id='.$application->getAttribute('id').'">'.$application->getAttribute('name').'</a>';
      echo '</td>';
      echo '<td>';
      echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this application ?').'\');">';
      echo '<input type="hidden" name="action" value="del" />';
      echo '<input type="hidden" name="name" value="Application_ApplicationGroup" />';
      echo '<input type="hidden" name="group" value="'.$id.'" />';
      echo '<input type="hidden" name="element" value="'.$application->getAttribute('id').'" />';
      echo '<input type="submit" value="'._('Delete from this group').'" />';
      echo '</form>';
      echo '</td>';
      echo '</tr>';
    }
  }

  if (count ($applications_available) ==0)
    echo '<tr><td colspan="2">'._('Not any available application to add').'</td></tr>';
  else {
    echo '<tr><form action="actions.php" method="post"><td>';
    echo '<input type="hidden" name="action" value="add" />';
    echo '<input type="hidden" name="name" value="Application_ApplicationGroup" />';
    echo '<input type="hidden" name="group" value="'.$id.'" />';
    echo '<select name="element">';
    foreach($applications_available as $application)
      echo '<option value="'.$application->getAttribute('id').'" >'.$application->getAttribute('name').'</option>';
    echo '</select>';
    echo '</td><td><input type="submit" value="'._('Add to this group').'" /></td>';
    echo '</form></tr>';
  }
  echo '</table>';
  echo '</div>';
/*
  // Servers
  echo '<div>';
  echo '<h2>'._('List of servers including this group').'</h2>';
  echo '<table border="0" cellspacing="1" cellpadding="3">';

  if (count($servers) == 0)
    echo '<tr><td colspan="2">'._('No server has this group').'</td></tr>';
  else {
    foreach($servers as $server) {
      echo '<tr>';
      echo '<td><a href="servers.php?action=manage&id='.$server->fqdn.'">'.$server->fqdn.'</a>';
      echo '</td>';
      echo '<td>';
      echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group from this server ?').'\');">';
      echo '<input type="hidden" name="action" value="del" />';
      echo '<input type="hidden" name="name" value="ApplicationGroup_Server" />';
      echo '<input type="hidden" name="group" value="'.$id.'" />';
      echo '<input type="hidden" name="server" value="'.$server->fqdn.'" />';
      echo '<input type="submit" value="'._('Delete from this group').'" /> FIXME';
      echo '</form>';
      echo '</td>';
      echo '</tr>';
    }
  }

  if (count ($servers_available) ==0)
    echo '<tr><td colspan="2">'._('Not any available server to add').'</td></tr>';
  else {
    echo '<tr><form action="actions.php" method="post"><td>';
    echo '<input type="hidden" name="action" value="add" />';
    echo '<input type="hidden" name="name" value="Application_ApplicationGroup" />';
    echo '<input type="hidden" name="group" value="'.$id.'" />';
    echo '<select name="element">';
    foreach($servers_available as $servers)
      echo '<option value="'.$server->fqdn.'" >'.$server->fqdn.'</option>';
    echo '</select>';
    echo '</td><td><input type="submit" value="'._('Add to this server').'" /></td>';
    echo '</form></tr>';
  }
  echo '</table>';
  echo '</div>';

*/

  echo '<div>';
  echo '<h2>'._('List of publications with this group').'</h1>';
  echo '<table border="0" cellspacing="1" cellpadding="3">';

  if (count($groups_users)==0)
    echo '<tr><td colspan="2">'._('No publications with this group').'</td></tr>';
  else {
    foreach($groups_users as $group_users) {
      echo '<tr>';
      echo '<td><a href="usersgroup.php?action=manage&id='.$group_users->id.'">'.$group_users->name.'</td>';
      echo '<td>';
      echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this publication ?').'\');">';
      echo '<input type="hidden" name="action" value="del" />';
      echo '<input type="hidden" name="name" value="Publication" />';
      echo '<input type="hidden" name="group_a" value="'.$id.'" />';
      echo '<input type="hidden" name="group_u" value="'.$group_users->id.'" />';
      echo '<input type="submit" value="'._('Delete this publication').'" />';
      echo '</form>';
      echo '</td>';
      echo '</tr>';
    }
  }

  if (count ($groups_users_available) ==0)
    echo '<tr><td colspan="2">'._('Not any publication to add').'</td></tr>';
  else {
    echo '<tr><form action="actions.php" method="get"><td>';
    echo '<input type="hidden" name="action" value="add" />';
    echo '<input type="hidden" name="name" value="Publication" />';
    echo '<input type="hidden" name="group_a" value="'.$id.'" />';
    echo '<select name="group_u">';
    foreach($groups_users_available as $group_users)
      echo '<option value="'.$group_users->id.'" >'.$group_users->name.'</option>';
    echo '</select>';
    echo '</td><td><input type="submit" value="'._('Add this publication').'" /></td>';
    echo '</form></tr>';
  }
  echo '</table>';
  echo '</div>';

  include_once('footer.php');
  die();
}

function show_default() {
  $groups = getAllAppsGroups();
  $has_group = ! (is_null($groups) or (count($groups) == 0));

  include_once('header.php');
  echo '<div>';
  echo '<h1>'._('Application groups management').'</h1>';

  echo '<div>';
  echo '<h2>'._('Application group list').'</h2>';
  if (! $has_group)
    echo _('No application groups available').'<br />';
  else {
    echo '<form action="appsgroup.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete these groups ?').'\');">';
    echo '<input type="hidden" name="action" value="del" />';
    echo '<table class="main_sub sortable" id="appgroups_list" border="0" cellspacing="1" cellpadding="5">';
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

      echo '<td><form action="" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group ?').'\');">';
      echo '<input type="submit" value="'._('Delete').'"/>';
      echo '<input type="hidden" name="action" value="del" />';
      echo '<input type="hidden" name="id" value="'.$group->id.'" />';
      echo '</form></td>';
      echo '</tr>';
    }
    $content = 'content'.(($count++%2==0)?1:2);
    echo '<tr class="'.$content.'">';
    echo '<td colspan="5"><a href="javascript:;" onclick="markAllRows(\'appgroups_list\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'appgroups_list\'); return false">'._('Unmark all').'</a></td>';
    echo '<td><input type="submit" value="'._('Delete').'"/></td>';
    echo '</table>';
    echo '</form>';

  }
  echo '</div>';

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

  include_once('footer.php');
}
