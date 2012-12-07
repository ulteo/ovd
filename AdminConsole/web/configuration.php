<?php
/**
 * Copyright (C) 2008-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2008
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
require_once(dirname(__FILE__).'/includes/core.inc.php');

if (! checkAuthorization('viewConfiguration'))
	redirect('index.php');


// core of the page
$sep = '___';

if (isset($_POST['submit'])) {
	if (! checkAuthorization('manageConfiguration'))
		redirect();

	// saving preferences
	unset($_POST['submit']);
	$setup = false;
	if (isset($_POST['setup'])) {
		$setup = true;
		unset($_POST['setup']);
		$elements_form = formToArray($_POST);
		try {
			$prefs = new Preferences_admin($elements_form, true);
		}
		catch (Exception $e) {
		}
	}
	else {
		$elements_form = formToArray($_POST);
		$prefs = new Preferences_admin($elements_form);
	}
	
	$ret = $prefs->backup();
	if ($ret > 0){
		// configuration saved
		popup_info(_('Configuration successfully saved'));
		redirect('index.php');
	}
	else {
		require_once(dirname(__FILE__).'/includes/page_template_static.php');
		header_static(_('Configuration'));
		echo 'problem : configuration not saved<br>';  // TODO (class msg...) + gettext
		footer_static();
	}
}
else {
	$can_manage_configuration = isAuthorized('manageConfiguration');

	if (isset($_GET['action']) && $_GET['action'] == 'init') {
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
		}
		
		require_once(dirname(__FILE__).'/includes/page_template.php');
		page_header();
		
		// printing of preferences
		if ($can_manage_configuration) {
			echo '<form method="post" action="configuration.php">';
			echo '<input type="hidden" name="setup" value="setup" />';
		}
		print_prefs5($prefs, 'general', 'sql');
		if ($can_manage_configuration) {
			echo '<input type="submit" id="submit" name="submit"  value="'._('Save').'" />';
			echo '</form>';
		}

		page_footer();
		
	}
	else {
		try {
			$prefs = new Preferences_admin();
		}
		catch (Exception $e) {
		}
		if (is_object($prefs)) {
			require_once(dirname(__FILE__).'/includes/page_template.php');
			page_header();

			print_prefs($prefs, $can_manage_configuration);

			page_footer();
		}
		else {
			die_error(_('Preferences not loaded'),__FILE__,__LINE__);
		}
	}
}
