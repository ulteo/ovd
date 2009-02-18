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

// core of the page
$sep = '___';

if (isset($_POST['submit'])) {
	// saving preferences
	unset($_POST['submit']);
	unset($_POST['mode']);

	$elements_form = formToArray($_POST);
	$prefs = new Preferences_admin($elements_form, true);
	$ret = $prefs->isValid();
	if ( $ret === true) {
		$ret = $prefs->backup();
		if ($ret > 0){
			// configuration saved
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
	try {
		$prefs = new Preferences_admin();
	}
	catch (Exception $e) {
	}
	if (is_object($prefs)) {
		if (!isset($_GET['mode']))
			redirect('configuration.php');

		require_once(dirname(__FILE__).'/header.php');
		echo '<table style="width: 98.5%; margin-left: 10px; margin-right: 10px;" border="0" cellspacing="0" cellpadding="0">';
		echo '<tr>';
		echo '<td style="width: 150px; text-align: center; vertical-align: top; background: url(\'media/image/submenu_bg.png\') repeat-y right;">';
		include_once(dirname(__FILE__).'/submenu/configuration-partial.php');
		echo '</td>';
		echo '<td style="text-align: left; vertical-align: top;">';
		echo '<div class="container" style="background: #fff; border-top: 1px solid  #ccc; border-right: 1px solid  #ccc; border-bottom: 1px solid  #ccc;">';

		echo '<script type="text/javascript"> configuration_switch_init();</script>';
		// printing of preferences
		echo '<form method="post" action="configuration-partial.php">';
		echo '<input type="hidden" name="mode" value="'.$_GET['mode'].'" />';
		switch ($_GET['mode']) {
			case 'general':
				print_prefs4($prefs, 'general', false);
				break;
			case 'events':
				if (array_key_exists('events',$prefs->elements))
					print_prefs4($prefs, 'events');
				break;
			default:
				print_prefs5($prefs, 'general', $_GET['mode']);
				break;
		}
		echo '<input type="submit" id="submit" name="submit"  value="'._('Save').'" />';
		echo '</form>';

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
