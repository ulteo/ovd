<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
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
	redirect();

if (!isset($_REQUEST['action']))
	redirect();

if (! in_array($_REQUEST['action'], array('add', 'del', 'change', 'modify', 'register', 'install_line', 'upgrade', 'replication', 'maintenance', 'available_sessions', 'external_name', 'web_port')))
	redirect();

if ($_REQUEST['name'] == 'System') {
	if (! checkAuthorization('manageServers'))
		redirect();

	$prefs = new Preferences_admin();
	if (! $prefs)
		die_error('get Preferences failed', __FILE__, __LINE__);

	if ($_REQUEST['action'] == 'change') {
		$prefs->set('general', 'system_in_maintenance', (($_REQUEST['switch_to']=='maintenance')?1:0));
		$prefs->backup();
	}

	redirect();
}

/*
 *  Install some Applications on a specific server
 */
if ($_REQUEST['name'] == 'Application_Server') {
	if (! checkAuthorization('manageServers'))
		redirect();

	if (!isset($_REQUEST['server']) || !isset($_REQUEST['application']))
		redirect();

	if (! is_array($_REQUEST['application']))
		$_REQUEST['application'] = array($_REQUEST['application']);

	$applicationDB = ApplicationDB::getInstance();

	$apps = array();
	foreach($_REQUEST['application'] as $id)
		$apps[]= $applicationDB->import($id);

	if ($_REQUEST['action'] == 'add')
		$t = new Task_install(0, $_REQUEST['server'], $apps);
	else
		$t = new Task_remove(0, $_REQUEST['server'], $apps);

	$tm = new Tasks_Manager();
	$tm->add($t);
	if ($_REQUEST['action'] == 'add')
		popup_info(_('Task successfully added'));
	else if ($_REQUEST['action'] == 'del')
		popup_info(_('Task successfully deleted'));
	redirect();

}
/*
if ($_REQUEST['name'] == 'ApplicationGroup_Server') {
	if (!isset($_REQUEST['server']) || !isset($_REQUEST['group']))
		redirect();

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

	redirect();
}*/

