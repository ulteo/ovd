<?php
/**
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

function checkup_liaison($type_, $element_, $group_) {
	switch ($type_) {
		case 'ApplicationServer':
			$applicationDB = ApplicationDB::getInstance();
			$buf = $applicationDB->import($element_);
			if (! is_object($buf))
				return 'Application "'.$element_.'" does not exist';

			$buf = Abstract_Server::load($group_);
			if (! $buf)
				return 'Server "'.$group_.'" does not exist';
			break;

		case 'AppsGroup':
			$applicationDB = ApplicationDB::getInstance();
			$buf = $applicationDB->import($element_);
			if (! is_object($buf))
				return 'Application "'.$element_.'" does not exist';

			$applicationsGroupDB = ApplicationsGroupDB::getInstance();
			$buf = $applicationsGroupDB->import($group_);
			if (! is_object($buf))
				return 'ApplicationsGroup "'.$group_.'" does not exist';
			break;

		case 'ApplicationMimeType':
			$applicationDB = ApplicationDB::getInstance();
			$buf = $applicationDB->import($element_);
			if (! is_object($buf))
				return 'Application "'.$element_.'" does not exist';
			break;

		case 'ServerSession':
			$buf = Abstract_Server::load($element_);
			if (! $buf)
				return 'Server "'.$element_.'" does not exist';

			$buf = Abstract_Session::load($group_);
			if (! $buf)
				return 'Session "'.$group_.'" does not exist';
			break;

		case 'UserGroupSharedFolder':
			$sharedfolderdb = SharedFolderDB::getInstance();
			$userGroupDB = UserGroupDB::getInstance();
			$buf = $userGroupDB->import($element_);
			if (! is_object($buf))
				return 'UserGroup "'.$element_.'" does not exist';

			$buf = $sharedfolderdb->import($group_);
			if (! $buf)
				return 'SharedFolder "'.$group_.'" does not exist';
			break;

		case 'UserProfile':
			$profiledb = ProfileDB::getInstance();
			$userDB = UserDB::getInstance();
			$buf = $userDB->import($element_);
			if (! is_object($buf))
				return 'User "'.$element_.'" does not exist';

			$buf = $profiledb->import($group_);
			if (! $buf)
				return 'Profile "'.$group_.'" does not exist';
			break;

		case 'UsersGroup':
			$userDB = UserDB::getInstance();
			$buf = $userDB->import($element_);
			if (! is_object($buf))
				return 'User "'.$element_.'" does not exist';

			$userGroupDB = UserGroupDB::getInstance();
			$buf = $userGroupDB->import($group_);
			if (! is_object($buf))
				return 'UserGroup "'.$group_.'" does not exist';
			break;

		case 'UsersGroupApplicationsGroup':
			$userGroupDB = UserGroupDB::getInstance();
			$buf = $userGroupDB->import($element_);
			if (! is_object($buf))
				return 'UserGroup "'.$element_.'" does not exist';

			$applicationsGroupDB = ApplicationsGroupDB::getInstance();
			$buf = $applicationsGroupDB->import($group_);
			if (! is_object($buf))
				return 'ApplicationsGroup "'.$group_.'" does not exist';
			break;

		case 'UsersGroupCached':
			$userDB = UserDB::getInstance();
			$buf = $userDB->import($element_);
			if (! is_object($buf))
				return 'User "'.$element_.'" does not exist';

			$userGroupDB = UserGroupDB::getInstance();
			$buf = $userGroupDB->import($group_);
			if (! is_object($buf))
				return 'UserGroup "'.$group_.'" does not exist';
			break;
	}

	return true;
}

function cleanup_liaison($type_, $element_, $group_) {
	if (checkup_liaison($type_, $element_, $group_) !== true) {
		Abstract_Liaison::delete($type_, $element_, $group_);

		return true;
	}

	return false;
}

function checkup_PHP_modules() {
	try {
		@include_once('libchart/classes/libchart.php');
	} catch (Exception $e) {}

	return array(
		'cURL'		=>	(function_exists('curl_init')),
		'Imagick'	=>	(class_exists('Imagick')),
		'LDAP'		=>	(function_exists('ldap_connect')),
		'libchart'	=>	(class_exists('LineChart')),
		'MySQL'		=>	(function_exists('mysql_connect')),
		'XML'		=>	(class_exists('DomDocument'))
	);
}

function cleanup_preferences() {
	$userGroupDB = UserGroupDB::getInstance();
	$prefs = new Preferences_admin();
	
	$default_usergroup_id = $prefs->get('general', 'user_default_group');
	if ($default_usergroup_id != '') {
		$group = $userGroupDB->import($default_usergroup_id);
		if (! is_object($group)) {
			// unset the default usergroup
			$mods_enable = $prefs->set('general', 'user_default_group', '');
			$prefs->backup();
		}
	}
}

$liaisons_types = array('ApplicationServer', 'AppsGroup', 'ApplicationMimeType', 'ServerSession', 'UsersGroup', 'UsersGroupApplicationsGroup');
if (Preferences::moduleIsEnabled('ProfileDB')) {
	$liaisons_types []= 'UserProfile';
}
if (Preferences::moduleIsEnabled('SharedFolderDB')) {
	$liaisons_types []= 'UserGroupSharedFolder';
}
if (Preferences::moduleIsEnabled('UsersGroupDBDynamic') || Preferences::moduleIsEnabled('UsersGroupDBDynamicCached')) {
	$liaisons_types []= 'UsersGroupCached';
}
ksort($liaisons_types);
if (isset($_POST['cleanup']) && $_POST['cleanup'] == 1 && array_key_exists('type', $_POST)) {
	switch ($_POST['type']) {
		case 'liaison':
			foreach ($liaisons_types as $liaisons_type) {
				$liaisons = Abstract_Liaison::load($liaisons_type, NULL, NULL);
				if (is_null($liaisons))
					continue;

				foreach ($liaisons as $k => $liaison)
					cleanup_liaison($liaisons_type, $liaison->element, $liaison->group);
			}
			break;
		
		case 'preferences':
			cleanup_preferences();
			break;
	}

	redirect('checkup.php');
}

$everything_ok = true;

page_header();

echo '<h1>'._('Checkup').'</h1>';

echo '<h2>'._('PHP Modules').'</h2>';

$PHP_modules = checkup_PHP_modules();
if (in_array(false, $PHP_modules)) {
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	$PHP_modules = checkup_PHP_modules();
	foreach ($PHP_modules as $name => $available) {
		echo '<tr>';
		echo '<td><span style="color: #666; font-weight: bold;">'.$name.'</span></td>';
		echo '<td>&nbsp;</td>';
		echo '<td>';
		if ($available === true)
			echo '<span class="msg_ok">OK</span>';
		else {
			$module_failed = true;
			echo '<span class="msg_error">ERROR</span>';
		}
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
	page_footer();
	die();
} else {
	echo '<span class="msg_ok">OK</span><br />';
	echo '<br />';
}

echo '<h2>'._('Liaisons').'</h2>';

foreach ($liaisons_types as $liaisons_type) {
	$liaisons = Abstract_Liaison::load($liaisons_type, NULL, NULL);
	if (is_null($liaisons))
		continue;

	$all_ok = true;

	echo '<br /><h3>'.$liaisons_type.'</h3>';

	echo '<table border="0" cellspacing="1" cellpadding="3">';

	foreach ($liaisons as $liaison) {
		$ret = checkup_liaison($liaisons_type, $liaison->element, $liaison->group);
		if ($ret === true)
			continue;

		if ($all_ok === true) {
			$everything_ok = false;
			$all_ok = false;

			echo '<tr><td colspan="5"><span class="msg_error">ERROR</span></td></tr>';
			echo '<tr><td colspan="5"></td></tr>';
		}

		echo '<tr>';
		echo '<td>'.$liaison->element.'</td>';
		echo '<td>=&gt;</td>';
		echo '<td>'.$liaison->group.'</td>';
		echo '<td>&nbsp;-&nbsp;</td>';
		echo '<td>';
		echo '<span class="msg_error">'.$ret.'</span>';
		echo '</td>';
		echo '</tr>';
	}

	if ($all_ok === true)
		echo '<tr><td><span class="msg_ok">OK</span></td></tr>';

	echo '</table>';
}

if ($everything_ok === false)
	echo '<br /><form action="" method="post"><input type="hidden" name="cleanup" value="1" /><input type="hidden" name="type" value="liaison" /><input type="submit" value="'._('Cleanup liaisons').'" /></form>';


$everything_ok = true; // reset

echo '<br /><h2>'._('Configuration').'</h2>';

$prefs = Preferences::getInstance();
$userGroupDB = UserGroupDB::getInstance();

echo '<h3>'._('Default usergroup').'</h3>';

$default_usergroup_id = $prefs->get('general', 'user_default_group');
if ($default_usergroup_id != '') {
	$group = $userGroupDB->import($default_usergroup_id);
	if (! is_object($group)) {
		$everything_ok = false;
		echo '<span class="msg_error">Usergroup "'.$default_usergroup_id.'" does not exist</span>';
	}
}
if ($everything_ok) {
	echo '<span class="msg_ok">OK</span>';
}
echo '<br />';

if ($everything_ok === false) {
	echo '<br />';
	echo '<form action="" method="post">';
	echo '<input type="hidden" name="cleanup" value="1" />';
	echo '<input type="hidden" name="type" value="preferences" />';
	echo '<input type="submit" value="'._('Cleanup configuration').'" />';
	echo '</form>';
}

page_footer();
