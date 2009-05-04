<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

$types = array('linux' => 'linux' , 'windows' => 'windows', 'weblink' => _('Web link'));

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$mods_enable = $prefs->get('general','module_enable');
if (!in_array('ApplicationDB',$mods_enable)){
	die_error(_('Module ApplicationDB must be enabled'),__FILE__,__LINE__);
}
$mod_app_name = 'admin_ApplicationDB_'.$prefs->get('ApplicationDB','enable');
$applicationDB = new $mod_app_name();

if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'manage') {
		if (isset($_REQUEST['id']))
			show_manage($_REQUEST['id'], $applicationDB);
	}
	elseif ($_REQUEST['action'] == 'modify' && $applicationDB->isWriteable()) {
		if (isset($_REQUEST['id'])) {
			modify_application($applicationDB, $_REQUEST['id'], $_POST);
			redirect();
		}
	}
	elseif ($_REQUEST['action'] == 'add' && $applicationDB->isWriteable()) {
		add_application($applicationDB, $_POST);
		redirect();
	}
	elseif ($_REQUEST['action'] == 'del' && $applicationDB->isWriteable()) {
		if (isset($_REQUEST['id']))
			del_application($applicationDB, $_REQUEST['id']);
		redirect();
	}
}

if (! isset($_GET['view']))
  $_GET['view'] = 'all';

if ($_GET['view'] == 'all')
  show_default($applicationDB);

function show_default($applicationDB) {
	global $types;
	$applications2 = $applicationDB->getList(true);
	$applications = array();
	foreach ($applications2 as $k => $v) {
		if ($v->getAttribute('static'))
			$applications[$k] = $v;
	}
	$is_empty = (is_null($applications) or count($applications)==0);
	
	$is_rw = $applicationDB->isWriteable();
	
	page_header();
	
	echo '<div>'; // general div
	echo '<h1>'._('Static Applications').'</h1>';
	echo '<div id="apps_list_div">'; // apps_list_div
	
	if ($is_empty) {
		echo _('No available application').'<br />';
	}
	else {
		echo '<div id="apps_list">'; // apps_list
		echo '<table class="main_sub sortable" id="applications_list_table" border="0" cellspacing="1" cellpadding="5">'; // table A
		echo '<thead>';
		echo '<tr class="title">';
		echo '<th>'._('Name').'</th>';
		echo '<th>'._('Description').'</th>';
		echo '<th>'._('Type').'</th>';
		echo '</tr>';
		echo '</thead>';
		$count = 0;
		foreach($applications as $app) {
			$content = 'content'.(($count++%2==0)?1:2);
		
			if ($app->getAttribute('published')) {
				$status_change_value = 0;
			} else {
				$status_change_value = 1;
			}
		
			$icon_id = ($app->haveIcon())?$app->getAttribute('id'):0;
		
			echo '<tr class="'.$content.'">';
			if ($is_rw)
				echo '<td><img src="media/image/cache.php?id='.$icon_id.'" alt="" title="" /> <a href="?action=manage&id='.$app->getAttribute('id').'">'.$app->getAttribute('name').'</a></td>';
			echo '<td>'.$app->getAttribute('description').'</td>';
			echo '<td style="text-align: center;"><img src="media/image/server-'.$app->getAttribute('type').'.png" alt="'.$app->getAttribute('type').'" title="'.$app->getAttribute('type').'" /><br />'.$app->getAttribute('type').'</td>';
		
			echo '<td><form action="" method="get">';
			echo '<input type="hidden" name="action" value="manage" />';
			echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
			echo '<input type="submit" value="'._('Manage').'"/>';
			echo '</form>';
			echo '</td>';
			echo '<td>';
			echo '<form action="" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this application?').'\');">';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
			echo '<input type="submit" value="'._('Delete').'" />';
			echo '</form>';
			echo '</td>';
		
			echo '</tr>';
		}
		echo '</table>'; // table A
		echo '</div>'; // apps_list
	}
	echo '</div>'; // general div
	if ($is_rw) {
		echo '<br />';
		echo '<h2>'._('Add a static application').'</h2>';
		echo '<div id="application_add">';
		echo '<form action="" method="post">';
		echo '<input type="hidden" name="action" value="add" />';
		
		echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
		$count = 0;
		foreach ($applicationDB->minimun_attributes() as $minimun_attribute) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			echo '<td>'._($minimun_attribute).'</td>';
			if ($minimun_attribute == 'type') {
				echo '<td>';
				echo '<select id="'.$minimun_attribute.'"  name="'.$minimun_attribute.'">';
				foreach ($types as $mykey => $myval) {
					echo '<option value="'.$mykey.'" >'.$myval.'</option>';
				}
				echo '</select>';
				echo '</td>';
			}
			else {
				echo '<td><input type="text" name="'.$minimun_attribute.'" value="" /></td>';
			}
			echo '</tr>';
		}
		
		echo '<tr class="'.$content.'">';
		echo '<td colspan="2">';
		echo '<input type="submit" value="'._('Add').'" />';
		echo '<input type="hidden" name="published" value="1" />';
		echo '<input type="hidden" name="static" value="1" />';
		echo '</td>';
		echo '</tr>';
		
		echo '</table>';
		echo '</form>';
		echo '</div>'; // application_add
	}
	page_footer();
	die();
}

