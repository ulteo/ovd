<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
require_once(dirname(__FILE__).'/includes/core-minimal.inc.php');

// core of the page
$sep = '___';

if (isset($_POST['submit'])) {
	// saving preferences
	unset($_POST['submit']);

	$elements_form = formToArray($_POST);
	$prefs = new Preferences_admin($elements_form);
	$ret = $prefs->isValid();
	if ( $ret === true) {
		$ret = $prefs->backup();
		if ($ret > 0){
			// configuration saved
			redirect('index.php');
		}
		else {
			header_static(_('Configuration'));
			echo 'problem : configuration not saved<br>';  // TODO (class msg...) + gettext
			footer_static();
		}
	}
	else {
		// conf not valid
		header_static(_('Configuration'));
		echo '<p class="msg_error centered">'.$ret.'</p>';
		print_prefs($prefs);
		footer_static();
	}
}
else {
	if (isset($_GET['action']) && $_GET['action'] == 'init') {
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
		}
		$prefs->initialize();
		header_static(_('Configuration'));
		print_prefs($prefs);
		include_once('footer.php');
	}
	else {
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
		}
		if (is_object($prefs)) {
			require_once(dirname(__FILE__).'/header.php');
			echo '<table style="width: 98.5%; margin-left: 10px; margin-right: 10px;" border="0" cellspacing="0" cellpadding="0">';
			echo '<tr>';
			echo '<td style="width: 150px; text-align: center; vertical-align: top; background: url(\'media/image/submenu_bg.png\') repeat-y right;">';
			include_once(dirname(__FILE__).'/submenu/configuration.php');
			echo '</td>';
			echo '<td style="text-align: left; vertical-align: top;">';
			echo '<div class="container" style="background: #fff; border-top: 1px solid  #ccc; border-right: 1px solid  #ccc; border-bottom: 1px solid  #ccc;">';

			print_prefs($prefs);

			echo '</div>';
			echo '</td>';
			echo '</tr>';
			echo '</table>';
			include_once('footer.php');
		}
		else {
			die_error(_('Preferences not loaded'));
		}
	}
}

