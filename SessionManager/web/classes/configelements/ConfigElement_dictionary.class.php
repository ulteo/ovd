<?php
/**
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009-2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class ConfigElement_dictionary extends ConfigElement {
	public static function values_equals($value1_, $value2_) {
		if (count($value1_) != count($value2_)) {
			return false;
		}
		
		foreach($value1_ as $k => $v) {
			if (! array_key_exists($k, $value2_)) {
				return false;
			}
			
			if ($v != $value2_[$k]) {
				return false;
			}
		}
		
		foreach($value2_ as $k => $v) {
			if (! array_key_exists($k, $value1_)) {
				return false;
			}
			
			if ($v != $value1_[$k]) {
				return false;
			}
		}
		
		return true;
	}
}
