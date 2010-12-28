<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
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

if (! checkAuthorization('viewApplications'))
	redirect('index.php');


if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action']=='manage') {
		if (isset($_REQUEST['id']))
			show_manage($_REQUEST['id']);
	}
}

show_default();


function show_default() {
	$applicationDB = ApplicationDB::getInstance();
	$mimes = $applicationDB->getAllMimeTypes();
	
	$is_empty = (count($mimes)==0);
	
	page_header();
	
	echo '<div>';
	echo '<h1>'._('Mime-Types').'</h1>';
	echo '<div id="mimes_list_div">';
	
	if ($is_empty)
		echo _('No available mimes-type').'<br />';
	else {
		echo '<table class="main_sub sortable" id="applications_list_table" border="0" cellspacing="1" cellpadding="5">';
		echo '<thead>';
		echo '<tr class="title">';
		echo '<th>'._('Name').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		$count = 0;
		foreach($mimes as $mime) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			echo '<td>'.$mime.'</td>';
			echo '<td><form action="">';
			echo '<input type="hidden" name="action" value="manage" />';
			echo '<input type="hidden" name="id" value="'.$mime.'" />';
			echo '<input type="submit" value="'._('More information').'"/>';
			echo '</form></td>';
			
			echo '</tr>';
		}
		
		echo '</tbody>';
		
		echo '</table>';
	}
	echo '</div>';
	echo '</div>';
	page_footer();
	die();
}


function show_manage($id_) {
	$applicationDB = ApplicationDB::getInstance();
	$applications = $applicationDB->getApplicationsWithMimetype($id_);
	
	page_header();
	
	echo '<div>';
	echo '<h1>'._('Mime-Type').' - '.$id_.'</h1>';
	echo '<div>';
	
	if (count($applications) == 0)
		echo _('No application with this mime-type').'<br />';
	else {
		echo '<table class="main_sub sortable" id="applications_list_table" border="0" cellspacing="1" cellpadding="5">';
		echo '<thead>';
		echo '<tr class="title">';
		echo '<th>'._('Name').'</th>';
		echo '<th>'._('OS').'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		$count = 0;
		foreach($applications as $app) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			echo '<td><a href="applications.php?action=manage&id='.$app->getAttribute('id').'">';
			echo '<img src="media/image/cache.php?id='.$app->getAttribute('id').'" alt="" title="" />&nbsp;';
			echo $app->getAttribute('name');
			echo '</a></td>';
			echo '<td><img src="media/image/server-'.$app->getAttribute('type').'.png" alt="'.$app->getAttribute('type').'" title="'.$app->getAttribute('type').'" /></td>';
			echo '</tr>';
		}
		
		echo '</tbody>';
		echo '</table>';
	}
	
	echo '</div>';
	echo '</div>';
	page_footer();
	die();
}
