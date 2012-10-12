<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

if (! checkAuthorization('viewApplications'))
	redirect('index.php');

$applicationDB = ApplicationDB::getInstance();

if (isset($_REQUEST['action'])) {
  if ($_REQUEST['action']=='manage') {
    if (isset($_REQUEST['id']))
      show_manage($_REQUEST['id'], $applicationDB);
  } elseif ($_REQUEST['action']=='icon') {
    if (isset($_REQUEST['id']))
      show_icon($_REQUEST['id'], $applicationDB);
  }
}

if (! isset($_GET['view']))
  $_GET['view'] = 'all';

if ($_GET['view'] == 'all')
  show_default($applicationDB);

function show_default($applicationDB) {
  $applications = $applicationDB->getList(true);
  $is_empty = (is_null($applications) or count($applications)==0);

  $is_rw = $applicationDB->isWriteable();

  page_header();

  echo '<div>'; // general div
  echo '<h1>'._('Applications').'</h1>';
  echo '<div id="apps_list_div">';

  if ($is_empty)
    echo _('No available application').'<br />';
  else {
    echo '<div id="apps_list">';
    echo '<table class="main_sub sortable" id="applications_list_table" border="0" cellspacing="1" cellpadding="5">';
    echo '<thead>';
    echo '<tr class="title">';
//     if ($is_rw)
//       echo '<th class="unsortable"></th>';
    echo '<th>'._('Name').'</th>';
    echo '<th>'._('Description').'</th>';
    echo '<th>'._('Type').'</th>';
    //echo '<th>'._('Status').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    $count = 0;
    foreach($applications as $app) {
      $content = 'content'.(($count++%2==0)?1:2);

      if ($app->getAttribute('published')) {
// 	$status = '<span class="msg_ok">'._('Available').'</span>';
// 	$status_change = _('Block');
	$status_change_value = 0;
      } else {
// 	$status = '<span class="msg_error">'._('Blocked').'</span>';
// 	$status_change = _('Unblock');
	$status_change_value = 1;
      }

      echo '<tr class="'.$content.'">';
      if ($is_rw)
// 	echo '<td><input class="input_checkbox" type="checkbox" name="manage_applications[]" value="'.$app->getAttribute('id').'" /></td>';
      echo '<td><img src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" /> <a href="?action=manage&id='.$app->getAttribute('id').'">'.$app->getAttribute('name').'</a></td>';
      echo '<td>'.$app->getAttribute('description').'</td>';
      echo '<td style="text-align: center;"><img src="media/image/server-'.$app->getAttribute('type').'.png" alt="'.$app->getAttribute('type').'" title="'.$app->getAttribute('type').'" /><br />'.$app->getAttribute('type').'</td>';
//       echo '<td>'.$status.'</td>';

      echo '<td><form action="">';
      echo '<input type="hidden" name="action" value="manage" />';
      echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
      echo '<input type="submit" value="'._('Manage').'"/>';
      echo '</form></td>';

      /*if ($is_rw) {
	echo '<td><form action="" method="post">';
	echo '<input type="hidden" name="action" value="publish" />';
	echo '<input type="hidden" name="name" value="Application" />';
	echo '<input type="hidden" name="checked_applications[]" value="'.$app->getAttribute('id').'" />';
	echo '<input type="hidden" name="published" value="'.$status_change_value.'" />';
	echo '<input type="submit" value="'.$status_change.'"/>';
	echo '</form></td>';
      }*/
      echo '</tr>';
    }

    echo '</tbody>';
    if ($is_rw) {
//       echo '<tfoot>';
      $content = 'content'.(($count++%2==0)?1:2);

//       echo '<tr class="'.$content.'">';
//       echo '<td colspan="6">';
//       echo '<a href="javascript:;" onclick="markAllRows(\'applications_list_table\'); return false">'._('Mark all').'</a>';
//       echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'applications_list_table\'); return false">'._('Unmark all').'</a>';
//       echo '</td>';
//       echo '<td>';

//     echo '<form action="applications.php" method="post" onsubmit="return updateMassActionsForm(this, \'applications_list_table\');">';
//     echo '	<input type="hidden" name="mass_action" value="block" />';
      /*echo '<input type="submit" name="unblock" value="'._('Unblock').'" />';
      echo '<br />';
      echo '<input type="hidden" name="name" value="Application" />';
      echo '<input type="hidden" name="action" value="publish" />';
      echo '<input type="submit" name="block" value="'._('Block').'" />';*/
//	  echo '</form>';
//       echo '</td>';
//       echo '</tr>';
//       echo '</tfoot>';
    }

    echo '</table>';
    echo '</div>'; // apps_list
    
    echo '<div id="remove_orphan_application">';
    echo '<h2>'._('Orphan Applications').'</h2>';
    echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to remove orphan applications?').'\');">';
    echo '<input type="hidden" name="action" value="remove_orphan" />';
    echo '<input type="hidden" name="name" value="Application" />';
    echo '<input type="submit" value="'._('Remove orphan applications').'"/>';
    echo '</form>';
    
    echo '</div>'; // remove_orphan_application
  }
  echo '</div>'; // apps_list_div
  echo '</div>'; // general div
  page_footer();
  die();
}

