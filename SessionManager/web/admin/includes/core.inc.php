<?php
/**
 * Copyright (C) 2008-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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
require_once(dirname(__FILE__).'/core-minimal.inc.php');
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

require_once(dirname(__FILE__).'/functions.inc.php');

function get_root_admin_url() {
	// Retrieve the admin root URL
	$root_admin_dir = dirname(dirname(__FILE__));
	$root_admin_url = @$_SERVER['REQUEST_URI'];
	
	$len1 = count(explode(DIRECTORY_SEPARATOR, $root_admin_dir));
	$len2 = count(explode(DIRECTORY_SEPARATOR, realpath(@$_SERVER['SCRIPT_FILENAME'])));
	if ($len1 > $len2) {
		// Error: not possible !
		return $root_admin_url;
	}
	
	for ($i=$len2 - $len1; $i>0; $i--) {
		$pos = strrpos ($root_admin_url, '/');
		if ($pos === False)
			// Error: not possible !
			return $root_admin_url;
		
		$root_admin_url = substr($root_admin_url, 0, $pos);
	}
	
	return $root_admin_url;
}
define('ROOT_ADMIN_URL', get_root_admin_url());

$prefs = Preferences::getInstance();
if (! $prefs)
	die_error('get Preferences failed', __FILE__, __LINE__);

$system_in_maintenance = $prefs->get('general', 'system_in_maintenance');
if ($system_in_maintenance == '1')
	popup_error(_('The system is on maintenance mode'));
