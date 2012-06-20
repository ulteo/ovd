<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2012
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

if (! is_array($_SESSION) || ! array_key_exists('admin_login', $_SESSION))
	redirect('index.php');

if (!isset($_SERVER['HTTP_REFERER']))
	redirect('index.php');

if (!isset($_REQUEST['name']))
	redirect();

if (!isset($_REQUEST['action']))
	redirect();

if (! in_array($_REQUEST['action'], array('add', 'del', 'change', 'clone', 'icon', 'modify', 'register', 'install_line', 'upgrade', 'replication', 'maintenance', 'available_sessions', 'external_name', 'rename', 'populate', 'publish', 'del_icon', 'unset_default', 'set_default', 'modify_rules', 'remove_orphan')))
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
	foreach($_REQUEST['application'] as $id) {
		$app = $applicationDB->import($id);
		if (! $app)
			continue;

		if ($app->getAttribute('static') == false) {
			if ($_REQUEST['action'] == 'add') {
				$tm = new Tasks_Manager();
				$t = new Task_install(0, $_REQUEST['server'], array($app));
				$tm->add($t);

				$msg = _('Task to add application \'%APPLICATION%\' on server \'%SERVER%\' was successfully added');
				popup_info(str_replace(array('%APPLICATION%', '%SERVER%'), array($id, $_REQUEST['server']), $msg));
			} elseif ($_REQUEST['action'] == 'del') {
				$tm = new Tasks_Manager();
				$t = new Task_remove(0, $_REQUEST['server'], array($app));
				$tm->add($t);

				$msg = _('Task to remove application \'%APPLICATION%\' from server \'%SERVER%\' was successfully added');
				popup_info(str_replace(array('%APPLICATION%', '%SERVER%'), array($id, $_REQUEST['server']), $msg));
			}
		} else {
			if ($_REQUEST['action'] == 'add') {
				$server = Abstract_Server::load($_REQUEST['server']);
				if (is_object($server)) {
					Abstract_Liaison::save('ApplicationServer', $id, $_REQUEST['server']);

					$server->syncStaticApplications();

					$msg = _('Application \'%APPLICATION%\' successfully added to server \'%SERVER%\'');
					popup_info(str_replace(array('%APPLICATION%', '%SERVER%'), array($id, $_REQUEST['server']), $msg));
				} else {
					$msg = _('An error occured while adding application \'%APPLICATION%\' to server \'%SERVER%\'');
					popup_error(str_replace(array('%APPLICATION%', '%SERVER%'), array($id, $_REQUEST['server']), $msg));
				}
			} elseif ($_REQUEST['action'] == 'del') {
				Abstract_Liaison::delete('ApplicationServer', $id, $_REQUEST['server']);

				$msg = _('Application \'%APPLICATION%\' successfully deleted from server \'%SERVER%\'');
				popup_info(str_replace(array('%APPLICATION%', '%SERVER%'), array($id, $_REQUEST['server']), $msg));
			}
		}
	}

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
	if (! checkAuthorization('manageApplications'))
		redirect();

	$applicationDB = ApplicationDB::getInstance();
	if (! $applicationDB->isWriteable()) {
		die_error(_('ApplicationDB is not writeable'),__FILE__,__LINE__);
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['id'])) {
			$app = $applicationDB->import($_REQUEST['id']);
			if (! is_object($app)) {
				popup_error(sprintf(_("Failed to import application '%s'"), $_REQUEST['id']));
				redirect();
			}
			$ret = $applicationDB->remove($app);
			if (! $ret) {
				popup_error(sprintf(_("Failed to delete application '%s'"), $app->getAttribute('name')));
				redirect();
			}
			popup_info(sprintf(_("Application '%s' successfully deleted"), $app->getAttribute('name')));
		}
	}
	
	if ($_REQUEST['action'] == 'publish') {
		if (isset($_REQUEST['checked_applications']) && is_array($_REQUEST['checked_applications']) && isset($_REQUEST['published'])) {
			foreach ($_REQUEST['checked_applications'] as $id) {
				$app = $applicationDB->import($id);
				if (!is_object($app)) {
					die_error(sprintf(_("Unable to import application %s"), $id), __FILE__, __LINE__);
				}
				
				$app->setAttribute('published', $_REQUEST['published']);
				
				$res = $applicationDB->update($app);
				if (! $res) {
					die_error(sprintf(_("Unable to modify application '%s'"), $id), __FILE__ ,__LINE__);
				}
			}
			popup_info(sprintf(_("Application '%s' successfully modified"), $app->getAttribute('name')));
		}
	}
	
	if ($_REQUEST['action'] == 'remove_orphan') {
		$applicationsDB = ApplicationDB::getInstance();
		$apps = $applicationsDB->getList();
		$success = true;
		if (is_array($apps)) {
			foreach ($apps as $app) {
				if ($app->isOrphan()) {
					$ret = $applicationsDB->remove($app);
					if ($ret !== true) {
						$success = false;
						break;
					}
				}
			}
			if ($success) {
				popup_info(_("Orphan applications successfully removed"));
			}
			else {
				popup_error(_("Problem while removing orphan applications"));
			}
		}
	}
	
	if ($_REQUEST['action'] == 'clone') {
		$applicationsDB = ApplicationDB::getInstance();
		$app = $applicationDB->import($_REQUEST['id']);
		if (! is_object($app)) {
			popup_error(sprintf(_("Failed to import application '%s'"), $_REQUEST['id']));
			redirect();
		}
		$icon_path = $app->getIconPath();
		$servers_liaisons = Abstract_Liaison::load('ApplicationServer', $app->getAttribute('id'), NULL);
		
		$app->unsetAttribute('id');
		$app->setAttribute('static', 1);
		$app->setAttribute('revision', 1);
		$ret = $applicationDB->add($app);
		if (! $ret) {
			popup_error(sprintf(_("Failed to add application '%s'"), $app->getAttribute('name')));
			redirect();
		}
		popup_info(sprintf(_("Application '%s' successfully added"), $app->getAttribute('name')));
		
		// Clone Icon
		$path_rw = $app->getIconPathRW();
		if (is_writable2($path_rw)) {
			@file_put_contents($path_rw, @file_get_contents($icon_path));
		}
		else {
			Logger::error('main', 'Unable to copy application icon ('.$path_rw.') is not writeable');
		}
		
		// Clone servers list
		foreach ($servers_liaisons as $liaison) {
			Abstract_Liaison::save('ApplicationServer', $app->getAttribute('id'), $liaison->group);
			$buf_server = Abstract_Server::load($liaison->group);
			if (is_object($buf_server))
				$buf_server->syncStaticApplications();
		}
		
		redirect('applications_static.php?action=manage&id='.$app->getAttribute('id'));
	}

	if ($_REQUEST['action'] == 'icon') {
		$applicationsDB = ApplicationDB::getInstance();
		$app = $applicationDB->import($_REQUEST['id']);
		if (! is_object($app)) {
			popup_error(sprintf(_("Failed to import application '%s'"), $_REQUEST['id']));
			redirect();
		}

		if (array_key_exists('file_icon', $_FILES)) {
			$upload = $_FILES['file_icon'];

			$have_file = true;
			if ($upload['error']) {
				switch ($upload['error']) {
					case 1: // UPLOAD_ERR_INI_SIZE
						popup_error(_('Oversized file for server rules'));
						redirect();
						break;
					case 3: // UPLOAD_ERR_PARTIAL
						popup_error(_('The file was corrupted while upload'));
						redirect();
						break;
					case 4: // UPLOAD_ERR_NO_FILE
						$have_file = false;
						break;
				}
			}

			if ($have_file) {
				$source_file = $upload['tmp_name'];
				if (! is_readable($source_file)) {
					popup_error(_('The file is not readable'));
					redirect();
				}

				if (get_classes_startwith('Imagick') == array()) {
					popup_error(_('No Imagick support found'));
					redirect();
				}

				$path_rw = $app->getIconPathRW();
				if (! is_writable2($path_rw)) {
					popup_error(sprintf(_("Unable to write icon file for application '%s'"), $app->getAttribute('id')));
					redirect();
				}

				try {
					$mypicture = new Imagick();
					$mypicture->readImage($source_file);
					$mypicture->scaleImage(32, 0);
					$mypicture->writeImage($app->getIconPathRW());
				}
				catch (Exception $e) {
					popup_error(_('Uploaded file is not a valid image'));
					redirect();
				}
			}

			popup_info(sprintf(_("Icon for application '%s' has been successfully uploaded"), $app->getAttribute('id')));
			redirect('applications.php?action=manage&id='.$app->getAttribute('id'));
		}

		if (array_key_exists('server', $_REQUEST)) {
			$server = Abstract_Server::load($_REQUEST['server']);
			if (! $server) {
				popup_error(sprintf(_("Failed to load server '%s'"), $_REQUEST['server']));
				redirect();
			}

			if ($server->isOnline())
				$server->getApplicationIcon($app->getAttribute('id'));

			popup_info(sprintf(_("Icon of application '%s' has been updated from server '%s'"), $app->getAttribute('id'), $server->getAttribute('fqdn')));
			redirect('applications.php?action=manage&id='.$app->getAttribute('id'));
		}

		redirect();
	}
}