function show_manage($id, $applicationDB) {
  $applicationsGroupDB = ApplicationsGroupDB::getInstance();
  $app = $applicationDB->import($id);
  if (!is_object($app))
    return false;
//     die_error('Unable to import application "'.$id.'"',__FILE__,__LINE__);
  if ( $app->getAttribute('static')) {
    redirect('applications_static.php?action=manage&id='.$app->getAttribute('id'));
  }
  $is_rw = $applicationDB->isWriteable();

  if ($app->getAttribute('published')) {
    $status = '<span class="msg_ok">'._('Available').'</span>';
    $status_change = _('Block');
    $status_change_value = 0;
  } else {
    $status = '<span class="msg_error">'._('Blocked').'</span>';
    $status_change = _('Unblock');
    $status_change_value = 1;
  }

    // Tasks
  $tm = new Tasks_Manager();
  $tm->load_from_application($id);
  $tm->refresh_all();

  $servers_in_install = array();
  $servers_in_remove = array();
  $tasks = array();
  foreach($tm->tasks as $task) {
	  if ($task->succeed())
		  continue;
	  if ($task->failed())
		  continue;

	  $tasks[]= $task;
	  if (get_class($task) == 'Task_install') {
		  if (! in_array($task->server, $servers_in_install))
			  $servers_in_install[]= $task->server;
	  }
	  if (get_class($task) == 'Task_remove') {
		  if (! in_array($task->server, $servers_in_remove))
			  $servers_in_remove[]= $task->server;
	  }
  }

  // Servers
  if ( $app->getAttribute('static'))
    $servers_all = array();
  else
    $servers_all = Abstract_Server::load_all();
  $liaisons = Abstract_Liaison::load('ApplicationServer', $app->getAttribute('id'), NULL);
  $servers_id = array();
  foreach ($liaisons as $liaison)
    $servers_id []= $liaison->group;
  $servers = array();
  $servers_available = array();
  foreach($servers_all as $server) {
    if (in_array($server->fqdn, $servers_id))
      $servers[]= $server;
    elseif(in_array($server->fqdn, $servers_in_install))
      continue;
    elseif (! $server->isOnline())
      continue;
    elseif ( $server->type != $app->getAttribute('type'))
      continue;
    elseif (is_array($server->roles) && array_key_exists(Server::SERVER_ROLE_APS, $server->roles))
      $servers_available[]= $server;
  }

  // App groups
  $appgroups = $applicationsGroupDB->getList(true);
  $groups_id = array();
  $liaisons = Abstract_Liaison::load('AppsGroup', $app->getAttribute('id'), NULL);
  foreach ($liaisons as $liaison)
    $groups_id []= $liaison->group;
  $groups = array();
  $groups_available = array();
  foreach ($appgroups as $group) {
    if (in_array($group->id, $groups_id))
      $groups[]= $group;
    else
      $groups_available[]= $group;
  }

  $can_manage_server = isAuthorized('manageServers');
  $can_manage_applicationsgroups = isAuthorized('manageApplicationsGroups');

  page_header();

  echo '<div>';
  echo '<h1><img src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" /> '.$app->getAttribute('name').'</h1>';

  echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
  echo '<tr class="title">';
  echo '<th>'._('Package').'</th>';
  echo '<th>'._('Type').'</th>';
//   echo '<th>'._('Status').'</th>';
  echo '<th>'._('Description').'</th>';
  echo '<th>'._('Executable').'</th>';
  echo '</tr>';

  echo '<tr class="content1">';
  echo '<td>'.$app->getAttribute('package').'</td>';
  echo '<td style="text-align: center;"><img src="media/image/server-'.$app->getAttribute('type').'.png" alt="'.$app->getAttribute('type').'" title="'.$app->getAttribute('type').'" /><br />'.$app->getAttribute('type').'</td>';
//   echo '<td>'.$status.'</td>';
  echo '<td>'.$app->getAttribute('description').'</td>';
  echo '<td>'.$app->getAttribute('executable_path').'</td>';
  echo '</tr>';
  echo '</table>';

  echo '<br />';
  echo '<table border="0" cellspacing="0" cellpadding="0">';
  echo '<tr><td>';
  echo '<form action="applications.php" method="get"">';
  echo '<input type="hidden" name="action" value="icon" />';
  echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
  echo '<input type="submit" value="'._('Set custom icon').'"/>';
  echo '</form>';
  echo '</td><td style="width: 10px;"></td><td>';
  echo '<form action="actions.php" method="post"">';
  echo '<input type="hidden" name="name" value="Application" />';
  echo '<input type="hidden" name="action" value="clone" />';
  echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
  echo '<input type="submit" value="'._('Clone to static application').'"/>';
  echo '</form>';
  echo '</td></tr>';
  echo '</table>';
  echo '<br />';
  
  // orphan part
  if ($app->isOrphan() && !($app->getAttribute('static'))) {
    echo '<br />';
    echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to remove this application?').'\');">';
    echo '<input type="hidden" name="action" value="del" />';
    echo '<input type="hidden" name="name" value="Application" />';
    echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
    echo _('This application is orphan').' <input type="submit" value="'._('Remove this application').'"/>';
    echo '</form>';
    echo '<br />';
  }

//   if ($is_rw) {
//     echo '<h2>'._('Settings').'</h2>';
//
//     echo '<form action="" method="post">';
//     echo '<input type="hidden" name="action" value="modify" />';
//     echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
//     echo '<input type="hidden" name="published" value="'.$status_change_value.'" />';
//     echo '<input type="submit" value="'.$status_change.'"/>';
//     echo '</form>';
//   }

  // Server part
  if (count($servers) + count($servers_in_install) + count($servers_available) > 0) {
    echo '<div>';
    echo '<h2>'._('Servers with this application').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';
    foreach($servers as $server) {
      $remove_in_progress = in_array($server->fqdn, $servers_in_remove);

      echo '<tr><td>';
      echo '<a href="servers.php?action=manage&fqdn='.$server->fqdn.'">'.$server->fqdn.'</a>';
      echo '</td>';
      echo '<td>';
      if ($remove_in_progress) {
	echo 'remove in progress';
      }
      elseif ($server->isOnline() and $can_manage_server and $app->getAttribute('type') == 'linux' and $app->hasAttribute('package') and $app->getAttribute('package') !== '') {
	echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to remove this application from this server?').'\');">';
	echo '<input type="hidden" name="action" value="del" />';
	echo '<input type="hidden" name="name" value="Application_Server" />';
	echo '<input type="hidden" name="application" value="'.$id.'" />';
	echo '<input type="hidden" name="server" value="'.$server->fqdn.'" />';
	echo '<input type="submit" value="'._('Remove from this server').'"/>';
	echo '</form>';
      }
      echo '</td>';
      echo '</tr>';
    }

    foreach($servers_in_install as $server) {
      echo '<tr><td>';
      echo '<a href="servers.php?action=manage&fqdn='.$server.'">'.$server.'</a>';
      echo '</td>';
      echo '<td>install in progress</td>';
      echo '</tr>';
    }

    if (count($servers_available) > 0 and $can_manage_server and $app->getAttribute('type') == 'linux' and $app->hasAttribute('package') and $app->getAttribute('package') !== '') {
      $display_list = false;
      foreach ($servers_available as $server) {
        if ($server->hasAttribute('ulteo_system') && $server->getAttribute('ulteo_system') == 1) {
          $display_list = true;
          break;
        }
      }
      if ($display_list) {
        echo '<tr>';
        echo '<form action="actions.php" method="post"><td>';
        echo '<input type="hidden" name="name" value="Application_Server" />';
        echo '<input type="hidden" name="action" value="add" />';
        echo '<input type="hidden" name="application" value="'.$id.'" />';
        echo '<select name="server">';
        foreach ($servers_available as $server) {
          if ($server->hasAttribute('ulteo_system') && $server->getAttribute('ulteo_system') == 1) {
            echo '<option value="'.$server->fqdn.'">'.$server->fqdn.'</option>';
          }
        }
        echo '</select>';
        echo '</td><td><input type="submit" value="'._('Install on this server').'" /></td>';
        echo '</form>';
        echo '</tr>';
      }
    }
    echo '</table>';
    echo "<div>\n";
  }

  if (count($tasks) >0) {
    echo '<h2>'._('Active tasks on this application').'</h1>';
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
      echo '<td>'.$task->server.', '.$task->getRequest().', '.$task->status_code.'</td>';
      echo '</tr>';
    }
    echo '</table>';
    echo "<div>\n";
  }


  if (count($appgroups) > 0) {
    echo '<div>';
    echo '<h2>'._('Groups with this application').'</h2>';
    echo '<table border="0" cellspacing="1" cellpadding="3">';
    foreach ($groups as $group) {
      echo '<tr>';
      echo '<td>';
      echo '<a href="appsgroup.php?action=manage&id='.$group->id.'">'.$group->name.'</a>';
      echo '</td>';
		if ($can_manage_applicationsgroups) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this application from this group?').'\');">';
			echo '<input type="hidden" name="name" value="Application_ApplicationGroup" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="element" value="'.$id.'" />';
			echo '<input type="hidden" name="group" value="'.$group->id.'" />';
			echo '<input type="submit" value="'._('Delete from this group').'" />';
			echo '</form></td>';
		}
      echo '</tr>';
    }

    if (count($groups_available) > 0 and $can_manage_applicationsgroups) {
      echo '<tr>';
      echo '<form action="actions.php" method="post"><td>';
      echo '<input type="hidden" name="name" value="Application_ApplicationGroup" />';
      echo '<input type="hidden" name="action" value="add" />';
      echo '<input type="hidden" name="element" value="'.$id.'" />';
      echo '<select name="group">';
      foreach ($groups_available as $group)
	echo '<option value="'.$group->id.'">'.$group->name.'</option>';
      echo '</select>';
      echo '</td><td><input type="submit" value="'._('Add to this group').'" /></td>';
      echo '</form>';
      echo '</tr>';
    }

    echo '</table>';
    echo "<div>\n";
  }
  
	// Mime-Type part
	echo '<div>';
	echo '<h2>'._('Mime-Types').'</h2>';
	echo '<div>';
	if (count($app->getMimeTypes()) == 0) {
		echo _('No available mimes-type').'<br />';
	}
	else {
		echo '<table border="0" cellspacing="1" cellpadding="3">';
		foreach($app->getMimeTypes() as $mime) {
			echo '<tr><td>';
			echo '<a href="mimetypes.php?action=manage&id='.urlencode($mime).'">'.$mime.'</a>';
			echo '</td></tr>';
		}
		echo '</table>';
	}
	echo '</div>';
	echo '</div>'; // mime div

  echo '</div>';
  echo '</div>';
  echo '</div>';
  echo '</div>';
  echo '</div>';
  echo '</div>';
  page_footer();
  die();
}

