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

if (isset($_GET['mass_action']) && $_GET['mass_action'] == 'register') {
	if (isset($_GET['register_servers']) && is_array($_GET['register_servers'])) {
		foreach ($_GET['register_servers'] as $server) {
			$server = new Server_admin($server);
			$server->register(0);
			$server->updateApplications();
		}
	}

	redirect('servers.php?action=list');
}

if (isset($_GET['mass_action']) && $_GET['mass_action'] == 'maintenance') {
	if (isset($_GET['manage_servers']) && is_array($_GET['manage_servers'])) {
		foreach ($_GET['manage_servers'] as $server) {
			$server = new Server_admin($server);
			if ($server->isOnline()) {
				if (isset($_GET['to_maintenance']))
					$server->setAttribute('locked', '1');
				else
					$server->setAttribute('locked', NULL);
			}
		}
	}

	redirect('servers.php?action=list');
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'install_line' && isset($_REQUEST['fqdn']) && isset($_REQUEST['line'])) {
	$t = new Task_install_from_line(0, $_REQUEST['fqdn'], $_REQUEST['line']);

	$tm = new Tasks_Manager();
	$tm->add($t);

	header('Location: servers.php?action=manage&fqdn='.$_REQUEST['fqdn']);
	die();
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'replication' && isset($_REQUEST['fqdn']) && isset($_REQUEST['servers'])) {
	$server_from = new Server($_REQUEST['fqdn']);
	$applications_from = $server_from->getApplications();

	$servers_fqdn = $_REQUEST['servers'];
	foreach($servers_fqdn as $server_fqdn) {
		$server_to = new Server($server_fqdn);
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
		$tm = new Tasks_Manager();
		if (count($to_delete) > 0) {
			$t = new Task_remove(0, $server_fqdn, $to_delete);
			$tm->add($t);
		}
		if (count($to_install) > 0) {
			$t = new Task_install(0, $server_fqdn, $to_install);
			$tm->add($t);
		}
	}
	redirect($_SERVER['HTTP_REFERER']);
}



if (isset($_GET['action']) && $_GET['action'] == 'register' && isset($_GET['fqdn'])) {
	$server = new Server_admin($_GET['fqdn']);
	$server->register(0);
	$server->updateApplications();

	redirect('servers.php?action=list');
}

if (isset($_GET['action']) && $_GET['action'] == 'maintenance' && isset($_GET['fqdn'])) {
	$server = new Server_admin($_GET['fqdn']);
	if ($server->isOnline()) {
		if (isset($_GET['maintenance']) && $_GET['maintenance'] == 1)
			$server->setAttribute('locked', '1');
		else
			$server->setAttribute('locked', NULL);
	}

	redirect('servers.php?action=list');
}

if (isset($_GET['action']) && $_GET['action'] == 'change' && isset($_GET['fqdn'])) {
	$server = new Server_admin($_GET['fqdn']);
	$server->setAttribute('nb_sessions', $_GET['nb_sessions']);
	if (isset($_GET['maintenance']) && $_GET['maintenance'] == 1)
		$server->setAttribute('locked', '1');
	else
		$server->setAttribute('locked', NULL);

	redirect('servers.php?action=manage&fqdn='.$_GET['fqdn']);
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['fqdn'])) {
	$server = new Server_admin($_GET['fqdn']);
	$server->delete();

	redirect('servers.php?action=list');
}


