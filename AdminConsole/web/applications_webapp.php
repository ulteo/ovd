<?php
/**
 * Copyright (C) 2013-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013, 2014
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
require_once(dirname(dirname(__FILE__)).'/includes/webapp.inc.php');

if (! checkAuthorization('viewApplications'))
	redirect();

	
if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'manage') {
		if (isset($_REQUEST['id']))
			show_manage($_REQUEST['id']);
	}
}

if (! isset($_GET['view']))
  $_GET['view'] = 'all';

if ($_GET['view'] == 'all')
  show_default();

function show_default() {
	$applications2 = $_SESSION['service']->applications_list();
	$applications = array();
	foreach ($applications2 as $k => $v) {
		if ($v->getAttribute('static') && $v->getAttribute('type') == 'webapp')
			$applications[$k] = $v;
	}
	$is_empty = (is_null($applications) or count($applications)==0);

	$is_rw = applicationdb_is_writable();

	$can_manage_applications = isAuthorized('manageApplications');

	page_header();

	echo '<div>'; // general div
	echo '<h1>'._('Web applications').'</h1>';
	echo '<div id="apps_list_div">'; // apps_list_div
	$count = 0;
	
	if ($is_empty) {
		echo _('No available application').'<br />';
	}
	else {
		echo '<div id="apps_list">'; // apps_list
		echo '<table class="main_sub sortable" id="applications_list_table" border="0" cellspacing="1" cellpadding="5">'; // table A
		echo '<thead>';
		echo '<tr class="title">';
		if (count($applications) > 1 and $is_rw and $can_manage_applications)
			echo '<th class="unsortable"></th>';
		echo '<th>'._('Name').'</th>';
		echo '<th>'._('Description').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach($applications as $app) {
			$content = 'content'.(($count++%2==0)?1:2);

			if ($app->getAttribute('published')) {
				$status_change_value = 0;
			} else {
				$status_change_value = 1;
			}

			echo '<tr class="'.$content.'">';
			if (count($applications) > 1 and $is_rw and $can_manage_applications)
				echo '<td><input class="input_checkbox" type="checkbox" name="checked_applications[]" value="'.$app->getAttribute('id').'" /></td>';
			echo '<td><img class="icon32" src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" /> ';
			if ($is_rw and $can_manage_applications)
				echo '<a href="?action=manage&id='.$app->getAttribute('id').'">';
			echo $app->getAttribute('name');
			if ($is_rw and $can_manage_applications)
				echo '</a>';
			echo '</td>';
			echo '<td>'.$app->getAttribute('description').'</td>';

			if ($can_manage_applications) {
				echo '<td>';
				echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this application?').'\');">';
				echo '<input type="hidden" name="name" value="Application_webapp" />';
				echo '<input type="hidden" name="action" value="del" />';
				echo '<input type="hidden" name="checked_applications[]" value="'.$app->getAttribute('id').'" />';
				echo '<input type="submit" value="'._('Delete').'" />';
				echo '</form>';
				echo '</td>';
			}

			echo '</tr>';
		}
		echo '</tbody>';

		if (count($applications) > 1 and $is_rw and $can_manage_applications) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tfoot>';
			echo '<tr class="'.$content.'">';
			echo '<td colspan="3">';
			echo '<a href="javascript:;" onclick="markAllRows(\'applications_list_table\'); return false">'._('Mark all').'</a>';
			echo ' / <a href="javascript:;" onclick="unMarkAllRows(\'applications_list_table\'); return false">'._('Unmark all').'</a>';
			echo '</td>';
			echo '<td>';
			echo '<form action="actions.php" method="post" onsubmit="return updateMassActionsForm(this, \'applications_list_table\') && confirm(\''._('Are you sure you want to delete these static applications?').'\');;">';
			echo '<input type="hidden" name="name" value="Application_webapp" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="submit" name="delete" value="'._('Delete').'"/>';
			echo '</form>';
			echo '</td>';
			echo '</tr>';
			echo '</tfoot>';
		}
		echo '</table>'; // table A
		echo '</div>'; // apps_list
	}

	if ($is_rw and $can_manage_applications) {
		echo '<br />';
		echo '<h2>'._('Add a web application').'</h2>';
		echo '<div id="application_add">';

		echo '<form action="actions.php" method="post" enctype="multipart/form-data">';
		echo '<input type="hidden" name="name" value="Application_webapp" />';
		echo '<input type="hidden" name="action" value="add" />';
		
		echo '<input type="hidden" name="published" value="1" />';
		echo '<input type="hidden" name="attributes_send[]" value="published" />';
		echo '<input type="hidden" name="static" value="1" />';
		echo '<input type="hidden" name="attributes_send[]" value="static" />';
		echo '<input type="hidden" name="type" value="webapp" />';
		echo '<input type="hidden" name="attributes_send[]" value="type" />';
			
		echo '<table border="0" class="main_sub" cellspacing="1" cellpadding="3" >';

		display_web_form();

		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr class="'.$content.'">';
		echo '<td colspan="2">';
		echo '<input type="submit" value="'._('Add').'" />';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		
		echo '</form>';

		echo '</div>'; // application_add
	}

	echo '</div>'; // general div
	page_footer();
	die();
}

function show_manage($id) {
	$app = $_SESSION['service']->application_info($id);
	$application_type = $app->getAttribute('type');

	if (!is_object($app))
		return false;

	$is_rw = applicationdb_is_writable();
	$can_manage_applications = isAuthorized('manageApplications');

	// App groups
	$appgroups =  $_SESSION['service']->applications_groups_list();
	$groups_id = array();
	if ($app->hasAttribute('groups')) {
		$groups_id = $app->getAttribute('groups');
	}

	$groups = array();
	$groups_available = array();
	foreach ($appgroups as $group) {
		if (array_key_exists($group->id, $groups_id))
			$groups[]= $group;
		else
			$groups_available[]= $group;
	}

	$servers_all = $_SESSION['service']->servers_list('online');
	$servers = array();
	foreach($servers_all as $server) {
		if(array_key_exists('webapps', $server->roles) && $server->roles['webapps'])
			$servers[]= $server;
	}

	$can_manage_server = isAuthorized('manageServers');

	page_header();

	echo '<div>';
	echo '<h1><img class="icon32" src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" /> '.$app->getAttribute('name').'</h1>';

	echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
	echo '<tr class="title">';
	echo '<th>'._('Type').'</th>';
	echo '<th>'._('Description').'</th>';

	if ($is_rw and $can_manage_applications) {
		echo '<th></th>';
	}
	echo '</tr>';

	echo '<tr class="content1">';

// 		echo '<td>'.$app->getAttribute('package').'</td>';
	echo '<td style="text-align: center;"><img src="media/image/server-'.$app->getAttribute('type').'.png" alt="'.$app->getAttribute('type').'" title="'.$app->getAttribute('type').'" /><br />'.$app->getAttribute('type').'</td>';
	echo '<td>'.$app->getAttribute('description').'</td>';
	
	if ($is_rw and $can_manage_applications) {
		echo '<td>';
		echo '<form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this application?').'\');">';
		echo '<input type="hidden" name="name" value="Application_webapp" />';
		echo '<input type="hidden" name="action" value="del" />';
		echo '<input type="hidden" name="checked_applications[]" value="'.$app->getAttribute('id').'" />';
		echo '<input type="submit"  value="'._('Delete').'" />';
		echo '</form>';
		echo '</td>';
	}

	echo '</tr>';
	echo '</table>';

	if ($is_rw and $can_manage_applications) {
		$app_info = $_SESSION['service']->application_webapp_info($id);
		echo '<br />';
		echo '<form action="actions.php" method="post"">';
		echo '<input type="hidden" name="name" value="Application_webapp" />';
		echo '<input type="hidden" name="action" value="clone" />';
		echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
		echo '<input type="submit" value="'._('Clone to new application').'"/>';
		echo '</form>';
	
		echo '<br />';
		echo '<h2>'._('Description').'</h2>';
		echo '<div id="application_modify">';

		echo '<form id="delete_icon" action="actions.php" method="post" style="display: none;">';
		echo '<input type="hidden" name="name" value="Application_webapp" />';
		echo '<input type="hidden" name="action" value="del_icon" />';
		echo '<input type="hidden" name="checked_applications[]" value="'.$app->getAttribute('id').'" />';
		echo '</form>';

		echo '<form action="actions.php" method="post" enctype="multipart/form-data" >'; // form A
		echo '<input type="hidden" name="name" value="Application_webapp" />';
		echo '<input type="hidden" name="action" value="modify" />';
		echo '<input type="hidden" name="published" value="1" />';
		echo '<input type="hidden" name="static" value="1" />';
		echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';

		echo '<table border="1"><tr><td>';
		echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
		$count = 1;
		$app->setAttribute('application_name', $app->getAttribute('name')); // ugly hack
		$app->setAttribute('url_prefix', $app_info['url_prefix']);
		$attr_list = array('application_name'=>_('Name'), 'description'=>('Description'), 'url_prefix'=>_('Url prefix'));
		
		foreach ($attr_list as $attr_name=>$display_name) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			echo '<td style="text-transform: capitalize;">';
			echo $display_name;
			
			$attr_value = $app->getAttribute($attr_name);
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="'.$attr_name.'" value="'.htmlspecialchars($attr_value).'" style="with:100%;"/>';
			echo '<input type="hidden" name="attributes_send[]" value="'.$attr_name.'" />';
			echo '</td>';
			echo '</tr>';
		}
		
		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr class="'.$content.'">';
		echo '<td>'._('Icon').'</td>';
		echo '<td>';
		echo '<img class="icon32" src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" /> ';
		echo '<input type="button" value="'._('Delete this icon').'" onclick="return confirm(\''._('Are you sure you want to delete this icon?').'\') && $(\'delete_icon\').submit();"/>';
		echo '<br />';
		echo '<input type="file"  name="file_icon" /> ';
		echo '</td>';
		echo '</tr>';

		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr class="'.$content.'">';
		echo '<td>'._('Configuration').'</td>';
		echo '<td>';
		echo '<textarea name="app_conf_raw" style="width:100%;height:12em">'.$app_info['raw_configuration'].'</textarea>';
		echo '<br />';
		echo '<a href="actions.php?name=Application_webapp&action=download&id='.$app->getAttribute('id').'">'._('Download').'</a>';
		echo '</td>';
		echo '</tr>';
		
		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr class="'.$content.'">';
		echo '<td colspan="2">';
		echo '<input type="submit" value="'._('Modify').'" />';
		echo '</td>';
		echo '</tr>';

		echo '</table>';

		echo '</form>'; // form A
		echo "</td>";
		
		echo "<td>";
		echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
		echo '<form action="actions.php" method="post">';
		echo '<input type="hidden" name="name" value="Application_webapp" />';
		echo '<input type="hidden" name="action" value="modify" />';
		echo '<input type="hidden" name="published" value="1" />';
		echo '<input type="hidden" name="static" value="1" />';
		echo '<input type="hidden" name="task" value="webapp_configuration" />';
		echo '<input type="hidden" name="id" value="'.$id.'" />';
		display_webapp_configuration($id);
		echo '</form>';
		echo '</table>';
		echo "</td></tr>";

		echo "</table>";
		echo '</div>'; // application_modify
	}

	if (count($servers) > 0) {
		echo '<div>';
		echo '<h2>'._('Servers').'</h2>';
		echo '<table border="0" cellspacing="1" cellpadding="3">';
		foreach($servers as $server) {
			echo '<tr><td>';
			echo '<a href="servers.php?action=manage&id='.$server->id.'">'.$server->getDisplayName().'</a>';
			echo '</td></tr>';
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
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this application from this group?').'\');">';
			echo '<input type="hidden" name="name" value="Application_ApplicationGroup" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="element" value="'.$id.'" />';
			echo '<input type="hidden" name="group" value="'.$group->id.'" />';
			echo '<input type="submit" value="'._('Delete from this group').'" />';
			echo '</form></td>';
			echo '</tr>';
		}

		if (count($groups_available) > 0) {
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
	

	echo '</div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
	echo '</div>';
	page_footer();
	die();
}

function display_web_form() {
	$inputs = array('name'=>_('Name'), 'url_prefix'=>_('Url prefix'), 'description'=>_('Description'), 'app_conf_file'=>_('Configuration'));
	$count = 0;

	foreach ($inputs as $attr_name=>$display_name) {
		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr class="'.$content.'">';
		echo '<td style="text-transform: capitalize">';
		echo $display_name;
		echo '</td>';
		echo '<td>';

		if ($attr_name == 'name')
			$attr_name = 'application_name';
		echo '<input name="'.$attr_name.'" id="'.$attr_name.'"';
		if(strcmp($attr_name, 'app_conf_file')==0)
			echo 'type="file"';
		echo '/>';

		echo '</td>';
		echo '</tr>';
		echo '<input type="hidden" name="attributes_send[]" value="'.$attr_name.'" />';
	}
}


function display_webapp_configuration($application_id) {
	// Fetch configuration and prepare for replacement
	$config = $_SESSION['service']->application_webapp_info($application_id);
	$count = 0;
	
	if($config!==NULL) {
		$pieces = NULL;
		$parsed_config = json_decode($config["raw_configuration"], True);
		
		if (array_key_exists('title', $parsed_config)) {
			echo('<h1>'.$parsed_config['title'].'</h1>');
		}
		
		if (array_key_exists('description', $parsed_config)) {
			echo('<p>'.$parsed_config['description'].'</p>');
		}
		
		foreach($parsed_config['Configuration'] as $name => $params) {
			$content = 'content'.(($count++%2==0)?1:2);
			$value = '';
			
			if (is_array($config['values']) && array_key_exists($name, $config['values'])) {
				$value = $config['values'][$name];
			} elseif (array_key_exists('value', $params)) {
				$value = $params['value'];
			}
			$attr_send = sprintf('<input type="hidden" name="attributes_send[]" value="%s" />', $name);
			if ($params['type'] === 'boolean') {
				if ($value)
					$input = sprintf('<input type="checkbox" name="%s" id="%s" checked="checked"/>', $name, $name);
				else
					$input = sprintf('<input type="checkbox" name="%s" id="%s"/>', $name, $name);
			} elseif (($params['type'] === 'string') || ($params['type'] === 'url') || ($params['type'] === 'inetaddr')) {
				$input = sprintf('<input type="text" name="%s" id="%s" value="%s"/>', $name, $name, $value);
			} else {
				//$input = sprintf('<input type="text" name="ignore" id="%s" value="%s" disabled="disabled"/>', $name, $params['value']);
				$attr_send = '';
			}
			$title = $name;
			if (array_key_exists('title', $params))
				$title = $params['title'];
			if($attr_send !== '')
				echo sprintf('<tr class="%s"><td style="text-transform: capitalize;">%s</td><td>%s%s</td></tr>', $content, $title, $input, $attr_send);
		}
		$content = 'content'.(($count++%2==0)?1:2);
		echo sprintf('<tr class="%s"><td colspan="2"><input type="submit" value="Update"/>', $content);
	}
	else
	{
		print "No config";
	}
}