if ($_REQUEST['name'] == 'Application_static') {
	if (! checkAuthorization('manageApplications'))
		redirect();
	
	$applicationDB = ApplicationDB::getInstance();
	if (! $applicationDB->isWriteable()) {
		die_error(_('ApplicationDB is not writeable'),__FILE__,__LINE__);
	}
	
	if ($_REQUEST['action'] == 'add') {
		if (isset($_REQUEST['attributes_send']) && is_array($_REQUEST['attributes_send'])) {
			$data = array();
			foreach ($_REQUEST['attributes_send'] as $attrib_name) {
				if (isset($_REQUEST[$attrib_name]))
					$data[$attrib_name] = $_REQUEST[$attrib_name];
			}
			
			$data['id'] = 666; // little hack
			if (isset($data['application_name'])) {
				$data['name'] = $data['application_name'];
				unset($data['application_name']);
			}
			
			$a = $applicationDB->generateApplicationFromRow($data);
			
			if (! $applicationDB->isOK($a)) {
				popup_error(_("Application is not ok"));
			}
			$a->unsetAttribute('id');
			
			$a->setAttribute('revision', 1);
			$ret = $applicationDB->add($a);
			if (! $ret) {
				popup_error(sprintf(_("Failed to add application '%s'"), $a->getAttribute('name')));
			}
			
			popup_info(sprintf(_("Application '%s' successfully added"), $a->getAttribute('name')));
			redirect('applications_static.php?action=manage&id='.$a->getAttribute('id'));
		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_applications']) && is_array($_REQUEST['checked_applications'])) {
			foreach ($_REQUEST['checked_applications'] as $id) {
				$app = $applicationDB->import($id);
				if (! is_object($app)) {
					die_error(sprintf(_("Unable to import application '%s'"), $id), __FILE__, __LINE__);
				}
				Abstract_Liaison::delete('ApplicationServer', $app->getAttribute('id'), NULL);
				$ret = $applicationDB->remove($app);
				if (! $ret) {
					popup_error(sprintf(_("Failed to delete application '%s'"), $app->getAttribute('name')));
				}
				$servers = Abstract_Server::load_available_by_type($app->getAttribute('type'), true);
				foreach ($servers as $server)
					$server->syncStaticApplications();
				popup_info(sprintf(_("Application '%s' successfully deleted"), $app->getAttribute('name')));
			}
			redirect('applications_static.php');
		}
	}
	
	if ($_REQUEST['action'] == 'del_icon') {
		if (isset($_REQUEST['checked_applications']) && is_array($_REQUEST['checked_applications'])) {
			foreach ($_REQUEST['checked_applications'] as $id) {
				$app = $applicationDB->import($id);
				$app->delIcon();
				popup_info(sprintf(_("'%s' application's icon was successfully deleted"), $app->getAttribute('name')));
				redirect('applications_static.php?action=manage&id='.$app->getAttribute('id'));
			}
		}
	}
	
	if ($_REQUEST['action'] == 'modify') {
		if (isset($_REQUEST['id']) && isset($_REQUEST['attributes_send']) && is_array($_REQUEST['attributes_send'])) {
			$data = array();
			foreach ($_REQUEST['attributes_send'] as $attrib_name) {
				if (isset($_REQUEST[$attrib_name]))
					$data[$attrib_name] = $_REQUEST[$attrib_name];
			}
			
			if (isset($data['application_name'])) {
				$data['name'] = $data['application_name'];
				unset($data['application_name']);
			}
			
			$app = $applicationDB->import($_REQUEST['id']);
			if (!is_object($app)) {
				die_error(sprintf(_("Unable to import application '%s'"), $_REQUEST['id']), __FILE__, __LINE__);
			}
			
			$attr_list = $app->getAttributesList();
			foreach ($data as $k => $v) {
				if (in_array($k, $attr_list)) {
					$app->setAttribute($k, $v);
				}
			}
			$app->setAttribute('revision', ($app->getAttribute('revision')+1));
			$ret = $applicationDB->update($app);
			if (! $ret) {
				popup_error(sprintf(_("Failed to modify application '%s'"), $app->getAttribute('name')));
			}
			$servers = Abstract_Server::load_available_by_type($app->getAttribute('type'), true);
			foreach ($servers as $server)
				$server->syncStaticApplications();
			popup_info(sprintf(_("Application '%s' successfully modified"), $app->getAttribute('name')));
			
			if (array_key_exists('file_icon', $_FILES)) {
				$upload = $_FILES['file_icon'];
		
				$have_file = true;
				if($upload['error']) {
					switch ($upload['error']) {
						case 1: // UPLOAD_ERR_INI_SIZE
							popup_error(_('Oversized file for server rules'));
							redirect();
							break;
						case 3: // UPLOAD_ERR_PARTIAL
							popup_error(_('The file was corrupted while upload'));
							redirect();
							break;
						case 4: // UPLOAD_ERR_NO_FILE
							$have_file = false;
							break;
					}
				}
				
				if ($have_file) {
					$source_file = $upload['tmp_name'];
					if (! is_readable($source_file)) {
						popup_error(_('The file is not readable'));
						redirect();
					}
					
					if ( get_classes_startwith('Imagick') != array()) {
						
						$path_rw = $app->getIconPathRW();
						if (is_writable2($path_rw)) {
							try {
								$mypicture = new Imagick();
								$mypicture->readImage($source_file);
								$mypicture->scaleImage(32, 0);
								$mypicture->writeImage($app->getIconPathRW());
							}
							catch (Exception $e) {
								popup_error(_('The icon is not an image'));
								redirect();
							}
						}
						else {
							Logger::error('main', 'getIconPathRW ('.$path_rw.') is not writeable');
							redirect();
						}
					}
					else {
						Logger::info('main', 'No Imagick support found');
					}
				}
			}
		}
	}
}