if ($_REQUEST['name'] == 'Application') {
	if ($_REQUEST['action'] == 'del') {
		$applicationDB = ApplicationDB::getInstance();
		
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
	if (! checkAuthorization('manageApplicationsGroups'))
		redirect();

	if ($_REQUEST['action'] == 'add') {
		$ret = Abstract_Liaison::save('AppsGroup', $_REQUEST['element'], $_REQUEST['group']);
		if ($ret === true) {
			$applicationsGroupDB = ApplicationsGroupDB::getInstance();
			$group = $applicationsGroupDB->import($_REQUEST['group']);
			if (is_object($group))
				popup_info(sprintf(_('ApplicationGroup \'%s\' successfully modified'), $group->name));
		}
	}

	if ($_REQUEST['action'] == 'del') {
		$ret = Abstract_Liaison::delete('AppsGroup', $_REQUEST['element'], $_REQUEST['group']);
		if ($ret === true) {
			$applicationsGroupDB = ApplicationsGroupDB::getInstance();
			$group = $applicationsGroupDB->import($_REQUEST['group']);
			if (is_object($group))
				popup_info(sprintf(_('ApplicationGroup \'%s\' successfully modified'), $group->name));
		}
	}
}

if ($_REQUEST['name'] == 'User_UserGroup') {
	if (! checkAuthorization('manageUsersGroups'))
		redirect();

	if ($_REQUEST['action'] == 'add') {
		$ret = Abstract_Liaison::save('UsersGroup', $_REQUEST['element'], $_REQUEST['group']);
		if ($ret === true) {
			$userGroupDB = UserGroupDB::getInstance();
			$group = $userGroupDB->import($_REQUEST['group']);
			if (is_object($group)) {
				popup_info(sprintf(_('UsersGroup \'%s\' successfully modified'), $group->name));
			}
			else {
				// problem, what to do ?
				popup_info(sprintf(_('UsersGroup \'%s\' successfully modified'), $_REQUEST['group']));
			}
		}
	}

	if ($_REQUEST['action'] == 'del') {
		$ret = Abstract_Liaison::delete('UsersGroup', $_REQUEST['element'], $_REQUEST['group']);
		if ($ret === true) {
			$userGroupDB = UserGroupDB::getInstance();
			$group = $userGroupDB->import($_REQUEST['group']);
			if (is_object($group)) {
				popup_info(sprintf(_('UsersGroup \'%s\' successfully modified'), $group->name));
			}
			else {
				// problem, what to do ?
				popup_info(sprintf(_('UsersGroup \'%s\' successfully modified'), $_REQUEST['group']));
			}
		}
	}
}

if ($_REQUEST['name'] == 'Publication') {
	if (! checkAuthorization('managePublications'))
		redirect();

	if (!isset($_REQUEST['group_a']) or !isset($_REQUEST['group_u']))
		redirect();

	if ($_REQUEST['action'] == 'add') {
		$l = Abstract_Liaison::load('UsersGroupApplicationsGroup', $_REQUEST['group_u'], $_REQUEST['group_a']);
		if (is_null($l)) {
			$ret = Abstract_Liaison::save('UsersGroupApplicationsGroup', $_REQUEST['group_u'], $_REQUEST['group_a']);
			if ($ret === true)
				popup_info(_('Publication successfully added'));
			else
				popup_error(_('Unable to save the publication'));
		}
		else
			popup_error(_('This publication already exists'));
	}

	if ($_REQUEST['action'] == 'del') {
		$l = Abstract_Liaison::load('UsersGroupApplicationsGroup', $_REQUEST['group_u'], $_REQUEST['group_a']);
		if (! is_null($l)) {
			$ret = Abstract_Liaison::delete('UsersGroupApplicationsGroup', $_REQUEST['group_u'], $_REQUEST['group_a']);
			if ($ret === true)
				popup_info(_('Publication successfully deleted'));
			else
				popup_error(_('Unable to delete the publication'));
		}
		else
			popup_error(_('This publication does not exist'));

	}
}

if ($_REQUEST['name'] == 'UserGroup_PolicyRule') {
	if (! checkAuthorization('manageUsersGroups'))
		redirect();

	if (!isset($_REQUEST['id']) 
		or !isset($_REQUEST['element'])
		or !in_array($_REQUEST['action'], array('add', 'del'))) {
		popup_error('Error usage');
		redirect();
	}

	if (isset($_SESSION['admin_ovd_user'])) {
		$policy = $_SESSION['admin_ovd_user']->getPolicy();
		if (! $policy['manageUsersGroup']) {
			Logger::warning('main', 'User(id='.$_SESSION['admin_ovd_user']->getAttribute('uid').') is  not allowed to perform UserGroup_PolicyRule add('.$_REQUEST['element'].')');
			popup_error('You are not allowed to perform this action');
			redirect();
		}
	}

	$userGroupDB = UserGroupDB::getInstance();
	$group = $userGroupDB->import($_REQUEST['id']);
	$policy = $group->getPolicy(false);

	if ($_REQUEST['action'] == 'add')
		$policy[$_REQUEST['element']] = true;
	else
		$policy[$_REQUEST['element']] = false;

	$group->updatePolicy($policy);
	popup_info(sprintf(_('UsersGroup \'%s\' successfully modified'), $group->name));
	redirect();
}

if ($_REQUEST['name'] == 'User') {
	if (! checkAuthorization('manageUsers')) {
		redirect('users.php');
	}
	$userDB = UserDB::getInstance();
	if (! $userDB->isWriteable()) {
		die_error(_('User Database not writeable'), __FILE__, __LINE__);
	}
	
	if ($_REQUEST['action'] == 'add') {
		$minimun_attributes =  array_unique(array_merge(array('login', 'displayname', 'uid',  'password'), get_needed_attributes_user_from_module_plugin()));
		if (!isset($_REQUEST['login']) or !isset($_REQUEST['displayname']) or !isset($_REQUEST['password']))
			die_error(_("Unable to create user"), __FILE__, __LINE__);
		
		$u = new User();
		foreach ($minimun_attributes as $attributes) {
			if (isset($_REQUEST[$attributes])) {
				$u->setAttribute($attributes, $_REQUEST[$attributes]);
			}
		}
		if (!isset($_REQUEST['uid']) || ($_REQUEST['uid'] == ''))
			$u->setAttribute('uid', str2num($_REQUEST['login']));
		else
			$u->setAttribute('uid', $_REQUEST['uid']);
		
		$res = $userDB->add($u);
		if (! $res)
			die_error(sprintf(_("Unable to create user '%s'",$_REQUEST['login'])), __FILE__, __LINE__);
		
		popup_info(sprintf(_("User '%s' successfully added"), $u->getAttribute('login')));
		redirect('users.php');
	}
			
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_users']) && is_array($_REQUEST['checked_users'])) {
			foreach ($_REQUEST['checked_users'] as $user_login) {
				$u = $userDB->import($user_login);
				$res = $userDB->remove($u);
				
				if (! $res) {
					die_error(sprintf(_("Unable to delete user '%s'"), $user_login), __FILE__, __LINE__);
				}
				else {
					popup_info(sprintf(_("User '%s' successfully deleted"), $user_login));
				}
			}
		}
		redirect('users.php');
	}
	
	if ($_REQUEST['action'] == 'modify') {
		$login = $_REQUEST['id'];
		$u = $userDB->import($login);
		
		if (! is_object($u))
			popup_info(sprintf(_("Unable to import user '%s'"), $login), __FILE__, __LINE__);
		
		foreach($u->getAttributesList() as $attr) {
			if (isset($_REQUEST[$attr])) {
				$u->setAttribute($attr, $_REQUEST[$attr]);
			}
		}
		
		$res = $userDB->update($u);
		if (! $res)
			die_error(sprintf(_("Unable to modify user '%s'"), $u->getAttribute('login')), __FILE__, __LINE__);
		
		popup_info(sprintf(_("User '%s' successfully modified"), $u->getAttribute('login')));
		redirect('users.php?action=manage&id='.$u->getAttribute('login'));
	}
}