if (! isset($_GET['action']) || $_GET['action'] == 'list') {
	include_once('header.php');

	echo '<div id="servers_div">';
	echo '<h1>'._('Servers').'</h1>';

	echo '<div id="servers_list_div">';

	$servers = new Servers();
	$u_servs = $servers->getUnregistered();
	if (is_array($u_servs)) {
		if (count($u_servs)>0){
			echo '<h2>'._('Unregistered servers').'</h2>';

			?>
			<form action="servers.php" method="get">
				<input type="hidden" name="mass_action" value="register" />
			<table id="unregistered_servers_table" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">
			<thead>
			<tr class="title">
				<th class="unsortable"></th><th><?php echo _('FQDN');?></th><th><?php echo _('Type');?></th><!--<th><?php echo _('Version');?></th>--><th><?php echo _('Details');?></th>
			</tr>
			</thead>
			<?php
			$count = 0;
			foreach($u_servs as $s){
				$s->getMonitoring();
				$applications = $s->getApplications();
				$apps_name = '';
				if (is_array($applications)){
					foreach ($applications as $a){
						$apps_name .= $a->getAttribute('name').'<br />';
					}
				}
				echo '<tr class="content';
				if ($count % 2 == 0)
					echo '1';
				else
					echo '2';
				echo '">';
				echo '<td><input type="checkbox" name="register_servers[]" value="'.$s->fqdn.'" /><form></form>';
				echo '<td>'.$s->fqdn.'</td>';
				echo '<td style="text-align: center;"><img src="media/image/server-'.$s->stringType().'.png" alt="'.$s->stringType().'" title="'.$s->stringType().'" /><br />'.$s->stringType().'</td>';
				//echo '<td>'.$s->stringVersion().'</td>';
				echo '<td>';
				echo _('CPU').': '.$s->getAttribute('cpu_model').' ('.$s->getAttribute('cpu_nb').' ';
				echo ($s->getAttribute('cpu_nb') > 1)?_('cores'):_('core');
				echo ')<br />';
				echo _('RAM').': '.round($s->getAttribute('ram')/1024).' MB';
				echo '</td>';
				?>
				<td>
					<form  method="get">
						<input type="submit" value="<?php echo _('Register');?>" />
						<input type="hidden" name="action" value="register" />
						<input type="hidden" name="fqdn" value="<?php echo $s->fqdn;?>" />
					</form>
				</td>
			</tr>
				<?php
				$count++;
			}
			echo '<tfoot>';
			echo '<tr class="content';
			echo ($count % 2 == 0)?'1':'2';
			echo '">';
			?>
				<td colspan="4">
					<a href="javascript:;" onclick="markAllRows('unregistered_servers_table'); return false"><?php echo _('Mark all');?></a> / <a href="javascript:;" onclick="unMarkAllRows('unregistered_servers_table'); return false"><?php echo _('Unmark all');?></a>
				</td>
				<td>
						<input type="submit" name="register" value="<?php
								echo _('Register');
						?>"/><br />
					</form>
				</td>
			</tr>
			</tfoot>
			<?php
			echo '</table>';
			echo '</form>';
		}
	}
	echo '</div>';

	echo '<br />';

	echo '<div id="servers_list_div">';

	$a_servs = $servers->getAll();
	if (is_array($a_servs)) {
		if (count($a_servs)>0){
			?>
			<form action="servers.php" method="get">
				<input type="hidden" name="mass_action" value="maintenance" />
			<table id="available_servers_table" class="main_sub sortable" id="available_servers" border="0" cellspacing="1" cellpadding="3">
			<thead>
			<tr class="title">
				<th class="unsortable"></th><th><?php echo _('FQDN');?></th><th><?php echo _('Type');?></th><!--<th><?php echo _('Version');?></th>--><th><?php echo _('Status');?></th><th><?php echo _('Sessions');?></th><th><?php echo _('Details');?></th><th><?php echo _('Monitoring');?></th><!--<th><?php echo _('Applications(physical)');?></th>-->
			</tr>
			</thead>
			<?php
			$count = 0;
			foreach($a_servs as $s){
				$applications = $s->getApplications();
				$apps_name = '';
				if (is_array($applications)){
					foreach ($applications as $a){
						$apps_name .= $a->getAttribute('name').'<br />';
					}
				}
				echo '<tr class="content';
				echo ($count % 2 == 0)?'1':'2';
				echo '">';
				echo '<td><input type="checkbox" name="manage_servers[]" value="'.$s->fqdn.'" /></td><form></form>';
				echo '<td>';
				echo '<a href="servers.php?action=manage&fqdn='.$s->fqdn.'">'.$s->fqdn.'</a>';
				echo '</td>';
				echo '<td style="text-align: center;"><img src="media/image/server-'.$s->stringType().'.png" alt="'.$s->stringType().'" title="'.$s->stringType().'" /><br />'.$s->stringType().'</td>';
				//echo '<td>'.$s->stringVersion().'</td>';
				echo '<td>'.$s->stringStatus().'</td>';
				echo '<td>';
				if ($s->isOnline())
					echo $s->stringSessions();
				echo '</td>';
				echo '<td>';
				echo _('CPU').': '.$s->getAttribute('cpu_model').' ('.$s->getAttribute('cpu_nb').' ';
				echo ($s->getAttribute('cpu_nb') > 1)?_('cores'):_('core');
				echo ')<br />';
				echo _('RAM').': '.round($s->getAttribute('ram')/1024).' '._('MB');
				echo '</td>';
				echo '<td>';
				if ($s->isOnline()) {
				echo _('CPU usage').': '.$s->getCpuUsage().'<br />';
				echo _('RAM usage').': '.$s->getRamUsage();
				}
				echo '</td>';
				//echo '<td>'.$apps_name.'</td>';
				?>
				<td>
					<form action="servers.php" method="get">
						<input type="submit" value="<?php echo _('Manage');?>"/>
						<input type="hidden" name="action" value="manage" />
						<input type="hidden" name="fqdn" value="<?php echo $s->fqdn;?>" />
					</form>
				</td>
				<td>
					<?php
						if ($s->isOnline()) {
					?>
					<form action="servers.php" method="get">
						<input type="submit" value="<?php
							if ($s->hasAttribute('locked'))
								echo _('Switch to production');
							else
								echo _('Switch to maintenance');
						?>"/>
						<input type="hidden" name="action" value="maintenance" />
						<input type="hidden" name="maintenance" value="<?php
							if ($s->hasAttribute('locked'))
								echo '0';
							else
								echo '1';
						?>" />
						<input type="hidden" name="fqdn" value="<?php echo $s->fqdn;?>" />
					</form>
					<?php
						}
					?>
				</td>
			</tr>
				<?php
				$count++;
			}
			echo '<tfoot>';
			echo '<tr class="content';
			echo ($count % 2 == 0)?'1':'2';
			echo '">';
			?>
				<td colspan="8">
					<a href="javascript:;" onclick="markAllRows('available_servers_table'); return false"><?php echo _('Mark all');?></a> / <a href="javascript:;" onclick="unMarkAllRows('available_servers_table'); return false"><?php echo _('Unmark all');?></a>
				</td>
				<td>
						<input type="submit" name="to_production" value="<?php
								echo _('Switch to production');
						?>"/><br />
						<input type="submit" name="to_maintenance" value="<?php
								echo _('Switch to maintenance');
						?>"/>
					</form>
				</td>
			</tr>
			</tfoot>
			<?php
			echo '</table>';
			echo '</form>';
		}
	} else
		echo 'No available server';
	echo '</div>';
}
elseif (isset($_GET['action']) && $_GET['action'] == 'manage' && isset($_GET['fqdn'])) {
	$server = new Server_admin($_GET['fqdn']);

	$buf_online = $server->isOnline();

	if ($buf_online == 'ready') {
		$buf_status = $server->getStatus();
		$buf_monitoring = $server->getMonitoring();
	}

	include_once('header.php');
?>
<div id="servers_div">
<h1><?php echo $server->fqdn;?></h1>
<?php
	if ($buf_online === false) {
		if ($server->status == 'down')
			echo '<h2><p class="msg_error centered">'._('Warning: Server is offline').'</p></h2>';
		elseif ($server->status == 'broken')
			echo '<h2><p class="msg_error centered">'._('Warning: Server is broken').'</p></h2>';
	}
?>
<h2><?php echo _('Monitoring');?></h2>

<table class="main_sub" border="0" cellspacing="1" cellpadding="3">
	<tr class="title">
		<th><?php echo _('Type');?></th><th><?php echo _('Version');?></th><th><?php echo _('Status');?></th><?php if ($server->isOnline()) { ?><th><?php echo _('Sessions');?></th><?php } ?><th><?php echo _('Details');?></th><?php if ($server->isOnline()) { ?><th><?php echo _('Monitoring');?></th><?php } ?>
	</tr>
	<tr class="content1">
		<td style="text-align: center;"><img src="media/image/server-<?php echo $server->stringType(); ?>.png" alt="<?php echo $server->stringType(); ?>" title="<?php echo $server->stringType(); ?>" /><br /><?php echo $server->stringType(); ?></td>
		<td><?php echo $server->stringVersion(); ?></td>
		<td><?php echo $server->stringStatus(); ?></td>
		<?php
		if ($buf_online) {
			echo '<td>';
			echo $server->stringSessions();
			echo '</td>';
		}
		?>
		<td>
		<?php echo _('CPU'); ?>: <?php echo $server->getAttribute('cpu_model'); ?> (x<?php echo $server->getAttribute('cpu_nb'); ?>)<br />
		<?php echo _('RAM'); ?>: <?php echo round($server->getAttribute('ram')/1024).' '._('MB'); ?>
		</td>
		<?php
			if ($buf_online) {
				echo '<td>';
				echo _('CPU usage').': '.$server->getCpuUsage().'<br />';
				echo _('RAM usage').': '.$server->getRamUsage();
				echo '</td>';
			}
		?>
	</tr>
</table>

<fieldset>
	<h2><?php echo _('Configuration');?></h2>

	<form action="servers.php" method="get">
		<input type="hidden" name="action" value="change" />
		<input type="hidden" name="fqdn" value="<?php echo $_GET['fqdn']; ?>" />

		<?php echo _('Sessions');?>
		<?php $server = new Server_admin($_GET['fqdn']); ?>
		<input value="-" onclick="field_increase('number', -1);" type="button">
		<input id="number" name="nb_sessions" value="<?php echo $server->getNbAvailableSessions(); ?>" size="3" onchange="field_check_integer(this);" type="text">
		<input value="+" onclick="field_increase('number', 1);" type="button">
		<br />
		<?php
			if ($server->isOnline()) {
			$buf_lock = $server->hasAttribute('locked');
		?>
		<input type="checkbox" name="maintenance" value="1"<?php
			if ($buf_lock)
				echo ' checked="checked"';
		?>/> <?php
			echo _('maintenance');
			}
		?>
		<br />
		<input type="submit" value="<?php echo _('change'); ?>" />
	</form>

	<?php
		if (isset($buf_lock) && $buf_lock) {
			?>
			<form action="servers.php" method="get">
				<input type="hidden" name="action" value="delete" />
				<input type="hidden" name="fqdn" value="<?php echo $_GET['fqdn']; ?>" />

				<input type="submit" value="<?php echo _('delete'); ?>" />
			</form>
			<?php
		}
if ($buf_online) {
echo '<h3>'._('Install an application from a package name').'</h3>';
echo '<form>';
echo '<input type="hidden" name="action" value="install_line">';
echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'">';
echo '<input type="text" name="line"> ';
echo '<input type="submit" value="'._('Install').'">';
echo '</form>';
	?>
</fieldset>

<h2><?php echo _('Applications available on this server'); ?></h2>
<?php
$buf = $server->updateApplications();

$tm = new Tasks_Manager();
$tm->load_from_server($server->fqdn);
$tm->refresh_all();

$apps_in_remove = array();
$apps_in_install = array();

$tasks = array();
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

if ($buf === false)
	echo '<span class="msg_error">'._('Cannot list available applications').'</span>';
else {
	$mods_enable = $prefs->get('general','module_enable');
	if (! in_array('ApplicationDB',$mods_enable))
		die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
	$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
	$applicationDB = new $mod_app_name();

	$applications_all = $applicationDB->getList();
	$applications = $server->getApplications();
	$applications_available = array();
	foreach($applications_all as $app) {
		if (in_array($app, $applications))
			continue;
		if (in_array($app, $apps_in_install))
			continue;

		$applications_available[]= $app;
	}

	$servers_all = Servers::getOnline();
	foreach($servers_all as $k => $v) {
		if ($v->fqdn == $server->fqdn)
			unset($servers_all[$k]);
	}

	$count = 0;
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	if (count($applications)==0)
		echo '<tr><td colspan="2">'._('No application on this server').'</td></tr>';
	else {
		foreach ($applications as $app) {
			$content = 'content'.(($count++%2==0)?1:2);
			$remove_in_progress = in_array($app, $apps_in_remove);

			$icon_id = ($app->haveIcon())?$app->getAttribute('id'):0;

			echo '<tr class="'.$content.'">';
			echo '<td>';
			echo '<img src="../cache/image/application/'.$icon_id.'.png" alt="'.$app->getAttribute('name').'" title="'.$app->getAttribute('name').'" /> ';
			echo '<a href="applications.php?action=manage&id='.$app->getAttribute('id').'">';
			echo $app->getAttribute('name').'</a>';
			echo '</td>';
			echo '<td>';
			if ($remove_in_progress) {
				echo 'remove in progress';
			}
			else {
				echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to remove this application from this server ?').'\');">';
				echo '<input type="hidden" name="action" value="del" />';
				echo '<input type="hidden" name="name" value="Application_Server" />';
				echo '<input type="hidden" name="server" value="'.$server->fqdn.'" />';
				echo '<input type="hidden" name="application" value="'.$app->getAttribute('id').'" />';
				echo '<input type="submit" value="'._('Remove from this server').'" />';
				echo '</form>';
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
		echo '<td>install in progress</td>';
		echo '</tr>';
	}

	if (count($applications_available)==0)
		echo '<tr><td colspan="2">'._('No available application').'</td></tr>';
	else {
		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr style="'.$content.'"><form action="actions.php" method="post">';
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

	if (count($servers_all)>0) {
		echo '<h3>'._('Replication').'</h3>';
		echo '<form action="" method="post">';
		echo '<input type="hidden" name="action" value="replication" />';
		echo '<input type="hidden" name="fqdn" value="'.$server->fqdn.'" />';

		echo '<table border="0" cellspacing="1" cellpadding="3">';
		foreach($servers_all as $server_) {
			echo '<tr>';
			echo '<td><input type="checkbox" name="servers[]" value="'.$server_->fqdn.'" /></td>';
			echo '<td><a href="servers.php?action=manage&fqdn='.$server_->fqdn.'">'.$server_->fqdn.'</a></td></tr>';
		}
		echo '<tr><td></td><td><input type="submit" value="'._('Replicate on those servers').'" /></td></tr>';
		echo '</table>';
	}
}




if (count($tasks) >0) {
	echo '<h2>'._('Active tasks on this server').'</h1>';
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
}

  $sessions = new Sessions();
  $sessions = $sessions->getActivesForServer($_GET['fqdn']);

  if (count($sessions) > 0)
    $has_sessions = true;
  else
    $has_sessions = false;

  echo '<h2>'._('Active sessions').'</h1>';
  if (! $has_sessions)
    echo _('No active sessions');
  else {
    echo '<table border="0" cellspacing="1" cellpadding="3">';
    foreach($sessions as $session) {
      echo '<form action="sessions.php"><tr>';
      echo '<td>'.$session->getStartTime().'</td>';
      echo '<td><a href="users.php?action=manage&id='.$session->getSetting('user_login').'">'.$session->getSetting('user_displayname').'</td>';
      echo '<td>';
      echo '<input type="hidden" name="info" value="'.$session->session.'" />';
      echo '</td><td><input type="submit" value="'._('Informations about this session').'" /></td>';
      echo '</td>';
      echo '</tr></form>';
    }
    echo '</table>';
  }
}
?>
<?php
}
else {
	echo 'TODO43432:NOACTION<br />';
}
?>
</div>
<?php
if (!isset($_GET['ajax'])) {
	include_once('footer.php');
}
