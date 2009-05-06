<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
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

if (!isset($_SESSION['wizard']))
	$_SESSION['wizard'] = array();

if (isset($_POST['from'])) {
	if ($_POST['from'] == 'step1') {
		if ($_POST['use'] == 'usergroups') {
			$_SESSION['wizard']['use_users'] = 'usergroups';

			if (!isset($_POST['usergroups']) || !is_array($_POST['usergroups']))
				show_step1(_('No selected user group'));
			else {
				$_SESSION['wizard']['usergroups'] = $_POST['usergroups'];
				show_step3();
			}
		} elseif ($_POST['use'] == 'users') {
			$_SESSION['wizard']['use_users'] = 'users';

			if (!isset($_POST['users']) || !is_array($_POST['users']))
				show_step1(_('No user selected'));
			else {
				$_SESSION['wizard']['users'] = $_POST['users'];
				show_step2();
			}
		}
	}

	if ($_POST['from'] == 'step2') {
		if (isset($_POST['submit_previous']))
			show_step1();
		elseif (isset($_POST['submit_next'])) {
			if (!isset($_POST['group_name']) || $_POST['group_name'] === '')
				show_step2(_('No group name'));
			else {
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
				if (!isset($_POST['appgroups']) || !is_array($_POST['appgroups']))
					show_step3(_('No application group selected'));
				else {
					$_SESSION['wizard']['appgroups'] = $_POST['appgroups'];
					show_step5();
				}
			elseif ($_SESSION['wizard']['use_apps'] == 'apps')
				if (!isset($_POST['apps']) || !is_array($_POST['apps']))
					show_step3(_('No application selected'));
				else {
					$_SESSION['wizard']['apps'] = $_POST['apps'];
					show_step4();
				}
		}
	}

	if ($_POST['from'] == 'step4') {
		if (isset($_POST['submit_previous']))
			show_step3();
		elseif (isset($_POST['submit_next'])) {
			if (!isset($_POST['group_name']) || $_POST['group_name'] === '')
				show_step4(_('No group name'));
			else {
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

function show_error($error_) {
  page_header();
  echo '<div>';
  echo '<h1>'._('Publication Wizard').'</h1>';
  echo '<span class="msg_error">'.$error_.'</span>';
  echo '</div>';

  page_footer();
  die();
}

function show_step1($error_=NULL) {
  $usergroups = get_all_usergroups();
  $has_usergroups = (count($usergroups) > 0);

  $usergroup_selected = false;
  if (!isset($_SESSION['wizard']['use_users']))
    $usergroup_selected = true;
  elseif ($_SESSION['wizard']['use_users'] == 'usergroups')
    $usergroup_selected = true;

  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('UserDB',$mods_enable))
    die_error(_('Module UserDB must be enabled'),__FILE__,__LINE__);
  $mod_user_name = 'admin_UserDB_'.$prefs->get('UserDB','enable');
  $userDB = new $mod_user_name();
  $users = $userDB->getList();

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('ApplicationDB',$mods_enable))
    die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
  $mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
  $applicationDB = new $mod_app_name();
  $applications = $applicationDB->getList();

  if (!count($users))
    show_error(_('No available user'));
  if (!count($applications))
    show_error(_('No available application'));

  page_header();
  echo '<div>';
  echo '<h1><a href="wizard.php">'._('Publication Wizard').'</a> - '._('User/group selection').'</h1>';

  if ($error_ != NULL)
    echo '<span class="msg_error">'.$error_.'</span>';

  echo '<form action="" method="post">';
  echo '<input type="hidden" name="from" value="step1" />';
  echo '<table class="" id="wizard_list_table" border="0" cellspacing="1" cellpadding="5">';
  if ($has_usergroups) {
	echo '<tr class="title">';
	echo '<th><input class="input_radio" type="radio" name="use" value="users" onclick="$(\'wizard_usergroups_list_table\').hide(); $(\'wizard_users_list_table\').show()"';
	if (!$usergroup_selected)
		echo ' checked="checked"';
	echo '/>'._('Create a group with users').'</th>';
	echo '<th><input class="input_radio" type="radio" name="use" value="usergroups" onclick="$(\'wizard_users_list_table\').hide(); $(\'wizard_usergroups_list_table\').show()"';
	if ($usergroup_selected)
		echo ' checked="checked"';
	echo '/> '._('Use usergroups').'</th>';
	echo '</tr>';
  } else
  	echo '<input type="hidden" name="use" value="users" />';

  echo '<tr>';
  echo '<td>';
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
		echo '<td><input class="input_checkbox" type="checkbox" name="usergroups[]" value="'.$usergroup->id.'" /> <a href="usersgroup.php?action=manage&id='.$usergroup->id.'">'.$usergroup->name.'</a></td>';
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

function show_step2($error_=NULL) {
  $count = 0;

  page_header();
  echo '<div>';
  echo '<h1><a href="wizard.php">'._('Publication Wizard').'</a> - '._('Create usergroup').'</h1>';

  if ($error_ != NULL)
    echo '<span class="msg_error">'.$error_.'</span>';

  echo '<form action="" method="post">';
  echo '<input type="hidden" name="from" value="step2" />';
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

function show_step3($error_=NULL) {
  $appgroups = getAllAppsGroups();
  $has_appgroups = (count($appgroups) > 0);

  $appgroup_selected = false;
  if (!isset($_SESSION['wizard']['use_apps']))
    $appgroup_selected = true;
  elseif ($_SESSION['wizard']['use_apps'] == 'appgroups')
    $appgroup_selected = true;

  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);
  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('ApplicationDB',$mods_enable))
    die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
  $mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
  $applicationDB = new $mod_app_name();
  $applications = $applicationDB->getList(true);

  page_header();
  echo '<div>';
  echo '<h1><a href="wizard.php">'._('Publication Wizard').'</a> - '._('Applications/groups selection').'</h1>';

  if ($error_ != NULL)
    echo '<span class="msg_error">'.$error_.'</span>';

  echo '<form action="" method="post">';
  echo '<input type="hidden" name="from" value="step3" />';
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
	echo '/> '._('Use appgroups').'</th>';
	echo '</tr>';
  } else
  	echo '<input type="hidden" name="use" value="apps" />';

  echo '<tr>';
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
		echo '<td><input class="input_checkbox" type="checkbox" name="appgroups[]" value="'.$appgroup->id.'" /> <a href="appsgroups.php?action=manage&id='.$appgroup->id.'">'.$appgroup->name.'</a></td>';
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
  echo '<input type="submit" name="submit_next" value="'._('Next').'" />';
  echo '</td>';
  echo '</tr>';
  echo '</table>';
  echo '</form>';
  echo '</div>';

  page_footer();
  die();
}

function show_step4($error_=NULL) {
  $count = 0;
  page_header();
  echo '<div>';
  echo '<h1><a href="wizard.php">'._('Publication Wizard').'</a> - '._('Create appgroup').'</h1>';

  if ($error_ != NULL)
    echo '<span class="msg_error">'.$error_.'</span>';

  echo '<form action="" method="post">';
  echo '<input type="hidden" name="from" value="step4" />';
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

		$g = new UsersGroup(NULL, $_SESSION['wizard']['user_group_name'], $_SESSION['wizard']['user_group_description'], 1);
		$res = $userGroupDB->add($g);

		if (!$res || !is_object($g) || $g->id == NULL)
			show_error(_('Cannot create usergroup'));

		$users = $_SESSION['wizard']['users'];

		foreach ($users as $user) {
			Abstract_Liaison::save('UsersGroup', $user, $g->id);
		}

		$usergroups = array($g->id);
	} else
		$usergroups = $_SESSION['wizard']['usergroups'];

	if ($_SESSION['wizard']['use_apps'] == 'apps') {
		$g = new AppsGroup(NULL, $_SESSION['wizard']['application_group_name'], $_SESSION['wizard']['application_group_description'], 1);
		$res = $g->insertDB();

		if (!$res || !is_object($g) || $g->id == NULL)
			show_error(_('Cannot create appgroup'));

		$apps = $_SESSION['wizard']['apps'];

		foreach ($apps as $app) {
			Abstract_Liaison::save('AppsGroup', $app, $g->id);
		}

		$appgroups = array($g->id);
	} else
		$appgroups = $_SESSION['wizard']['appgroups'];

	foreach ($usergroups as $usergroup) {
		foreach ($appgroups as $appgroup) {
			Abstract_Liaison::save('UsersGroupApplicationsGroup',$usergroup, $appgroup);
		}
	}

	if (isset($_SESSION['wizard']));
		unset($_SESSION['wizard']);

	redirect('publications.php');
	die();
}
