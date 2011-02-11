<?php
/**
 * Copyright (C) 2008-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2009
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

if (! checkAuthorization('managePublications'))
	redirect('index.php');


if (!isset($_SESSION['wizard']))
	$_SESSION['wizard'] = array();

if (isset($_POST['from'])) {
	if ($_POST['from'] == 'step1') {
		if ($_POST['use'] == 'usergroups') {
			$_SESSION['wizard']['use_users'] = 'usergroups';

			if (!isset($_POST['usergroups']) || !is_array($_POST['usergroups'])) {
				popup_error(_('No user group selected'));
				show_step1();
			} else {
				$_SESSION['wizard']['usergroups'] = $_POST['usergroups'];
				show_step3();
			}
		} elseif ($_POST['use'] == 'users') {
			$_SESSION['wizard']['use_users'] = 'users';

			if (!isset($_POST['users']) || !is_array($_POST['users'])) {
				popup_error(_('No user selected'));
				show_step1();
			} else {
				$_SESSION['wizard']['users'] = $_POST['users'];
				show_step2();
			}
		}
	}

	if ($_POST['from'] == 'step2') {
		if (isset($_POST['submit_previous']))
			show_step1();
		elseif (isset($_POST['submit_next'])) {
			if (!isset($_POST['group_name']) || $_POST['group_name'] === '') {
				popup_error(_('No group name'));
				show_step2();
			} else {
				$_SESSION['wizard']['user_group_name'] = $_POST['group_name'];
				$_SESSION['wizard']['user_group_description'] = $_POST['group_description'];
				show_step3();
			}
		}
	}

	if ($_POST['from'] == 'step3') {
		if ($_POST['use'] == 'appgroups')
			$_SESSION['wizard']['use_apps'] = 'appgroups';
		elseif ($_POST['use'] == 'apps')
			$_SESSION['wizard']['use_apps'] = 'apps';

		if (isset($_POST['submit_previous'])) {
			if ($_SESSION['wizard']['use_users'] == 'usergroups')
				show_step1();
			elseif ($_SESSION['wizard']['use_users'] == 'users')
				show_step2();
		} elseif (isset($_POST['submit_next'])) {
			if ($_SESSION['wizard']['use_apps'] == 'appgroups')
				if (!isset($_POST['appgroups']) || !is_array($_POST['appgroups'])) {
					popup_error(_('No application group selected'));
					show_step3();
				} else {
					$_SESSION['wizard']['appgroups'] = $_POST['appgroups'];
					show_step5();
				}
			elseif ($_SESSION['wizard']['use_apps'] == 'apps')
				if (!isset($_POST['apps']) || !is_array($_POST['apps'])) {
					popup_error(_('No application selected'));
					show_step3();
				} else {
					$_SESSION['wizard']['apps'] = $_POST['apps'];
					show_step4();
				}
		}
	}

	if ($_POST['from'] == 'step4') {
		if (isset($_POST['submit_previous']))
			show_step3();
		elseif (isset($_POST['submit_next'])) {
			if (!isset($_POST['group_name']) || $_POST['group_name'] === '') {
				popup_error(_('No group name'));
				show_step4();
			} else {
				$_SESSION['wizard']['application_group_name'] = $_POST['group_name'];
				$_SESSION['wizard']['application_group_description'] = $_POST['group_description'];
				show_step5();
			}
		}
	}

	if ($_POST['from'] == 'step5') {
		if (isset($_POST['submit_previous'])) {
			if ($_SESSION['wizard']['use_apps'] == 'appgroups')
				show_step3();
			elseif ($_SESSION['wizard']['use_apps'] == 'apps')
				show_step4();
		} elseif (isset($_POST['submit_next']))
			do_validate();
	}
} else
	show_default();

function show_default() {
  show_step1();
}

function show_step1() {
  $usergroupdb = UserGroupDB::getInstance();
  $usergroups = $usergroupdb->getList(true);
  $has_usergroups = (count($usergroups) > 0);

  $usergroup_selected = false;
  if (!isset($_SESSION['wizard']['use_users']))
    $usergroup_selected = true;
  elseif ($_SESSION['wizard']['use_users'] == 'usergroups')
    $usergroup_selected = true;

  $userDB = UserDB::getInstance();
  $prefs = Preferences::getInstance();
  $search_limit = $prefs->get('general', 'max_items_per_page');
  list($users, $sizelimit_exceeded) = $userDB->getUsersContains('', array('login'), $search_limit);
  if (! is_array($users))
    $users = array();

  $applicationDB = ApplicationDB::getInstance();
  $applications = $applicationDB->getList(true);

  if (!count($users))
    popup_error(_('No available user'));
  if (!count($applications))
    popup_error(_('No available application'));
  if ($sizelimit_exceeded) {
    popup_error(_('Unable to display users list: too many users'));
    $users = array();
  }

  page_header();
  echo '<div>';
  echo '<h1><a href="wizard.php">'._('Publication Wizard').'</a> - '._('User/group selection').'</h1>';

  echo '<form action="" method="post">';
  echo '<input type="hidden" name="from" value="step1" />';
  echo '<table class="" id="wizard_list_table" border="0" cellspacing="1" cellpadding="5">';
  if ($has_usergroups) {
	echo '<tr class="title">';
	echo '<th>';
	if ($usergroupdb->isWriteable() && count($users) > 0) {
		echo '<input class="input_radio" type="radio" name="use" value="users" onclick="$(\'wizard_usergroups_list_table\').hide(); $(\'wizard_users_list_table\').show()"';
		if (!$usergroup_selected)
			echo ' checked="checked"';
		echo '/>'._('Create a group with users');
	}
	echo '</th>';
	echo '<th><input class="input_radio" type="radio" name="use" value="usergroups" onclick="$(\'wizard_users_list_table\').hide(); $(\'wizard_usergroups_list_table\').show()"';
	if ($usergroup_selected)
		echo ' checked="checked"';
	echo '/> '._('Use usergroups').'</th>';
	echo '</tr>';
  } else
  	echo '<input type="hidden" name="use" value="users" />';

  echo '<tr>';
  echo '<td>';
  if ($usergroupdb->isWriteable()) {
    $count = 0;
    echo '<table class="main_sub"';
    if ($has_usergroups) {
      if ($usergroup_selected)
        echo ' style="display: none" ';
    }
    echo 'id="wizard_users_list_table" border="0" cellspacing="1" cellpadding="5">';
    foreach($users as $user) {
      $content = 'content'.(($count++%2==0)?1:2);

      echo '<tr class="'.$content.'">';
      echo '<td colspan="2"><input class="input_checkbox" type="checkbox" name="users[]" value="'.$user->getAttribute('login').'"';
      if (isset($_SESSION['wizard']['users']) && in_array($user->getAttribute('login'), $_SESSION['wizard']['users']))
        echo ' checked="checked"';
      echo '/> <a href="users.php?action=manage&id='.$user->getAttribute('login').'">'.$user->getAttribute('displayname').'</a></td>';
      echo '</tr>';
    }
    $content = 'content'.(($count++%2==0)?1:2);
    echo '<tr class="'.$content.'"><td colspan="2"><a href="javascript:;" onclick="markAllRows(\'wizard_users_list_table\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'wizard_users_list_table\'); return false">'._('Unmark all').'</a></td></tr>';
    echo '</table>';
    echo '</td>';
  }
  echo '<td>';
  if ($has_usergroups) {
	$count = 0;
	echo '<table class="main_sub"';
	if (!$usergroup_selected)
		echo ' style="display: none;" ';
	echo 'id="wizard_usergroups_list_table" border="0" cellspacing="1" cellpadding="5">';
	foreach($usergroups as $usergroup) {
		$content = 'content'.(($count++%2==0)?1:2);

		echo '<tr class="'.$content.'">';
		echo '<td><input class="input_checkbox" type="checkbox" name="usergroups[]" value="'.$usergroup->getUniqueID().'" /> <a href="usersgroup.php?action=manage&id='.$usergroup->getUniqueID().'">'.$usergroup->name.'</a></td>';
		echo '</tr>';
	}
	$content = 'content'.(($count++%2==0)?1:2);
	echo '<tr class="'.$content.'"><td><a href="javascript:;" onclick="markAllRows(\'wizard_usergroups_list_table\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'wizard_usergroups_list_table\'); return false">'._('Unmark all').'</a></td></tr>';
	echo '</table>';
  }
  echo '</td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td style="text-align: right;" colspan="2">';
  echo '<input type="submit" name="submit_next" value="'._('Next').'" />';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
  echo '</form>';
  echo '</div>';

  page_footer();
  die();
}

function show_step2() {
  $count = 0;

  page_header();
  echo '<div>';
  echo '<h1><a href="wizard.php">'._('Publication Wizard').'</a> - '._('Create usergroup').'</h1>';

  echo '<form action="" method="post">';
  echo '<input type="hidden" name="from" value="step2" />';
  echo '<input style="display: none;" type="submit" name="submit_next" value="'._('Next').'" />';
  echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
  $content = 'content'.(($count++%2==0)?1:2);
  echo '<tr class="'.$content.'">';
  echo '<th>'._('Name').'</th>';
  echo '<td><input type="text" name="group_name" value="'.@$_SESSION['wizard']['user_group_name'].'" /></td>';
  echo '</tr>';
  $content = 'content'.(($count++%2==0)?1:2);
  echo '<tr class="'.$content.'">';
  echo '<th>'._('Description').'</th>';
  echo '<td><input type="text" name="group_description" value="'.@$_SESSION['wizard']['user_group_description'].'" /></td>';
  echo '</tr>';
  $content = 'content'.(($count++%2==0)?1:2);
  echo '<tr class="'.$content.'">';
  echo '<td colspan="2">';
  echo '<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  	echo '<td style="text-align: left;">';
  	echo '<input type="submit" name="submit_previous" value="'._('Previous').'" />';
  	echo '</td>';
  	echo '<td style="text-align: right;">';
  	echo '<input type="submit" name="submit_next" value="'._('Next').'" />';
  	echo '</td>';
  echo '</tr>';
  echo '</table>';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
  echo '</form>';

  echo '</div>';

  page_footer();
  die();
}

function show_step3() {
  $applicationsGroupDB = ApplicationsGroupDB::getInstance();
  $appgroups = $applicationsGroupDB->getList(true);
  $has_appgroups = (count($appgroups) > 0);

  $appgroup_selected = false;
  if (!isset($_SESSION['wizard']['use_apps']))
    $appgroup_selected = true;
  elseif ($_SESSION['wizard']['use_apps'] == 'appgroups')
    $appgroup_selected = true;

  $applicationDB = ApplicationDB::getInstance();
  $applications = $applicationDB->getList(true);
  $has_applications  = $applications !== array() && !is_null($applications);

  if (!$has_applications) {
    popup_error(_('No available application'));
  }
  page_header();
  echo '<div>';
  echo '<h1><a href="wizard.php">'._('Publication Wizard').'</a> - '._('Applications/groups selection').'</h1>';

  echo '<form action="" method="post">';
  echo '<input type="hidden" name="from" value="step3" />';
  if ($has_applications)
    echo '<input style="display: none;" type="submit" name="submit_next" value="'._('Next').'" />';
  echo '<table class="" id="wizard_list_table" border="0" cellspacing="1" cellpadding="5">';
  if ($has_appgroups) {
	echo '<tr class="title">';
	echo '<th><input class="input_radio" type="radio" name="use" value="apps" onclick="$(\'wizard_appgroups_list_table\').hide(); $(\'wizard_apps_list_table\').show()"';
	if (!$appgroup_selected)
		echo ' checked="checked"';
	echo '/>'._('Create a group with applications').'</th>';
	echo '<th><input class="input_radio" type="radio" name="use" value="appgroups" onclick="$(\'wizard_apps_list_table\').hide(); $(\'wizard_appgroups_list_table\').show()"';
	if ($appgroup_selected)
		echo ' checked="checked"';
	echo '/> '._('Use an application group').'</th>';
	echo '</tr>';
  } else
  	echo '<input type="hidden" name="use" value="apps" />';

  echo '<tr>';
  if ($has_applications) {
    echo '<td>';
    $count = 0;
    echo '<table class="main_sub"';
    if ($has_appgroups) {
      if ($appgroup_selected)
        echo ' style="display: none" ';
    }
    echo 'id="wizard_apps_list_table" border="0" cellspacing="1" cellpadding="5">';
    foreach($applications as $application) {
      $content = 'content'.(($count++%2==0)?1:2);

      echo '<tr class="'.$content.'">';
      echo '<td colspan="2" onmouseover="showInfoBulle(\''.str_replace("'", "&rsquo;", $application->getAttribute('description')).'\'); return false;" onmouseout="hideInfoBulle(); return false;"><input class="input_checkbox" type="checkbox" name="apps[]" value="'.$application->getAttribute('id').'"';
      if (isset($_SESSION['wizard']['apps']) && in_array($application->getAttribute('id'), $_SESSION['wizard']['apps']))
        echo ' checked="checked"';
      echo '/>';
      echo '&nbsp; <img src="media/image/cache.php?id='.$application->getAttribute('id').'" alt="" title="" /> &nbsp; <a href="applications.php?action=manage&id='.$application->getAttribute('id').'">'.$application->getAttribute('name').'</a> <img src="media/image/server-'.$application->getAttribute('type').'.png" width="16" height="16" alt="'.$application->getAttribute('type').'" title="'.$application->getAttribute('type').'" /></td>';
      echo '</tr>';
    }
    $content = 'content'.(($count++%2==0)?1:2);
    echo '<tr class="'.$content.'"><td colspan="2"><a href="javascript:;" onclick="markAllRows(\'wizard_apps_list_table\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'wizard_apps_list_table\'); return false">'._('Unmark all').'</a></td></tr>';
    echo '</table>';
    echo '</td>';
  }
  echo '<td>';
  if ($has_appgroups) {
	$count = 0;
	echo '<table class="main_sub"';
	if (!$appgroup_selected)
		echo ' style="display: none" ';
	echo 'id="wizard_appgroups_list_table" border="0" cellspacing="1" cellpadding="5">';
	foreach($appgroups as $appgroup) {
		$content = 'content'.(($count++%2==0)?1:2);

		echo '<tr class="'.$content.'">';
		echo '<td><input class="input_checkbox" type="checkbox" name="appgroups[]" value="'.$appgroup->id.'" /> <a href="appsgroup.php?action=manage&id='.$appgroup->id.'">'.$appgroup->name.'</a></td>';
		echo '</tr>';
	}
	$content = 'content'.(($count++%2==0)?1:2);
	echo '<tr class="'.$content.'"><td><a href="javascript:;" onclick="markAllRows(\'wizard_appgroups_list_table\'); return false">'._('Mark all').'</a> / <a href="javascript:;" onclick="unMarkAllRows(\'wizard_appgroups_list_table\'); return false">'._('Unmark all').'</a></td></tr>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';
  }
  echo '</td>';
  echo '</tr>';
  echo '<tr>';
  echo '<td style="text-align: left;">';
  echo '<input type="submit" name="submit_previous" value="'._('Previous').'" />';
  echo '</td>';
  echo '<td style="text-align: right;">';
  if ($has_applications)
    echo '<input type="submit" name="submit_next" value="'._('Next').'" />';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
  echo '</form>';
  echo '</div>';

  page_footer();
  die();
}

function show_step4() {
  $count = 0;
  page_header();
  echo '<div>';
  echo '<h1><a href="wizard.php">'._('Publication Wizard').'</a> - '._('Create application group').'</h1>';

  echo '<form action="" method="post">';
  echo '<input type="hidden" name="from" value="step4" />';
  echo '<input style="display: none;" type="submit" name="submit_next" value="'._('Next').'" />';
  echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
  $content = 'content'.(($count++%2==0)?1:2);
  echo '<tr class="'.$content.'">';
  echo '<th>'._('Name').'</th>';
  echo '<td><input type="text" name="group_name" value="'.@$_SESSION['wizard']['application_group_name'].'" /></td>';
  echo '</tr>';
  $content = 'content'.(($count++%2==0)?1:2);
  echo '<tr class="'.$content.'">';
  echo '<th>'._('Description').'</th>';
  echo '<td><input type="text" name="group_description" value="'.@$_SESSION['wizard']['application_group_description'].'" /></td>';
  echo '</tr>';
  $content = 'content'.(($count++%2==0)?1:2);
  echo '<tr class="'.$content.'">';
  echo '<td colspan="2">';
  echo '<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  	echo '<td style="text-align: left;">';
  	echo '<input type="submit" name="submit_previous" value="'._('Previous').'" />';
  	echo '</td>';
  	echo '<td style="text-align: right;">';
  	echo '<input type="submit" name="submit_next" value="'._('Next').'" />';
  	echo '</td>';
  echo '</tr>';
  echo '</table>';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
  echo '</form>';

  echo '</div>';

  page_footer();
  die();
}

function show_step5() {
  page_header();
  echo '<div>';
  echo '<h1><a href="wizard.php">'._('Publication Wizard').'</a> - '._('Confirmation').'</h1>';

  echo '<p>'._('Are you sure that you want to create this publication?').'</p>';

	echo '<table style="width: 50%;" border="0" cellspacing="1" cellpadding="3">';
	echo '<tr>';
	echo '<td style="text-align: left; vertical-align: top;">';
	echo '<div class="container rounded" style="background: #eee;">';
	if ($_SESSION['wizard']['use_users'] == 'usergroups') {  
		$usergroupDB = UserGroupDB::getInstance();

		echo '<p style="font-weight: bold;">';
		if (count($_SESSION['wizard']['usergroups']) == 1)
			echo _('Between this users group');
		else
			echo _('Between these users groups');
		echo '</p>';

		echo '<ul>';
		foreach ($_SESSION['wizard']['usergroups'] as $ug_id) {
			$ug = $usergroupDB->import($ug_id);
			if (! is_object($ug)) {
				Logger::warning('main', '(admin/wizard) Usergroup \''.$ug_id.'\' import failed');
				continue;
			}

			echo '<li>'.$ug->name.'</li>';
		}
		echo '</ul>';
	} elseif ($_SESSION['wizard']['use_users'] == 'users') {
		$userDB = UserDB::getInstance();

		echo '<p style="font-weight: bold;">';
		echo _('Between this newly created users group');
		echo '</p>';

		echo '<ul>';
		echo '<li><strong>'._('Name:').'</strong> '.$_SESSION['wizard']['user_group_name'].'</li>';
		echo '<li><strong>'._('Description: ').'</strong> '.$_SESSION['wizard']['user_group_description'].'</li>';
		echo '<li><strong>'._('Users:').'</strong> <ul>';
		foreach ($_SESSION['wizard']['users'] as $user_login) {
			$user = $userDB->import($user_login);
			if (! is_object($user)) {
				Logger::warning('main', '(admin/wizard) User \''.$user_login.'\' import failed');
				continue;
			}

			echo '<li>'.$user->getAttribute('displayname').'</li>';
		}
		echo '</ul></li>';
		echo '</ul>';
	}
	echo '</div>';
	echo '</td>';
	echo '<td style="width: 50px;">';
	echo '</td>';
	echo '<td style="text-align: left; vertical-align: top;">';
	echo '<div class="container rounded" style="background: #eee;">';
	if ($_SESSION['wizard']['use_apps'] == 'appgroups') {
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();

		echo '<p style="font-weight: bold;">';
		if (count($_SESSION['wizard']['appgroups']) == 1)
			echo _('and this applications group');
		else
			echo _('and these applications groups');
		echo '</p>';

		echo '<ul>';
		foreach ($_SESSION['wizard']['appgroups'] as $ag_id) {
			$appgroup = $applicationsGroupDB->import($ag_id);
			if (is_object($appgroup))
				echo '<li>'.$appgroup->name.'</li>';
		}
		echo '</ul>';
	} elseif ($_SESSION['wizard']['use_apps'] == 'apps') {
		$applicationDB = ApplicationDB::getInstance();

		echo '<p style="font-weight: bold;">';
		echo _('and this newly created applications group');
		echo '</p>';

		echo '<ul>';
		echo '<li><strong>'._('Name:').'</strong> '.$_SESSION['wizard']['application_group_name'].'</li>';
		echo '<li><strong>'._('Description: ').'</strong> '.$_SESSION['wizard']['application_group_description'].'</li>';
		echo '<li><strong>'._('Applications:').'</strong> <ul>';
		foreach ($_SESSION['wizard']['apps'] as $application_id) {
			$application = $applicationDB->import($application_id);
			if (! is_object($application)) {
				Logger::warning('main', '(admin/wizard) Application \''.$application_id.'\' import failed');
				continue;
			}

			echo '<li>'.$application->getAttribute('name').'</li>';
		}
		echo '</ul></li>';
		echo '</ul>';
	}
	echo '</div>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';

  echo '<form action="" method="post">';
  echo '<input type="hidden" name="from" value="step5" />';
  echo '<table style="width: 50%;" class="" border="0" cellspacing="1" cellpadding="5">';
  echo '<tr>';
  echo '<td colspan="2">';
  echo '<table style="width: 100%;" border="0" cellspacing="0" cellpadding="0">';
  echo '<tr>';
  	echo '<td style="text-align: left;">';
  	echo '<input type="submit" name="submit_previous" value="'._('Previous').'" />';
  	echo '</td>';
  	echo '<td style="text-align: right;">';
  	echo '<input type="submit" name="submit_next" value="'._('Confirm').'" />';
  	echo '</td>';
  echo '</tr>';
  echo '</table>';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
  echo '</form>';

  echo '</div>';

  page_footer();
  die();
}

function do_validate() {
	if ($_SESSION['wizard']['use_users'] == 'users') {
		if (! checkAuthorization('manageUsersGroups'))
			redirect();

		$userGroupDB = UserGroupDB::getInstance();
		if (! $userGroupDB->isWriteable())
			return false;

		$g = new UsersGroup(NULL, $_SESSION['wizard']['user_group_name'], $_SESSION['wizard']['user_group_description'], 1);
		$res = $userGroupDB->add($g);

		if (!$res || !is_object($g) || $g->id == NULL)
			popup_error(_('Cannot create usergroup'));

		$users = $_SESSION['wizard']['users'];

		foreach ($users as $user) {
			Abstract_Liaison::save('UsersGroup', $user, $g->getUniqueID());
		}

		$usergroups = array($g->getUniqueID());
	} else
		$usergroups = $_SESSION['wizard']['usergroups'];

	if ($_SESSION['wizard']['use_apps'] == 'apps') {
		if (! checkAuthorization('manageApplicationsGroups'))
			redirect();

		$g = new AppsGroup(NULL, $_SESSION['wizard']['application_group_name'], $_SESSION['wizard']['application_group_description'], 1);
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		$res = $applicationsGroupDB->add($g);

		if (!$res || !is_object($g) || $g->id == NULL)
			popup_error(_('Cannot create application group'));

		$apps = $_SESSION['wizard']['apps'];

		foreach ($apps as $app) {
			Abstract_Liaison::save('AppsGroup', $app, $g->id);
		}

		$appgroups = array($g->id);
	} else
		$appgroups = $_SESSION['wizard']['appgroups'];

	foreach ($usergroups as $usergroup) {
		foreach ($appgroups as $appgroup) {
			$exists = Abstract_Liaison::load('UsersGroupApplicationsGroup', $usergroup, $appgroup);
			if (is_object($exists) === false)
				Abstract_Liaison::save('UsersGroupApplicationsGroup', $usergroup, $appgroup);
		}
	}

	if (isset($_SESSION['wizard']));
		unset($_SESSION['wizard']);

	redirect('publications.php');
	die();
}
