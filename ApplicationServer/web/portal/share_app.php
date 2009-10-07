<?php
/**
 * Copyright (C) 2008 Ulteo SAS
 * http://www.ulteo.com
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
require_once(dirname(__FILE__).'/../includes/core.inc.php');

echo '<table border="0" cellspacing="1" cellpadding="3">';
echo '<tr>';
echo '<td colspan="2">';
echo '	<table style="width: 100%;" border="0" cellspacing="0" cellpadding="10"><tr><td>';
echo '		<h2 style="text-align: center;">'._('Application sharing').'</h2>';
echo '	<fieldset style="border: 0;">';
echo '		<form action="javascript:;" method="post" onsubmit="doInvite(\'portal\'); return false;">';
echo '			<input type="hidden" id="invite_access_id" name="access_id" value="'.$_GET['access_id'].'" />';
echo '			<p>'._('Email address').': <input type="text" id="invite_email" name="email" value="" /> <input class="input_checkbox" type="checkbox" id="invite_mode" name="mode" /> '._('active mode').'</p>';
echo '			<p><input type="submit" id="invite_submit" value="'._('Invite').'" /></p>';
echo '		</form>';
echo '	</fieldset>';
echo '	</td></tr></table>';
echo '</td>';
echo '</tr>';
echo '</table>';
