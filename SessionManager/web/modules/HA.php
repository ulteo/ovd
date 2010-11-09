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
 
abstract class HA extends Module  {
	protected static $instance=NULL;
	public static function getInstance() {
		if (is_null(self::$instance)) {
			$prefs = Preferences::getInstance();
			if (! $prefs)
				die_error('get Preferences failed',__FILE__,__LINE__);
			$mods_enable = $prefs->get('general','module_enable');
			if (!in_array('UserDB',$mods_enable)){
				die_error(_('UserDB module must be enabled'),__FILE__,__LINE__);
			}
			$mod_app_name = 'UserDB_'.$prefs->get('UserDB','enable');
			self::$instance = new $mod_app_name();
		}
		return self::$instance;
	}
}
