<?php
/**
 * Copyright (C) 2013-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2013, 2014
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
	
	$certificates = $_SESSION['service']->certificates_list();
	$certificates_limits = $_SESSION['service']->certificates_limits();
	
	page_header();
	echo '<div>';
	echo '<h1>'._('Activation certificates').'</h1>';

	print_summary();

	echo '<div>';
	echo '<table border="0" cellspacing="1" cellpadding="3">';
	echo '<tr><th>'._('Organization').'</th><th>'._('Certificate owner').'</th><th>'._('Email').'</th><th>'._('Start Date (UTC)').'</th><th>'._('End Date  (UTC)').'</th><th>'._('Type').'</th><th>'._('Status').'</th></tr>';

	foreach ($certificates as $certificate) {
		echo '<tr>';
		echo '<td>'.$certificate['organization'].'</td>';
		echo '<td>'.$certificate['owner'].'</td>';
		echo '<td>'.$certificate['Email'].'</td>';
		echo '<td>'.date('m/d/Y H:i:s', $certificate['start']).'</td>';
		echo '<td>'.date('m/d/Y H:i:s', $certificate['expiry']).'</td>';

		echo '<td>';
		if($certificate['concurrent_users'] !== null) {
			echo _('Connected User Limit').' : '.$certificate['concurrent_users'].'<br/>';
		}
		if($certificate['named_users'] !== null) {
			echo _('Assigned Users').' : '.$certificate['named_users'].'<br/>';
		}
		echo '</td>';

		if (! $certificate['valid']) {
			echo '<td class="msg_error">'._('Invalid Ulteo certificate').'</td>';
		}
		else if ($certificate['expired']) {
			echo '<td class="msg_error">'._('Certificate expired').'</td>';
		}
		else {
			$delta = get_expiry_days($certificate['expiry']);
			if ($delta < 20) {
				echo '<td class="msg_warn">'.sprintf(_('Only %d days remaining'), $delta).'</td>';
			}
			else {
				echo '<td class="msg_ok">'.sprintf(_('OK (%d days remaining)'), $delta).'</td>';
			}
		}
		
		if ($can_manage_configuration) {
			echo '<td><form action="actions.php" method="post" onsubmit="return confirm(\''._('Are you sure you want to delete this certificate?').'\');">';
			echo '<input type="hidden" name="name" value="Certificate" />';
			echo '<input type="hidden" name="action" value="del" />';
			echo '<input type="hidden" name="id" value="'.$certificate['id'].'" />';
			echo '<input type="submit" value="'._('Delete this certificate').'" />';
			echo '</form></td>';
		}
		echo '</tr>';
	}
	
	echo '</table>';
	echo '</div>';
	
	if ($can_manage_configuration) {
		echo '<div>';
		echo '<h2>'._('Install a certificate').'</h2>';
		echo '<table border="0" cellspacing="1" cellpadding="5">';
		echo '<tr>';
		echo '<td>';
		echo '<form action="actions.php" method="post" enctype="multipart/form-data" >';
		echo '<input type="hidden" name="name" value="Certificate" />';
		echo '<input type="hidden" name="action" value="add" />';
		echo '<input type="file" name="certificate" /> ';
		echo '<input type="submit" value="'._('Install this certificate').'" />';
		echo '</form>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';
	}

	if ($can_manage_configuration && ! in_array($certificates_limits["named_users_max"], array(0, null))) {
		echo '<div>';
		echo '<h2>'._('Assigned Users list').'</h2>';
		echo '<table border="0" cellspacing="1" cellpadding="5">';
		echo '<tr>';
		echo '<td>';
		echo '<form action="actions.php" method="post" enctype="multipart/form-data" >';
		echo '<input type="hidden" name="name" value="Certificate" />';
		echo '<input type="hidden" name="action" value="reset_named_users" />';
		echo '<input type="submit" value="'._('Reset assigned users').'" />';
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
	$limits = $_SESSION['service']->certificates_limits();
	$delta = get_expiry_days($limits['global_to']);
	$color = 0;

	echo '<table class="main_sub2" border="0" cellspacing="1" cellpadding="3">';
	echo '<tr class="title"><th colspan="2">'._('Activation certificates summary').'</th></tr>';

	echo '<tr class="content'.($color++ % 2 +1).'">';
	echo '<td style="width: 200px;">';
	echo '<span>'._('State').'</span>';
	echo '</td>';
	if (! $limits['global_validity']) {
		echo '<td class="msg_error">'._('No valid Ulteo certificate').'</td>';
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

	if($limits['global_from']) {
		echo '<tr class="content'.($color++ % 2 +1).'">';
		echo '<td style="width: 200px;">';
		echo '<span>'._('Certificate valid from').'</span>';
		echo '</td>';
		echo '<td>'.date('m/d/Y H:i:s', $limits['global_from']).'</td>';
		echo '</tr>';
	}

	if($limits['global_to']) {
		echo '<tr class="content'.($color++ % 2 +1).'">';
		echo '<td style="width: 200px;">';
		echo '<span>'._('Certificate valid until').'</span>';
		echo '</td>';
		echo '<td>'.date('m/d/Y H:i:s', $limits['global_to']).'</td>';
		echo '</tr>';
	}

	if($limits['concurrent_users_max']) {
		echo '<tr class="content'.($color++ % 2 +1).'">';
		echo '<td style="width: 200px;">';
		echo '<span>'._('Connected User Limit').'</span>';
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
		echo '<span>'._('Assigned User Limit').'</span>';
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