function show_icon($id, $applicationDB) {
	$applicationsGroupDB = ApplicationsGroupDB::getInstance();
	$app = $applicationDB->import($id);
	if (!is_object($app))
		return false;
	//     die_error('Unable to import application "'.$id.'"',__FILE__,__LINE__);
	if ( $app->getAttribute('static')) {
		redirect('applications_static.php?action=manage&id='.$app->getAttribute('id'));
	}
	$is_rw = $applicationDB->isWriteable();

	$liaisons = Abstract_Liaison::load('ApplicationServer', $app->getAttribute('id'), NULL);

	$servers = array();
	foreach ($liaisons as $liaison) {
		$server = Abstract_Server::load($liaison->group);

		if (! $server->isOnline())
			continue;

		$servers[] = $server;
	}

	page_header();

	echo '<div>';
	echo '<h1><img src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" /> '.$app->getAttribute('name').'</h1>';

	echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
	echo '<tr class="title">';
	echo '<th>'._('Package').'</th>';
	echo '<th>'._('Type').'</th>';
	//   echo '<th>'._('Status').'</th>';
	echo '<th>'._('Description').'</th>';
	echo '<th>'._('Executable').'</th>';
	echo '</tr>';

	echo '<tr class="content1">';
	echo '<td>'.$app->getAttribute('package').'</td>';
	echo '<td style="text-align: center;"><img src="media/image/server-'.$app->getAttribute('type').'.png" alt="'.$app->getAttribute('type').'" title="'.$app->getAttribute('type').'" /><br />'.$app->getAttribute('type').'</td>';
	//   echo '<td>'.$status.'</td>';
	echo '<td>'.$app->getAttribute('description').'</td>';
	echo '<td>'.$app->getAttribute('executable_path').'</td>';
	echo '</tr>';
	echo '</table>';
	echo '<br />';

	echo '<h2>'._('Select an icon from an Application Server').'</h2>';
	echo '<table border="0" cellspacing="1" cellpadding="5">';
	foreach ($servers as $server) {
		$ret = query_url($server->getBaseURL().'/aps/application/icon/'.$app->getAttribute('id'));
		if (! $ret)
			continue;

		$imgfile = tempnam(NULL, 'ico');
		@file_put_contents($imgfile, $ret);

		try {
			if (class_exists('Imagick')) {
				$imagick = new Imagick();
				$imagick->readImage($imgfile);
			} else {
				if (file_exists($imgfile))
					@unlink($imgfile);
				continue;
			}
		} catch (Exception $e) {
			if (file_exists($imgfile))
				@unlink($imgfile);
			continue;
		}

		if (! file_exists($imgfile))
			continue;

		echo '<tr>';
		echo '<td style="width: 32px;"><img src="media/image/temp_icon.php?tempnam='.basename($imgfile).'" /></td><td><a href="servers.php?action=manage&amp;fqdn='.$server->getAttribute('fqdn').'">'.$server->getAttribute('fqdn').'</a></td><td><form action="actions.php" method="post"><input type="hidden" name="name" value="Application" /><input type="hidden" name="action" value="icon" /><input type="hidden" name="id" value="'.$app->getAttribute('id').'" /><input type="hidden" name="server" value="'.$server->getAttribute('fqdn').'" /><input type="submit" value="'._('Select this icon').'" /></form></td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '<br />';

	echo '<h2>'._('Upload an icon').'</h2>';
	echo '<table border="0" cellspacing="1" cellpadding="5">';
	echo '<tr>';
	echo '<td>';
	echo '<form action="actions.php" method="post" enctype="multipart/form-data" >'; // form A
	echo '<input type="hidden" name="name" value="Application" />';
	echo '<input type="hidden" name="action" value="icon" />';
	echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
	echo '<input type="file" name="file_icon" /> ';
	echo '<input type="submit" value="'._('Upload this icon').'" />';
	echo '</form>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';

	echo '</div>';
	page_footer();
	die();
}
