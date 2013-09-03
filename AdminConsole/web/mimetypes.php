<?php
/**
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010-2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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
	$mimes = $_SESSION['service']->mime_types_list();
	
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
	$mime = $_SESSION['service']->mime_type_info($id_);
	$applications = $mime['applications'];
	uasort($applications, "application_cmp");
	
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
		foreach($applications as $application_id => $application) {
			$content = 'content'.(($count++%2==0)?1:2);
			echo '<tr class="'.$content.'">';
			echo '<td><a href="applications.php?action=manage&id='.$application_id.'">';
			echo '<img class="icon32" src="media/image/cache.php?id='.$application_id.'" alt="" title="" />&nbsp;';
			echo $application['name'];
			echo '</a></td>';
			echo '<td><img src="media/image/server-'.$application['type'].'.png" alt="'.$application['type'].'" title="'.$application['type'].'" /></td>';
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
