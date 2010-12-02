<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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
require_once(dirname(__FILE__).'/includes/core-minimal.inc.php');

if (! checkAuthorization('viewConfiguration'))
	redirect('index.php');

// core of the page
$sep = '___';

if (isset($_POST['submit'])) {
	if (! checkAuthorization('manageConfiguration'))
		redirect();

	// saving preferences
	unset($_POST['submit']);
	unset($_POST['mode']);

	$elements_form = formToArray($_POST);
	$prefs = new Preferences_admin($elements_form, true);
	$ret = $prefs->isValid();
	if ( $ret === true) {
		$ret = $prefs->backup();
		if ($ret > 0){
			$buf = $prefs->get('general', 'admin_language');
			$language = locale2unix($buf);
			setlocale(LC_ALL, $language.'.UTF-8');

			// configuration saved
			popup_info(_('Configuration successfully saved'));
			redirect();
		}
		else {
			header_static(_('Configuration'));
			echo '<p class="msg_error centered">problem : configuration not saved</p>';  // TODO (class msg...) + gettext
			footer_static();
		}
	}
	else {
		// conf not valid
		header_static(_('Configuration'));
		echo '<p class="msg_error centered">'.$ret.'</p>';
		footer_static();
	}
}
else {
	$can_manage_configuration = isAuthorized('manageConfiguration');

	try {
		$prefs = new Preferences_admin();
	}
	catch (Exception $e) {
	}
	if (is_object($prefs)) {
		if (!isset($_GET['mode']))
			redirect('configuration-sumup.php');

		require_once(dirname(__FILE__).'/includes/page_template.php');
		page_header();

		echo '<script type="text/javascript"> configuration_switch_init();</script>';
		// printing of preferences
		if ($can_manage_configuration) {
			echo '<form method="post" action="configuration-partial.php">';
			echo '<input type="hidden" name="mode" value="'.$_GET['mode'].'" />';
		}
		switch ($_GET['mode']) {
			case 'general':
				print_prefs4($prefs, 'general', false);
				print_prefs5($prefs, 'general', 'mails_settings');
				echo '<br />';
				print_prefs5($prefs, 'general', 'policy');
				break;
			case 'auth':
				if (array_key_exists('AuthMethod',$prefs->elements))
					print_prefs4($prefs, 'AuthMethod');
				break;
			case 'events':
				if (array_key_exists('events',$prefs->elements))
					print_prefs4($prefs, 'events');
				break;
			case 'session_settings':
				print_prefs5($prefs, 'general', 'session_settings_defaults');
				print_prefs5($prefs, 'general', 'remote_desktop_settings');
				print_prefs5($prefs, 'general', 'remote_applications_settings');
				break;
			default:
				print_prefs5($prefs, 'general', $_GET['mode']);
				break;
		}
		if ($can_manage_configuration) {
			echo '<input type="submit" id="submit" name="submit"  value="'._('Save').'" />';
			echo '</form>';
		}

		page_footer();
	}
	else {
		die_error(_('Preferences not loaded'),__FILE__,__LINE__);
	}
}
