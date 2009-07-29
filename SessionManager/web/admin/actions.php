<?php
/**
 * Copyright (C) 2008,2009 Ulteo SAS
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

if (!isset($_SERVER['HTTP_REFERER']))
	redirect('index.php');

if (!isset($_REQUEST['name']))
	redirect($_SERVER['HTTP_REFERER']);

if (!isset($_REQUEST['action']))
	redirect($_SERVER['HTTP_REFERER']);

if ($_REQUEST['action'] != 'add' && $_REQUEST['action'] != 'del') {
	header('Location: '.$_SERVER['HTTP_REFERER']);
	die();
}

/*
 *  Install some Applications on a specific server
 */
if ($_REQUEST['name'] == 'Application_Server') {
	if (!isset($_REQUEST['server']) || !isset($_REQUEST['application'])) {
		header('Location: '.$_SERVER['HTTP_REFERER']);
		die();
	}

	if (! is_array($_REQUEST['application']))
		$_REQUEST['application'] = array($_REQUEST['application']);

	$mods_enable = $prefs->get('general','module_enable');
	if (! in_array('ApplicationDB',$mods_enable))
		die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
	$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
	$applicationDB = new $mod_app_name();

	$apps = array();
	foreach($_REQUEST['application'] as $id)
		$apps[]= $applicationDB->import($id);

	if ($_REQUEST['action'] == 'add')
		$t = new Task_install(0, $_REQUEST['server'], $apps);
	else
		$t = new Task_remove(0, $_REQUEST['server'], $apps);

	$tm = new Tasks_Manager();
	$tm->add($t);
	redirect($_SERVER['HTTP_REFERER']);

}
/*
if ($_REQUEST['name'] == 'ApplicationGroup_Server') {
	if (!isset($_REQUEST['server']) || !isset($_REQUEST['group']))
		header('Location: '.$_SERVER['HTTP_REFERER']);

	if (!is_array($_REQUEST['server']))
		$_REQUEST['server'] = array($_REQUEST['server']);

	$l = new AppsGroupLiaison(NULL, $_REQUEST['group']);

	if ($_REQUEST['action'] == 'add')
		$task_type = Task_Install;
	else
		$task_type = Task_Remove;
	$t = new $task_type(0, $_REQUEST['server'], $l->elements());
	$tm = new Tasks_Manager();
	$tm->add($t);

	header('Location: '.$_SERVER['HTTP_REFERER']);
	die();
}*/

if ($_REQUEST['name'] == 'Application') {
	if ($_REQUEST['action'] == 'del') {
		$prefs = Preferences::getInstance();
		
		$mods_enable = $prefs->get('general','module_enable');
		if (!in_array('ApplicationDB',$mods_enable)){
			die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
		}
		$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
		$applicationDB = new $mod_app_name();
		
		if ($applicationDB->isWriteable()) {
			$app = $applicationDB->import($_REQUEST['id']);
			$applicationDB->remove($app);
		}
		else {
			die_error(_('ApplicationDB is not writeable'),__FILE__,__LINE__);
		}
	}
}

if ($_REQUEST['name'] == 'Application_ApplicationGroup') {
	if ($_REQUEST['action'] == 'add') {
		Abstract_Liaison::save('AppsGroup', $_REQUEST['element'], $_REQUEST['group']);
	}

	if ($_REQUEST['action'] == 'del') {
		Abstract_Liaison::delete('AppsGroup', $_REQUEST['element'], $_REQUEST['group']);
	}
}

if ($_REQUEST['name'] == 'User_UserGroup') {
	if ($_REQUEST['action'] == 'add') {
		Abstract_Liaison::save('UsersGroup', $_REQUEST['element'], $_REQUEST['group']);
	}

	if ($_REQUEST['action'] == 'del') {
		Abstract_Liaison::delete('UsersGroup', $_REQUEST['element'], $_REQUEST['group']);
	}
}

if ($_REQUEST['name'] == 'Publication') {
	if (!isset($_REQUEST['group_a']) or !isset($_REQUEST['group_u']))
		redirect($_SERVER['HTTP_REFERER']);

	if ($_REQUEST['action'] == 'add') {
		$l = Abstract_Liaison::load('UsersGroupApplicationsGroup', $_REQUEST['group_u'], $_REQUEST['group_a']);
		if (is_null($l))
			Abstract_Liaison::save('UsersGroupApplicationsGroup', $_REQUEST['group_u'], $_REQUEST['group_a']);
		else
			popup_error(_('This publication already exists'));
	}

	if ($_REQUEST['action'] == 'del') {
		$l = Abstract_Liaison::load('UsersGroupApplicationsGroup', $_REQUEST['group_u'], $_REQUEST['group_a']);
		if (! is_null($l))
			Abstract_Liaison::delete('UsersGroupApplicationsGroup', $_REQUEST['group_u'], $_REQUEST['group_a']);
		else
			popup_error(_('This publication does not exist'));

	}
}