if ($_REQUEST['name'] == 'default_browser') {
	if (! checkAuthorization('manageApplications'))
		redirect();

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
	if (! checkAuthorization('manageApplications'))
		redirect();

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
				popup_info(sprintf(_('Application \'%s\' successfully deleted'), $app->getAttribute('name')));
			}
		}
	}
}

if ($_REQUEST['name'] == 'SharedFolder') {
	if (! checkAuthorization('manageSharedFolders'))
		redirect();

	if ($_REQUEST['action']=='add') {
		action_add_sharedfolder();
		popup_info(_('SharedFolder successfully added'));
		redirect();
	}
	elseif ($_REQUEST['action']=='del') {
		if (isset($_REQUEST['id'])) {
			action_del_sharedfolder($_REQUEST['id']);
			popup_info(_('SharedFolder successfully deleted'));
			redirect();
		}
	}
}

if ($_REQUEST['name'] == 'SharedFolder_ACL') {
	if (! checkAuthorization('manageSharedFolders'))
		redirect();

	if ($_REQUEST['action'] == 'add' && isset($_REQUEST['sharedfolder_id']) && isset($_REQUEST['usergroup_id'])) {
		action_add_sharedfolder_acl($_REQUEST['sharedfolder_id'], $_REQUEST['usergroup_id']);
		popup_info(_('SharedFolder successfully modified'));
		redirect();
	}
	elseif ($_REQUEST['action'] == 'del' && isset($_REQUEST['sharedfolder_id']) && isset($_REQUEST['usergroup_id'])) {
		action_del_sharedfolder_acl($_REQUEST['sharedfolder_id'], $_REQUEST['usergroup_id']);
		popup_info(_('SharedFolder successfully modified'));
		redirect();
	}
}

if ($_REQUEST['name'] == 'News') {
	if ($_REQUEST['action'] == 'add' && isset($_REQUEST['news_title']) && isset($_REQUEST['news_content'])) {
		$news = new News('');
		$news->title = $_REQUEST['news_title'];
		if ($news->title == '')
			$news->title = '('._('Untitled').')';
		$news->content = $_REQUEST['news_content'];
		$news->timestamp = time();
		$ret = Abstract_News::save($news);
		if ($ret === true)
			popup_info(_('News successfully added'));
		redirect();
	}
	elseif ($_REQUEST['action'] == 'del' && isset($_REQUEST['id'])) {
		$buf = Abstract_News::delete($_REQUEST['id']);

		if (! $buf)
			popup_error(_('Unable to delete this news'));
		else
			popup_info(_('News successfully deleted'));

		redirect();
	}
}

