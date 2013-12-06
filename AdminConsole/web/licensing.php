<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
 * Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2013
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


if (! checkAuthorization('viewConfiguration'))
	redirect();

show_default();

function get_expiry_days($date) {
		$delta = $date - gmmktime();
		return sprintf('%d', $delta/(60 * 60 * 24));
	}


function show_default() {
	$can_manage_configuration = isAuthorized('manageConfiguration');
	
	$licenses = $_SESSION['service']->licenses_list();
	$licenses_limits = $_SESSION['service']->licenses_limits();
	
	page_header();
	echo '<div>';
	echo '<h1>'._('Licensing').'</h1>';

	print_summary();

	echo '<div>';
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	echo '<tr><th>'._('Organization').'</th><th>'._('License owner').'</th><th>'._('email').'</th><th>'._('Start date (UTC)').'</th><th>'._('End date  (UTC)').'</th><th>'._('Type').'</th><th>'._('Status').'</th></tr>';

	foreach ($licenses as $license) {
		echo '<tr>';
		echo '<td>'.$license['organization'].'</td>';
		echo '<td>'.$license['owner'].'</td>';
		echo '<td>'.$license['email'].'</td>';
		echo '<td>'.date('m/d/Y H:i:s', $license['start']).'</td>';
		echo '<td>'.date('m/d/Y H:i:s', $license['expiry']).'</td>';

		echo '<td>';
		if($license['concurrent_users'] !== null) {
			echo _('Concurrent users').' : '.$license['concurrent_users'].'<br/>';
		}
		if($license['named_users'] !== null) {
			echo _('Named users').' : '.$license['named_users'].'<br/>';
		}
		echo '</td>';

		if (! $license['valid']) {
			echo '<td class="msg_error">'._('Invalid Ulteo license').'</td>';
		}
		else if ($license['expired']) {
			echo '<td class="msg_error">'._('License expired').'</td>';
		}
		else {
			$delta = get_expiry_days($license['expiry']);
			if ($delta < 20) {
				echo '<td class="msg_warn">'.sprintf(_('Only %d days remaining'), $delta).'</td>';
			}
			else {
				echo '<td class="msg_ok">'.sprintf(_('OK (%d days remaining)'), $delta).'</td>';
			}
		}
		
		if ($can_manage_configuration) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this license?').'\');">';
			echo '<input type="hidden" name="name" value="License" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="id" value="'.$license['id'].'" />';
			echo '<input type="submit" value="'._('Delete this license').'" />';
			echo '</form></td>';
		}
		echo '</tr>';
	}
	
	echo '</table>';
	echo '</div>';
	
	if ($can_manage_configuration) {
		echo '<div>';
		echo '<h2>'._('Upload an license').'</h2>';
		echo '<table border="0" cellspacing="1" cellpadding="5">';
		echo '<tr>';
		echo '<td>';
		echo '<form action="actions.php" method="post" enctype="multipart/form-data" >';
		echo '<input type="hidden" name="name" value="License" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="file" name="license" /> ';
		echo '<input type="submit" value="'._('Upload this license').'" />';
		echo '</form>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';
	}

	if ($can_manage_configuration && ! in_array($licenses_limits["named_users_max"], array(0, null))) {
		echo '<div>';
		echo '<h2>'._('Reset named users list').'</h2>';
		echo '<table border="0" cellspacing="1" cellpadding="5">';
		echo '<tr>';
		echo '<td>';
		echo '<form action="actions.php" method="post" enctype="multipart/form-data" >';
		echo '<input type="hidden" name="name" value="License" />';
		echo '<input type="hidden" name="action" value="reset_named_users" />';
		echo '<input type="submit" value="'._('Reset named users').'" />';
		echo '</form>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';
	}
	
	echo '</div>';
	page_footer();
	die();
}

function print_summary() {
	$limits = $_SESSION['service']->licenses_limits();
	$delta = get_expiry_days($limits['global_to']);
	$color = 0;

	echo '<table class="main_sub2" border="0" cellspacing="1" cellpadding="3">';
	echo '<tr class="title"><th colspan="2">'._('Licensing summary').'</th></tr>';

	echo '<tr class="content'.($color++ % 2 +1).'">';
	echo '<td style="width: 200px;">';
	echo '<span>'._('State').'</span>';
	echo '</td>';
	if (! $limits['global_validity']) {
		echo '<td class="msg_error">'._('No valid Ulteo license').'</td>';
	} else {
		if ($delta < 20) {
			echo '<td class="msg_warn">'.sprintf(_('Only %d days remaining'), $delta).'</td>';
		}
		else {
			echo '<td class="msg_ok">'.sprintf(_('OK (%d days remaining)'), $delta).'</td>';
		}
	}
	echo '</td>';
	echo '</tr>';

	echo '<tr class="content'.($color++ % 2 +1).'">';
	echo '<td style="width: 200px;">';
	echo '<span>'._('License valid from').'</span>';
	echo '</td>';
	echo '<td>'.date('m/d/Y H:i:s', $limits['global_from']).'</td>';
	echo '</tr>';

	echo '<tr class="content'.($color++ % 2 +1).'">';
	echo '<td style="width: 200px;">';
	echo '<span>'._('License valid until').'</span>';
	echo '</td>';
	echo '<td>'.date('m/d/Y H:i:s', $limits['global_to']).'</td>';
	echo '</tr>';

	if($limits['concurrent_users_max']) {
		echo '<tr class="content'.($color++ % 2 +1).'">';
		echo '<td style="width: 200px;">';
		echo '<span>'._('Concurrent users limit').'</span>';
		echo '</td>';
		if($limits['concurrent_users_current'] > $limits['concurrent_users_max']) {
			echo '<td class="msg_error">';
		} elseif($limits['concurrent_users_current'] == $limits['concurrent_users_max']) {
			echo '<td class="msg_warn">';
		} else {
			echo '<td class="msg_ok">';
		}
		echo $limits['concurrent_users_current'].' / '.$limits['concurrent_users_max'].'</td>';
		echo '</tr>';
	}

	if($limits['named_users_max']) {
		echo '<tr class="content'.($color++ % 2 +1).'">';
		echo '<td style="width: 200px;">';
		echo '<span>'._('Named users limit').'</span>';
		echo '</td>';
		if($limits['named_users_current'] > $limits['named_users_max']) {
			echo '<td class="msg_error">';
		} elseif($limits['named_users_current'] == $limits['named_users_max']) {
			echo '<td class="msg_warn">';
		} else {
			echo '<td class="msg_ok">';
		}
		echo $limits['named_users_current'].' / '.$limits['named_users_max'].'</td>';
		echo '</tr>';
	}

  echo '</table>';
}
