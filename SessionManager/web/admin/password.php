<?php
/**
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

if (! checkAuthorization('manageConfiguration')) {
	pop_error(_('User not authorized to manage configuration'));
	redirect('index.php');
}

if (!isset($_REQUEST['action'])) {
	pop_error(_('No action found'));
	redirect('index.php');
}

if ($_REQUEST['action'] == 'change') {
	page_header();
	echo '<h1>'._('Change Administrator password').'</h1>';
	echo '<form action="actions.php" method="post">';
	echo '<input type="hidden" name="name" value="password" />';
	echo '<input type="hidden" name="action" value="change" />';
	
	echo '<table class="main_sub" border="0" cellspacing="1" cellpadding="3">';
	echo '<tr class="content1">';
	echo '<th>'._('Current password').'</th>';
	echo '<td><input type="password" name="password_current" value="" /></td>';
	echo '</tr>';
	
	echo '<tr class="content2">';
	echo '<th>'._('New password').'</th>';
	echo '<td><input type="password" name="password" value="" /></td>';
	echo '</tr>';
	
	echo '<tr class="content2">';
	echo '<th>'._('Retype password').'</th>';
	echo '<td><input type="password" name="password_confirm" value="" /></td>';
	echo '</tr>';
	
	echo '<tr class="content1">';
	echo '<td style="text-align: right;" colspan="2"><input type="submit" value="'._('Change').'"/></td>';
	echo '</tr>';
	
	echo '</table>';
	echo '</form>';
	page_footer();
}