if ($_REQUEST['name'] == 'Application_MimeType') {
	if (! checkAuthorization('manageApplications'))
		redirect();
	
	$applicationDB = ApplicationDB::getInstance();
	if (! $applicationDB->isWriteable()) {
		die_error(_('ApplicationDB is not writeable'),__FILE__,__LINE__);
	}
	
	if ($_REQUEST['action'] == 'add') {
		if (isset($_REQUEST['id']) && isset($_REQUEST['mime'])) {
			$app = $applicationDB->import($_REQUEST['id']);
			if (!is_object($app)) {
				die_error(sprintf(_("Unable to import application '%s'"), $_REQUEST['id']), __FILE__, __LINE__);
			}
			
			$mimes = $app->getMimeTypes();
			if (! in_array($_REQUEST['mime'], $mimes)) {
				$mimes []= $_REQUEST['mime'];
				$app->setMimeTypes($mimes);
				
				$ret = $applicationDB->update($app);
				if (! $ret)
					popup_error(sprintf(_("Failed to modify application '%s'"), $app->getAttribute('name')));
			}
		}
	}
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['id']) && isset($_REQUEST['mime'])) {
			$app = $applicationDB->import($_REQUEST['id']);
			if (!is_object($app)) {
				die_error(sprintf(_("Unable to import application '%s'"), $_REQUEST['id']), __FILE__, __LINE__);
			}
			
			$mimes = $app->getMimeTypes();
			if (in_array($_REQUEST['mime'], $mimes)) {
				unset($mimes[array_search($_REQUEST['mime'], $mimes)]);
				$app->setMimeTypes($mimes);
				
				$ret = $applicationDB->update($app);
				if (! $ret)
					popup_error(sprintf(_("Failed to modify application '%s'"), $app->getAttribute('name')));
			}
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

if ($_REQUEST['name'] == 'ApplicationsGroup') {
	if (! checkAuthorization('manageApplicationsGroups'))
		redirect();
	
	$applicationsGroupDB = ApplicationsGroupDB::getInstance();
	if (! $applicationsGroupDB->isWriteable()) {
		die_error(_('Application Group Database is not writeable'), __FILE__, __LINE__);
	}
	
	if ($_REQUEST['action'] == 'add') {
		if ( isset($_REQUEST['name_appsgroup']) && isset($_REQUEST['description_appsgroup'])) {
			$name = $_REQUEST['name_appsgroup'];
			$description = $_REQUEST['description_appsgroup'];
			
			$g = new AppsGroup(NULL, $name, $description, 1);
			$res = $applicationsGroupDB->add($g);
			if (!$res) {
				popup_error(sprintf(_("Unable to create applications group '%s'"), $name));
				redirect('appsgroup.php');
			}
			
			popup_info(sprintf(_("Applications group '%s' successfully added"), $name));
			redirect('appsgroup.php?action=manage&id='.$g->id);
		}

	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_groups']) and is_array($_REQUEST['checked_groups'])) {
			$ids = $_REQUEST['checked_groups'];
			foreach ($ids as $id) {
				$group = $applicationsGroupDB->import($id);
				if (! is_object($group)) {
					popup_error(sprintf(_("Importing applications group '%s' failed"), $id));
					continue;
				}
				
				if (! $applicationsGroupDB->remove($group)) {
					popup_error(sprintf(_("Unable to remove applications group '%s'"), $group->name));
					continue;
				}
				popup_info(sprintf(_("Applications group '%s' successfully deleted"), $group->name));
			}
			redirect('appsgroup.php');
		}
	}
	
	if ($_REQUEST['action'] == 'modify') {
		if (isset($_REQUEST['id']) && (isset($_REQUEST['name_appsgroup']) || isset($_REQUEST['description_appsgroup']) || isset($_REQUEST['published_appsgroup']))) {
			$id = $_REQUEST['id'];
			$group = $applicationsGroupDB->import($id);
			if (! is_object($group))
				popup_error(sprintf(_("Import of applications group '%s' failed"), $id));
			
			$has_change = false;
			
			if (isset($_REQUEST['name_appsgroup'])) {
				$group->name = $_REQUEST['name_appsgroup'];
				$has_change = true;
			}
			
			if (isset($_REQUEST['description_appsgroup'])) {
				$group->description = $_REQUEST['description_appsgroup'];
				$has_change = true;
			}
			
			if (isset($_REQUEST['published_appsgroup'])) {
				$group->published = (bool)$_REQUEST['published_appsgroup'];
				$has_change = true;
			}
			
			if ($has_change) {
				if (! $applicationsGroupDB->update($group))
					popup_error(sprintf(_("Unable to modify applications group '%s'"), $group->name));
				else
					popup_info(sprintf(_("Applications group '%s' successfully modified"), $group->name));
			}
			redirect('appsgroup.php?action=manage&id='.$group->id);
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
		$userGroupDB = UserGroupDB::getInstance();

		$group = $userGroupDB->import($_REQUEST['group']);
		if (! is_object($group)) {
			popup_error(sprintf(_("Usergroup '%s' does not exist"), $_REQUEST['group']));
			redirect();
		}

		if ($group->isDefault()) {
			popup_error(sprintf(_("Unable to remove a user from usergroup '%s' because it is the default usergroup"), $group->name));
			redirect();
		}

		Abstract_Liaison::delete('UsersGroup', $_REQUEST['element'], $_REQUEST['group']);
		popup_info(sprintf(_('UsersGroup \'%s\' successfully modified'), $group->name));
		
		redirect();
	}
}

if ($_REQUEST['name'] == 'Publication') {
	if (! checkAuthorization('managePublications'))
		redirect();

	if (!isset($_REQUEST['group_a']) or !isset($_REQUEST['group_u']))
		redirect();

	if ($_REQUEST['action'] == 'add') {
		$usersGroupDB = UserGroupDB::getInstance();
		$usergroup = $usersGroupDB->import($_REQUEST['group_u']);
		if (is_object($usergroup) == false) {
			popup_error(sprintf(_("Importing usergroup '%s' failed"), $_REQUEST['group_u']));
			redirect();
		}
		$applicationsGroupDB = ApplicationsGroupDB::getInstance();
		$applicationsgroup = $applicationsGroupDB->import($_REQUEST['group_a']);
		if (is_object($applicationsgroup) == false) {
			popup_error(sprintf(_("Importing applications group '%s' failed"), $_REQUEST['group_a']));
			redirect();
		}
		
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

if ($_REQUEST['name'] == 'UserGroup') {
	if (! checkAuthorization('manageUsersGroups'))
		redirect();
		
	$userGroupDB = UserGroupDB::getInstance();
	
	if ($_REQUEST['action'] == 'add') {
		if (isset($_REQUEST['type']) && isset($_REQUEST['name_group']) &&  isset($_REQUEST['description_group'])) {
			if ($_REQUEST['name_group'] == '') {
				popup_error(_('You must define a name for your usergroup'));
				redirect('usersgroup.php');
			}
			
			if ($_REQUEST['type'] == 'static') {
				if (! $userGroupDB->isWriteable()) {
					die_error(_('User Group Database is not writeable'), __FILE__, __LINE__);
				}
				$g = new UsersGroup(NULL,$_REQUEST['name_group'], $_REQUEST['description_group'], 1);
			}
			elseif ($_REQUEST['type'] == 'dynamic') {
				if (Preferences::moduleIsEnabled('UserGroupDBDynamic') == false && $_REQUEST['cached'] === '0') {
					popup_error(_('UserGroupDBDynamic module must be enabled'));
					redirect('usersgroup.php');
				}
				
				if (Preferences::moduleIsEnabled('UserGroupDBDynamicCached') == false && $_REQUEST['cached'] !== '0') {
					popup_error(_('UserGroupDBDynamicCached module must be enabled'));
					redirect('usersgroup.php');
				}
				
				$rules = array();
				foreach ($_POST['rules'] as $rule) {
					if ($rule['value'] == '') {
						popup_error(_('You must provide a value to each rule of your usergroup'));
						redirect();
					}
					
					$buf = new UserGroup_Rule(NULL);
					$buf->attribute = $rule['attribute'];
					$buf->type = $rule['type'];
					$buf->value = $rule['value'];
					
					$rules[] = $buf;
				}
				
				if ($_REQUEST['cached'] === '0')
					$g = new UsersGroup_dynamic(NULL, $_REQUEST['name_group'], $_REQUEST['description_group'], 1, $rules, $_REQUEST['validation_type']);
				else
					$g = new UsersGroup_dynamic_cached(NULL, $_REQUEST['name_group'], $_REQUEST['description_group'], 1, $rules, $_REQUEST['validation_type'], $_REQUEST['schedule']);
			}
			else {
				die_error(_('Unknow usergroup type'));
			}
			
			$res = $userGroupDB->add($g);
			if (!$res) {
				popup_error(sprintf(_("Unable to create usergroup '%s'"), $_REQUEST['name_group']));
				redirect('usersgroup.php');
			}
			
			popup_info(_('UserGroup successfully added'));
			redirect('usersgroup.php?action=manage&id='.$g->getUniqueID());
		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_groups']) && is_array($_REQUEST['checked_groups'])) {
			foreach ($_REQUEST['checked_groups'] as $id) {
				$group = $userGroupDB->import($id);
				if (! is_object($group)) {
					popup_error(sprintf(_("Failed to import Usergroup '%s'"), $id));
					redirect();
				}
				
				if ($group->type == 'static') {
					if (! $userGroupDB->isWriteable()) {
						die_error(_('User Group Database not writeable'), __FILE__, __LINE__);
					}
				}
				
				if (! $userGroupDB->remove($group)) {
					popup_error(sprintf(_("Unable to remove usergroup '%s'"), $id));
				}
				
				popup_info(sprintf(_("UserGroup '%s' successfully deleted"), $group->name));
			}
			redirect('usersgroup.php');
		}
	}
	
	if ($_REQUEST['action'] == 'modify') {
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			if ((str_startswith($id, 'static_')) && (! $userGroupDB->isWriteable())) {
				die_error(_('User Group Database not writeable'), __FILE__, __LINE__);
			}
			
			$group = $userGroupDB->import($id);
			if (! is_object($group)) {
				popup_error(sprintf(_("Failed to import Usergroup '%s'"), $id));
				redirect();
			}
			
			$has_change = false;
			
			if (isset($_REQUEST['name_group'])) {
				$group->name = $_REQUEST['name_group'];
				$has_change = true;
			}
			
			if (isset($_REQUEST['description'])) {
				$group->description = $_REQUEST['description'];
				$has_change = true;
			}
			
			if (isset($_REQUEST['published'])) {
				$group->published = (bool)$_REQUEST['published'];
				$has_change = true;
			}
			
			if (isset($_REQUEST['schedule'])) {
				$group->schedule = $_REQUEST['schedule'];
				$has_change = true;
			}
			
			if (! $has_change)
				return false;
			
			if (! $userGroupDB->update($group)) {
				popup_error(sprintf(_("Unable to update Usergroup '%s'"), $group->name));
				redirect();
			}
			
			popup_info(sprintf(_("UserGroup '%s' successfully modified"), $group->name));
			redirect('usersgroup.php?action=manage&id='.$group->getUniqueID());
		}
	}
	
	if (($_REQUEST['action'] == 'set_default') or ($_REQUEST['action'] == 'unset_default')) {
		if (! checkAuthorization('manageConfiguration')) {
			die_error(_('Not enough rights'));
		}
		if (isset($_REQUEST['id'])) {
			try {
				$prefs = new Preferences_admin();
			}
			catch (Exception $e) {
				// Error header save
				die_error('error R6');
			}
			
			$id = $_REQUEST['id'];
			
			$new_default = ($_REQUEST['action'] == 'set_default')?$id:NULL;
			
			$group = $userGroupDB->import($id);
			if (! is_object($group)) {
				popup_error(sprintf(_("Failed to import group '%s'"), $id));
				redirect();
			}
			
			$mods_enable = $prefs->set('general', 'user_default_group', $new_default);
			if (! $prefs->backup()) {
				Logger::error('main', 'usersgroup.php action_default: Unable to save prefs');
				popup_error(_("Unable to save prefs"));
			}
			
			popup_info(sprintf(_("UserGroup '%s' successfully modified"), $group->name));
			redirect('usersgroup.php?action=manage&id='.$group->getUniqueID());
			
		}
	}
	
	if ($_REQUEST['action'] == 'modify_rules') {
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			$group = $userGroupDB->import($id);
			if (! is_object($group)) {
				popup_error(sprintf(_("Failed to import Usergroup '%s'"), $id));
				redirect();
			}
			
			$rules = array();
			foreach ($_POST['rules'] as $rule) {
				if ($rule['value'] == '') {
					popup_error(_('You must give a value to each rule of your usergroup'));
					redirect();
				}
				
				$buf = new UserGroup_Rule(NULL);
				$buf->attribute = $rule['attribute'];
				$buf->type = $rule['type'];
				$buf->value = $rule['value'];
				$buf->usergroup_id = $id;
				
				$rules[] = $buf;
			}
			$group->rules = $rules;
			
			$group->validation_type = $_REQUEST['validation_type'];
			
			if (! $userGroupDB->update($group)) 
				popup_error(sprintf(_("Unable to update Usergroup '%s'"), $group->name));
			else
				popup_info(sprintf(_("Rules of '%s' successfully modified"), $group->name));
			
			redirect('usersgroup.php?action=manage&id='.$group->getUniqueID());
		}
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
			Logger::warning('main', 'User(login='.$_SESSION['admin_ovd_user']->getAttribute('login').') is  not allowed to perform UserGroup_PolicyRule add('.$_REQUEST['element'].')');
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

if ($_REQUEST['name'] == 'UserGroup_settings') {
	$prefs = Preferences::getInstance();
	$userGroupDB = UserGroupDB::getInstance();
	if (isset($_REQUEST['unique_id']) && isset($_REQUEST['action'])) {
		$unique_id = $_REQUEST['unique_id'];
		$group = $userGroupDB->import($unique_id);
		if (! is_object($group)) {
			popup_error(sprintf(_("Failed to import Usergroup '%s'"), $unique_id));
			redirect();
		}
		$ret = null;
		if ($_REQUEST['action'] == 'add' && isset($_REQUEST['container']) && isset($_REQUEST['element_id'])) {
			$container = $_REQUEST['container'];
			$setting_to_add = $_REQUEST['element_id'];
			$session_settings_defaults = $prefs->getElements('general', $container);
			if (array_key_exists($setting_to_add, $session_settings_defaults) == false) {
				Logger::error('main', "(action.php) UserGroup_settings, add unable to find '$setting_to_add' in '$container'");
				popup_error(_("unable to find '$setting_to_add' in '$container'"));
				redirect();
			}
			
			$config_element = clone $session_settings_defaults[$setting_to_add];
			$ugp = new UserGroup_Preferences($group->getUniqueID(), 'general', $container, $setting_to_add, $config_element->content);
			$ret = Abstract_UserGroup_Preferences::save($ugp);
		}
		else if ($_REQUEST['action'] == 'del' && isset($_REQUEST['container']) && isset($_REQUEST['element_id'])) {
			$container = $_REQUEST['container'];
			$element_id = $_REQUEST['element_id'];
			$ret = Abstract_UserGroup_Preferences::delete($group->getUniqueID(), 'general', $container, $element_id);
		}
		else if ($_REQUEST['action'] == 'modify' && isset($_REQUEST['container'])) {
			$container = $_REQUEST['container'];
			$formdata = array();
			$sep = '___';
			$sepkey = 'general'.$sep.$container;
			foreach ($_REQUEST as $key2 =>  $value2) {
				if ( substr($key2, 0, strlen($sepkey)) == $sepkey) {
					$formdata[$key2] = $value2;
				}
			}
			$formarray = formToArray($formdata);
			if (isset($formarray['general'][$container])) {
				$data = $formarray['general'][$container];
				
				// TODO: to be better...
				$ret = null;
				$todel = Abstract_UserGroup_Preferences::loadByUserGroupId($group->getUniqueID(), 'general', $container);
				foreach ($todel as $key2 => $value2) {
					$ret = Abstract_UserGroup_Preferences::delete($group->getUniqueID(), 'general', $container, $value2->element_id);
					if ( $ret !== true) {
						break;
					}
				}
				
				foreach ($data as $element_id => $value) {
					$ugp = new UserGroup_Preferences($group->getUniqueID(), 'general', $container, $element_id, $value);
					$ret = Abstract_UserGroup_Preferences::save($ugp);
					if ( $ret !== true) {
						break;
					}
				}
			}
		}
		
		if ($ret === true) {
			popup_info(_('Usergroup successfully modified'));
		}
		else if ($ret === false) {
			popup_error(_('Failed to modify usergroup'));
		}
	}
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
		$minimun_attributes = array('login', 'displayname', 'password');
		if (!isset($_REQUEST['login']) or !isset($_REQUEST['displayname']) or !isset($_REQUEST['password']))
			die_error(_("Unable to create user"), __FILE__, __LINE__);
		
		$u = new User();
		foreach ($minimun_attributes as $attributes) {
			if (isset($_REQUEST[$attributes])) {
				$u->setAttribute($attributes, $_REQUEST[$attributes]);
			}
		}
		
		if ($u->hasAttribute('password') && $u->getAttribute('password') === '') {
			popup_error(_('Unable to create user with an empty password'));
			redirect();
		}
		
		$res = $userDB->add($u);
		if (! $res) {
			popup_error(sprintf(_("Unable to create user '%s'"), $_REQUEST['login']));
			redirect();
		}
		
		popup_info(sprintf(_("User '%s' successfully added"), $u->getAttribute('login')));
		redirect('users.php');
	}
			
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_users']) && is_array($_REQUEST['checked_users'])) {
			foreach ($_REQUEST['checked_users'] as $user_login) {
				$sessions = Abstract_Session::getByUser($user_login);
				$has_sessions = count($sessions);
				if ($has_sessions) {
					popup_error(sprintf(_("Unable to delete user '%s' because he has an active session"), $user_login));
				}
				else {
					$u = $userDB->import($user_login);
					if (! is_object($u)) {
						popup_error(sprintf(_("Unable to delete user '%s'"), $user_login));
						redirect();
					}
					if (Preferences::moduleIsEnabled('ProfileDB')) {
						$profiledb = ProfileDB::getInstance();
						$netfolders = $u->getProfiles();
						if (is_array($netfolders)) {
							foreach ($netfolders as $netfolder)
								$profiledb->remove($netfolder->id);
						}
					}
					$res = $userDB->remove($u);
					
					if (! $res) {
						die_error(sprintf(_("Unable to delete user '%s'"), $user_login), __FILE__, __LINE__);
					}
					else {
						popup_info(sprintf(_("User '%s' successfully deleted"), $user_login));
					}
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
			if (isset($_REQUEST[$attr]) && (!($_REQUEST[$attr] === '' && $attr === 'password'))) {
				$u->setAttribute($attr, $_REQUEST[$attr]);
			}
		}
		
		$res = $userDB->update($u);
		if (! $res)
			die_error(sprintf(_("Unable to modify user '%s'"), $u->getAttribute('login')), __FILE__, __LINE__);
		
		popup_info(sprintf(_("User '%s' successfully modified"), $u->getAttribute('login')));
		redirect('users.php?action=manage&id='.$u->getAttribute('login'));
	}
	
	if ($_REQUEST['action'] == 'populate') {
		$override = ($_REQUEST['override'] == '1');
		if ($_REQUEST['password'] == 'custom') {
			if (strlen($_REQUEST['password_str']) == 0) {
				popup_error(_('No custom password given for populating the database.'));
				redirect();
			}
			
			$password = $_REQUEST['password_str'];
		}
		else
			$password = NULL;
		
		$ret = $userDB->populate($override, $password);
		if ($ret) {
			popup_info(_('User database populated.'));
		}
		else {
			popup_error(_('User database population failed.'));
		}
		redirect('users.php');
	}
}

if ($_REQUEST['name'] == 'SharedFolder') {
	if (Preferences::moduleIsEnabled('SharedFolderDB') == false) {
		popup_error(_('Shared folder management is not enabled'));
		redirect();
	}
	
	if (! checkAuthorization('manageSharedFolders'))
		redirect();

	if ($_REQUEST['action']=='add') {
		action_add_sharedfolder();
		redirect();
	}
	
	if ($_REQUEST['action'] == 'del' && array_key_exists('ids', $_REQUEST)) {
		$sharedfolderdb = SharedFolderDB::getInstance();
		$status = true;
		foreach ($_REQUEST['ids'] as $id) {
			$buf = $sharedfolderdb->remove($id);
			if ($buf !== true) {
				$status = false;
				$sharedfolder = $sharedfolderdb->import($id);
				if (is_object($sharedfolder)) {
					popup_error(sprintf(_("Unable to delete network folder '%s'"), $sharedfolder->name));
				}
				else {
					popup_error(sprintf(_("Unable to delete network folder with id '%s'"), $id));
				}
			}
		}
		if ($status)
			popup_info(_("Network folders successfully deleted"));
		
		redirect();
	}
	
	if ($_REQUEST['action'] == 'rename') {
		if (isset($_REQUEST['id']) && isset($_REQUEST['sharedfolder_name'])) {
			$id = $_REQUEST['id'];
			$new_name = $_REQUEST['sharedfolder_name'];
			
			$sharedfolderdb = SharedFolderDB::getInstance();
			$sharedfolder = $sharedfolderdb->import($id);
			if (is_object($sharedfolder)) {
				if (! $sharedfolderdb->exists($new_name) || $new_name == $sharedfolder->name) {
					$sharedfolder->name = $new_name;
					$ret = $sharedfolderdb->update($sharedfolder);
					if ($ret === true)
						popup_info(_('Shared folder successfully renamed'));
					else
						popup_error(_('Update of the shared folder failed'));
				} else
					popup_error(_('A shared folder with that name already exists!'));
			}
			redirect('sharedfolders.php?action=manage&id='.$id);
		}
	}
}

if ($_REQUEST['name'] == 'SharedFolder_ACL') {
	if (Preferences::moduleIsEnabled('SharedFolderDB') == false) {
		popup_error(_('Shared folder management is not enabled'));
		redirect();
	}
	
	if (! checkAuthorization('manageSharedFolders'))
		redirect();

	if ($_REQUEST['action'] == 'add' && isset($_REQUEST['sharedfolder_id']) && isset($_REQUEST['usergroup_id'])) {
		action_add_sharedfolder_acl($_REQUEST['sharedfolder_id'], $_REQUEST['usergroup_id']);
		redirect();
	}
	elseif ($_REQUEST['action'] == 'del' && isset($_REQUEST['sharedfolder_id']) && isset($_REQUEST['usergroup_id'])) {
		action_del_sharedfolder_acl($_REQUEST['sharedfolder_id'], $_REQUEST['usergroup_id']);
		redirect();
	}
}

if ($_REQUEST['name'] == 'Profile') {
	if (Preferences::moduleIsEnabled('ProfileDB') == false) {
		popup_error(_('Profile management is not enabled'));
		redirect();
	}
	
	if (! checkAuthorization('manageServers'))
		redirect();

	if ($_REQUEST['action'] == 'del') {
		$profiledb = ProfileDB::getInstance();
		foreach ($_REQUEST['ids'] as $id) {
			$network_folder = $profiledb->import($id);
			if (is_object($network_folder)) {
				if (! $network_folder->isUsed())
					$buf = $profiledb->remove($network_folder->id);
				else
					$buf = false;
				
				if (! $buf)
					popup_error(sprintf(_("Unable to delete profile '%s'"), $network_folder->id));
				else
					popup_info(sprintf(_("Profile '%s' successfully deleted"), $network_folder->id));
			}
			else {
				popup_error(sprintf(_("Unable to load profile '%s'"), $id));
			}
		}

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
		if (isset($_REQUEST['password_current']) && isset($_REQUEST['password']) && isset($_REQUEST['password_confirm'])) {
			if ($_REQUEST['password'] == '') {
				popup_error(_('Password is empty'));
			}
			else if ($_REQUEST['password'] != $_REQUEST['password_confirm']) {
				popup_error(_('Passwords are not identical'));
			}
			else if(! defined('SESSIONMANAGER_ADMIN_PASSWORD') or SESSIONMANAGER_ADMIN_PASSWORD != md5($_REQUEST['password_current'])) {
				popup_error(_('Current password is not correct'));
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

if ($_REQUEST['name'] == 'Session') {
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['selected_session']) && is_array($_REQUEST['selected_session'])) {
			foreach ($_POST['selected_session'] as $session) {
				$session = Abstract_Session::load($session);
				
				if (is_object($session)) {
					if (! $session->orderDeletion(true, Session::SESSION_END_STATUS_ADMINKILL)) {
						Logger::error('main', 'Unable to order deletion of session \''.$session->id.'\': purging');
						Abstract_Session::delete($session->id);
						popup_error(sprintf(_("Unable to delete session '%s'"), $session->id));
						continue;
					}
					else {
						popup_info(sprintf(_("Session '%s' successfully deleted"), $session->id));
					}
				}
			}
			redirect('sessions.php');
		}
	}
}

if ($_REQUEST['name'] == 'Server') {
	if (! checkAuthorization('manageServers'))
		redirect();
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_servers']) && is_array($_REQUEST['checked_servers'])) {
			foreach ($_REQUEST['checked_servers'] as $fqdn) {
				$sessions = Abstract_Session::getByServer($fqdn);
				if (count($sessions) > 0) {
					popup_error(sprintf(_("Unable to delete the server '%s' because there are active sessions on it."), $fqdn));
					continue; 
				}
			
				$buf = Abstract_Server::load($fqdn);
				if (is_object($buf)) {
					$buf->orderDeletion();
					Abstract_Server::delete($buf->fqdn);
					popup_info(sprintf(_("Server '%s' successfully deleted"), $buf->getAttribute('fqdn')));
				}
			}
			$buf = count(Abstract_Server::load_registered(false));
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
					popup_info(sprintf(_("Server '%s' successfully registered"), $buf->getAttribute('fqdn')));
				}
				else {
					popup_error(sprintf(_("Failed to register Server '%s'"), $buf->getAttribute('fqdn')));
				}
			}
		}
		
		$buf = count(Abstract_Server::load_registered(false));
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
		redirect();
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
			if (! validate_ip($_REQUEST['external_name']) && ! validate_fqdn($_REQUEST['external_name'])) {
				popup_error(sprintf(_("Redirection name \"%s\" is invalid"), $_REQUEST['external_name']));
				redirect();
			}

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
				if (! array_key_exists('aps', $server_to->roles) || $server_to->roles['aps'] !== true)
					continue;

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

if ($_REQUEST['name'] == 'Task') {
	// it is the rigth place ? (see similar block on name=server action=install_line
		if (! checkAuthorization('manageServers'))
		redirect();
	
	$tm = new Tasks_Manager();
	$tm->load_all();
	$tm->refresh_all();
	
	if ($_REQUEST['action'] == 'add') {
		if (isset($_POST['type'])) {
			$type_task = 'Task_'.$_POST['type'];
			try {
				$task = new $type_task(0, $_POST['server'], $_POST['request']);
				$tm->add($task);
				popup_info(_("Task successfully added"));
			}
			catch (Exception $e) {
				Logger::error('main', 'tasks.php error create task (type=\''.$type_task.'\')');
				popup_error("error create task (type='$type_task')");
			}
		}
		redirect('tasks.php');
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_tasks']) && is_array($_REQUEST['checked_tasks'])) {
			foreach ($_REQUEST['checked_tasks'] as $id) {
				$task = false;
				foreach($tm->tasks as $t) {
					if ($t->id == $id) {
						$task = $t;
						break;
					}
				}
				
				if ($task === false) {
					popup_error('Unable to find task id '.$id);
					redirect('tasks.php');
				}
				
				if (! ($task->succeed() || $task->failed())) {
					popup_error('Task '.$id.' not removable');
					redirect('tasks.php');
				}
				
				$tm->remove($id);
				popup_info(_('Task successfully deleted'));
				redirect('tasks.php');
			}
		}
		redirect('tasks.php');
	}
}

