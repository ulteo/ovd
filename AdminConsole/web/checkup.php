<?php
/**
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
 * Author Jeremy DESVAGES <jeremy@ulteo.com> 2010-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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

if (isset($_POST['cleanup']) && $_POST['cleanup'] == 1 && array_key_exists('type', $_POST)) {
	switch ($_POST['type']) {
		case 'liaison':
			$_SESSION['service']->cleanup_liaisons();
			break;
		
		case 'preferences':
			$_SESSION['service']->cleanup_preferences();
			break;
	}

	redirect('checkup.php');
}

$checkup = $_SESSION['service']->checkup();
if (is_null($checkup)) {
	popup_error(_('Unable to load checkup'));
	redirect();
}

$everything_ok = true;

page_header();

echo '<h1>'._('Checkup').'</h1>';

echo '<h2>'._('PHP Modules').'</h2>';

$PHP_modules = $checkup['php'];
if (in_array(false, $PHP_modules)) {
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	foreach ($PHP_modules as $name => $available) {
		echo '<tr>';
		echo '<td><span style="color: #666; font-weight: bold;">'.$name.'</span></td>';
		echo '<td>&nbsp;</td>';
		echo '<td>';
		if ($available === true)
			echo '<span class="msg_ok">OK</span>';
		else {
			$module_failed = true;
			echo '<span class="msg_error">ERROR</span>';
		}
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
	page_footer();
	die();
} else {
	echo '<span class="msg_ok">OK</span><br />';
	echo '<br />';
}

echo '<h2>'._('Liaisons').'</h2>';

foreach ($checkup['liaisons'] as $liaisons_type => $result) {
	$all_ok = $result['status'];
	if (! $all_ok) {
		$everything_ok = false;
	}
	
	echo '<br /><h3>'.$liaisons_type.'</h3>';

	echo '<table border="0" cellspacing="1" cellpadding="3">';

	if (array_key_exists('errors', $result)) {
		echo '<tr><td colspan="5"><span class="msg_error">ERROR</span></td></tr>';
		echo '<tr><td colspan="5"></td></tr>';
		
		foreach ($result['errors'] as $error) {
			echo '<tr>';
			echo '<td>'.$error['element'].'</td>';
			echo '<td>=&gt;</td>';
			echo '<td>'.$error['group'].'</td>';
			echo '<td>&nbsp;-&nbsp;</td>';
			echo '<td>';
			echo '<span class="msg_error">'.$error['text'].'</span>';
			echo '</td>';
			echo '</tr>';
		}
	}

	if ($all_ok === true)
		echo '<tr><td><span class="msg_ok">OK</span></td></tr>';

	echo '</table>';
}

if ($everything_ok === false)
	echo '<br /><form action="" method="post"><input type="hidden" name="cleanup" value="1" /><input type="hidden" name="type" value="liaison" /><input type="submit" value="'._('Cleanup liaisons').'" /></form>';


$everything_ok = $checkup['conf']['default_users_group']['status']; // reset

echo '<br /><h2>'._('Configuration').'</h2>';

echo '<h3>'._('Default usergroup').'</h3>';

if (! $everything_ok) {
	echo '<span class="msg_error">'.$checkup['conf']['default_users_group']['text'].'</span>';
}
if ($everything_ok) {
	echo '<span class="msg_ok">OK</span>';
}
echo '<br />';

if ($everything_ok === false) {
	echo '<br />';
	echo '<form action="" method="post">';
	echo '<input type="hidden" name="cleanup" value="1" />';
	echo '<input type="hidden" name="type" value="preferences" />';
	echo '<input type="submit" value="'._('Cleanup configuration').'" />';
	echo '</form>';
}

page_footer();
