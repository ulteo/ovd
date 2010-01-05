<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
 * Author Jeremy DESVAGES <jeremy@ulteo.com>
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
require_once(dirname(__FILE__).'/includes/page_template.php');

if (! checkAuthorization('viewServers'))
	redirect('index.php');


if (isset($_REQUEST['mass_register'])) {
	if (! checkAuthorization('manageServers'))
		redirect();

	if (isset($_REQUEST['checked_servers']) && is_array($_REQUEST['checked_servers'])) {
		foreach ($_REQUEST['checked_servers'] as $server) {
			$buf = Abstract_Server::load($server);
			$buf->register();
			Abstract_Server::save($buf);
			$buf->updateApplications();
		}
	}

	$buf = count(Servers::getUnregistered());
	if ($buf == 0)
		redirect('servers.php');

	redirect();
}

if (isset($_REQUEST['mass_delete_unregistered'])) {
	if (! checkAuthorization('manageServers'))
		redirect();

	if (isset($_REQUEST['checked_servers']) && is_array($_REQUEST['checked_servers'])) {
		foreach ($_REQUEST['checked_servers'] as $server) {
			$buf = Abstract_Server::load($server);
			if (! $buf)
				continue;

			$buf->orderDeletion();
			Abstract_Server::delete($buf->fqdn);
		}
	}

	$buf = count(Servers::getUnregistered());
	if ($buf == 0)
		redirect('servers.php');

	redirect();
}