function action_add_sharedfolder() {
	$sharedfolderdb = SharedFolderDB::getInstance();
	
	$sharedfolder_name = $_REQUEST['sharedfolder_name'];
	if ($sharedfolder_name == '') {
		popup_error(_('You must provide a name to your shared folder'));
		return false;
	}

	$buf = $sharedfolderdb->importFromName($sharedfolder_name);
	if (count($buf) > 0) {
		popup_error(_('A shared folder with this name already exists'));
		return false;
	}

	$buf = new SharedFolder();
	$buf->name = $sharedfolder_name;
	$buf->status = NetworkFolder::NF_STATUS_INACTIVE;
	
	if (array_key_exists('sharedfolder_server', $_REQUEST)) {
		$a_server = Abstract_Server::load($_REQUEST['sharedfolder_server']);
	}
	else {
		$a_server = $sharedfolderdb->chooseFileServer();
	}
	if (is_object($a_server) === false) {
		popup_error(_('No server avalaible for shared folder'));
		return false;
	}
	
	$buf->server = $a_server->fqdn;
	
	$ret = $sharedfolderdb->addToServer($buf, $a_server);
	if (! $ret) {
		popup_error(_('Unable to add shared folder'));
		return false;
	}

	popup_info(sprintf(_('Shared folder \'%s\' successfully added'), $buf->name));
	return true;
}

