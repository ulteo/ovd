<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com>
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

/**
  * Static class holding references to existing events
  * Events shouldn't be created directly but use the getEvent method
  */
class Events {
	private static $list = array ();

	public static function getEvent ($type_) {
		if (isset (self::$list[$type_]))
		{
			print "Event exists<br />";
			return self::$list[$type_];
		} else {
			require_once (EVENTS_DIR.'/'.$type_.'.class.php');
			$ev = new $type_ ();
			self::$list[$type_] = $ev;
			return $ev;
		}
	}
}

