<?php
/**
 * Copyright (C) 2012 Ulteo SAS
 * http://www.ulteo.com
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

require_once(dirname(dirname(__FILE__)).'/includes/core-minimal.inc.php');
require_once(dirname(dirname(__FILE__)).'/includes/page_template_static.php');


if (! array_key_exists('errormsg', $_SESSION)) {
	// This page is only used to display error.
	// If there is no error, we redirect to index.php
	redirect('index.php');
}

$errors = array_unique($_SESSION['errormsg']);
unset($_SESSION['errormsg']);

header_static(DEFAULT_PAGE_TITLE.' - '._('Error'));

echo '<h2 class="centered">'._('Error').'</h2>';
echo '<div class="centered">';
if (count($errors) > 0) {
	echo '<div id="adminError">';
	echo '<span class="msg_error">';
	if (count($errors) > 1) {
		echo '<ul>';
		foreach ($errors as $error_msg)
			echo '<li>'.$error_msg.'</li>';
		echo '</ul>';
	} 
	else {
		echo $errors[0];
	}
	echo '</span>';
	echo '</div>';
}

echo '</div>';
footer_static();
