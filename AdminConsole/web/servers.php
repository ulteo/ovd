<?php
/**
 * Copyright (C) 2008-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2008-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2008, 2009, 2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
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
require_once(dirname(dirname(__FILE__)).'/includes/page_template.php');

if (! checkAuthorization('viewServers'))
	redirect('index.php');

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'manage' && isset($_REQUEST['id'])) {
  show_manage($_REQUEST['id']);
}

if (! isset($_GET['view']))
  $_GET['view'] = 'all';

if ($_GET['view'] == 'all')
  show_default();
elseif ($_GET['view'] == 'unregistered')
  show_unregistered();

function show_default() {
	$a_servs = $_SESSION['service']->servers_list();
	if (is_null($a_servs)) {
		$a_servs = array();
	}
  
  uasort($a_servs, "server_cmp");
  if (! is_array($a_servs))
    $a_servs = array();

  $nb_a_servs_online_maintenance = 0;
  $nb_a_servs_not_maintenance = 0;
  foreach($a_servs as $s) {
	$external_name_checklist = array('localhost', '127.0.0.1');
	if (in_array($s->getExternalName(), $external_name_checklist))
		popup_error(sprintf(_('Server "%s": redirection name may be invalid!'), $s->getDisplayName()));
	
	if ($s->isOnline() and $s->getAttribute('locked'))
		$nb_a_servs_online_maintenance++;
	if (! $s->getAttribute('locked'))
		$nb_a_servs_not_maintenance++;
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
    if ($av_servers > 1 and $can_do_action and ($nb_a_servs_online_maintenance > 0 or $nb_a_servs_not_maintenance > 0))
      echo '<th class="unsortable"></th>';
    echo '<th>'._('Name').'</th><th>'._('Type').'</th>';
    echo '<th>'._('Version').'</th>';
    echo '<th>'._('Roles').'</th>';
    echo '<th>'._('Status').'</th><th>'._('Details').'</th>';
    // echo '<th>'._('Applications(physical)'.</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $count = 0;
    foreach($a_servs as $s) {
      $content = 'content'.(($count++%2==0)?1:2);
      $server_online = $s->isOnline();
      $dn = $s->getDisplayName();

	if ($s->getAttribute('locked')) {
	  $switch_msg = _('Switch to production');
	  $switch_value = 0;
	}
	else {
	  $switch_msg = _('Switch to maintenance');
	  $switch_value = 1;
	}


      echo '<tr class="'.$content.'">';
	if ($av_servers > 1 and $can_do_action and ($nb_a_servs_online_maintenance > 0 or $nb_a_servs_not_maintenance > 0)) {
		echo '<td>';
		if ($server_online || $switch_value == 1)
			echo '<input class="input_checkbox" type="checkbox" name="checked_servers[]" value="'.$s->id.'" />';
		echo '</td>';
	}
      echo '<td>';
	echo '<a href="servers.php?action=manage&id='.$s->id.'">'.$dn;
	if ($s->hasAttribute('external_name') && $s->getAttribute('external_name') != $dn)
		echo '<br/><em style="margin-left: 10px; font-size: 0.8em;">'.$s->getAttribute('external_name').'</em>';
	if ($s->fqdn != $dn && $s->fqdn != $s->getAttribute('external_name'))
		echo '<br/><em style="margin-left: 10px; font-size: 0.8em;">'.$s->fqdn.'</em>';
	echo '</a>';
      echo '</td>';
      echo '<td style="text-align: center;"><img src="media/image/server-'.$s->stringType().'.png" alt="'.$s->stringType().'" title="'.$s->stringType().'" /><br />'.$s->stringType().'</td>';
		echo '<td>'.$s->stringVersion().'</td>';
      echo '<td>';
      echo '<ul>';
      $roles = $s->getAttribute('roles');
      ksort($roles);
      $server_roles_disabled = array();
      if ($s->hasAttribute('roles_disabled')) {
          $server_roles_disabled = $s->getAttribute('roles_disabled');
      }
      foreach ($roles as $a_role => $role_enabled) {
          echo "<li class=\"" . (array_key_exists($a_role, $server_roles_disabled)?"role_disabled":"role_enabled") . "\">$a_role</li>";
      }
      echo '</ul>';
      echo '</td>';
      echo '<td>'.$s->stringStatus().'</td>';
      echo '<td>';
      echo _('CPU').': '.$s->getAttribute('cpu_model').' ('.$s->getAttribute('cpu_nb_cores').' ';
      echo ($s->getAttribute('cpu_nb_cores') > 1)?_('cores'):_('core');
      echo ')<br />';
      echo _('RAM').': '.round($s->getAttribute('ram_total')/1024).' '._('MB');
      echo '</td>';

      if ($can_do_action and ($nb_a_servs_online_maintenance > 0 or $nb_a_servs_not_maintenance > 0)) {
	echo '<td>';
	  if ($server_online || $switch_value == 1) {
	  echo '<form action="actions.php" method="post">';
	  echo '<input';
       if ($switch_value == 0)
         echo ' style="background: #05a305; color: #fff; font-weight: bold;"';
       echo ' type="submit" value="'.$switch_msg.'"/>';
	  echo '<input type="hidden" name="action" value="maintenance" />';
	  echo '<input type="hidden" name="name" value="Server" />';
	  if ($switch_value == 0) 
		echo '<input type="hidden" name="to_production" value="to_production"/>';
	  else
		echo '<input type="hidden" name="to_maintenance" value="to_maintenance"/>';
	  echo '<input type="hidden" name="locked" value="'.$switch_value.'" />';
	  echo '<input type="hidden" name="checked_servers[]" value="'.$s->id.'" />';
	  echo '</form>';
	  }
	echo '</td>';
      }
      echo '</tr>';
    }
    echo '</tbody>';

    if ($av_servers > 1 and $can_do_action and ($nb_a_servs_online_maintenance > 0 or $nb_a_servs_not_maintenance > 0)) {
      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tfoot>';
      echo '<tr class="'.$content.'">';
      echo '<td colspan="7">';
      echo '<a href="javascript:;" onclick="markAllRows(\'available_servers_table\'); return false">'._('Mark all').'</a>';
      echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'available_servers_table\'); return false">'._('Unmark all').'</a>';
      echo '</td>';
      echo '<td>';
      echo '<form action="actions.php" method="post" onsubmit="return updateMassActionsForm(this, \'available_servers_table\');">';
      echo '<input type="hidden" name="name" value="Server" />';
      echo '<input type="hidden" name="action" value="maintenance" />';
	if ($nb_a_servs_online_maintenance > 0)
		echo '<input type="submit" name="to_production" value="'._('Switch to production').'"/>';
	if ($nb_a_servs_online_maintenance > 0 and $nb_a_servs_not_maintenance > 0)
		echo '<br />';
	if ($nb_a_servs_not_maintenance > 0)
		echo '<input type="submit" name="to_maintenance" value="'._('Switch to maintenance').'"/>';
      echo '</form>';
      echo '</td>';
      echo '</tr>';
      echo '</tfoot>';
    }

    echo '</table>';
  } else {
    echo _('No server');
  }

  echo '</div>';
  echo '</div>';
  page_footer();
  die();
}

function show_unregistered() {
	$u_servs = $_SESSION['service']->getUnregisteredServersList();
	if (is_null($u_servs)) {
		$u_servs = array();
	}
  uasort($u_servs, "server_cmp");

	$can_do_action = isAuthorized('manageServers');

  page_header();

  echo '<div id="servers_div">';
  echo '<h1>'._('Unregistered Servers').'</h1>';

  if (count($u_servs) > 0){
    echo '<div id="servers_list_div">';

    echo '<table id="unregistered_servers_table" class="main_sub sortable" border="0" cellspacing="1" cellpadding="3">';
    echo '<thead>';
    echo '<tr class="title">';
    if (count($u_servs) > 1)
      echo '<th class="unsortable"></th>';
    echo '<th>'._('FQDN').'</th><th>'._('Type').'</th>';
    	echo '<th>'._('Version').'</th>';
    echo '<th>'._('Roles').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $count = 0;
    foreach($u_servs as $s) {
      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tr class="'.$content.'">';
            if (count($u_servs) > 1)
	echo '<td><input class="input_checkbox" type="checkbox" name="checked_servers[]" value="'.$s->id.'" />';

      echo '<td>'.$s->fqdn.'</td>';
      echo '<td style="text-align: center;"><img src="media/image/server-'.$s->stringType().'.png" alt="'.$s->stringType().'" title="'.$s->stringType().'" /><br />'.$s->stringType().'</td>';
		echo '<td>'.$s->stringVersion().'</td>';
      echo '<td>';
      echo '<ul>';
      foreach ($s->roles as $a_role => $role_enabled) {
        if ($role_enabled) {
          echo "<li>$a_role</li>";
        }
      }
      echo '</ul>';
      echo '</td>';

		if ($can_do_action) {
			echo '<td>';
			if ($s->hasAttribute('can_register') && $s->getAttribute('can_register') == true) {
				echo '<form action="actions.php" method="post">';
				echo '<input type="hidden" name="name" value="Server" />';
				echo '<input type="hidden" name="action" value="register" />';
				echo '<input type="hidden" name="checked_servers[]" value="'.$s->id.'" />';
				echo '<input style="background: #05a305; color: #fff; font-weight: bold;" type="submit" value="'._('Register').'" />';
				echo '</form>';
			}
			echo '</td>';

			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this server?').'\');">';
			echo '<input type="hidden" name="name" value="Server" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="checked_servers[]" value="'.$s->id.'" />';
			echo '<input type="submit" value="'._('Delete').'" />';
			echo '</form>';
			echo '</td>';
		}

      echo '</tr>';
    }
    echo '</tbody>';

    // Mass actions
    if (count($u_servs) > 1) {
      $content = 'content'.(($count++%2==0)?1:2);
      echo '<tfoot>';
      echo '<tr class="'.$content.'">';
      echo '<td colspan="5">';
      echo '<a href="javascript:;" onclick="markAllRows(\'unregistered_servers_table\'); return false">'._('Mark all').'</a>';
      echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'unregistered_servers_table\'); return false">'._('Unmark all').'</a>';
      echo '</td>';
      echo '<td>';
      echo '<form action="actions.php" method="post" onsubmit="return updateMassActionsForm(this, \'unregistered_servers_table\');">';
      echo '<input type="hidden" name="name" value="Server" />';
      echo '<input type="hidden" name="action" value="register" />';
      echo '<input type="submit" name="mass_register" value="'._('Register').'"/><br />';
      echo '</form>';
      echo '</td>';
      echo '<td>';
      echo '<form action="actions.php" method="post" onsubmit="return updateMassActionsForm(this, \'unregistered_servers_table\');">';
      echo '<input type="hidden" name="name" value="Server" />';
      echo '<input type="hidden" name="action" value="del" />';
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
    echo _('No unregistered servers');
  }

  echo '</div>';
  echo '</div>';
  page_footer();
  die();
}

function show_manage($id_) {
	$server =  $_SESSION['service']->server_info($id_);

   if (! $server || $server->getAttribute('registered') === false)
     redirect('servers.php');

  $server_online = $server->isOnline();

  $buf_status = $server->getAttribute('status');
  if ($buf_status == 'down')
    $status_error_msg = _('Warning: server is offline');
  elseif ($buf_status == 'broken')
    $status_error_msg = _('Warning: server is broken');

  $server_lock = $server->getAttribute('locked');
    if ($server_lock) {
      $switch_button = _('Switch to production');
      $switch_value = 0;
    }
    else {
      $switch_button = _('Switch to maintenance');
      $switch_value = 1;
    }

	$servers_groups_list = $_SESSION['service']->servers_groups_list();
	$servers_groups_published_id = array();
	if ($server->hasAttribute('servers_groups')) {
		$servers_groups_published_id = $server->getAttribute('servers_groups');
	}
	
	$servers_groups_published = array();
	$servers_groups_available = array();
	foreach($servers_groups_list as $servers_group_id => $servers_group) {
		if (array_key_exists($servers_group_id, $servers_groups_published_id)) {
			$servers_groups_published[$servers_group_id] = $servers_group;
		}
		else {
			$servers_groups_available[$servers_group_id] = $servers_group;
		}
	}

	$server_roles_disabled = array();
	if ($server->hasAttribute('roles_disabled')) {
		$server_roles_disabled = $server->getAttribute('roles_disabled');
	}
  
  ksort($server->roles);
  $var = array();
  foreach ($server->roles as $role => $bool) {
    $ret = server_display_role_preparation($role, $server);
    if (! is_bool($ret)) {
      $var[$role] = $ret;
    }
  }
  $can_do_action = isAuthorized('manageServers');

	$dn = null;
	if ($server->hasAttribute('display_name') && ! is_null($server->getAttribute('display_name')))
		$dn = $server->getAttribute('display_name');
	
	$external_name = null;
	if ($server->hasAttribute('external_name'))
		$external_name = $server->getAttribute('external_name');
  
  page_header();
  echo '<script type="text/javascript" src="media/script/ajax/servers.js" charset="utf-8"></script>';

  echo '<div id="servers_div">';
  echo '<h1>'.$server->getDisplayName().'</h1>';

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
  echo '<td>'._('CPU').': '.$server->getAttribute('cpu_model').'  ('.$server->getAttribute('cpu_nb_cores').' ';
  echo ($server->getAttribute('cpu_nb_cores') > 1)?_('cores'):_('core');
  echo ')<br />'._('RAM').': '.round($server->getAttribute('ram_total')/1024).' '._('MB').'</td>';

  if ($server_online) {
    echo '<td>';
        echo _('CPU usage').': '.$server->getCpuUsage().'%<br />';
        echo display_loadbar($server->getCpuUsage());
        echo _('RAM usage').': '.$server->getRamUsage().'%<br />';
        echo display_loadbar($server->getRamUsage());
        foreach ($server->roles as $role => $enabled) {
          switch ($role) {
            case Server::SERVER_ROLE_APS:
              echo _('Session usage').': '.$server->getSessionUsage().'%<br />';
              echo display_loadbar((($server->getSessionUsage() > 100)?100:$server->getSessionUsage()));
              break;
            case Server::SERVER_ROLE_FS:
              echo _('Disk usage').': '.$server->getDiskUsage().'%<br />';
              echo display_loadbar((($server->getDiskUsage() > 100)?100:$server->getDiskUsage()));
              break;
          }
        }
    echo '</td>';
  }

  echo '</tr>';
  echo '</table>';
  echo '</div>';

  echo '<div class="section">';
  echo '<h2>'._('Configuration').'</h2>';
  echo '<table>';
  
	echo '<tr><td>';
	echo _('Display name').': ';
	echo '</td><td>';
	if ($can_do_action) {
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="server" value="'.$server->id.'" />';
		echo '<input type="hidden" name="action" value="display_name" />';
	}
	echo '<input type="text" name="display_name" value="'.((is_null($dn))?'':$dn).'" />';
	if ($can_do_action) {
		echo ' <input type="submit" value="'.((is_null($dn))?_('define'):_('change')).'" />';
		echo '</form>';
		
	}
	echo '</td><td>';
	if (is_null($dn))
		echo sprintf(_('(no display name defined yet, use "%s" instead'), $server->getDisplayName());
	else {
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="server" value="'.$server->id.'" />';
		echo '<input type="hidden" name="action" value="display_name" />';
		echo '<input type="submit" value="'._('delete').'" />';
		echo '</form>';
	}
	echo "</td></tr>\n";
  
	echo '<tr><td>';
	echo _('Internal name (fqdn)').': ';
	echo '</td><td>';
	if ($can_do_action) {
		echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to change the internal name of this server? The server will switch to a broken state if the name is not valid!').'\');">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="server" value="'.$server->id.'" />';
		echo '<input type="hidden" name="action" value="fqdn" />';
	}
	echo '<input type="text" name="fqdn" value="'.$server->fqdn.'" />';
	if ($can_do_action) {
		echo ' <input type="submit" value="'._('change').'" />';
		echo '</form>';
		
	}
	echo "</td></tr>\n";

  echo '<tr><td>';
  echo _('Redirection name for this server').': ';
  echo '</td><td>';
  if ($can_do_action) {
    echo '<form action="actions.php" method="post">';
    echo '<input type="hidden" name="name" value="Server" />';
    echo '<input type="hidden" name="server" value="'.$server->id.'" />';
    echo '<input type="hidden" name="action" value="external_name" />';
  }
  echo '<input type="text" name="external_name" value="'.((is_null($external_name))?'':$external_name).'" />';
  if ($can_do_action) {
    echo ' <input type="submit" value="'.((is_null($external_name))?_('define'):_('change')).'" />';
    echo '</form>';
  }
	echo '</td><td>';
	if (is_null($external_name))
		echo sprintf(_('(no external name defined yet, use "%s" instead'), $server->getExternalName());
	else {
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="server" value="'.$server->id.'" />';
		echo '<input type="hidden" name="action" value="external_name" />';
		echo '<input type="submit" value="'._('delete').'" />';
		echo '</form>';
	}
  echo "</td></tr>\n";
  
	echo '<tr><td>';
	echo _('Redirection port (rdp) for this server').': ';
	echo '</td><td>';
	if ($can_do_action) {
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Server" />';
		echo '<input type="hidden" name="server" value="'.$server->id.'" />';
		echo '<input type="hidden" name="action" value="rdp_port" />';
	}
	echo '<input type="text" name="rdp_port" value="'.$server->getApSRDPPort().'" />';
	if ($can_do_action) {
		echo ' <input type="submit" value="'._('change').'" />';
		echo '</form>';
	}
	echo '</td><td>';
	if ($server->getApSRDPPort() == Server::DEFAULT_RDP_PORT)
		echo _('(default value)');
	else
		echo sprintf(_('(overloaded, default value is %d)'), Server::DEFAULT_RDP_PORT);
	echo "</td></tr>\n";
  
	// Roles enabled / disabled
	echo '<tr><td>';
	echo _('Roles available on this server').': ';
	echo '</td>';
	$role_i = 0;
	foreach ($server->roles as $role => $bool) {
		echo '<td>'.$role;
		
		if (! array_key_exists($role, $server_roles_disabled))
			echo ' (<em class="msg_ok">enabled</em>)';
		else
			echo ' (<em class="msg_error">disabled</em>)';
		
		if ($can_do_action) {
			echo '<span style="float: right;">';
			echo '<form action="actions.php" method="post">';
			echo '<input type="hidden" name="name" value="Server" />';
			echo '<input type="hidden" name="server" value="'.$server->id.'" />';
			echo '<input type="hidden" name="action" value="role" />';
			echo '<input type="hidden" name="role" value="'.$role.'" />';
			
			if (! array_key_exists($role, $server_roles_disabled)) {
				echo '<input type="hidden" name="do" value="disable" />';
				echo '<input type="submit" value="'._('disable this role').'" />';
			}
			else {
				echo '<input type="hidden" name="do" value="enable" />';
				echo '<input type="submit" value="'._('enable this role').'" />';
			}
			
			echo '</form>';
			echo '</span>';
		}
		echo '</td>';
		
		$role_i++;
		if ($role_i < count($server->roles))
			echo '</tr><tr><td></td>';
	}
	echo '</tr>';

	if ($can_do_action) {
		if ($server_online || $switch_value == 1) {
			echo '<tr><td></td><td>';
			echo '<form action="actions.php" method="post">';
			echo '<input type="hidden" name="name" value="Server" />';
			echo '<input type="hidden" name="checked_servers[]" value="'.$server->id.'" />';
			echo '<input type="hidden" name="action" value="maintenance" />';
			if ($switch_value == 0) 
				echo '<input type="hidden" name="to_production" value="to_production"/>';
			else
				echo '<input type="hidden" name="to_maintenance" value="to_maintenance"/>';
			echo '<input';
			if ($switch_value == 0)
				echo ' style="background: #05a305; color: #fff; font-weight: bold;"';
			echo ' type="submit" value="'.$switch_button.'"/>';
			echo '</form>';
			echo '</td></tr>';
		}


		if ($server_lock || !$server_online) {
			echo '<tr><td></td><td>';
			echo '<form action="actions.php" method="get" onsubmit="return confirm(\''._('Are you sure you want to delete this server?').'\');">';
			echo '<input type="hidden" name="name" value="Server" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="checked_servers[]" value="'.$server->id.'" />';
			echo '<input type="submit" value="'._('Delete').'" />';
			echo '</form>';
			echo '</td></tr>';
		}
	}
  echo '</table>';
  echo '</div>';

	echo '<div>';
	echo '<h2>'._('List of Server Groups including this server').'</h2>';
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	if (count($servers_groups_published) == 0) {
		echo '<tr><td colspan="2">'._('No group has this group').'</td></tr>';
	}
	else {
		foreach($servers_groups_published as $group_id => $group) {
			echo '<tr><td>';
			echo '<a href="serversgroup.php?action=manage&amp;id='.$group->id.'">'.$group->name.'</a>';
			echo '</td>';
			if ($can_do_action) {
				echo '<td>';
				echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this group from this server?').'\');">';
				echo '<input type="hidden" name="action" value="del" />';
				echo '<input type="hidden" name="name" value="Server_ServersGroup" />';
				echo '<input type="hidden" name="group" value="'.$group->id.'" />';
				echo '<input type="hidden" name="server" value="'.$server->id.'" />';
				echo '<input type="submit" value="'._('Delete from this group').'" />';
				echo '</form>';
				echo '</td>';
			}
			
			echo '</tr>';
		}
	}
	
	if ($can_do_action) {
		if (count($servers_groups_available) == 0) {
			echo '<tr><td colspan="2">'._('Not any available group to add').'</td></tr>';
		}
		else {
			echo '<tr><form action="actions.php" method="post"><td>';
			echo '<input type="hidden" name="action" value="add" />';
			echo '<input type="hidden" name="name" value="Server_ServersGroup" />';
			echo '<input type="hidden" name="server" value="'.$server->id.'" />';
			echo '<select name="group">';
			foreach($servers_groups_available as $group_id => $group) {
				echo '<option value="'.$group->id.'" >'.$group->name.'</option>';
			}
			
			echo '</select>';
			echo '</td><td><input type="submit" value="'._('Add to this group').'" /></td>';
			echo '</form></tr>';
		}
	}
	
	echo '</table>';
	echo '</div>';
	echo '<br/><br/>';
  
	foreach ($server->roles as $role => $bool) {
		if (array_key_exists($role, $var)) {
			echo '<div>'; // div role
			echo '<fieldset class="role">';
			echo '<legend>';
			echo '<a href="javascript:;" onclick="toggleContent(\'role_'.$role.'\'); return false;">';
			echo '<span id="role_'.$role.'_ajax"></span>';
			echo sprintf(_('Role: %s'), strtoupper($role));
			echo '</a>';
			echo '</legend>';
			echo '<div id="role_'.$role.'_content" style="display:none;">';
			echo server_display_role($role, $server, $var[$role]);
			echo '</div>';
			echo '<div id="role_'.$role.'_content_off" style="text-align: center;">';
			echo '<a href="javascript:;" onclick="toggleContent(\'role_'.$role.'\'); return false;" class="button">...</a>';
			echo '</div>';
			echo '</fieldset>';
			echo '<script type="text/javascript">Event.observe(window, \'load\', function() { initContent(\'role_'.$role.'\'); });</script></div>';
			echo '</div>';
		}
	}
	
  page_footer();
  die();
}
