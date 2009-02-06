<?php
/**
 * Copyright (C) 2009 Ulteo SAS
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

if (isset($_GET['view']) && $_GET['view'] == '') {
} else {
	echo '<div class="container">';
	echo '<a href="applications.php">'._('Applications').'</a>';
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="appsgroup.php">'._('Application groups').'</a>';
	echo '</div>';
	echo '<div class="container" style="background: #fff; border-top: 1px solid #ccc; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc;">';
	echo _('Publications');
	echo '</div>';
	echo '<div class="container">';
	echo '<a href="wizard.php">'._('Publication wizard').'</a>';
	echo '</div>';
}