if ($_REQUEST['name'] == 'default_browser') {
	if ($_REQUEST['action'] == 'add') {
		$prefs = new Preferences_admin();
		if (! $prefs)
			die_error('get Preferences failed',__FILE__,__LINE__);

		$mods_enable = $prefs->get('general','module_enable');
		if (!in_array('ApplicationDB',$mods_enable)){
			die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
		}
		$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB','enable');
		$applicationDB = new $mod_app_name();
		$app = $applicationDB->import($_REQUEST['browser']);
		if (is_object($app)) {
			$browsers = $prefs->get('general', 'default_browser');
			$browsers[$_REQUEST['type']] = $app->getAttribute('id');
			$prefs->set('general', 'default_browser', $browsers);
			$prefs->backup();
		}
	}
}

if ($_REQUEST['name'] == 'static_application') {
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['attribute']) && ($_REQUEST['attribute'] == 'icon_file')) {
			if (isset($_REQUEST['id'])) {
				$prefs = new Preferences_admin();
				if (! $prefs)
					die_error('get Preferences failed',__FILE__,__LINE__);

				$mods_enable = $prefs->get('general','module_enable');
				if (!in_array('ApplicationDB',$mods_enable)){
					die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
				}
				$mod_app_name = 'ApplicationDB_'.$prefs->get('ApplicationDB','enable');
				$applicationDB = new $mod_app_name();
				$app = $applicationDB->import($_REQUEST['id']);
				Abstract_Liaison::delete('StaticApplicationServer', $app->getAttribute('id'), NULL);
				$app->delIcon();
			}
		}
	}
}

if ($_REQUEST['name'] == 'SharedFolder') {
	if ($_REQUEST['action']=='add') {
		action_add_sharedfolder();
		redirect();
	}
	elseif ($_REQUEST['action']=='del') {
		if (isset($_REQUEST['id'])) {
			action_del_sharedfolder($_REQUEST['id']);
			redirect();
		}
	}
}

if ($_REQUEST['name'] == 'SharedFolder_ACL') {
	if ($_REQUEST['action'] == 'add' && isset($_REQUEST['sharedfolder_id']) && isset($_REQUEST['usergroup_id'])) {
		action_add_sharedfolder_acl($_REQUEST['sharedfolder_id'], $_REQUEST['usergroup_id']);
		redirect();
	}
	elseif ($_REQUEST['action'] == 'del' && isset($_REQUEST['sharedfolder_id']) && isset($_REQUEST['usergroup_id'])) {
		action_del_sharedfolder_acl($_REQUEST['sharedfolder_id'], $_REQUEST['usergroup_id']);
		redirect();
	}
}

function action_add_sharedfolder() {
	$sharedfolder_name = $_REQUEST['sharedfolder_name'];
	if ($sharedfolder_name == '') {
		popup_error(_('You must give a name to your shared folder'));
		return false;
	}

	$buf = SharedFolders::getByName($sharedfolder_name);
	if (count($buf) > 0) {
		popup_error(_('A shared folder with this name already exists'));
		return false;
	}

	$buf = new SharedFolder(NULL);
	$buf->name = $sharedfolder_name;
	Abstract_SharedFolder::save($buf);

	return true;
}

function action_del_sharedfolder($sharedfolder_id) {
	$buf = Abstract_SharedFolder::delete($sharedfolder_id);

	if (! $buf) {
		popup_error(_('Unable to delete this shared folder'));
		return false;
	}

	return true;
}

function action_add_sharedfolder_acl($sharedfolder_id_, $usergroup_id_) {
	$sharedfolder = Abstract_SharedFolder::load($sharedfolder_id_);
	if (! $sharedfolder) {
		popup_error(_('Unable to create this shared folder access'));
		return false;
	}

	Abstract_SharedFolder::add_acl($sharedfolder, $usergroup_id_);

	return true;
}

function action_del_sharedfolder_acl($sharedfolder_id_, $usergroup_id_) {
	$sharedfolder = Abstract_SharedFolder::load($sharedfolder_id_);
	if (! $sharedfolder) {
		popup_error(_('Unable to delete this shared folder access'));
		return false;
	}

	Abstract_SharedFolder::del_acl($sharedfolder, $usergroup_id_);

	return true;
}

redirect();