if (isset($_REQUEST['mass_action']) && $_REQUEST['mass_action'] == 'maintenance') {
	if (isset($_REQUEST['manage_servers']) && is_array($_REQUEST['manage_servers'])) {
		foreach ($_REQUEST['manage_servers'] as $server) {
			$buf = Abstract_Server::load($server);
			if (isset($_REQUEST['to_maintenance']))
				$buf->setAttribute('locked', true);
			elseif ($buf->isOnline())
				$buf->setAttribute('locked', false);

			Abstract_Server::save($buf);
			popup_info(sprintf(_('Server \'%s\' successfully modified'), $buf->getAttribute('fqdn')));
		}
	}

	redirect();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'install_line' && isset($_REQUEST['fqdn']) && isset($_REQUEST['line'])) {
//FIX ME ?
	$t = new Task_install_from_line(0, $_REQUEST['fqdn'], $_REQUEST['line']);

	$tm = new Tasks_Manager();
	$tm->add($t);
	popup_info(_('Task successfully added'));

	redirect();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'upgrade' && isset($_REQUEST['fqdn'])) {
	$t = new Task_upgrade(0, $_REQUEST['fqdn']);

	$tm = new Tasks_Manager();
	$tm->add($t);
	popup_info(_('Server upgrading'));
	redirect();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'replication' && isset($_REQUEST['fqdn']) && isset($_REQUEST['servers'])) {
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

if (isset($_GET['action']) && $_GET['action'] == 'register' && isset($_GET['fqdn'])) {
	if (! checkAuthorization('manageServers'))
		redirect();

	$buf = Abstract_Server::load($_GET['fqdn']);
	$buf->register();
	Abstract_Server::save($buf);
	$buf->updateApplications();
	popup_info(sprintf(_('Server \'%s\' successfully registered'), $buf->getAttribute('fqdn')));

	$buf = count(Servers::getUnregistered());
	if ($buf == 0)
		redirect('servers.php');

	redirect();
}

if (isset($_GET['action']) && $_GET['action'] == 'maintenance' && isset($_GET['fqdn'])) {
	if (! checkAuthorization('manageServers'))
		redirect();

	$buf = Abstract_Server::load($_GET['fqdn']);
	if (isset($_GET['maintenance']) && $_GET['maintenance'] == 1)
		$buf->setAttribute('locked', true);
	elseif ($buf->isOnline())
		$buf->setAttribute('locked', false);

	Abstract_Server::save($buf);
	popup_info(sprintf(_('Server \'%s\' successfully modified'), $buf->getAttribute('fqdn')));

	redirect();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'available_sessions' && isset($_REQUEST['fqdn'])) {
	if (! checkAuthorization('manageServers'))
		redirect();

	if (isset($_REQUEST['max_sessions'])) {
		$server = Abstract_Server::load($_REQUEST['fqdn']);
		$server->setAttribute('max_sessions', $_REQUEST['max_sessions']);
		Abstract_Server::save($server);
		popup_info(sprintf(_('Server \'%s\' successfully modified'), $server->getAttribute('fqdn')));
	}

	redirect();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'external_name' && isset($_REQUEST['fqdn'])) {
	if (! checkAuthorization('manageServers'))
		redirect();

	if (isset($_REQUEST['external_name'])) {
		$server = Abstract_Server::load($_REQUEST['fqdn']);
		$server->setAttribute('external_name', $_REQUEST['external_name']);
		Abstract_Server::save($server);
		popup_info(sprintf(_('Server \'%s\' successfully modified'), $server->getAttribute('fqdn')));
	}

	redirect();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'web_port' && isset($_REQUEST['fqdn'])) {
	if (! checkAuthorization('manageServers'))
		redirect();

	if (isset($_REQUEST['web_port'])) {
		$server = Abstract_Server::load($_REQUEST['fqdn']);
		$server->setAttribute('web_port', $_REQUEST['web_port']);
		Abstract_Server::save($server);
		popup_info(sprintf(_('Server \'%s\' successfully modified'), $server->getAttribute('fqdn')));
	}

	redirect();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete' && isset($_REQUEST['fqdn'])) {
	if (! checkAuthorization('manageServers'))
		redirect();

	$sessions = Sessions::getByServer($_REQUEST['fqdn']);
	if (count($sessions) > 0) {
		popup_error(_('Unable to delete a server when there are active sessions on it!'));
		redirect();
	}

	$buf = Abstract_Server::load($_REQUEST['fqdn']);
	if (is_object($buf)) {
		$buf->orderDeletion();
		Abstract_Server::delete($buf->fqdn);
		popup_info(sprintf(_('Server \'%s\' successfully deleted'), $buf->getAttribute('fqdn')));
	}

	$buf = count(Servers::getUnregistered());
	if ($buf == 0)
		redirect('servers.php');

	redirect();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'manage' && isset($_REQUEST['fqdn'])) {
  show_manage($_REQUEST['fqdn']);
}

if (! isset($_GET['view']))
  $_GET['view'] = 'all';

if ($_GET['view'] == 'all')
  show_default();
elseif ($_GET['view'] == 'unregistered')
  show_unregistered();

function show_default() {
//FIX ME ?
  $a_servs = Servers::getRegistered();
  if (! is_array($a_servs))
    $a_servs = array();

  $nb_a_servs_online = 0;
  foreach($a_servs as $s) {
	$external_name_checklist = array('localhost', '127.0.0.1');
	if (in_array($s->fqdn, $external_name_checklist) && in_array($s->getAttribute('external_name'), $external_name_checklist))
		popup_error($s->fqdn.': '._('redirection name may be invalid!'));
	if ($s->getAttribute('external_name') == '')
		popup_error($s->fqdn.': '._('redirection name cannot be empty!'));

    if ($s->isOnline())
      $nb_a_servs_online++;
  }

	$can_do_action = isAuthorized('manageServers');

  page_header();

  echo '<div id="servers_div">';
  echo '<h1>'._('Servers').'</h1>';

  $av_servers = count($a_servs);

  if ($av_servers > 0) {
    echo '<div id="servers_list_div">';
    echo '<table id="available_servers_table" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
    echo '<thead>';
    echo '<tr class="title">';
    if ($av_servers > 1 and $can_do_action)
      echo '<th class="unsortable"></th>';
    echo '<th>'._('FQDN').'</th><th>'._('Type').'</th>';
    // echo '<th>'._('Version').'</th>';
    echo '<th>'._('Status').'</th><th>'._('Details').'</th>';
    if ($nb_a_servs_online > 0)
      echo '<th>'._('Monitoring').'</th>';
    // echo '<th>'._('Applications(physical)'.</th>';
    echo '</tr>';
    echo '</thead>';

    $count = 0;
    foreach($a_servs as $s) {
      $content = 'content'.(($count++%2==0)?1:2);
      $server_online = $s->isOnline();

	if ($s->getAttribute('locked')) {
	  $switch_msg = _('Switch to production');
	  $switch_value = 0;
	}
	else {
	  $switch_msg = _('Switch to maintenance');
	  $switch_value = 1;
	}


      echo '<tr class="'.$content.'">';
      if ($av_servers > 1 and $can_do_action)
        echo '<td><input class="input_checkbox" type="checkbox" name="manage_servers[]" value="'.$s->fqdn.'" /></td>';
      echo '<td>';
      echo '<a href="servers.php?action=manage&fqdn='.$s->fqdn.'">'.$s->fqdn.'</a>';
      echo '</td>';
      echo '<td style="text-align: center;"><img src="media/image/server-'.$s->stringType().'.png" alt="'.$s->stringType().'" title="'.$s->stringType().'" /><br />'.$s->stringType().'</td>';
      // echo '<td>'.$s->stringVersion().'</td>';
      echo '<td>'.$s->stringStatus().'</td>';
      echo '<td>';
      echo _('CPU').': '.$s->getAttribute('cpu_model').' ('.$s->getAttribute('cpu_nb_cores').' ';
      echo ($s->getAttribute('cpu_nb_cores') > 1)?_('cores'):_('core');
      echo ')<br />';
      echo _('RAM').': '.round($s->getAttribute('ram_total')/1024).' '._('MB');
      echo '</td>';

      if ($nb_a_servs_online > 0) {
	echo '<td>';
	if ($server_online) {
	  echo _('CPU usage').': '.$s->getCpuUsage().'%<br />';
	  echo display_loadbar($s->getCpuUsage());
	  echo _('RAM usage').': '.$s->getRamUsage().'%<br />';
	  echo display_loadbar($s->getRamUsage());
	  echo _('Sessions usage').': '.$s->getSessionUsage().'%<br />';
	  echo display_loadbar($s->getSessionUsage());
	}
	echo '</td>';
      }
      echo '<td>';
      echo '<form action="servers.php" method="get">';
      echo '<input type="submit" value="'._('Manage').'"/>';
      echo '<input type="hidden" name="action" value="manage" />';
      echo '<input type="hidden" name="fqdn" value="'.$s->fqdn.'" />';
      echo '</form>';
      echo '</td>';

      if ($can_do_action) {
	echo '<td>';
	  if ($server_online || $switch_value == 1) {
	  echo '<form action="servers.php" method="get">';
	  echo '<input';
       if ($switch_value == 0)
         echo ' style="background: #05a305; color: #fff; font-weight: bold;"';
       echo ' type="submit" value="'.$switch_msg.'"/>';
	  echo '<input type="hidden" name="action" value="maintenance" />';
	  echo '<input type="hidden" name="maintenance" value="'.$switch_value.'" />';
	  echo '<input type="hidden" name="fqdn" value="'.$s->fqdn.'" />';
	  echo '</form>';
	  }
	echo '</td>';
      }
      echo '</tr>';
    }

    if ($av_servers > 1 and $can_do_action) {
      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tfoot>';
      echo '<tr class="'.$content.'">';
      echo '<td colspan="'.(($nb_a_servs_online > 0)?7:6).'">';
      echo '<a href="javascript:;" onclick="markAllRows(\'available_servers_table\'); return false">'._('Mark all').'</a>';
      echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'available_servers_table\'); return false">'._('Unmark all').'</a>';
      echo '</td>';
      echo '<td>';
      echo '<form action="servers.php" method="post" onsubmit="return updateMassActionsForm(this, \'available_servers_table\');">';
      echo '<input type="hidden" name="mass_action" value="maintenance" />';
      echo '<input type="submit" name="to_production" value="'._('Switch to production').'"/><br />';
      echo '<input type="submit" name="to_maintenance" value="'._('Switch to maintenance').'"/>';
      echo '</form>';
      echo '</td>';
      echo '</tr>';
      echo '</tfoot>';
    }

    echo '</table>';
  }

  echo '</div>';
  echo '</div>';
  page_footer();
  die();
}

function show_unregistered() {
//FIX ME ?
  $u_servs = Servers::getUnregistered();
  if (! is_array($u_servs))
    $u_servs = array();

	$can_do_action = isAuthorized('manageServers');

  page_header();

  echo '<div id="servers_div">';
  echo '<h1>'._('Unregistered servers').'</h1>';

  if (count($u_servs) > 0){
    echo '<div id="servers_list_div">';

    echo '<table id="unregistered_servers_table" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
    echo '<thead>';
    echo '<tr class="title">';
    if (count($u_servs) > 1)
      echo '<th class="unsortable"></th>';
    echo '<th>'._('FQDN').'</th><th>'._('Type').'</th>';
//     echo '<th>'._('Version').'</th>';
    echo '<th>'._('Details').'</th>';
    echo '</tr>';
    echo '</thead>';

    $count = 0;
    foreach($u_servs as $s) {
      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tr class="'.$content.'">';
            if (count($u_servs) > 1)
	echo '<td><input class="input_checkbox" type="checkbox" name="checked_servers[]" value="'.$s->fqdn.'" />';

	if ($s->getAttribute('type') == '')
		$s->getType();
// 	if ($s->getAttribute('version') == '')
// 		$s->getVersion();
	if ($s->getAttribute('cpu_model') == '')
		$s->getMonitoring();

      Abstract_Server::save($s);

      echo '<td>'.$s->fqdn.'</td>';
      echo '<td style="text-align: center;"><img src="media/image/server-'.$s->stringType().'.png" alt="'.$s->stringType().'" title="'.$s->stringType().'" /><br />'.$s->stringType().'</td>';
//       echo '<td>'.$s->stringVersion().'</td>';
      echo '<td>';
      echo _('CPU').': '.$s->getAttribute('cpu_model').' ('.$s->getAttribute('cpu_nb_cores').' ';
      echo ($s->getAttribute('cpu_nb_cores') > 1)?_('cores'):_('core');
      echo ')<br />';
      echo _('RAM').': '.round($s->getAttribute('ram_total')/1024).' MB';
      echo '</td>';

		if ($can_do_action) {
			echo '<td>';
			if ($s->isOK()) {
				echo '<form action="servers.php" method="get">';
				echo '<input type="hidden" name="action" value="register" />';
				echo '<input type="hidden" name="fqdn" value="'.$s->fqdn.'" />';
				echo '<input style="background: #05a305; color: #fff; font-weight: bold;" type="submit" value="'._('Register').'" />';
				echo '</form>';
			}
			echo '</td>';

			echo '<td>';
			echo '<form action="servers.php" method="get" onsubmit="return confirm(\''._('Are you sure you want to delete this server?').'\');">';
			echo '<input type="hidden" name="action" value="delete" />';
			echo '<input type="hidden" name="fqdn" value="'.$s->fqdn.'" />';
			echo '<input type="submit" value="'._('Delete').'" />';
			echo '</form>';
			echo '</td>';
		}

      echo '</tr>';
    }

    // Mass actions
    if (count($u_servs) > 1) {
      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tfoot>';
      echo '<tr class="'.$content.'">';
      echo '<td colspan="4">';
      echo '<a href="javascript:;" onclick="markAllRows(\'unregistered_servers_table\'); return false">'._('Mark all').'</a>';
      echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'unregistered_servers_table\'); return false">'._('Unmark all').'</a>';
      echo '</td>';
      echo '<td>';
      echo '<form action="servers.php" method="post" onsubmit="return updateMassActionsForm(this, \'unregistered_servers_table\');">';
      echo '<input type="submit" name="mass_register" value="'._('Register').'"/><br />';
      echo '</form>';
      echo '</td>';
      echo '<td>';
      echo '<form action="servers.php" method="post" onsubmit="return updateMassActionsForm(this, \'unregistered_servers_table\');">';
      echo '<input type="submit" name="mass_delete_unregistered" value="'._('Delete').'"/><br />';
      echo '</form>';
      echo '</td>';
      echo '</tr>';
      echo '</tfoot>';
    }

    echo '</table>';
    echo '</div>';
    echo '<br />';
  } else {
    echo _('No unregistered server');
    echo '<br /><br />';
  }

  echo '</div>';
  echo '</div>';
  page_footer();
  die();
}

function show_manage($fqdn) {
  $server = Abstract_Server::load($fqdn);

  if (! $server)
    redirect('servers.php');

	$external_name_checklist = array('localhost', '127.0.0.1');
	if (in_array($server->fqdn, $external_name_checklist) && in_array($server->getAttribute('external_name'), $external_name_checklist))
		popup_error($server->fqdn.': '._('redirection name may be invalid!'));
	if ($server->getAttribute('external_name') == '')
		popup_error($server->fqdn.': '._('redirection name cannot be empty!'));

//FIX ME?
  if ($server->getAttribute('status') == 'ready')
    $server->getStatus();

  $server_online = $server->isOnline();

  if ($server_online) {
    $buf = $server->getMonitoring();
    if ($buf === false)
      popup_error(_('Cannot get server monitoring'));
    Abstract_Server::save($server);
  }

  $buf_status = $server->getAttribute('status');
  if ($buf_status == 'down')
    $status_error_msg = _('Warning: server is offline');
  elseif ($buf_status == 'broken')
    $status_error_msg = _('Warning: server is broken');

  $server_lock = $server->getAttribute('locked');

  if ($server_online) {
    $buf = $server->updateApplications();
    if ($buf === false)
      popup_error(_('Cannot list available applications'));
  }

//FIX ME ?
  $tm = new Tasks_Manager();
  $tm->load_from_server($server->fqdn);
  $tm->refresh_all();

  $apps_in_remove = array();
  $apps_in_install = array();

  $tasks = array();
  if ($server_online) {
    foreach($tm->tasks as $task) {
      if (! $task->succeed())
        $tasks[]= $task;
    }

    foreach($tasks as $task) {
      if (get_class($task) == 'Task_install') {
    foreach($task->applications as $app) {
      if (! in_array($app, $apps_in_install))
        $apps_in_install[]= $app;
    }
      }
      if (get_class($task) == 'Task_remove') {
	foreach($task->applications as $app) {
	  if (! in_array($app, $apps_in_remove))
	    $apps_in_remove[]= $app;
	}
      }
    }
  }


  $prefs = Preferences::getInstance();
  if (! $prefs)
    die_error('get Preferences failed',__FILE__,__LINE__);

  $mods_enable = $prefs->get('general','module_enable');
  if (! in_array('ApplicationDB',$mods_enable))
    die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
  $mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
  $applicationDB = new $mod_app_name();

  $applications_all = $applicationDB->getList(true);
  $applications = $server->getApplications();
  if (! is_array($applications))
    $applications = array();
  usort($applications, 'application_cmp');

  $applications_available = array();
  if ($server_online) {
    foreach($applications_all as $app) {
      if (in_array($app, $applications))
        continue;
      if (in_array($app, $apps_in_install))
        continue;
      if ($app->getAttribute('type') != $server->getAttribute('type'))
        continue;

      $applications_available[]= $app;
    }
  }

  if (!$server_online && count($applications) == 0)
    $applications_all = array();


  $servers_all = Servers::getOnline();
  foreach($servers_all as $k => $v) {
    if ($v->fqdn == $server->fqdn)
      unset($servers_all[$k]);
  }

  $servers_replication = Servers::getOnline();
  foreach($servers_replication as $k => $v) {
    if ($v->fqdn == $server->fqdn)
      unset($servers_replication[$k]);
    else {
       if ( $v->type != $server->getAttribute('type')) {
         unset($servers_replication[$k]);
       }
    }
  }

  $sessions = Sessions::getByServer($_GET['fqdn']);

  if (count($sessions) > 0)
    $has_sessions = true;
  else
    $has_sessions = false;

    if ($server_lock) {
      $switch_button = _('Switch to production');
      $switch_value = 0;
    }
    else {
      $switch_button = _('Switch to maintenance');
      $switch_value = 1;
    }

  $external_name = $server->getAttribute('external_name');
  $web_port = $server->getAttribute('web_port');

	$can_do_action = isAuthorized('manageServers');

  page_header();
  echo '<script type="text/javascript" src="media/script/ajax/servers.js" charset="utf-8"></script>';

  echo '<div id="servers_div">';
  echo '<h1>'.$server->fqdn.'</h1>';

//   if ($server_online === false)
//     echo '<h2><p class="msg_error centered">'.$status_error_msg.'</p></h2>';

  echo '<div class="section">';
  echo '<h2>'._('Monitoring').'</h2>';
  echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
  echo '<tr class="title">';

  echo '<th>'._('Type').'</th><th>'._('Version').'</th><th>'._('Status').'</th>';
  echo '<th>'._('Details').'</th>';
  if ($server_online)
    echo '<th>'._('Monitoring').'</th>';
  echo '</tr>';

  echo '<tr class="content1">';
  echo '<td style="text-align: center;"><img src="media/image/server-'.$server->stringType().'.png" alt="'.$server->stringType().'" title="'.$server->stringType().'" /><br />'.$server->stringType().'</td>';

  echo '<td>'.$server->stringVersion().'</td>';
  echo '<td>'.$server->stringStatus().'</td>';
  echo '<td>'._('CPU').'; : '.$server->getAttribute('cpu_model').'  ('.$server->getAttribute('cpu_nb_cores').' ';
  echo ($server->getAttribute('cpu_nb_cores') > 1)?_('cores'):_('core');
  echo ')<br />'._('RAM').' : '.round($server->getAttribute('ram_total')/1024).' '._('MB').'</td>';

  if ($server_online) {
    echo '<td>';
        echo _('CPU usage').': '.$server->getCpuUsage().'%<br />';
        echo display_loadbar($server->getCpuUsage());
        echo _('RAM usage').': '.$server->getRamUsage().'%<br />';
        echo display_loadbar($server->getRamUsage());
        echo _('Sessions usage').': '.$server->getSessionUsage().'%<br />';
        echo display_loadbar($server->getSessionUsage());
    echo '</td>';
  }

  echo '</tr>';
  echo '</table>';
  echo '</div>';

  echo '<div class="section">';
  echo '<h2>'._('Configuration').'</h2>';
  echo '<table>';

	if ($server->type == 'windows' && ! is_null($server->windows_domain)) {
		echo '<tr><td>';
		echo _('Inside Active Directory domain').': ';
		echo '</td><td>';
		echo $server->windows_domain;
		echo '</td></tr>';
	}

	if ($server->type == 'linux') {
		echo '<tr><td>';
		echo _('Number of available sessions on this server').': ';
		echo '</td><td>';
		if ($can_do_action) {
			echo '<form action="servers.php" method="GET">';
			echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'" />';
			echo '<input type="hidden" name="action" value="available_sessions" />';
			echo '<input type="button" value="-" onclick="field_increase(\'number\', -1);" /> ';
		}
		echo '<input type="text" id="number" name="max_sessions" value="'.$server->getNbMaxSessions().'" size="3" onchange="field_check_integer(this);" />';
		if ($can_do_action) {
			echo ' <input type="button" value="+" onclick="field_increase(\'number\', 1);" />';
			echo ' <input type="submit" value="'._('change').'" />';
			echo '</form>';
		}
		echo '</td></tr>';

		echo '<tr><td>';
		echo _('Redirection name of this server').': ';
		echo '</td><td>';
		if ($can_do_action) {
			echo '<form action="servers.php" method="GET">';
			echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'" />';
			echo '<input type="hidden" name="action" value="external_name" />';
		}
		echo '<input type="text" name="external_name" value="'.$external_name.'" />';
		if ($can_do_action) {
			echo ' <input type="submit" value="'._('change').'" />';
			echo '</form>';
		}
		echo "</td></tr>\n";
	}

  echo '<tr><td>';
  echo _('Web port of this server').': ';
  echo '</td><td>';
	if ($can_do_action) {
		echo '<form action="servers.php" method="GET">';
		echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'" />';
		echo '<input type="hidden" name="action" value="web_port" />';
	}
  echo '<input type="text" name="web_port" value="'.$web_port.'" />';
  if ($can_do_action) {
    echo ' <input type="submit" value="'._('change').'" />';
    echo '</form>';
  }
  echo "</td></tr>\n";

	if ($can_do_action) {
		if ($server_online || $switch_value == 1) {
			echo '<tr><td></td><td>';
			echo '<form action="servers.php" method="get">';
			echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'" />';
			echo '<input type="hidden" name="action" value="maintenance" />';
			echo '<input type="hidden" name="maintenance" value="'.$switch_value.'" />';
			echo '<input';
			if ($switch_value == 0)
				echo ' style="background: #05a305; color: #fff; font-weight: bold;"';
			echo ' type="submit" value="'.$switch_button.'"/>';
			echo '</form>';
			echo '</td></tr>';
		}


		if ($server_lock || !$server_online) {
			echo '<tr><td></td><td>';
			echo '<form action="servers.php" method="get" onsubmit="return confirm(\''._('Are you sure you want to delete this server?').'\');">';
			echo '<input type="hidden" name="action" value="delete" />';
			echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'" />';
			echo '<input type="submit" value="'._('Delete').'" />';
			echo '</form>';
			echo '</td></tr>';
		}
	}
  echo '</table>';
  echo '</div>';

	if ($server_online && $server->getAttribute('type') == 'linux' && $can_do_action) {
    echo '<div class="section">';
    echo '<h2>'._('Install an application').'</h2>';
    echo '<form>';
    echo '<input type="hidden" name="action" value="install_line">';
    echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'">';
    echo '<input type="text" name="line"> ';
    echo '<input type="submit" value="'._('Install from a package name').'">';
    echo '</form>';
    echo '<br />';

    echo '<div id="installableApplicationsList">';
    echo '<a href="javascript:;" onclick="toggleInstallableApplicationsList(\''.$server->fqdn.'\'); return false;"><div style="width: 16px; height: 16px; float: left;" id="installableApplicationsList_ajax"></div></a><div style="float: left;"><a href="javascript:;" onclick="toggleInstallableApplicationsList(\''.$server->fqdn.'\'); return false;">&nbsp;'._('more options').'</a></div>';
	echo '<div style="clear: both;"></div>';
    echo '<div id="installableApplicationsList_content" style="display: none;"><script type="text/javascript">Event.observe(window, \'load\', function() { offContent(\'installableApplicationsList\'); });</script></div>';
    echo '</div>';

    echo '<div id="installableApplicationsListDefault" style="display: none; visibility: hidden;">';
    echo '<form>';
    echo '<input type="hidden" name="action" value="install_line">';
    echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'">';
    echo '<table>';
    echo '<tr>';
    echo '<td>'._('Category').'</td>';
    echo '<td><div id="installable_applications_category"></div></td>';
    echo '<td></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>'._('Application').'</td>';
    echo '<td><div id="installable_applications_application"></div></td> ';
    echo '<td><input type="submit" value="'._('Install').'" /></td>';
    echo '<td></td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';
    echo '</div>';
    echo '</div>';

    echo '<div class="section">';
    echo '<h2>'._('Upgrade').'</h2>';
    echo '<form>';
    echo '<input type="hidden" name="action" value="upgrade">';
    echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'">';
    echo '<input type="submit" value="'._('Upgrade the internal system and applications').'">';
    echo '</form>';
    echo '</div>';
  }


  // Application part
  if (count($applications_all) > 0) {
    $count = 0;
    echo '<div class="section">';
    echo '<h2>'._('Applications available on this server').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';

    if (count($applications) > 0) {
      foreach ($applications as $app) {
	$content = 'content'.(($count++%2==0)?1:2);
	$remove_in_progress = in_array($app, $apps_in_remove);

	echo '<tr class="'.$content.'">';
	echo '<td>';
	echo '<img src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" /> ';
	echo '<a href="applications.php?action=manage&id='.$app->getAttribute('id').'">';
	echo $app->getAttribute('name').'</a>';
	echo '</td>';
	if ($server_online && $server->type == 'linux' && $can_do_action) {
	  echo '<td>';
	  if ($remove_in_progress)
	    echo 'remove in progress';
	  else {
	    echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to remove this application from this server?').'\');">';
	    echo '<input type="hidden" name="action" value="del" />';
	    echo '<input type="hidden" name="name" value="Application_Server" />';
	    echo '<input type="hidden" name="server" value="'.$server->fqdn.'" />';
	    echo '<input type="hidden" name="application" value="'.$app->getAttribute('id').'" />';
	    echo '<input type="submit" value="'._('Remove from this server').'" />';
	    echo '</form>';
	  }
	}

	echo '</td>';
	echo '</tr>';
      }
    }

    foreach ($apps_in_install as $app) {
      $content = 'content'.(($count++%2==0)?1:2);

      echo '<tr class="'.$content.'">';
      echo '<td>';
      echo '<a href="applications.php?action=manage&id='.$app->getAttribute('id').'">';
      echo $app->getAttribute('name').'</a>';
      echo '</td>';
      echo '<td>'._('install in progress').'</td>';
      echo '</tr>';
    }

    if (count($applications_available) > 0 && $can_do_action) {
      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tr class="'.$content.'"><form action="actions.php" method="post">';
      echo '<input type="hidden" name="action" value="add" />';
      echo '<input type="hidden" name="name" value="Application_Server" />';
      echo '<input type="hidden" name="server" value="'.$server->fqdn.'" />';
      echo '<td>';
      echo '<select name="application">';
      foreach ($applications_available as $app)
        echo '<option value="'.$app->getAttribute('id').'">'.$app->getAttribute('name').'</option>';
      echo '</select>';
      echo '</td>';
      echo '<td><input type="submit" value="'._('Install on this server').'" /></td>';
      echo '</form></tr>';
    }

    echo '</table>';
    echo "</div>\n";
  }


  // Server Replication part
  if (count($servers_replication)>0 && $server->getAttribute('type') == 'linux' && $can_do_action) {
    echo '<div class="section">';
    echo '<h2>'._('Replication').'</h2>';
    echo '<form action="" method="post">';
    echo '<input type="hidden" name="action" value="replication" />';
    echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'" />';

    echo '<table border="0" cellspacing="1" cellpadding="3">';
    foreach($servers_replication as $server_) {
      echo '<tr>';
      echo '<td><input class="input_checkbox" type="checkbox" name="servers[]" value="'.$server_->fqdn.'" /></td>';
      echo '<td><a href="servers.php?action=manage&fqdn='.$server_->fqdn.'">'.$server_->fqdn.'</a></td></tr>';
    }
    echo '<tr><td></td><td><input type="submit" value="'._('Replicate on those servers').'" /></td></tr>';
    echo '</table>';
    echo "</div>\n";
  }


  // Tasks part
  if (count($tasks) >0) {
    echo '<div class="section">';
    echo '<h2>'._('Active tasks on this server').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';
    echo '<tr class="title">';
    echo '<th>'._('ID').'</th>';
    echo '<th>'._('Type').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '<th>'._('Details').'</th>';
    echo '</tr>';

    $count = 0;
    foreach($tasks as $task) {
      $content = 'content'.(($count++%2==0)?1:2);
      if ($task->failed())
	$status = '<span class="msg_error">'._('Error').'</span>';
      else
	$status = '<span class="msg_ok">'.$task->status.'</span>';

      echo '<tr class="'.$content.'">';
      echo '<td><a href="tasks.php?action=manage&id='.$task->id.'">'.$task->id.'</a></td>';
      echo '<td>'.get_class($task).'</td>';
      echo '<td>'.$status.'</td>';
      echo '<td>'.$task->getRequest().', '.$task->status_code.'</td>';
      echo '</tr>';
    }
    echo '</table>';
    echo "</div>\n";
  }


  // Sessions part
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
      echo '<td><a href="users.php?action=manage&id='.$session->getAttribute('user_login').'">'.$session->getAttribute('user_displayname').'</td>';
      echo '<td>';
      echo '<input type="hidden" name="info" value="'.$session->id.'" />';
      echo '</td><td><input type="submit" value="'._('Informations about this session').'" /></td>';
      echo '</td>';
      echo '</tr></form>';
    }
    echo '</table>';
    echo '</div>';
  }

  echo '</div>';
  echo '</div>';
  echo '</div>';
  echo '</div>';
  page_footer();
  die();
}
