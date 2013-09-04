<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2008, 2009, 2011, 2012, 2013
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
require_once(dirname(__FILE__).'/includes/core.inc.php');
require_once(dirname(__FILE__).'/includes/page_template.php');

if (! checkAuthorization('viewApplicationsGroups'))
	redirect('index.php');

if (isset($_REQUEST['action'])) {
  if ($_REQUEST['action']=='manage') {
    if (isset($_REQUEST['id']))
      show_manage($_REQUEST['id']);
  }
}

show_default();

function show_default() {
	$groups = $_SESSION['service']->applications_groups_list();
  $has_group = ! (is_null($groups) or (count($groups) == 0));

	$can_manage_applicationsgroups = isAuthorized('manageApplicationsGroups');

  page_header();

  echo '<div>';
  echo '<h1>'._('Application groups').'</h1>';

  echo '<div>';
  if (! $has_group)
    echo _('No available application groups').'<br />';
  else {
    echo '<table class="main_sub sortable" id="appgroups_list" border="0" cellspacing="1" cellpadding="5">';
    echo '<thead>';
    echo '<tr class="title">';
    if ($can_manage_applicationsgroups && count($groups) > 1)
        echo '<th class="unsortable"></th>';
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Description').'</th>';
    echo '<th>'._('Status').'</th>';
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
		if ($can_manage_applicationsgroups && count($groups) > 1)
			echo '<td><input class="input_checkbox" type="checkbox" name="checked_groups[]" value="'.$group->id.'" /></td>';
      echo '<td><a href="?action=manage&id='.$group->id.'">'.$group->name.'</a></td>';
      echo '<td>'.$group->description.'</td>';
      echo '<td class="centered">'.$publish.'</td>';

		if ($can_manage_applicationsgroups) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
			echo '<input type="submit" value="'._('Delete').'"/>';
			echo '<input type="hidden" name="name" value="ApplicationsGroup" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="checked_groups[]" value="'.$group->id.'" />';
			echo '</form></td>';
			echo '</tr>';
		}
    }
	echo '</tbody>';
	if ($can_manage_applicationsgroups && count($groups) > 1) {
		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tfoot>';
		echo '<tr class="'.$content.'">';
		echo '<td colspan="4"><a href="javascript:;" onclick="markAllRows(\'appgroups_list\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'appgroups_list\'); return false">'._('Unmark all').'</a></td>';
		echo '<td>';
		echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete these groups?').'\') && updateMassActionsForm(this, \'appgroups_list\');">';
		echo '<input type="hidden" name="name" value="ApplicationsGroup" />';
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

	if ($can_manage_applicationsgroups) {
		echo '<div>';
		echo '<h2>'._('Create a new group').'</h2>';
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="ApplicationsGroup" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';

		echo '<tr class="content1">';
		echo '<th>'._('Name').'</th>';
		echo '<td><input type="text" name="name_appsgroup" value="" /></td>';
		echo '</tr>';

		echo '<tr class="content2">';
		echo '<th>'._('Description').'</th>';
		echo '<td><input type="text" name="description_appsgroup" value="" /></td>';
		echo '</tr>';
		/*
		echo '<tr class="content2">';
		echo '<th>'._('Status').'</th>';
		echo '<td>';
		echo '<input class="input_radio" type="radio" name="published" value="1" checked />'._('Enable');
		echo '<input class="input_radio" type="radio" name="published" value="0"  />'._('Block');
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
  page_footer();
}

function show_manage($id) {
	$group = $_SESSION['service']->applications_group_info($id);
	if (! is_object($group)) {
		popup_error(_('Unable to import group'));
		redirect();
	}

  if ($group->published) {
    $status = '<span class="msg_ok">'._('Enabled').'</span>';
    $status_change = _('Block');
    $status_change_value = 0;

  } else {
    $status = '<span class="msg_error">'._('Blocked').'</span>';
    $status_change = _('Enable');
    $status_change_value = 1;
  }

	$applications_all = $_SESSION['service']->applications_list();
  $applications_id = array();
	if ($group->hasAttribute('applications')) {
		$applications_id = $group->getAttribute('applications');
	}

  $applications = array();
  $applications_available = array();

  foreach($applications_all as $application) {
    if (! in_array($application->getAttribute('id'), $applications_id))
      $applications_available[]= $application;
    else
      $applications[]= $application;
  }
	uasort($applications, "application_cmp");
	uasort($applications_available, "application_cmp");

  // Publications
  $groups_users = array();
	if ($group->hasAttribute('usersgroups')) {
		$groups_users = $group->getAttribute('usersgroups');
		uasort($groups_users, "usergroup_cmp");
	}

	$usersgroupsList = new UsersGroupsList($_REQUEST);
	$groups_users_all = $usersgroupsList->search();
	if (! is_array($groups_users_all)) {
		$groups_users_all = array();
		popup_error(_("Failed to get users groups list"));
	}
	uasort($groups_users_all, "usergroup_cmp");
	$searchDiv = $usersgroupsList->getForm();

  $groups_users_available = array();
  foreach($groups_users_all as $group_users) {
    if (! array_key_exists($group_users->id, $groups_users))
      $groups_users_available[]= $group_users;
  }

	$can_manage_applicationsgroups = isAuthorized('manageApplicationsGroups');
	$can_manage_publications = isAuthorized('managePublications');

  page_header();

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

	if ($can_manage_applicationsgroups) {
		echo '<div>';
		echo '<h2>'._('Settings').'</h2>';
		echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group?').'\');">';
		echo '<input type="submit" value="'._('Delete this group').'"/>';
		echo '<input type="hidden" name="name" value="ApplicationsGroup" />';
		echo '<input type="hidden" name="action" value="del" />';
		echo '<input type="hidden" name="checked_groups[]" value="'.$id.'" />';
		echo '<input type="hidden" name="id" value="'.$id.'" />';
		echo '</form>';
		echo '<br/>';

		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="ApplicationsGroup" />';
		echo '<input type="hidden" name="action" value="modify" />';
		echo '<input type="hidden" name="id" value="'.$id.'" />';
		echo '<input type="hidden" name="published_appsgroup" value="'.$status_change_value.'" />';
		echo '<input type="submit" value="'.$status_change.'"/>';
		echo '</form>';
		echo '<br/>';

		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="ApplicationsGroup" />';
		echo '<input type="hidden" name="action" value="modify" />';
		echo '<input type="hidden" name="id" value="'.$id.'" />';
		echo '<input type="text" name="name_appsgroup"  value="'.$group->name.'" size="50" /> ';
		echo '<input type="submit" value="'._('Update the name').'"/>';
		echo '</form>';
		echo '<br/>';

		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="ApplicationsGroup" />';
		echo '<input type="hidden" name="action" value="modify" />';
		echo '<input type="hidden" name="id" value="'.$id.'" />';
		echo '<input type="text" name="description_appsgroup"  value="'.$group->description.'" size="50" /> ';
		echo '<input type="submit" value="'._('Update the description').'"/>';
		echo '</form>';
		echo '<br/>';
	}

  // Application part
	if ((count($applications_all) > 0 and $can_manage_applicationsgroups) or count($applications) > 0) {
    echo '<div>';
    echo '<h2>'._('List of applications in this group').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';

    if (count($applications) > 0) {
      foreach($applications as $application) {
	echo '<tr>';
	echo '<td><img class="icon32" src="media/image/cache.php?id='.$application->getAttribute('id').'" alt="'.$application->getAttribute('name').'" title="'.$application->getAttribute('name').'" /> <a href="applications.php?action=manage&id='.$application->getAttribute('id').'">'.$application->getAttribute('name').'</a>';
	echo '</td>';
		if ($can_manage_applicationsgroups) {
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this application?').'\');">';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="name" value="Application_ApplicationGroup" />';
			echo '<input type="hidden" name="group" value="'.$id.'" />';
			echo '<input type="hidden" name="element" value="'.$application->getAttribute('id').'" />';
			echo '<input type="submit" value="'._('Delete from this group').'" />';
			echo '</form>';
			echo '</td>';
		}
	echo '</tr>';
      }
    }

    if (count ($applications_available) > 0 and $can_manage_applicationsgroups) {
      echo '<tr><form action="actions.php" method="post"><td>';
      echo '<input type="hidden" name="action" value="add" />';
      echo '<input type="hidden" name="name" value="Application_ApplicationGroup" />';
      echo '<input type="hidden" name="group" value="'.$id.'" />';
      echo '<select name="element">';
      foreach($applications_available as $application)
	echo '<option value="'.$application->getAttribute('id').'" >'.$application->getAttribute('name').' ('.$application->getAttribute('type').')</option>';
      echo '</select>';
      echo '</td><td><input type="submit" value="'._('Add to this group').'" /></td>';
      echo '</form></tr>';
    }

    echo '</table>';
    echo '</div>';
  }
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
      echo '<td><a href="servers.php?action=manage&id='.$server->id.'">'.$server->getDisplayName().'</a>';
      echo '</td>';
      echo '<td>';
      echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group from this server?').'\');">';
      echo '<input type="hidden" name="action" value="del" />';
      echo '<input type="hidden" name="name" value="ApplicationGroup_Server" />';
      echo '<input type="hidden" name="group" value="'.$id.'" />';
      echo '<input type="hidden" name="server" value="'.$server->id.'" />';
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
      echo '<option value="'.$server->id.'" >'.$server->getDisplayName().'</option>';
    echo '</select>';
    echo '</td><td><input type="submit" value="'._('Add to this server').'" /></td>';
    echo '</form></tr>';
  }
  echo '</table>';
  echo '</div>';

*/

  // Publication part
  if (count($groups_users_all) > 0) {
    echo '<div>';
    echo '<h2>'._('List of publications for this group').'</h1>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';

    if (count($groups_users) > 0) {
      foreach($groups_users as $group_users_id => $group_users_name) {
	echo '<tr>';
	echo '<td><a href="usersgroup.php?action=manage&id='.$group_users_id.'">'.$group_users_name.'</td>';

		if ($can_manage_publications) {
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this publication?').'\');">';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="name" value="Publication" />';
			echo '<input type="hidden" name="group_a" value="'.$id.'" />';
			echo '<input type="hidden" name="group_u" value="'.$group_users_id.'" />';
			echo '<input type="submit" value="'._('Delete this publication').'" />';
			echo '</form>';
			echo '</td>';
		}
	echo '</tr>';
      }
    }

    if (count ($groups_users_available) > 0 and $can_manage_publications) {
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
	echo $searchDiv;
    echo '</div>';
  }

  echo '</div>';
  echo '</div>';
  echo '</div>';
  page_footer();
  die();
}
