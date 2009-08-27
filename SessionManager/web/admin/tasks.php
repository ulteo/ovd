<?php
/**
 * Copyright (C) 2008-2009 Ulteo SAS
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

if (! checkAuthorization('viewServers'))
		redirect();


$tm = new Tasks_Manager();
$tm->load_all();
$tm->refresh_all();

if (isset($_POST['action'])) {
	if (! checkAuthorization('manageServers'))
		redirect();

	if ($_POST['action']=='create') {
		if (isset($_POST['type'])) {
			$type_task = 'Task_'.$_POST['type'];
			try {
				$task = new $type_task(0, $_POST['server'], $_POST['request']);
				$tm->add($task);
			}
			catch (Exception $e) {
				Logger::error('main', 'tasks.php error create task (type=\''.$type_task.'\')');
				popup_error('error create task (type=\''.$type_task.'\')');
			}
		}
	}
	elseif ($_POST['action']=='remove') {
		if (isset($_POST['task']))
			if (do_remove($tm, $_POST['task']))
				redirect('tasks.php');
	}

	redirect();
}

if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action']=='manage') {
		if (isset($_REQUEST['id']))
			show_manage($_REQUEST['id'], $tm);
	}
}

show_default($tm);


function do_remove($tm, $id) {
	$task = false;
	foreach($tm->tasks as $t) {
		if ($t->id == $id) {
			$task = $t;
			break;
		}
	}

	if ($task === false) {
		popup_error('Unable to find task id '.$id);
		return false;
	}

	if (! ($task->succeed() || $task->failed())) {
		popup_error('Task '.$id.' not removable');
		return false;
	}

	$tm->remove($id);
	return true;
}

function show_manage($id, $tm) {
	$task = false;
	foreach($tm->tasks as $t) {
		if ($t->id == $id) {
			$task = $t;
			break;
		}
	}
	
	if ($task === false)
		die_error('Unable to find task '.$id, __FILE__,__LINE__);
	
	$infos = $task->get_AllInfos();
	$can_remove = ($task->succeed() || $task->failed());

	$can_do_action = isAuthorized('manageServers');

	page_header();

	echo '<div id="tasks_div">';
	echo '<h1><a href="?">'._('Tasks managment').'</a> - '.$id.'</h1>';

	echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
	echo '<tr class="title">';
	echo '<th>'._('Creation time').'</th>';
	echo '<th>'._('Type').'</th>';
	echo '<th>'._('Servers').'</th>';
	echo '<th>'._('Status').'</th>';
	echo '<th>'._('Details').'</th>';
	echo '<th>'._('Job id').'</th>';
	if ($can_remove && $can_do_action)
    		echo '<th></th>';
	echo '</tr>';
	
	if ($task->succeed())
		$status = '<span class="msg_ok">'._('Finished').'</span>';
	elseif ($task->failed())
		$status = '<span class="msg_error">'._('Error').'</span>';
	else
		$status = $task->status.' ('.$task->status_code.')';

	echo '<tr class="content1">';
	echo '<td>'.date('Y-m-d H:i:s', $task->t_begin).'</td>';
	echo '<td>'.get_class($task).'</td>';
	echo '<td><a href="servers.php?action=manage&fqdn='.$task->server.'">'.$task->server.'</a></td>';
	echo '<td>'.$status.'</td>';
	echo '<td>'.$task->getRequest().'</td>';
	echo '<td>'.$task->job_id.'</td>';
	if ($can_remove && $can_do_action) {
		echo '<td>';
		echo '<form action="" method="post">';
		echo '<input type="hidden" name="action" value="remove" />';
		echo '<input type="hidden" name="task" value="'.$task->id.'" />';
		echo '<input type="submit" value="'._('Delete').'" />';
		echo '</form>';
		echo '</td>';
	}
	echo '</tr>';
	echo '</table>';
	
	foreach($infos as $k => $v) {
		echo '<h3>'.$k.'</h3>';
		echo '<pre>'.$v.'</pre>';
	}
	
	
	echo '</div>';


	page_footer();
	die();
}

function show_default($tm) {
  $servers = Servers::getOnline();

	$can_do_action = isAuthorized('manageServers');

  page_header();

  echo '<div id="tasks_div">';
  echo '<h1>'._('Tasks').'</h1>';

  if (count($tm->tasks) > 0) {
    echo '<div id="tasks_list_div">';
    echo '<h2>'._('List of tasks').'</h2>';

    echo '<table class="main_sub sortable" id="tasks_list_table" border="0" cellspacing="1" cellpadding="5">';
    echo '<tr class="title">';
    echo '<th>'._('ID').'</th>';
    echo '<th>'._('Creation time').'</th>';
    echo '<th>'._('Type').'</th>';
    echo '<th>'._('Server').'</th>';
    echo '<th>'._('Status').'</th>';
    echo '<th>'._('Details').'</th>';
    echo '</tr>';
    $count = 0;
    foreach($tm->tasks as $task) {
      $content = 'content'.(($count++%2==0)?1:2);
      $can_remove = ($task->succeed() || $task->failed());

      if ($task->succeed())
	      $status = '<span class="msg_ok">'._('Finished').'</span>';
      elseif ($task->failed())
	      $status = '<span class="msg_error">'._('Error').'</span>';
      else
	      $status = $task->status.' ('.$task->status_code.')';

      echo '<tr class="'.$content.'">';
      echo '<td><a href="?action=manage&id='.$task->id.'">'.$task->id.'</a></td>';
      echo '<td>'.date('Y-m-d H:i:s', $task->t_begin).'</td>';
      echo '<td>'.get_class($task).'</td>';
      echo '<td><a href="servers.php?action=manage&fqdn='.$task->server.'">'.$task->server.'</a></td>';
      echo '<td>'.$status.'</td>';
      echo '<td>'.$task->getRequest().'</td>';
      echo '<td>';
      if ($can_remove && $can_do_action) {
    	echo '<form action="" method="post">';
	echo '<input type="hidden" name="action" value="remove" />';
	echo '<input type="hidden" name="task" value="'.$task->id.'" />';
	echo '<input type="submit" value="'._('Delete').'" />';
    	echo '</form>';
      }
      echo '</td>';
      echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
  }
  
    if (count($servers)>0 && $can_do_action) {
    	echo '<h2>'._('Install a package from command line').'</h2>';

    	echo '<form action="" method="post">';
	echo '<input type="hidden" name="action" value="create" />';
    	echo '<select name="server">';
    	foreach ($servers as $server)
		echo '<option value="'.$server->fqdn.'">'.$server->fqdn.'</option>';
    	echo '</select>';
    	echo '<input type="text" name="request" value="" />';
    	echo '<input type="hidden" name="type" value="install_from_line" />';
    	echo '<input type="submit" name="submit" value="'._('Install').'" />';
    	echo '</form>';

        echo '<h2>'._('Upgrade the internal system and applications').'</h2>';

        echo '<form action="" method="post">';
        echo '<input type="hidden" name="action" value="create" />';
        echo '<input type="hidden" name="type" value="upgrade" />';
        echo '<input type="hidden" name="request" value="" />'; // hack for the task creation
        echo '<select name="server">';
        foreach ($servers as $server)
            echo '<option value="'.$server->fqdn.'">'.$server->fqdn.'</option>';
        echo '</select>';
        echo '<input type="submit" name="submit" value="'._('Upgrade').'" />';
        echo '</form>';
    }

    echo '</div>';
    page_footer();
    die();
}
