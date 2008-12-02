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
$items = array(
	'servers.php'		=>	_('Servers'),
	'sessions.php'		=>	_('Sessions'),
	'users.php'		=>	_('Users'),
	'applications.php'	=>	_('Applications'),
	'publications.php'	=>	_('Publications'),
	//'wizard.php'		=>	_('Publication Wizard'),
	'configuration.php'	=>	_('Configuration'),
	'logout.php'		=>	_('Logout')
);

$in_menu = basename('http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']);

$i = 0;
foreach($items as $k => $v) {
	$css_classes = 'menu';
	$css_classes .= ' content'.(($i++%2==0)?1:2);
	$css_classes .= (($in_menu == $k)?' in':'');

	echo '<a href="'.$k.'"><div class="'.$css_classes.'">'.$v.'</div></a>'."\n";
}