if ($_REQUEST['name'] == 'password') {
	if ($_REQUEST['action'] == 'change') {
		if (isset($_REQUEST['password']) && isset($_REQUEST['password_confirm'])) {
			if ($_REQUEST['password'] != $_REQUEST['password_confirm']) {
				popup_error(_('Passwords are not identical'));
			}
			else {
				$ret = change_admin_password($_REQUEST['password']);
				if ($ret) {
					popup_info(_('Password successfully changed'));
					redirect('configuration-sumup.php');
				}
				else {
					popup_error(_('Password not changed'));
				}
			}
		}
		redirect();
	}
	
}

if ($_REQUEST['name'] == 'Server') {
	if (! checkAuthorization('manageServers'))
		redirect();
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_servers']) && is_array($_REQUEST['checked_servers'])) {
			foreach ($_REQUEST['checked_servers'] as $fqdn) {
				$sessions = Sessions::getByServer($fqdn);
				if (count($sessions) > 0) {
					popup_error(sprintf_("Unable to delete the server '%s' because there are active sessions on it."), $fqdn);
					continue; 
				}
			
				$buf = Abstract_Server::load($fqdn);
				if (is_object($buf)) {
					$buf->orderDeletion();
					Abstract_Server::delete($buf->fqdn);
					popup_info(sprintf(_("Server '%s' successfully deleted"), $buf->getAttribute('fqdn')));
				}
			}
			$buf = count(Servers::getUnregistered());
			if ($buf == 0)
				redirect('servers.php');
			else
				redirect('servers.php?view=unregistered');
		}
	}
	
	if ($_REQUEST['action'] == 'register') {
		if (isset($_REQUEST['checked_servers']) && is_array($_REQUEST['checked_servers'])) {
			foreach ($_REQUEST['checked_servers'] as $server) {
				$buf = Abstract_Server::load($server);
				$res = $buf->register();
				if ($res) {
					Abstract_Server::save($buf);
					$buf->updateApplications();
					popup_info(sprintf(_("Server '%s' successfully registered"), $buf->getAttribute('fqdn')));
				}
				else {
					popup_info(sprintf(_("Failed to register Server '%s'"), $buf->getAttribute('fqdn')));
				}
			}
		}
		
		$buf = count(Servers::getUnregistered());
		if ($buf == 0)
			redirect('servers.php');
		else
			redirect('servers.php?view=unregistered');
	}
	
	if ($_REQUEST['action'] == 'maintenance') {
		if (isset($_REQUEST['checked_servers']) && is_array($_REQUEST['checked_servers']) && (isset($_REQUEST['to_maintenance']) || isset($_REQUEST['to_production']))) {
			foreach ($_REQUEST['checked_servers'] as $server) {
				$a_server = Abstract_Server::load($server);
				if (isset($_REQUEST['to_maintenance']))
					$a_server->setAttribute('locked', true);
				elseif (isset($_REQUEST['to_production']) && $a_server->isOnline())
					$a_server->setAttribute('locked', false);
	
				Abstract_Server::save($a_server);
				popup_info(sprintf(_("Server '%s' successfully modified"), $a_server->getAttribute('fqdn')));
			}
		}
		redirect('servers.php');
	}
	
	if ($_REQUEST['action'] == 'available_sessions') {
		if (isset($_REQUEST['fqdn']) && isset($_REQUEST['max_sessions'])) {
			$server = Abstract_Server::load($_REQUEST['fqdn']);
			$server->setAttribute('max_sessions', $_REQUEST['max_sessions']);
			Abstract_Server::save($server);
			popup_info(sprintf(_("Server '%s' successfully modified"), $server->getAttribute('fqdn')));
			
			redirect('servers.php?action=manage&fqdn='.$server->getAttribute('fqdn'));
		}
	}
	
	if ($_REQUEST['action'] == 'external_name') {
		if (isset($_REQUEST['external_name']) && isset($_REQUEST['fqdn'])) {
			$server = Abstract_Server::load($_REQUEST['fqdn']);
			$server->setAttribute('external_name', $_REQUEST['external_name']);
			Abstract_Server::save($server);
			popup_info(sprintf(_("Server '%s' successfully modified"), $server->getAttribute('fqdn')));
		
			redirect('servers.php?action=manage&fqdn='.$server->getAttribute('fqdn'));
		}
	}
	
	if ($_REQUEST['action'] == 'install_line') {
		if (isset($_REQUEST['fqdn']) && isset($_REQUEST['line'])) {
			//FIX ME ?
			$t = new Task_install_from_line(0, $_REQUEST['fqdn'], $_REQUEST['line']);
		
			$tm = new Tasks_Manager();
			$tm->add($t);
			popup_info(_('Task successfully added'));
		
			redirect('servers.php?action=manage&fqdn='.$_REQUEST['fqdn']);
		}
	}
	
	if ($_REQUEST['action'] == 'upgrade') {
		if (isset($_REQUEST['fqdn'])) {
			$t = new Task_upgrade(0, $_REQUEST['fqdn']);
		
			$tm = new Tasks_Manager();
			$tm->add($t);
			
			popup_info(sprintf(_("Server '%s' is upgrading"), $_REQUEST['fqdn']));
			redirect('servers.php?action=manage&fqdn='.$_REQUEST['fqdn']);
		}
	}
	
	if ($_REQUEST['action'] == 'replication') {
		if (isset($_REQUEST['fqdn']) && isset($_REQUEST['servers'])) {
			$server_from = Abstract_Server::load($_REQUEST['fqdn']);
			$applications_from = $server_from->getApplications();
		
			$servers_fqdn = $_REQUEST['servers'];
			foreach($servers_fqdn as $server_fqdn) {
				$server_to = Abstract_Server::load($server_fqdn);
				$applications_to = $server_to->getApplications();
		
				$to_delete = array();
				foreach($applications_to as $app) {
					if (! in_array($app, $applications_from))
						$to_delete[]= $app;
				}
		
				$to_install = array();
				foreach($applications_from as $app) {
					if (! in_array($app, $applications_to))
						$to_install[]= $app;
				}
				/*
				echo 'replicate '.$_REQUEST['fqdn'].' on '.$server_fqdn.'<br>';
				echo 'to_install: ';
				var_dump($to_install);
				echo '<br>to remove: ';
				var_dump($to_delete);
				echo '<hr/>';
				die();
				*/
				//FIX ME ?
				$tm = new Tasks_Manager();
				if (count($to_delete) > 0) {
					$t = new Task_remove(0, $server_fqdn, $to_delete);
					popup_info(_('Task successfully deleted'));
					$tm->add($t);
				}
				if (count($to_install) > 0) {
					$t = new Task_install(0, $server_fqdn, $to_install);
					$tm->add($t);
					popup_info(_('Task successfully added'));
				}
			}
			redirect();
		}
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
	$ret = Abstract_SharedFolder::save($buf);

	if ($ret === true)
		popup_info(sprintf(_('SharedFolder \'%s\' successfully added'), $buf->name));
	return true;
}

