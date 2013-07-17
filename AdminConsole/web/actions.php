<?php
/**
 * Copyright (C) 2008-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2008-2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012-2014
 * Author David LECHEVALIER <david@ulteo.com> 2012, 2014
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
 * Author Tomasz MACKOWIAK <tomasz.mackowiak@stxnext.pl> 2013
 * Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2013
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
require_once(dirname(dirname(__FILE__)).'/includes/core.inc.php');

require_once(dirname(dirname(__FILE__)).'/includes/webapp.inc.php');

if (! is_array($_SESSION) || ! array_key_exists('admin_login', $_SESSION))
	redirect('index.php');

if (!isset($_SERVER['HTTP_REFERER']))
	redirect('index.php');

if (!isset($_REQUEST['name']))
	redirect();

if (!isset($_REQUEST['action']))
	redirect();

if ($_REQUEST['name'] == 'System') {
	if (! checkAuthorization('manageServers'))
		redirect();

	$_SESSION['service']->system_switch_maintenance(($_REQUEST['switch_to']!='maintenance'));
	redirect();
}

$ignore_redirect = false;

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

	$apps = array();
	foreach($_REQUEST['application'] as $id) {
		$app = $_SESSION['service']->application_info($id);
		if (! $app)
			continue;

		if ($app->getAttribute('static') == false) {
			if ($_REQUEST['action'] == 'add') {
				$_SESSION['service']->task_debian_application_install($id, $_REQUEST['server']);

				$msg = _('Task to add application \'%APPLICATION%\' on server \'%SERVER%\' was successfully added');
				popup_info(str_replace(array('%APPLICATION%', '%SERVER%'), array($id, $_REQUEST['server']), $msg));
			} elseif ($_REQUEST['action'] == 'del') {
				$_SESSION['service']->task_debian_application_remove($id, $_REQUEST['server']);

				$msg = _('Task to remove application \'%APPLICATION%\' from server \'%SERVER%\' was successfully added');
				popup_info(str_replace(array('%APPLICATION%', '%SERVER%'), array($id, $_REQUEST['server']), $msg));
			}
		} else {
			if ($_REQUEST['action'] == 'add') {
				$ret = $_SESSION['service']->server_add_static_application($id, $_REQUEST['server']);
				if ($ret === true) {
					$msg = _('Application \'%APPLICATION%\' successfully added to server \'%SERVER%\'');
					popup_info(str_replace(array('%APPLICATION%', '%SERVER%'), array($id, $_REQUEST['server']), $msg));
				} else {
					$msg = _('An error occurred while adding application \'%APPLICATION%\' to server \'%SERVER%\'');
					popup_error(str_replace(array('%APPLICATION%', '%SERVER%'), array($id, $_REQUEST['server']), $msg));
				}
			} elseif ($_REQUEST['action'] == 'del') {
				$_SESSION['service']->server_remove_static_application($id, $_REQUEST['server']);

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
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['id'])) {
			$app = $_SESSION['service']->application_info($_REQUEST['id']);
			if (! is_object($app)) {
				popup_error(sprintf(_("Unknown application '%s'"), $_REQUEST['id']));
				redirect();
			}
			
			$ret = $_SESSION['service']->application_remove($_REQUEST['id']);
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
				$app = $_SESSION['service']->application_info($id);
				if (! is_object($app)) {
					popup_error(sprintf(_("Unknown application '%s'"), $id));
					redirect();
				}
				
				$res = $_SESSION['service']->application_publish($id, $_REQUEST['published']);
				if (! $res) {
					popup_error(sprintf(_("Unable to modify application '%s'"), $id));
				}
			}
			popup_info(sprintf(_("Application '%s' successfully modified"), $app->getAttribute('name')));
		}
	}
	
	if ($_REQUEST['action'] == 'remove_orphan') {
		$res = $_SESSION['service']->applications_remove_orphans();
		if ($res) {
			popup_info(_("Orphan applications successfully removed"));
		}
		else {
			popup_error(_("Problem while removing orphan applications"));
		}
	}
	
	if ($_REQUEST['action'] == 'clone') {
		$app = $_SESSION['service']->application_info($_REQUEST['id']);
		if (! is_object($app)) {
			popup_error(sprintf(_("Failed to import application '%s'"), $_REQUEST['id']));
			redirect();
		}
		
		$ret = $_SESSION['service']->application_clone($_REQUEST['id']);
		if (! $ret) {
			popup_error(sprintf(_("Failed to clone application '%s'"), $app->getAttribute('name')));
			redirect();
		}
		popup_info(sprintf(_("Application '%s' successfully added"), $app->getAttribute('name')));
		redirect('applications_static.php?action=manage&id='.$app->getAttribute('id'));
	}

	if ($_REQUEST['action'] == 'icon') {
		$app = $_SESSION['service']->application_info($_REQUEST['id']);
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
						popup_error(_('File too large to be used for server rules'));
						redirect();
						break;
					case 3: // UPLOAD_ERR_PARTIAL
						popup_error(_('The file was corrupted during the upload'));
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

				$content = @file_get_contents($source_file);
				$ret = $_SESSION['service']->application_icon_set($_REQUEST['id'], base64_encode($content));
				if (! $ret) {
					popup_error(_('Unable to change icon'));
					redirect();
				}
			}

			popup_info(sprintf(_("Icon for application '%s' has been successfully uploaded"), $app->getAttribute('id')));
			redirect('applications.php?action=manage&id='.$app->getAttribute('id'));
		}

		if (array_key_exists('server', $_REQUEST)) {
			$server =  $_SESSION['service']->server_info($_REQUEST['server']);
			if (! $server) {
				popup_error(sprintf(_("Unknown server '%s'"), $_REQUEST['server']));
				redirect();
			}

			$_SESSION['service']->application_icon_setFromServer($_REQUEST['id'], $_REQUEST['server']);
			popup_info(sprintf(_("Icon of application '%s' has been updated from server '%s'"), $app->getAttribute('id'), $server->getDisplayName()));
			redirect('applications.php?action=manage&id='.$app->getAttribute('id'));
		}

		redirect();
	}
}

if ($_REQUEST['name'] == 'Application_static') {
	if (! checkAuthorization('manageApplications'))
		redirect();
	
	if ($_REQUEST['action'] == 'add') {
		if (isset($_REQUEST['attributes_send']) && is_array($_REQUEST['attributes_send'])) {
			if (! array_key_exists('application_name', $_REQUEST) or 
				! array_key_exists('description', $_REQUEST) or
				! array_key_exists('executable_path', $_REQUEST) or
				! array_key_exists('type', $_REQUEST)) {
				redirect();
			}
			
			$name = $_REQUEST['application_name'];
			$description = $_REQUEST['description'];
			$executable_path = $_REQUEST['executable_path'];
			$type = $_REQUEST['type'];
			
			if ($type == 'weblink') {
				$ret = $_SESSION['service']->application_weblink_add($name, $description, $executable_path);
			}
			else {
				$ret = $_SESSION['service']->application_static_add($name, $description, $type, $executable_path);
			}
			
			if (! $ret) {
				popup_error(sprintf(_("Failed to add application '%s'"), $name));
			}
			else {
				popup_info(sprintf(_("Application '%s' successfully added"), $name));
			}
			
			redirect('applications_static.php?action=manage&id='.$ret);
		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_applications']) && is_array($_REQUEST['checked_applications'])) {
			foreach ($_REQUEST['checked_applications'] as $id) {
				$app = $_SESSION['service']->application_info($id);
				if (! is_object($app)) {
					popup_error(sprintf(_("Unable to import application '%s'"), $id));
					redirect();
				}
				
				$_SESSION['service']->application_static_remove($id);
				popup_info(sprintf(_("Application '%s' successfully deleted"), $app->getAttribute('name')));
			}
			redirect('applications_static.php');
		}
	}
	
	if ($_REQUEST['action'] == 'del_icon') {
		if (isset($_REQUEST['checked_applications']) && is_array($_REQUEST['checked_applications'])) {
			foreach ($_REQUEST['checked_applications'] as $id) {
				$app = $_SESSION['service']->application_info($id);
				if (! is_object($app)) {
					popup_error(sprintf(_("Unable to import application '%s'"), $id));
					redirect();
				}
				
				$_SESSION['service']->application_static_removeIcon($id);
				popup_info(sprintf(_("'%s' application's icon was successfully deleted"), $app->getAttribute('name')));
				redirect('applications_static.php?action=manage&id='.$app->getAttribute('id'));
			}
		}
	}
	
	if ($_REQUEST['action'] == 'modify') {
		if (isset($_REQUEST['id']) && isset($_REQUEST['attributes_send']) && is_array($_REQUEST['attributes_send'])) {
			$app = $_SESSION['service']->application_info($_REQUEST['id']);
			if (! is_object($app)) {
				popup_error(sprintf(_("Unable to import application '%s'"), $_REQUEST['id']));
				redirect();
			}
			
			if (array_key_exists('application_name', $_REQUEST)) {
				$app->setAttribute('name', $_REQUEST['application_name']);
			}
			
			if (array_key_exists('description', $_REQUEST)) {
				$app->setAttribute('description', $_REQUEST['description']);
			}
			
			if (array_key_exists('executable_path', $_REQUEST)) {
				$app->setAttribute('executable_path', $_REQUEST['executable_path']);
			}
			
			$ret = $_SESSION['service']->application_static_modify(
				$app->getAttribute('id'),
				$app->getAttribute('name'),
				$app->getAttribute('description'),
				$app->getAttribute('executable_path'));
			if (! $ret) {
				popup_error(sprintf(_("Failed to modify application '%s'"), $app->getAttribute('name')));
			}
			
			if (array_key_exists('file_icon', $_FILES)) {
				$upload = $_FILES['file_icon'];
				
				$have_file = true;
				if($upload['error']) {
					switch ($upload['error']) {
						case 1: // UPLOAD_ERR_INI_SIZE
							popup_error(_('File too large to be used for server rules'));
							redirect();
							break;
						case 3: // UPLOAD_ERR_PARTIAL
							popup_error(_('The file was corrupted during the upload'));
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
					
					$content = @file_get_contents($source_file);
					$ret = $_SESSION['service']->application_icon_set($app->getAttribute('id'), base64_encode($content));
					if (! $ret) {
						popup_error(_('Unable to change icon'));
						redirect();
					}
				}
			}
		}
	}
}

if ($_REQUEST['name'] == 'Application_webapp') {
	if (! checkAuthorization('manageApplications'))
		redirect();
	
	if ($_REQUEST['action'] == 'add') {
		if (isset($_REQUEST['attributes_send']) && is_array($_REQUEST['attributes_send'])) {

			if (! array_key_exists('application_name', $_REQUEST) or 
				! array_key_exists('description', $_REQUEST) or
				! array_key_exists('type', $_REQUEST)) {
				redirect();
			}

			if(! array_key_exists('app_conf_file', $_FILES)) {
				redirect();
			}

			$name = $_REQUEST['application_name'];
			$description = $_REQUEST['description'];
			$url_prefix = $_REQUEST['url_prefix'];
			
			if (empty($name)) {
				popup_error(_('The Web Application name was not specified'));
				redirect();
			}
			
			$upload = $_FILES['app_conf_file'];

			$have_file = true;
			if ($upload['error']) {
				switch ($upload['error']) {
					case 1: // UPLOAD_ERR_INI_SIZE
						popup_error(_('File too large to be used for server rules'));
						redirect();
						break;
					case 3: // UPLOAD_ERR_PARTIAL
						popup_error(_('The file was corrupted during the upload'));
						redirect();
						break;
					case 4: // UPLOAD_ERR_NO_FILE
						$have_file = false;
						break;
				}
			}
			
			if (! $have_file) {
				popup_error(_('No configuration file was specified'));
				redirect();
			}
			
			$source_file = $upload['tmp_name'];
			if (! is_readable($source_file)) {
				popup_error(_('The file is not readable'));
				redirect();
			}

			$configuration = @file_get_contents($source_file);
			$parsed_config = json_decode($configuration, True);
			if(!$parsed_config){
				popup_error(_('Incorrect configuration file format'));
				redirect();
			}

			if(count(array_keys($parsed_config)) > 1) {
				popup_info(_('Configuration file has more than one main level key. The first main level key will be used'));
			}

			if (empty ($url_prefix)) {
				popup_error(_('No URL prefix'));
				redirect();
			}
			
			// Check if url prefix is valid.
			if (! checkUrlPrefixFormat($url_prefix)) {
				popup_error(_('Incorrect URL prefix format'));
				redirect();
			}
			
			// Check if url prefix is unique across all registered apps.
			if (! checkUrlPrefixUnique($url_prefix)) {
				popup_error(_('URL prefix is not unique'));
				redirect();
			}
			
			$pieces = NULL;
			preg_match_all('/\$\((\w+)\)/m', $configuration, $pieces);
			$has_matches = count($pieces) > 0;

			if($has_matches) {
				$dynamic_vars = array_unique($pieces[1]);
				foreach($dynamic_vars as $index=>$varname) {
					if (!array_key_exists($varname, $parsed_config['Configuration'])) {
						popup_error(_('Incorrect configuration file format - missing '.$varname.' parameter'));
						redirect();
					}
				}
			}

			$ret = $_SESSION['service']->application_webapp_add($name, $description, $url_prefix, $configuration);
			if (! $ret) {
				popup_error(_('Unable to add Web Application'));
				redirect();
			}

			// Set default icon for applications. The icon used is
			// elementary-xfce/apps/32/application-default-icon.png
			$content_base64 = ""
				. "iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAMAAABEpIrGAAAAA3NCSVQICAjb4U/gAAACEFBMVEUA"
				. "AAAXFxcSEhIUFBQYGBgXFxePj4+KioovLy8zMzOLi4tUVFRTU1OLi4uOjo6Ojo5NTU1OTk6KiopX"
				. "V1eGhoZZWVmEhIRcXFyCgoJeXl6AgIBhYWF+fn5jY2N6enpkZGR5eXloaGh1dXVpaWlzc3NQUFBt"
				. "bW1xcXFubm5PT09ubm5wcHBQUFBsbGxycnJpaWlnZ2d1dXVkZGR3d3dhYWF6enpRUVFfX198fHyO"
				. "jo5VVVV+fn5cXFyBgYGJiYlaWlpsbGyDg4Orq6tQUFBXV1eGhoZQUFBdXV1fX19gYGBhYWFiYmJj"
				. "Y2NkZGRlZWVmZmZnZ2doaGhqampra2tsbGxtbW1ubm5vb29wcHBxcXFycnJzc3N0dHR1dXV2dnZ3"
				. "d3d4eHh5eXl6enp7e3t8fHx9fX1+fn5/f3+AgICBgYGCgoKDg4OEhISFhYWGhoaHh4eIiIiJiYmK"
				. "ioqLi4uMjIyNjY2Ojo6Pj4+QkJCRkZGSkpKTk5OUlJSVlZWWlpaXl5eYmJiZmZmampqbm5ucnJyd"
				. "nZ2enp6fn5+goKChoaGioqKjo6OkpKSlpaWmpqanp6eoqKipqamqqqqrq6usrKytra2urq6vr6+w"
				. "sLCxsbGysrKzs7O0tLS1tbW2tra3t7e4uLi6urq7u7u9vb2/v7/GxsbJycnLy8vNzc3Pz8/S0tLT"
				. "09PW1tbY2Nja2trc3Nw32WW7AAAARnRSTlMACw4aICIiI0FBxcbKzc3O2Nvl5+jp6erq6+vt7e7u"
				. "7+/w8PHx8vLy8/T09PX19fb39/j4+fn6+vr6+/v8/Pz9/f39/v7+s6UsGQAAAexJREFUOMvFk89O"
				. "U0EUxr8zc0srUqREoyFUAgvjnxjFxJ3PYHwHn8u3ceFSjSxEIiK05UIppUit3Dnf5+LWtlEX7vyW"
				. "M7/85szMOcD/jwGo1u4/+8vW663RZQnUn760SaBJXr0ZAhmAzRdfQ8yyLGbRDJInTym5nr8dA9Uk"
				. "QGJgaaAoASwCSiBJotEcCgaRTpISZgCaHIBa27hzi+4uUR4ABAAkRbI4SskrFaaUF+XSL4OTBvPd"
				. "8/5CLh2kQbe+AZKYAiJwNqh2u1lEsYvqoHeN1MRAOoA125sDHZZhtHa7R3qcAoR0coXUgyb2t0zz"
				. "JySpmRqAk3QRioeNDhp3P9inq5UlzhTpLnUCYjF/BHxfLCqjEeukxwlASITp1AD0SEkuMk6LVHiU"
				. "vgyZ3wDQlc+vx28sgVAa3OcW193anR+XnZb5en3OfVoD3YPy0GcIh22ETNxvUKLbFJDZ2XFICBFy"
				. "hGMsaPzUAQBd7lxtFM3NWkq1zWbRWOVvR8B0+ri/eLGyjZW4cX1p1ymlyS1SMpPvLH+WCZeHtryT"
				. "QDFVZv4i0JjDbj7BXq5ckijNGlh2bLsNaNy3M4bksGAGgwEQBIlCMRwDo9a9YDCDGUACKB0f35dA"
				. "4LsYAQB+Xo7M0AEgHadAGFDq/4xE/cNs/gSkwISNM9q1fQAAAABJRU5ErkJggg==";

			$icon_ret = $_SESSION['service']->application_icon_set($ret, $content_base64);
			if (! $icon_ret) {
				// Display warning, but continue flow.
				popup_info(_('Unable to change icon'));
			}
			
			popup_info(sprintf(_("Web Application '%s' has been successfully uploaded"), $ret));
			redirect('applications_webapp.php?action=manage&id='.$ret);

		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_applications']) && is_array($_REQUEST['checked_applications'])) {
			foreach ($_REQUEST['checked_applications'] as $id) {
				$app = $_SESSION['service']->application_info($id);
				if (! is_object($app)) {
					popup_error(sprintf(_("Unable to import application '%s'"), $id));
					redirect();
				}
				
				$_SESSION['service']->application_webapp_remove($id);
				popup_info(sprintf(_("Application '%s' successfully deleted"), $app->getAttribute('name')));
			}
			redirect('applications_webapp.php');
		}
	}
	
	if ($_REQUEST['action'] == 'del_icon') {
		if (isset($_REQUEST['checked_applications']) && is_array($_REQUEST['checked_applications'])) {
			foreach ($_REQUEST['checked_applications'] as $id) {
				$app = $_SESSION['service']->application_info($id);
				if (! is_object($app)) {
					popup_error(sprintf(_("Unable to import application '%s'"), $id));
					redirect();
				}
				
				$_SESSION['service']->application_static_removeIcon($id);
				popup_info(sprintf(_("'%s' application's icon was successfully deleted"), $app->getAttribute('name')));
				redirect('applications_webapp.php?action=manage&id='.$app->getAttribute('id'));
			}
		}
	}
	
	if ($_REQUEST['action'] == 'modify') {
		// Update values of additional parameters.
		if (isset($_REQUEST['task'])) {
			// extract from request all edited params
			$dynamic_variables = array();
			if (isset($_REQUEST['attributes_send']) && is_array($_REQUEST['attributes_send']))
				foreach ($_REQUEST['attributes_send'] as $attr_name)
					$dynamic_variables[$attr_name] = $_REQUEST[$attr_name];

			$app_id = $_REQUEST['id'];
			$config = $_SESSION['service']->application_webapp_info($app_id);
			if ($config !== NULL) {
				$parsed_config = json_decode($config['raw_configuration'], True);
				
				// rewrite values from request into config
				foreach ($dynamic_variables as $name => $value) {
					if ($parsed_config['Configuration'][$name]['type'] === 'boolean') {
						$dynamic_variables[$name] = (bool)$value == "on";
					} elseif ($parsed_config['Configuration'][$name]['type'] === 'url') {
						if (!preg_match("#((http|https)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#ie", $value)) {
							popup_error(sprintf(_("Parameter '%s' is not a valid URL"), $name));
							redirect();
						}
					} elseif ($parsed_config['Configuration'][$name]['type'] === 'inetaddr') {
						if (!filter_var($value, FILTER_VALIDATE_IP)) {
							popup_error(sprintf(_("Parameter '%s' is not a valid IP address"), $name));
							redirect();
						}
					}
				}
				
				if ($dynamic_variables != $config['values']) {
					//save
					$ret = $_SESSION['service']->application_webapp_set_values($app_id, $dynamic_variables);
					if (! $ret) {
						popup_error(_('Unable to update Web Application configuration'));
						redirect();
					}
				}
			}
			redirect('applications_webapp.php?action=manage&id='.$app_id);
		}
		// Modyfing application.
		elseif (isset($_REQUEST['id']) && isset($_REQUEST['attributes_send']) && is_array($_REQUEST['attributes_send'])) {
			$app = $_SESSION['service']->application_info($_REQUEST['id']);
			$webapp = $_SESSION['service']->application_webapp_info($_REQUEST['id']);
			if (! is_object($app) || $webapp === NULL) {
				popup_error(sprintf(_("Unable to import application '%s'"), $_REQUEST['id']));
				redirect();
			}
			
			$app_modified = false;
			$webapp_modified = false;
			
			$current_url_prefix = $webapp['url_prefix'];
			if (array_key_exists('url_prefix', $_REQUEST)) {
				$form_url_prefix = $_REQUEST['url_prefix'];
				if ($form_url_prefix) {
					// Prefix is present in the form.
					// Check if url prefix is valid.
					if (! checkUrlPrefixFormat($form_url_prefix)) {
						popup_error(_('Incorrect URL prefix format'));
						redirect();
					}
					
					// Check if url prefix is unique across all registered apps.
					if (! checkUrlPrefixUnique($form_url_prefix, $app->getAttribute('id'))) {
						popup_error(_('URL prefix is not unique'));
						redirect();
					}

				} else {
					$form_url_prefix = $current_url_prefix;
				}
			} else {
				$form_url_prefix = $current_url_prefix;
			}
			
			if ($form_url_prefix != $current_url_prefix) {
				$webapp_modified = true;
				$webapp['url_prefix'] = $form_url_prefix;
			}

			if (array_key_exists('application_name', $_REQUEST)) {
				if ($app->getAttribute('name') != $_REQUEST['application_name']) {
					$app_modified = true;
					$app->setAttribute('name', $_REQUEST['application_name']);
				}
			}
			
			if (array_key_exists('description', $_REQUEST)) {
				if ($app->getAttribute('description') != $_REQUEST['description']) {
					$app_modified = true;
					$app->setAttribute('description', $_REQUEST['description']);
				}
			}
			
			if ($app_modified){
				$ret = $_SESSION['service']->application_static_modify(
					$app->getAttribute('id'),
					$app->getAttribute('name'),
					$app->getAttribute('description'));
				if (! $ret) {
					popup_error(sprintf(_("Failed to modify application '%s'"), $app->getAttribute('name')));
				}
			}

			if (array_key_exists('file_icon', $_FILES)) {
				$upload = $_FILES['file_icon'];
				
				$have_file = true;
				if($upload['error']) {
					switch ($upload['error']) {
						case 1: // UPLOAD_ERR_INI_SIZE
							popup_error(_('File too large to be used for server rules'));
							redirect();
							break;
						case 3: // UPLOAD_ERR_PARTIAL
							popup_error(_('The file was corrupted during the upload'));
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
					
					$content = @file_get_contents($source_file);
					$ret = $_SESSION['service']->application_icon_set($app->getAttribute('id'), base64_encode($content));
					if (! $ret) {
						popup_error(_('Unable to change icon'));
						redirect();
					}
				}
			}
			if (array_key_exists('app_conf_raw', $_REQUEST)) {
				$configuration = $_REQUEST['app_conf_raw'];
				$parsed_config = json_decode($configuration, True);
				if(!$parsed_config){
					popup_error(_('Incorrect configuration file format'));
					redirect();
				}

				$pieces = NULL;
				preg_match_all('/\$\((\w+)\)/m', $configuration, $pieces);
				$has_matches = count($pieces) > 0;

				if($has_matches) {
					$dynamic_vars = array_unique($pieces[1]);
					foreach($dynamic_vars as $index=>$name) {
						if (!array_key_exists($name, $parsed_config['Configuration'])) {
							popup_error(_('Incorrect configuration file format - missing '.$name.' parameter'));
							redirect();
						}
					}
				}

				if ($configuration != $webapp['raw_configuration']) {
					$webapp['raw_configuration'] = $configuration;
					$webapp_modified = true;
				}
			}
			
			if ($webapp_modified) {
				$ret = $_SESSION['service']->application_webapp_modify($app->getAttribute('id'), $webapp);
				if (! $ret) {
					popup_error(_('Unable to change configuration'));
					redirect();
				}
			}
			$app_id = $app->getAttribute('id');
			popup_info(sprintf(_("'%s' application was successfully modified"), $app->getAttribute('name')));
			redirect('applications_webapp.php?action=manage&id='.$app_id);
		}
	}

	if ($_REQUEST['action'] == 'clone') {
		$app = $_SESSION['service']->application_info($_REQUEST['id']);
		if (! is_object($app)) {
			popup_error(sprintf(_("Failed to import application '%s'"), $_REQUEST['id']));
			redirect();
		}

		$ret = $_SESSION['service']->application_webapp_clone($_REQUEST['id']);
		if (! $ret) {
			popup_error(sprintf(_("Failed to clone Web Application '%s'"), $app->getAttribute('name')));
			redirect();
		}
		popup_info(sprintf(_("Web Application '%s' successfully added"), $app->getAttribute('name')));
		redirect('applications_webapp.php');
	}
	
	if ($_REQUEST['action'] == 'download') {
		$app_id = $_REQUEST['id'];
		$config = $_SESSION['service']->application_webapp_info($app_id);

		if ($config!==NULL) {
			header('Content-disposition: attachment; filename=webapp_'.$app_id.'_config.json');
			header('Content-type: application/x-json');
			$ignore_redirect = true;
			print $config['raw_configuration'];
		} else {
			popup_error(sprintf(_("Failed to get application's configuration '%s'"), $_REQUEST['id']));
			redirect('applications_webapp.php');
		}
	}
}

if ($_REQUEST['name'] == 'Application_MimeType') {
	if (! checkAuthorization('manageApplications'))
		redirect();
	
	if ($_REQUEST['action'] == 'add') {
		if (isset($_REQUEST['id']) && isset($_REQUEST['mime'])) {
			$app = $_SESSION['service']->application_info($_REQUEST['id']);
			if (! is_object($app)) {
				popup_error(sprintf(_("Unable to import application '%s'"), $_REQUEST['id']));
				redirect();
			}
			
			$ret = $_SESSION['service']->application_add_mime_type($_REQUEST['id'], $_REQUEST['mime']);
			if (! $ret) {
				popup_error(sprintf(_("Failed to modify application '%s'"), $app->getAttribute('name')));
			}
		}
	}
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['id']) && isset($_REQUEST['mime'])) {
			$app = $_SESSION['service']->application_info($_REQUEST['id']);
			if (! is_object($app)) {
				popup_error(sprintf(_("Unable to import application '%s'"), $_REQUEST['id']));
				redirect();
			}
			
			$ret = $_SESSION['service']->applications_remove_mime_type($_REQUEST['id'], $_REQUEST['mime']);
			if (! $ret) {
				popup_error(sprintf(_("Failed to modify application '%s'"), $app->getAttribute('name')));
			}
		}
	}
}

if ($_REQUEST['name'] == 'Application_ApplicationGroup') {
	if (! checkAuthorization('manageApplicationsGroups'))
		redirect();
	
	$group = $_SESSION['service']->applications_group_info($_REQUEST['group']);
	if (! is_object($group)) {
		popup_error(sprintf(_('Unable to import group "%s"'), $_REQUEST['group']));
		redirect();
	}
	
	if ($_REQUEST['action'] == 'add') {
		$ret = $_SESSION['service']->applications_group_add_application($_REQUEST['element'], $_REQUEST['group']);
		if ($ret === true) {
			popup_info(sprintf(_('Application Group \'%s\' successfully modified'), $group->name));
		}
	}

	if ($_REQUEST['action'] == 'del') {
		$ret = $_SESSION['service']->applications_group_remove_application($_REQUEST['element'], $_REQUEST['group']);
		if ($ret === true) {
			popup_info(sprintf(_('Application Group \'%s\' successfully modified'), $group->name));
		}
	}
}

if ($_REQUEST['name'] == 'ApplicationsGroup') {
	if (! checkAuthorization('manageApplicationsGroups'))
		redirect();
	
	if ($_REQUEST['action'] == 'add') {
		if ( isset($_REQUEST['name_appsgroup']) && isset($_REQUEST['description_appsgroup'])) {
			$name = $_REQUEST['name_appsgroup'];
			$description = $_REQUEST['description_appsgroup'];
			
			$res = $_SESSION['service']->applications_group_add($name, $description);
			if (!$res) {
				popup_error(sprintf(_("Unable to create Application Group '%s'"), $name));
				redirect('appsgroup.php');
			}
			
			popup_info(sprintf(_("Application Group '%s' successfully added"), $name));
			redirect('appsgroup.php?action=manage&id='.$res);
		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_groups']) and is_array($_REQUEST['checked_groups'])) {
			$ids = $_REQUEST['checked_groups'];
			foreach ($ids as $id) {
				$group = $_SESSION['service']->applications_group_info($id);
				if (! is_object($group)) {
					popup_error(sprintf(_("Importing Application Group '%s' failed"), $id));
					continue;
				}
				
				$res = $_SESSION['service']->applications_group_remove($id);
				if (! $res) {
					popup_error(sprintf(_("Unable to remove Application Group '%s'"), $group->name));
					continue;
				}
				popup_info(sprintf(_("Application Group '%s' successfully deleted"), $group->name));
			}
			redirect('appsgroup.php');
		}
	}
	
	if ($_REQUEST['action'] == 'modify') {
		if (isset($_REQUEST['id']) && (isset($_REQUEST['name_appsgroup']) || isset($_REQUEST['description_appsgroup']) || isset($_REQUEST['published_appsgroup']))) {
			$id = $_REQUEST['id'];
			$group = $_SESSION['service']->applications_group_info($id);
			if (! is_object($group))
				popup_error(sprintf(_("Import of Application Group '%s' failed"), $id));
			
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
				$res = $_SESSION['service']->applications_group_modify($group->id, $group->name, $group->description, $group->published);
				if (! $res)
					popup_error(sprintf(_("Unable to modify Application Group '%s'"), $group->name));
				else
					popup_info(sprintf(_("Application Group '%s' successfully modified"), $group->name));
			}
			redirect('appsgroup.php?action=manage&id='.$group->id);
		}
	}
}


if ($_REQUEST['name'] == 'Certificate') {
	if (! checkAuthorization('manageConfiguration'))
		redirect();
	
	if ($_REQUEST['action'] == 'add') {
		if (! array_key_exists('certificate', $_FILES)) {
			popup_info(_("No Subscription Keys were uploaded"));
			redirect();
		}
		
		$upload = $_FILES['certificate'];
		if ($upload['error']) {
			switch ($upload['error']) {
				case 1: // UPLOAD_ERR_INI_SIZE
					popup_error(_('File too large to be used for server rules'));
					break;
				case 3: // UPLOAD_ERR_PARTIAL
					popup_error(_('The file was corrupted during the upload'));
					break;
				case 4: // UPLOAD_ERR_NO_FILE
					popup_error(_('No files were uploaded'));
					break;
			}
			
			redirect();
		}
		
		$source_file = $upload['tmp_name'];
		if (! is_readable($source_file)) {
			popup_error(_('The file is not readable'));
			redirect();
		}

		$content = @file_get_contents($source_file);
		if (strlen($content) == 0) {
			popup_error(_('The file is empty'));
			redirect();
		}
		
		$res = $_SESSION['service']->certificate_add(base64_encode($content));
		if (! $res) {
			popup_error(_('The uploaded file is not a valid Subscription Key'));
			redirect();
		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (empty($_REQUEST['id'])) {
			redirect();
		}
		
		$_SESSION['service']->certificate_del($_REQUEST['id']);
		redirect();
	}

	if ($_REQUEST['action'] == 'reset_named_users') {
		$_SESSION['service']->certificate_reset_named_users();
		redirect();
	}
}

if ($_REQUEST['name'] == 'User_UserGroup') {
	if (! checkAuthorization('manageUsersGroups'))
		redirect();

	if ($_REQUEST['action'] == 'add') {
		$ret = $_SESSION['service']->users_group_add_user($_REQUEST['element'], $_REQUEST['group']);
		if ($ret === true) {
			$group = $_SESSION['service']->users_group_info($_REQUEST['group']);
			if (is_object($group)) {
				popup_info(sprintf(_('User Group \'%s\' successfully modified'), $group->name));
			}
			else {
				// problem, what to do ?
				popup_info(sprintf(_('User Group \'%s\' successfully modified'), $_REQUEST['group']));
			}
		}
	}

	if ($_REQUEST['action'] == 'del') {
		$group = $_SESSION['service']->users_group_info($_REQUEST['group']);
		if (! is_object($group)) {
			popup_error(sprintf(_("User Group '%s' does not exist"), $_REQUEST['group']));
			redirect();
		}

		if ($group->isDefault()) {
			popup_error(sprintf(_("Unable to remove a user from User Group '%s' because it is the default User Group"), $group->name));
			redirect();
		}

		$_SESSION['service']->users_group_remove_user($_REQUEST['element'], $_REQUEST['group']);
		popup_info(sprintf(_('User Group \'%s\' successfully modified'), $group->name));
		
		redirect();
	}
}

if ($_REQUEST['name'] == 'Publication') {
	if (! checkAuthorization('managePublications'))
		redirect();

	if (!isset($_REQUEST['group_a']) or !isset($_REQUEST['group_u']))
		redirect();

	if ($_REQUEST['action'] == 'add') {
		$usergroup = $_SESSION['service']->users_group_info($_REQUEST['group_u']);
		if (is_object($usergroup) == false) {
			popup_error(sprintf(_("Importing User Group '%s' failed"), $_REQUEST['group_u']));
			redirect();
		}
		
		$applicationsgroup = $_SESSION['service']->applications_group_info($_REQUEST['group_a']);
		if (is_object($applicationsgroup) == false) {
			popup_error(sprintf(_("Importing Application Group '%s' failed"), $_REQUEST['group_a']));
			redirect();
		}
		
		$res = $_SESSION['service']->publication_add($_REQUEST['group_u'], $_REQUEST['group_a']);
		if (! $res) {
			popup_error(_('Unable to save the Publication'));
			redirect();
		}
		
		popup_info(_('Publication successfully added'));
	}

	if ($_REQUEST['action'] == 'del') {
		$res = $_SESSION['service']->publication_remove($_REQUEST['group_u'], $_REQUEST['group_a']);
		if (! $res) {
			popup_error(_('Unable to delete the Publication'));
			redirect();
		}
		
		popup_info(_('Publication successfully deleted'));
	}
}

if ($_REQUEST['name'] == 'UserGroup') {
	if (! checkAuthorization('manageUsersGroups'))
		redirect();
	
	if ($_REQUEST['action'] == 'add') {
		if (isset($_REQUEST['type']) && isset($_REQUEST['name_group']) &&  isset($_REQUEST['description_group'])) {
			if ($_REQUEST['name_group'] == '') {
				popup_error(_('You must define a name for your User Group'));
				redirect('usersgroup.php');
			}
			
			if ($_REQUEST['type'] == 'static') {
				$res = $_SESSION['service']->users_group_add($_REQUEST['name_group'], $_REQUEST['description_group']);
			}
			elseif ($_REQUEST['type'] == 'dynamic') {
				if ($_REQUEST['cached'] === '0') {
					$res = $_SESSION['service']->users_group_dynamic_add($_REQUEST['name_group'], $_REQUEST['description_group'], $_REQUEST['validation_type']);
				}
				else {
					$res = $_SESSION['service']->users_group_dynamic_cached_add($_REQUEST['name_group'], $_REQUEST['description_group'], $_REQUEST['validation_type'], $_REQUEST['schedule']);
				}
				
				if (!$res) {
					popup_error(sprintf(_("Unable to create User Group '%s'"), $_REQUEST['name_group']));
					redirect('usersgroup.php');
				}
				
				$res = $_SESSION['service']->users_group_dynamic_modify($res, $_POST['rules'], $_REQUEST['validation_type']);
			}
			else {
				die_error(_('Unknown User Group type'));
			}
			
			if (!$res) {
				popup_error(sprintf(_("Unable to create User Group '%s'"), $_REQUEST['name_group']));
				redirect('usersgroup.php');
			}
			
			popup_info(_('User Group successfully added'));
			redirect('usersgroup.php?action=manage&id='.$res);
		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_groups']) && is_array($_REQUEST['checked_groups'])) {
			foreach ($_REQUEST['checked_groups'] as $id) {
				$group = $_SESSION['service']->users_group_info($id);
				if (! is_object($group)) {
					popup_error(sprintf(_("Failed to import User Group '%s'"), $id));
					redirect();
				}
				
				$res = $_SESSION['service']->users_group_remove($id);
				if (! $res) {
					popup_error(sprintf(_("Unable to remove User Group '%s'"), $id));
				}
				
				popup_info(sprintf(_("User Group '%s' successfully deleted"), $group->name));
			}
			redirect('usersgroup.php');
		}
	}
	
	if ($_REQUEST['action'] == 'modify') {
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			
			$group = $_SESSION['service']->users_group_info($id);
			if (! is_object($group)) {
				popup_error(sprintf(_("Failed to import User Group '%s'"), $id));
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
			
			if ($has_change) {
				$ret = $_SESSION['service']->users_group_modify($group->id, $group->name, $group->description, $group->published);
				if (! $ret) {
					popup_error(sprintf(_("Unable to update User Group '%s'"), $group->name));
					redirect();
				}
			}
			
			if (isset($_REQUEST['schedule'])) {
				$ret = $_SESSION['service']->users_group_dynamic_cached_set_schedule($group->id, $_REQUEST['schedule']);
				if (! $ret) {
					popup_error(sprintf(_("Unable to update User Group '%s'"), $group->name));
					redirect();
				}
			}
			
			popup_info(sprintf(_("User Group '%s' successfully modified"), $group->name));
			redirect('usersgroup.php?action=manage&id='.$group->id);
		}
	}
	
	if (($_REQUEST['action'] == 'set_default') or ($_REQUEST['action'] == 'unset_default')) {
		if (! checkAuthorization('manageConfiguration')) {
			die_error(_('Not enough rights'));
		}
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			
			$group = $_SESSION['service']->users_group_info($id);
			if (! is_object($group)) {
				popup_error(sprintf(_("Failed to import group '%s'"), $id));
				redirect();
			}
			
			if ($_REQUEST['action'] == 'set_default') {
				$_SESSION['service']->system_set_default_users_group($group->id);
			}
			else {
				$_SESSION['service']->system_unset_default_users_group();
			}
			
			popup_info(sprintf(_("User Group '%s' successfully modified"), $group->name));
			redirect('usersgroup.php?action=manage&id='.$group->id);
			
		}
	}
	
	if ($_REQUEST['action'] == 'modify_rules') {
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			$group = $_SESSION['service']->users_group_info($id);
			if (! is_object($group)) {
				popup_error(sprintf(_("Failed to import User Group '%s'"), $id));
				redirect();
			}
			
			$res = $_SESSION['service']->users_group_dynamic_modify($id, $_POST['rules'], $_REQUEST['validation_type']);
			if (! $res) 
				popup_error(sprintf(_("Unable to update User Group '%s'"), $group->name));
			else
				popup_info(sprintf(_("Rules of '%s' successfully modified"), $group->name));
			
			redirect('usersgroup.php?action=manage&id='.$group->id);
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
			popup_error('You are not allowed to perform this action');
			redirect();
		}
	}

	$group = $_SESSION['service']->users_group_info($_REQUEST['id']);
	if (! is_object($group)) {
		popup_error(sprintf(_("Failed to import User Group '%s'"), $id));
		redirect();
	}

	if ($_REQUEST['action'] == 'add')
		$res = $_SESSION['service']->users_group_add_policy($group->id, $_REQUEST['element']);
	else
		$res = $_SESSION['service']->users_group_remove_policy($group->id, $_REQUEST['element']);

	popup_info(sprintf(_('User Group \'%s\' successfully modified'), $group->name));
	redirect();
}

if ($_REQUEST['name'] == 'UserGroup_settings') {
	if (isset($_REQUEST['unique_id']) && isset($_REQUEST['action'])) {
		$unique_id = $_REQUEST['unique_id'];
		
		$ret = null;
		if ($_REQUEST['action'] == 'add' && isset($_REQUEST['container']) && isset($_REQUEST['element_id'])) {
			$ret = $_SESSION['service']->users_group_settings_set($unique_id, $_REQUEST['container'], $_REQUEST['element_id'], null);
		}
		else if ($_REQUEST['action'] == 'del' && isset($_REQUEST['container']) && isset($_REQUEST['element_id'])) {
			$ret = $_SESSION['service']->users_group_settings_remove($unique_id, $_REQUEST['container'], $_REQUEST['element_id']);
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
				foreach ($data as $element_id => $value) {
					$ret = $_SESSION['service']->users_group_settings_set($unique_id, $container, $element_id, $value);
					if ( $ret !== true) {
						break;
					}
				}
			}
		}
		
		if ($ret === true) {
			popup_info(_('User Group successfully modified'));
		}
		else if ($ret === false) {
			popup_error(_('Failed to modify User Group'));
		}
	}
}

if ($_REQUEST['name'] == 'User') {
	if (! checkAuthorization('manageUsers')) {
		redirect('users.php');
	}
	
	if ($_REQUEST['action'] == 'add') {
		$minimun_attributes = array('login', 'displayname', 'password');
		if (!isset($_REQUEST['login']) or !isset($_REQUEST['displayname']) or !isset($_REQUEST['password']))
			die_error(_("Unable to create user"), __FILE__, __LINE__);
		
		if ($_REQUEST['password'] === '') {
			popup_error(_('Unable to create user with an empty password'));
			redirect();
		}
		
		$res = $_SESSION['service']->user_add($_REQUEST['login'], $_REQUEST['displayname'], $_REQUEST['password']);
		if (! $res) {
			popup_error(sprintf(_("Unable to create user '%s'"), $_REQUEST['login']));
			redirect();
		}
		
		popup_info(sprintf(_("User '%s' successfully added"), $_REQUEST['login']));
		redirect('users.php');
	}
			
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_users']) && is_array($_REQUEST['checked_users'])) {
			foreach ($_REQUEST['checked_users'] as $user_login) {
				$res = $_SESSION['service']->user_remove($user_login);
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
		
		$displayname = null;
		if (array_key_exists('displayname', $_REQUEST) && strlen($_REQUEST['displayname']) > 0) {
			$displayname = $_REQUEST['displayname'];
		}
		$password = null;
		if (array_key_exists('password', $_REQUEST) && strlen($_REQUEST['password']) > 0) {
			$password = $_REQUEST['password'];
		}
		
		if ($displayname == null && $password == null) {
			redirect();
		}
		
		$res = $_SESSION['service']->user_modify($login, $displayname, $password);
		if (! $res)
			die_error(sprintf(_("Unable to modify User '%s'"), $login), __FILE__, __LINE__);
		
		popup_info(sprintf(_("User '%s' successfully modified"), $login));
		redirect('users.php?action=manage&id='.$login);
	}
	
	if ($_REQUEST['action'] == 'populate') {
		$override = ($_REQUEST['override'] == '1');
		if ($_REQUEST['password'] == 'custom') {
			if (strlen($_REQUEST['password_str']) == 0) {
				popup_error(_('No custom password specified for populating the database.'));
				redirect();
			}
			
			$password = $_REQUEST['password_str'];
		}
		else
			$password = NULL;
		
		$ret = $_SESSION['service']->users_populate($override, $password);
		if ($ret) {
			popup_info(_('User database populated.'));
		}
		else {
			popup_error(_('User database population failed.'));
		}
		redirect('users.php');
	}
}

if ($_REQUEST['name'] == 'User_settings') {
	if (isset($_REQUEST['unique_id']) && isset($_REQUEST['action'])) {
		$ret = null;
		if ($_REQUEST['action'] == 'add' && isset($_REQUEST['container']) && isset($_REQUEST['element_id'])) {
			$ret = $_SESSION['service']->user_settings_set($_REQUEST['unique_id'], $_REQUEST['container'], $_REQUEST['element_id'], null);
		}
		else if ($_REQUEST['action'] == 'del' && isset($_REQUEST['container']) && isset($_REQUEST['element_id'])) {
			$ret = $_SESSION['service']->user_settings_remove($_REQUEST['unique_id'], $_REQUEST['container'], $_REQUEST['element_id']);
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
				
				$ret = null;
				foreach ($data as $element_id => $value) {
					$ret = $_SESSION['service']->user_settings_set($_REQUEST['unique_id'], $container, $element_id, $value);
					if ( $ret !== true) {
						break;
					}
				}
			}
		}
		
		if ($ret === true) {
			popup_info(_('User successfully modified'));
		}
		else if ($ret === false) {
			popup_error(_('Failed to modify User'));
		}
	}
}


if ($_REQUEST['name'] == 'default_browser') {
	if (! checkAuthorization('manageApplications'))
		redirect();

	if ($_REQUEST['action'] == 'add') {
		if ($_REQUEST['browser'] != -1) {
			$_SESSION['service']->default_browser_set($_REQUEST['type'], $_REQUEST['browser']);
		}
		else {
			$_SESSION['service']->default_browser_unset($_REQUEST['type']);
		}
	}
}

if ($_REQUEST['name'] == 'SharedFolder') {
	if (! checkAuthorization('manageSharedFolders'))
		redirect();

	if ($_REQUEST['action']=='add') {
		action_add_sharedfolder();
		redirect();
	}
	
	if ($_REQUEST['action'] == 'del' && array_key_exists('ids', $_REQUEST)) {
		$status = false;
		foreach ($_REQUEST['ids'] as $id) {
			$sharedfolder = $_SESSION['service']->shared_folder_info($id);
			if (! is_object($sharedfolder)) {
				popup_error(sprintf(_("Unable to delete network folder with id '%s'"), $id));
			}
			else {
				$res = $_SESSION['service']->shared_folder_remove($id);
				if ($res !== true) {
					popup_error(sprintf(_("Unable to delete network folder '%s'"), $sharedfolder->name));
				}
				else {
					$status = true;
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
			
			$sharedfolder = $_SESSION['service']->shared_folder_info($id);
			if (is_object($sharedfolder)) {
				$ret = $_SESSION['service']->shared_folder_rename($id, $new_name);
				if ($ret === true)
					popup_info(_('Shared folder successfully renamed'));
				else
					popup_error(_('Update of the shared folder failed'));
			}
			redirect('sharedfolders.php?action=manage&id='.$id);
		}
	}
}

if ($_REQUEST['name'] == 'SharedFolder_ACL') {
	if (! checkAuthorization('manageSharedFolders'))
		redirect();

	if ($_REQUEST['action'] == 'add' && isset($_REQUEST['sharedfolder_id']) && isset($_REQUEST['usergroup_id'])) {
		if (! array_key_exists('mode', $_REQUEST) or ! in_array($_REQUEST['mode'], array('ro', 'rw'))) {
			redirect();
		}
		
		action_add_sharedfolder_acl($_REQUEST['sharedfolder_id'], $_REQUEST['usergroup_id'], $_REQUEST['mode']);
		redirect();
	}
	elseif ($_REQUEST['action'] == 'del' && isset($_REQUEST['sharedfolder_id']) && isset($_REQUEST['usergroup_id'])) {
		action_del_sharedfolder_acl($_REQUEST['sharedfolder_id'], $_REQUEST['usergroup_id']);
		redirect();
	}
}

if ($_REQUEST['name'] == 'Profile') {
	if (! checkAuthorization('manageServers'))
		redirect();

	if ($_REQUEST['action'] == 'add') {
		if (! isset($_REQUEST['users']) || ! is_array($_REQUEST['users'])) {
			if (! isset($_REQUEST['user']) || is_array($_REQUEST['user'])) {
				redirect();
			}
			
			$_REQUEST['users'] = array($_REQUEST['user']);
		}
		
		foreach ($_REQUEST['users'] as $user_login) {
			$res = $_SESSION['service']->user_profile_add($user_login);
			if (! $res) {
				popup_error(_('Unable to associate the user to the profile'));
				redirect();
			}
			
			popup_info(sprintf(_("Profile successfully created for user '%s'"), $user_login));
		}
		
		redirect();
	}

	if ($_REQUEST['action'] == 'del') {
		foreach ($_REQUEST['ids'] as $id) {
			$res = $_SESSION['service']->user_profile_remove($id);
			if (! $res) {
				popup_error(sprintf(_("Unable to delete profile '%s'"), $id));
			}
			else {
				popup_info(sprintf(_("Profile '%s' successfully deleted"), $id));
			}
		}

		redirect();
	}
}

if ($_REQUEST['name'] == 'ServersGroup') {
	if (! checkAuthorization('manageServers'))
		redirect();
	
	if ($_REQUEST['action'] == 'add') {
		if ( isset($_REQUEST['name_group']) && isset($_REQUEST['description_group'])) {
			$name = $_REQUEST['name_group'];
			$description = $_REQUEST['description_group'];
			
			$res = $_SESSION['service']->servers_group_add($name, $description);
			if (!$res) {
				popup_error(sprintf(_("Unable to create server group '%s'"), $name));
				redirect();
			}
			
			popup_info(sprintf(_("Server group '%s' successfully added"), $name));
			redirect('serversgroup.php?action=manage&id='.$res);
		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_groups']) and is_array($_REQUEST['checked_groups'])) {
			$ids = $_REQUEST['checked_groups'];
			foreach ($ids as $id) {
				$res = $_SESSION['service']->servers_group_remove($id);
				if (! $res) {
					popup_error(sprintf(_("Unable to remove group '%s'"), $id));
					continue;
				}
				
				popup_info(sprintf(_("Server Group '%s' successfully deleted"), $id));
			}
			
			redirect('serversgroup.php');
		}
	}
	
	if ($_REQUEST['action'] == 'modify') {
		if (isset($_REQUEST['id']) && (isset($_REQUEST['name_group']) || isset($_REQUEST['description_group']) || isset($_REQUEST['published_group']))) {
			$id = $_REQUEST['id'];
			$name = null;
			$description = null;
			$published = null;
			
			if (isset($_REQUEST['name_group'])) {
				$name = $_REQUEST['name_group'];
			}
			
			if (isset($_REQUEST['description_group'])) {
				$description = $_REQUEST['description_group'];
			}
			
			if (isset($_REQUEST['published_group'])) {
				$published = (bool)$_REQUEST['published_group'];
			}
			
			$res = $_SESSION['service']->servers_group_modify($id, $name, $description, $published);
			if (! $res) {
				popup_error(sprintf(_("Unable to modify application group '%s'"), $id));
				redirect();
			}
			
			popup_info(sprintf(_("Application Group '%s' successfully modified"), $name));
			redirect('serversgroup.php?action=manage&id='.$id);
		}
	}
}

if ($_REQUEST['name'] == 'Server_ServersGroup') {
	if (! checkAuthorization('manageServers')) {
		redirect();
	}
	
	if (! isset($_REQUEST['server']) && isset($_REQUEST['group'])) {
		redirect();
	}
	
	if ($_REQUEST['action'] == 'add') {
		$ret = $_SESSION['service']->servers_group_add_server($_REQUEST['server'], $_REQUEST['group']);
		if ($ret === true) {
			popup_info(sprintf(_('Server Group \'%s\' successfully modified'), $_REQUEST['group']));
		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		$ret = $_SESSION['service']->servers_group_remove_server($_REQUEST['server'], $_REQUEST['group']);
		if ($ret === true) {
			popup_info(sprintf(_('Server Group \'%s\' successfully modified'), $_REQUEST['group']));
		}
	}
}

if ($_REQUEST['name'] == 'UsersGroupServersGroup') {
	if (! checkAuthorization('managePublications')) {
		redirect();
	}
	
	if (!isset($_REQUEST['servers_group']) or !isset($_REQUEST['users_group'])) {
		redirect();
	}
	
	if ($_REQUEST['action'] == 'add') {
		$ret = $_SESSION['service']->servers_group_publication_add($_REQUEST['users_group'], $_REQUEST['servers_group']);
		if ($ret !== true) {
			popup_error(_('Unable to save the publication'));
			redirect();
		}
		
		popup_info(_('Publication successfully added'));
	}

	if ($_REQUEST['action'] == 'del') {
		$ret = $_SESSION['service']->servers_group_publication_remove($_REQUEST['users_group'], $_REQUEST['servers_group']);
		if ($ret !== true) {
			popup_error(_('Unable to delete the publication'));
			redirect();
		}
		
		popup_info(_('Publication successfully deleted'));
	}
}


if ($_REQUEST['name'] == 'NetworkFolder') {
	if (! checkAuthorization('manageServers'))
		redirect();

	if ($_REQUEST['action'] == 'del') {
		foreach ($_REQUEST['ids'] as $id) {
			$_SESSION['service']->network_folder_remove($id);
		}
		
		redirect();
	}
}


if ($_REQUEST['name'] == 'News') {
	if ($_REQUEST['action'] == 'add' && isset($_REQUEST['news_title']) && isset($_REQUEST['news_content'])) {
		$ret = $_SESSION['service']->news_add($_REQUEST['news_title'], $_REQUEST['news_content']);
		if ($ret === true)
			popup_info(_('News successfully added'));
		redirect();
	}
	elseif ($_REQUEST['action'] == 'del' && isset($_REQUEST['id'])) {
		$buf = $_SESSION['service']->news_remove($_REQUEST['id']);

		if (! $buf)
			popup_error(_('Unable to delete this news'));
		else
			popup_info(_('News successfully deleted'));

		redirect();
	}
}
if ($_REQUEST['name'] == 'Script') {
	if ($_REQUEST['action'] == 'add' && isset($_REQUEST['script_name']) && isset($_REQUEST['script_data'])) {
		$data = $_REQUEST['script_data'];
		if (array_key_exists('script_file', $_FILES) && trim($data) == '') {
			$upload = $_FILES['script_file'];
			
			$have_file = true;
			if ($upload['error']) {
				switch ($upload['error']) {
					case 1: // UPLOAD_ERR_INI_SIZE
						popup_error(_('File too large to be used for server rules'));
						redirect();
						break;
					case 3: // UPLOAD_ERR_PARTIAL
						popup_error(_('The file was corrupted during the upload'));
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
				
				$data = @file_get_contents($source_file);
			}
		}
		
		$ret = $_SESSION['service']->script_add($_REQUEST['script_name'], $_REQUEST['script_os'], $_REQUEST['script_type'], $data);

		if ($ret === true)
			popup_info(_('Script successfully added'));
		redirect();
	}
	elseif ($_REQUEST['action'] == 'del' && isset($_REQUEST['id'])) {
		$buf = $_SESSION['service']->script_remove($_REQUEST['id']);

		if (! $buf)
			popup_error(_('Unable to delete this script'));
		else
			popup_info(_('Script successfully deleted'));

		redirect();
	}
}

if ($_REQUEST['name'] == 'Script_UserGroup') {
	if (! checkAuthorization('manageScriptsGroups'))
		redirect();
	
	$group = $_SESSION['service']->users_group_info($_REQUEST['group']);
	if (! is_object($group)) {
		popup_error(sprintf(_('Unable to import group "%s"'), $_REQUEST['group']));
		redirect();
	}
	if ($_REQUEST['action'] == 'add') {
		$ret = $_SESSION['service']->users_group_add_script($_REQUEST['element'], $_REQUEST['group']);
		if ($ret === true) {
			popup_info(sprintf(_('UserGroup \'%s\' successfully modified'), $group->name));
		}
	}
	
	if ($_REQUEST['action'] == 'del') {
		$ret = $_SESSION['service']->users_group_remove_script($_REQUEST['element'], $_REQUEST['group']);
		if ($ret === true) {
			popup_info(sprintf(_('UserGroup \'%s\' successfully modified'), $group->name));
		}
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
			else if($_REQUEST['password_current'] != $_SESSION['admin_password']) {
				popup_error(_('Current password is not correct'));
			}
			else {
				$ret = $_SESSION['service']->administrator_password_set($_REQUEST['password']);
				if ($ret) {
					$_SESSION['admin_password'] = $_REQUEST['password'];
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
		if (! checkAuthorization('manageSession'))
			redirect();
	
		if (isset($_REQUEST['selected_session']) && is_array($_REQUEST['selected_session'])) {
			foreach ($_POST['selected_session'] as $session) {
				$ret = $_SESSION['service']->session_kill($session);
				if (! $ret) {
					popup_error(sprintf(_("Unable to delete session '%s'"), $session));
					continue;
				}
				else {
					popup_info(sprintf(_("Session '%s' successfully deleted"), $session));
				}
			}
			redirect('sessions.php');
		}
	}
	
	if ($_REQUEST['action'] == 'disc') {
		if (! checkAuthorization('manageSession'))
			redirect();
		
		if (isset($_REQUEST['selected_session']) && is_array($_REQUEST['selected_session'])) {
			foreach ($_POST['selected_session'] as $session) {
				$ret = $_SESSION['service']->session_disconnect($session);
				if (! $ret) {
					popup_error(sprintf(_("Unable to disconnect session '%s'"), $session));
					continue;
				}
				else {
					popup_info(sprintf(_("Session '%s' successfully disconnected"), $session));
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
			foreach ($_REQUEST['checked_servers'] as $server_id) {
				$server = $_SESSION['service']->server_info($server_id);
				if (! is_object($server)) {
					popup_error(sprintf(_("Unknown server '%s'"), $server_id));
					continue; 
				}
				
				$_SESSION['service']->server_remove($server_id);
				popup_info(sprintf(_("Server '%s' successfully deleted"), $server->getDisplayName()));
			}
			redirect('servers.php');
		}
	}
	
	if ($_REQUEST['action'] == 'register') {
		$errors = false;
		if (isset($_REQUEST['checked_servers']) && is_array($_REQUEST['checked_servers'])) {
			foreach ($_REQUEST['checked_servers'] as $server) {
				$buf = $_SESSION['service']->server_info($server);
				if (! is_object($buf)) {
					popup_error(sprintf(_("Unknown server '%s'"), $server));
					continue; 
				}
				
				$res = $_SESSION['service']->server_register($server);
				if ($res) {
					popup_info(sprintf(_("Server '%s' successfully registered"), $buf->getDisplayName()));
				}
				else {
					$errors = true;
					popup_error(sprintf(_("Failed to register server '%s'"), $buf->getDisplayName()));
				}
			}
		}
		
		$servers = $_SESSION['service']->getUnregisteredServersList();
		if (is_null($servers) || count($servers) == 0)
			redirect('servers.php');
		else
			redirect('servers.php?view=unregistered');
	}
	
	if ($_REQUEST['action'] == 'maintenance') {
		if (isset($_REQUEST['checked_servers']) && is_array($_REQUEST['checked_servers']) && (isset($_REQUEST['to_maintenance']) || isset($_REQUEST['to_production']))) {
			foreach ($_REQUEST['checked_servers'] as $server) {
				$a_server = $_SESSION['service']->server_info($server);
				if (! is_object($a_server)) {
					popup_error(sprintf(_("Unknown server '%s'"), $server));
					continue; 
				}
				
				$_SESSION['service']->server_switch_maintenance($server, isset($_REQUEST['to_maintenance']));
			}
		}
		redirect();
	}
	
	if ($_REQUEST['action'] == 'available_sessions') {
		if (isset($_REQUEST['server']) && isset($_REQUEST['max_sessions'])) {
			$server = $_SESSION['service']->server_info($_REQUEST['server']);
			if (! is_object($server)) {
				popup_error(sprintf(_("Unknown server '%s'"), $_REQUEST['server']));
				redirect(); 
			}
			
			$_SESSION['service']->server_set_available_sessions($_REQUEST['server'], $_REQUEST['max_sessions']);
			popup_info(sprintf(_("Server '%s' successfully modified"), $server->getDisplayName()));
			
			redirect('servers.php?action=manage&id='.$server->id);
		}
	}
	
	if ($_REQUEST['action'] == 'display_name') {
		if (! isset($_REQUEST['server']))
			redirect();
		
		if (! isset($_REQUEST['display_name']) || strlen($_REQUEST['display_name']) == 0)
			$new_dn = null;
		else
			$new_dn = $_REQUEST['display_name'];
		
		$server = $_SESSION['service']->server_info($_REQUEST['server']);
		if (! is_object($server)) {
			popup_error(sprintf(_("Unknown server '%s'"), $_REQUEST['server']));
			redirect(); 
		}
		
		$dn = null;
		if ($server->hasAttribute('display_name') && ! is_null($server->getAttribute('display_name')))
			$dn = $server->getAttribute('display_name');
		
		if ($new_dn == $dn) {
			popup_error(_("Nothing to save. New display name is identicall to old one"));
			redirect();
		}
		
		if ($new_dn !== null) {
			$_SESSION['service']->server_set_display_name($_REQUEST['server'], $new_dn);
		}
		else {
			$_SESSION['service']->server_unset_display_name($_REQUEST['server']);
		}
		
		popup_info(sprintf(_("Server '%s' successfully modified"), $server->getDisplayName()));
		
		redirect();
	}
	
	if ($_REQUEST['action'] == 'fqdn') {
		if (isset($_REQUEST['fqdn']) && isset($_REQUEST['server'])) {
			if (! validate_ip($_REQUEST['fqdn']) && ! validate_fqdn($_REQUEST['fqdn'])) {
				popup_error(sprintf(_("Internal name \"%s\" is invalid"), $_REQUEST['fqdn']));
				redirect();
			}
			
			$server = $_SESSION['service']->server_info($_REQUEST['server']);
			if (! is_object($server)) {
				popup_error(sprintf(_("Unknown server '%s'"), $_REQUEST['server']));
				redirect(); 
			}
			
			$_SESSION['service']->server_set_fqdn($_REQUEST['server'], $_REQUEST['fqdn']);
			popup_info(sprintf(_("Server '%s' successfully modified"), $server->fqdn));
			
			redirect('servers.php?action=manage&id='.$server->id);
		}
	}
	
	if ($_REQUEST['action'] == 'external_name') {
		if (! isset($_REQUEST['server']))
			redirect();
		
		if (! isset($_REQUEST['external_name']) || strlen($_REQUEST['external_name']) == 0)
			$external_name = null;
		else {
			if (! validate_ip($_REQUEST['external_name']) && ! validate_fqdn($_REQUEST['external_name'])) {
				popup_error(sprintf(_("Redirection name \"%s\" is invalid"), $_REQUEST['external_name']));
				redirect();
			}
			
			$external_name = $_REQUEST['external_name'];
		}

		$server = $_SESSION['service']->server_info($_REQUEST['server']);
		if (! is_object($server)) {
			popup_error(sprintf(_("Unknown server '%s'"), $_REQUEST['server']));
			redirect(); 
		}
		
		if ($external_name !== null) {
			$_SESSION['service']->server_set_external_name($_REQUEST['server'], $external_name);
		}
		else {
			$_SESSION['service']->server_unset_external_name($_REQUEST['server']);
		}
		
		popup_info(sprintf(_("Server '%s' successfully modified"), $server->getDisplayName()));
	
		redirect('servers.php?action=manage&id='.$server->id);
	}
	
	if ($_REQUEST['action'] == 'install_line') {
		if (isset($_REQUEST['server']) && isset($_REQUEST['line'])) {
			$_SESSION['service']->task_debian_install_packages($_REQUEST['server'], $_REQUEST['line']);
			popup_info(_('Task successfully added'));
		
			redirect('servers.php?action=manage&id='.$_REQUEST['server']);
		}
	}
	
	if ($_REQUEST['action'] == 'upgrade') {
		if (isset($_REQUEST['server'])) {
			$_SESSION['service']->task_debian_upgrade($_REQUEST['server']);
			
			popup_info(sprintf(_("Server '%s' is upgrading"), $_REQUEST['server']));
			redirect('servers.php?action=manage&id='.$_REQUEST['server']);
		}
	}
	
	if ($_REQUEST['action'] == 'replication') {
		if (isset($_REQUEST['server']) && isset($_REQUEST['servers'])) {
			$servers_id = $_REQUEST['servers'];
			foreach($servers_id as $server_id) {
				$_SESSION['service']->task_debian_server_replicate($server_id, $_REQUEST['server']);
			}
			redirect();
		}
	}
	
	if ($_REQUEST['action'] == 'rdp_port') {
		if (isset($_REQUEST['rdp_port']) && isset($_REQUEST['server'])) {
			if (! ctype_digit($_REQUEST['rdp_port']) || $_REQUEST['rdp_port'] < 1 || $_REQUEST['rdp_port'] > 65535 ) {
				popup_error(sprintf(_("RDP port \"%s\" is invalid"), $_REQUEST['rdp_port']));
				redirect();
			}
			
			$server = $_SESSION['service']->server_info($_REQUEST['server']);
			if (! is_object($server)) {
				popup_error(sprintf(_("Unknown server '%s'"), $_REQUEST['server']));
				redirect();
			}
			
			if ($_REQUEST['rdp_port'] == $server->getApSRDPPort()) {
				popup_error(_("Nothing to save. RDP port is identicall to old one"));
				redirect();
			}
			
			if ($_REQUEST['rdp_port'] == Server::DEFAULT_RDP_PORT)
				$_SESSION['service']->server_unset_rdp_port($_REQUEST['server']);
			else
				$_SESSION['service']->server_set_rdp_port($_REQUEST['server'], $_REQUEST['rdp_port']);
			
			popup_info(sprintf(_("Server '%s' successfully modified"), $server->getDisplayName()));
			
			redirect();
		}
	}
	
	if ($_REQUEST['action'] == 'role') {
		if (! isset($_REQUEST['server']) || ! isset($_REQUEST['role']) || ! isset($_REQUEST['do'])) {
			redirect();
		}
		
		if (! in_array($_REQUEST['do'], array('enable', 'disable'))) {
			redirect();
		}
		
		$server = $_SESSION['service']->server_info($_REQUEST['server']);
		if (! is_object($server)) {
			popup_error(sprintf(_("Unknown server '%s'"), $_REQUEST['server']));
			redirect();
		}
		
		if (! array_key_exists($_REQUEST['role'], $server->roles)) {
			popup_error(sprintf(_("%s is not an available role"), $_REQUEST['role']));
			redirect();
		}
		else if ($_REQUEST['do'] == 'disable' && array_key_exists($_REQUEST['role'], $server->roles_disabled)) {
			popup_error(sprintf(_("Nothing to save. Role %s is already disabled"), $_REQUEST['role']));
			redirect();
		}
		else if ($_REQUEST['do'] == 'enable' && ! array_key_exists($_REQUEST['role'], $server->roles_disabled)) {
			popup_error(sprintf(_("Nothing to save. Role %s is already enabled"), $_REQUEST['role']));
			redirect();
		}
		
		if ($_REQUEST['do'] == 'disable')
			$_SESSION['service']->server_role_disable($_REQUEST['server'], $_REQUEST['role']);
		else
			$_SESSION['service']->server_role_enable($_REQUEST['server'], $_REQUEST['role']);
		
		popup_info(sprintf(_("Server '%s' successfully modified"), $server->getDisplayName()));
		
		redirect();
	}
}

if ($_REQUEST['name'] == 'SessionReporting') {
	if (! checkAuthorization('manageReporting'))
		redirect();
	
	if ($_REQUEST['action'] == 'delete') {
		if (! isset($_REQUEST['session']) && ! isset($_REQUEST['sessions']))
			redirect();
			
		if (isset($_REQUEST['sessions']) && ! is_array($_REQUEST['sessions']))
			redirect();
		
		if (isset($_REQUEST['sessions']))
			$sessions_id = $_REQUEST['sessions'];
		else
			$sessions_id = array($_REQUEST['session']);
		
		foreach ($sessions_id as $sessions_id) {
			$ret = $_SESSION['service']->session_report_remove($sessions_id);
			if ($ret !== true) {
				popup_error(sprintf(_("Unable to delete archived session '%s'"), $sessions_id));
				continue; 
			}
			
			popup_info(sprintf(_("Archived session '%s' successfully deleted"), $sessions_id));
		}
	}
}

if ($_REQUEST['name'] == 'Task') {
	// it is the rigth place ? (see similar block on name=server action=install_line
		if (! checkAuthorization('manageServers'))
		redirect();
	
	if ($_REQUEST['action'] == 'add') {
		if (isset($_POST['type'])) {
			if ($_POST['type'] == 'upgrade') {
				$res = $_SESSION['service']->task_debian_upgrade($_REQUEST['server']);
			}
			else if ($_POST['type'] == 'install_from_line') {
				$res = $_SESSION['service']->task_debian_install_packages($_REQUEST['server'], $_POST['request']);
			}
			else {
				redirect();
			}
			
			if ($res) {
				popup_info(_("Task successfully added"));
			}
			else {
				popup_error("error create task (type='".$_POST['type']."')");
			}
		}
		redirect('tasks.php');
	}
	
	if ($_REQUEST['action'] == 'del') {
		if (isset($_REQUEST['checked_tasks']) && is_array($_REQUEST['checked_tasks'])) {
			foreach ($_REQUEST['checked_tasks'] as $id) {
				$ret = $_SESSION['service']->task_remove($id);
				if (! $ret) {
					popup_error('Task '.$id.' not removable');
					redirect('tasks.php');
				}
				
				popup_info(_('Task successfully deleted'));
				redirect('tasks.php');
			}
		}
		redirect('tasks.php');
	}
}

function action_add_sharedfolder() {
	$sharedfolder_name = $_REQUEST['sharedfolder_name'];
	if ($sharedfolder_name == '') {
		popup_error(_('You must provide a name to your shared folder'));
		return false;
	}

	$server = null;
	if (array_key_exists('sharedfolder_server', $_REQUEST)) {
		$server = $_REQUEST['sharedfolder_server'];
	}
	
	$ret = $_SESSION['service']->shared_folder_add($sharedfolder_name, $server);
	if (! $ret) {
		popup_error(_('Unable to add shared folder'));
		return false;
	}

	popup_info(sprintf(_('Shared folder \'%s\' successfully added'), $sharedfolder_name));
	return true;
}

function action_add_sharedfolder_acl($sharedfolder_id_, $usergroup_id_, $mode_) {
	$sharedfolder = $_SESSION['service']->shared_folder_info($sharedfolder_id_);
	if (! $sharedfolder) {
		popup_error(_('Unable to create this shared folder access'));
		return false;
	}
	
	$ret = $_SESSION['service']->shared_folder_add_group($usergroup_id_, $sharedfolder_id_, $mode_);
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
	$sharedfolder = $_SESSION['service']->shared_folder_info($sharedfolder_id_);
	if (! $sharedfolder) {
		popup_error(_('Unable to delete this shared folder access'));
		return false;
	}

	$ret = $_SESSION['service']->shared_folder_remove_group($usergroup_id_, $sharedfolder_id_);
	if ($ret === true) {
		popup_info(_('Shared folder successfully modified'));
		return true;
	}
	else {
		popup_error(sprintf(_("Unable to modify shared folder named '%s'"), $sharedfolder->name));
		return false;
	}
}

if (!$ignore_redirect) 
	redirect();