function add_application($applicationDB, $data_) {
  if (! isset($data_['type']))
    return false;
  
  unset($data_['action']);
  $data_['id'] = 666; // little hack
  $a = $applicationDB->generateApplicationFromRow($data_);
  if (! $applicationDB->isOK($a))
    return false;
  $a->unsetAttribute('id');
  return $applicationDB->add($a);
}

function del_application($applicationDB, $id_) {
	$app = $applicationDB->import($id_);
	if (is_object($app)) {
		return $applicationDB->remove($app);
	}
	return false;
}

function modify_application($applicationDB, $id_, $data_) {
	unset($data_['action']);
	$app = $applicationDB->import($id_);
	if (!is_object($app))
		return false;
	$attr_list = $app->getAttributesList();
	foreach ($data_ as $k => $v) {
		if (in_array($k, $attr_list)) {
			$app->setAttribute($k, $v);
		}
	}
	$applicationDB->update($app);
}

function show_manage($id, $applicationDB, $modify_=false) {
	global $types;
	$app = $applicationDB->import($id);
	if (!is_object($app))
		return false;
	
	$is_rw = $applicationDB->isWriteable();
	
	// App groups
	$appgroups = getAllAppsGroups();
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
	
	page_header();

	$icon_id = ($app->haveIcon())?$app->getAttribute('id'):0;
	echo '<div>';
	echo '<h1><img src="media/image/cache.php?id='.$icon_id.'" alt="" title="" /> '.$app->getAttribute('name').'</h1>';
	
	echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
	echo '<tr class="title">';
// 	echo '<th>'._('Package').'</th>';
	echo '<th>'._('Type').'</th>';
	echo '<th>'._('Description').'</th>';
	echo '<th>'._('Executable').'</th>';
	if ($is_rw) {
		echo '<th></th>';
	}
	echo '</tr>';
	
	echo '<tr class="content1">';
	if ($modify_ == false ) {
// 		echo '<td>'.$app->getAttribute('package').'</td>';
		echo '<td style="text-align: center;"><img src="media/image/server-'.$app->getAttribute('type').'.png" alt="'.$app->getAttribute('type').'" title="'.$app->getAttribute('type').'" /><br />'.$app->getAttribute('type').'</td>';
		echo '<td>'.$app->getAttribute('description').'</td>';
		echo '<td>'.$app->getAttribute('executable_path').'</td>';
		
		echo '<td>';
		echo '<form action="" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this application?').'\');">';
		echo '<input type="hidden" name="action" value="del" />';
		echo '<input type="hidden" name="id" value="'.$app->getAttribute('id').'" />';
		echo '<input type="submit"  value="'._('Delete').'" />';
		echo '</form>';
		echo '</td>';
	}
	else {
		echo '<form action="" method="post">';
		echo '<input type="hidden" name="action" value="modify_static" />';
		echo '<td><input type="text" name="package" value="'.$app->getAttribute('package').'" /></td>';
		echo '<td>';
		echo '<select id="type"  name="type">';
		foreach ($types as $mykey => $myval){
			echo '<option value="'.$mykey.'" >'.$myval.'</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '<td><input type="text" name="description" value="'.$app->getAttribute('description').'" /></td>';
		echo '<td><input type="text" name="executable_path" value="'.$app->getAttribute('executable_path').'" /></td>';
		echo '<td><input type="submit" value="'._('Modify2').'"/></td>';
		echo '</form>';
	}
	echo '</tr>';
	echo '</table>';
	
	
	if ($is_rw) {
		echo '<br />';
		echo '<h2>'._('Modify').'</h2>';
		echo '<div id="application_modify">';
		echo '<form action="" method="post">';
		echo '<input type="hidden" name="action" value="modify" />';
		
		echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="5">';
		$count = 1;
		$attr_list = $app->getAttributesList();
		
		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr class="'.$content.'">';
		echo '<td>'._('type').'</td>';
		echo '<td>';
		foreach ($types as $type => $name) {
			echo '<input type="radio" name="type" value="'.$type.'"';
			if ($app->getAttribute('type') == $type)
				echo ' checked="checked"';
			echo '/>';
			echo '<img src="media/image/server-'.$type.'.png" alt="'.$type.'" title="'.$type.'" />';
		}
		echo '</td>';
		echo '</tr>';
		
		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr class="'.$content.'">';
		echo '<td>'._('name').'</td>';
		echo '<td><input type="text" name="name" value="'.$app->getAttribute('name').'" /></td>';
		echo '</tr>';
		
		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr class="'.$content.'">';
		echo '<td>'._('description').'</td>';
		echo '<td><textarea rows="7" cols="60"  name="description">'.$app->getAttribute('description').'</textarea></td>';
		echo '</tr>';
		
		$content = 'content'.(($count++%2==0)?1:2);
		echo '<tr class="'.$content.'">';
		echo '<td>'._('executable_path').'</td>';
		echo '<td><input type="text" name="executable_path" value="'.$app->getAttribute('executable_path').'" /></td>';
		echo '</tr>';
		$content = 'content'.(($count++%2==0)?1:2);
		
		echo '<tr class="'.$content.'">';
		echo '<td colspan="2">';
		echo '<input type="submit" value="'._('Modify').'" />';
		echo '<input type="hidden" name="published" value="1" />';
		echo '<input type="hidden" name="static" value="1" />';
		echo '</td>';
		echo '</tr>';
		
		echo '</table>';
		echo '</form>';
		echo '</div>'; // application_modify
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