function action_del_sharedfolder($sharedfolder_id) {
	$sharedfolderdb = SharedFolderDB::getInstance();
	
	$sharedfolder = $sharedfolderdb->import($sharedfolder_id);
	if (! $sharedfolder) {
		popup_error(_('Unable to delete this shared folder'));
		return false;
	}

	$a_server = Abstract_Server::load($sharedfolder->server);
	if (! $a_server) {
		popup_error(sprintf(_("Unable to delete shared folder on file server '%s'"), $sharedfolder->server));
		return false;
	}
	
	if ($sharedfolder->isUsed()) {
		popup_error(sprintf(_("Unable to delete shared folder '%s' because it is currently being used"), $sharedfolder->name));
		return false;
	}

	$ret = $a_server->deleteNetworkFolder($sharedfolder->id);
	if (! $ret) {
		popup_error(sprintf(_("Unable to delete shared folder on file server '%s'"), $sharedfolder->server));
		return false;
	}

	$buf = $sharedfolderdb->remove($sharedfolder);
	if (! $buf) {
		popup_error(_('Unable to delete this shared folder'));
		return false;
	}

	popup_info(_('Shared folder successfully deleted'));
	return true;
}

function action_add_sharedfolder_acl($sharedfolder_id_, $usergroup_id_) {

	$sharedfolderdb = SharedFolderDB::getInstance();
	$sharedfolder = $sharedfolderdb->import($sharedfolder_id_);
	if (! $sharedfolder) {
		popup_error(_('Unable to create this shared folder access'));
		return false;
	}

	$usergroupDB = UserGroupDB::getInstance();
	$group = $usergroupDB->import($usergroup_id_);
	if (is_object($group) === false) {
		popup_error(_('Unable to load usergroup'));
		return false;
	}
	
	$ret = $sharedfolder->addUserGroup($group);
	if ($ret === true) {
		popup_info(_('Shared folder successfully modified'));
		return true;
	}
	else {
		popup_error(sprintf(_("Unable to modify shared folder named '%s'"), $sharedfolder->name));
		return false;
	}
}

function action_del_sharedfolder_acl($sharedfolder_id_, $usergroup_id_) {
	$sharedfolderdb = SharedFolderDB::getInstance();
	$sharedfolder = $sharedfolderdb->import($sharedfolder_id_);
	if (! $sharedfolder) {
		popup_error(_('Unable to delete this shared folder access'));
		return false;
	}

	$usergroupDB = UserGroupDB::getInstance();
	$group = $usergroupDB->import($usergroup_id_);
	if (is_object($group) === false) {
		popup_error(_('Unable to load usergroup'));
		return false;
	}
	
	$ret = $sharedfolder->delUserGroup($group);
	if ($ret === true) {
		popup_info(_('Shared folder successfully modified'));
		return true;
	}
	else {
		popup_error(sprintf(_("Unable to modify shared folder named '%s'"), $sharedfolder->name));
		return false;
	}
}

redirect();
