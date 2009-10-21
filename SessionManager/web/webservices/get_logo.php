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
require_once(dirname(__FILE__).'/../includes/core-minimal.inc.php');

Logger::debug('main', '(webservices/get_logo) Starting webservices/get_logo.php');

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed',__FILE__,__LINE__);

$web_interface_settings = $prefs->get('general', 'web_interface_settings');
$logo_url = $web_interface_settings['logo_url'];

if (! str_startswith($logo_url, 'http'))
	$logo_url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')?'https':'http').'://'.$_SERVER['SERVER_NAME'].$logo_url;

$buf = query_url_request($logo_url, false);

if (! str_startswith($buf['content_type'], 'image/')) {
	Logger::error('main', '(webservices/get_logo) target(\''.$logo_url.'\') is not an image');
	die('(webservices/get_logo) $logo_url target is not an image');
}

header('Content-Type: '.$buf['content_type']);

echo $buf['data'];

die();
