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

require_once(dirname(__FILE__).'/../includes/core.inc.php');

class Htaccess {
	public static function set_php_value($key_, $value_) {
		$filename = APS_ROOT.'/.htaccess';
		if (!is_writable($filename) || !is_readable($filename))
			return false;

		if (($contents = file_get_contents($filename)) === false)
			return false;

		$new_contents = '';
		$lines = split("\n",$contents);
		$found = false;
		foreach ($lines as $line) {
			$line = preg_replace('/ +/', ' ', trim($line));
			if (!$line)
				continue;

			$words = split(' ', $line);
			if ((count($words) != 3) ||($words[0] != 'php_value') || ($words[1] != $key_)) {
				$new_contents .= "$line\n";
				continue;
			}
			$found = true;
			/* if $value_ is empty we just forget this config */
			if ($value_)
				$new_contents .= "php_value $key_ $value_\n";
		}

		if (!$found && $value_)
			$new_contents .= "php_value $key_ $value_";

		return @file_put_contents($filename, $new_contents);
	}
}
