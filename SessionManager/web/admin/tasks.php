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

$tm = new Tasks_Manager();
$tm->load_all();
$tm->refresh_all();

if (isset($_POST['submit'])) {
	$task = new Task_install_from_line(0, $_POST['server'], $_POST['request']);
	$tm->add($task);
}

if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action']=='manage') {
		if (isset($_REQUEST['id']))
			show_manage($_REQUEST['id'], $tm);
	}
}

show_default($tm);


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

	include_once('header.php');

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
	echo '</tr>';
	echo '</table>';
	
	foreach($infos as $k => $v) {
		echo '<h3>'.$k.'</h3>';
		echo '<pre>'.$v.'</pre>';
	}
	
	
	echo '</div>';


	include_once('footer.php');
	die();
}

function show_default($tm) {
  $servers = Servers::getOnline();

  include_once('header.php');

  echo '<div id="tasks_div">';
  echo '<h1>'._('Tasks').'</h1>';
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
      echo '<td>'.$task->getRequest().' ('.$task->job_id.')</td>';
      echo '</tr>';
    }
    echo '</table>';

    echo '</div>';
    echo '</div>';

    echo '<h2>'._('Install a package from command line').'</h2>';
    if (count($servers)==0)
	    echo _('No available server');
    else {
    	echo '<form action="" method="post">';
    	echo '<select name="server">';
    	foreach ($servers as $server)
		echo '<option value="'.$server->fqdn.'">'.$server->fqdn.'</option>';
    	echo '</select>';
    	echo '<input type="text" name="request" value="" />';
    	echo '<input type="submit" name="submit" value="'._('Install').'" />';
    	echo '</form>';
    }

    include_once('footer.php');
    die();
}
