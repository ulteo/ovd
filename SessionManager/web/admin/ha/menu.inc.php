<?php
/**
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Arnaud LEGRAND <arnaud@ulteo.com>
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

$mod_menu = array();

$prefs = Preferences::getInstance();
$mods_enable = $prefs->get('general','module_enable');

if (in_array('HA',$mods_enable)) {
	$mod_menu['main'] = array('id' => 'main','name' => _('High Availability'),'page' => 'status.php','parent' => array(),'always_display' => true);
	$mod_menu['status'] = array('id' => 'status','name' => _('Status'),'page' => 'status.php','parent' => array('main'));
	$mod_menu['logs'] = array('id' => 'logs','name' => _('Logs'),'page' => 'logs.php','parent' => array('main'));
	$mod_menu['configuration'] = array('id' => 'configuration','name' => _('Configuration'),'page' => 'configuration.php','parent' => array('main'));
}
