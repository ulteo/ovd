<?php
/**
 * Copyright (C) 2008 Ulteo SAS
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
define('SESSIONMANAGER_CONF_FILE', '/etc/ulteo/sessionmanager/config.inc.php');

define('DEFAULT_PAGE_TITLE', _('Open Virtual Desktop'));

$base_url = str_replace('/admin', '', dirname('http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'])).'/';
define('DEFAULT_LOGO_URL', $base_url.'media/image/header.png');

define('DEFAULT_TIMEOUT', 10);

$list_languages = array(
	'en_GB.UTF-8'	=>	'English',
	'fr_FR.UTF-8'	=>	'Français'
);
$desktop_locale = 'en_GB.UTF-8';

$list_desktop_sizes = array(
	'auto'	=>	_('Maximum'),
);
$desktop_size = 'auto';

$list_desktop_qualitys = array(
	//0	=>	'Auto',
	2	=>	_('Lowest'),
	5	=>	_('Medium'),
	8	=>	_('High'),
	9	=>	_('Highest'),
);
$desktop_quality = 9;

$list_desktop_timeouts = array(
	60		=>	_('1 minute'),
	120		=>	_('2 minutes'),
	300		=>	_('5 minutes'),
	600		=>	_('10 minutes'),
	900		=>	_('15 minutes'),
	1800	=>	_('30 minutes'),
	3600	=>	_('1 hour'),
	7200	=>	_('2 hours'),
	18000	=>	_('5 hours'),
	36000	=>	_('10 hours'),
	86400	=>	_('1 day'),
);
$desktop_timeout = 60*60*24;
$timeout = 60*60*24;

$start_app = '';

$debug = 0;

$proxy_type = '';
$proxy_host = '';
$proxy_port = '';
$proxy_username = '';
$proxy_password = '';