function action_del_sharedfolder($sharedfolder_id) {
	$buf = Abstract_SharedFolder::delete($sharedfolder_id);

	if (! $buf) {
		popup_error(_('Unable to delete this shared folder'));
		return false;
	}

	popup_info(_('SharedFolder successfully deleted'));
	return true;
}

function action_add_sharedfolder_acl($sharedfolder_id_, $usergroup_id_) {
	$sharedfolder = Abstract_SharedFolder::load($sharedfolder_id_);
	if (! $sharedfolder) {
		popup_error(_('Unable to create this shared folder access'));
		return false;
	}

	$ret = Abstract_SharedFolder::add_acl($sharedfolder, $usergroup_id_);
	
	if ($ret === true)
		popup_info(_('SharedFolder successfully modified'));
	
	return true;
}

function action_del_sharedfolder_acl($sharedfolder_id_, $usergroup_id_) {
	$sharedfolder = Abstract_SharedFolder::load($sharedfolder_id_);
	if (! $sharedfolder) {
		popup_error(_('Unable to delete this shared folder access'));
		return false;
	}

	$ret = Abstract_SharedFolder::del_acl($sharedfolder, $usergroup_id_);
	if ($ret === true)
		popup_info(_('SharedFolder successfully modified'));
	return true;
}

redirect();
