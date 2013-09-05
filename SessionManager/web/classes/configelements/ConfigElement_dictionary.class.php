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
	public function contentEqualsTo($content_) {
		if (count($this->content) != count($content_)) {
			return false;
		}
		
		foreach($this->content as $k => $v) {
			if (! array_key_exists($k, $content_)) {
				return false;
			}
			
			if ($v != $content_[$k]) {
				return false;
			}
		}
		
		foreach($content_ as $k => $v) {
			if (! array_key_exists($k, $this->content)) {
				return false;
			}
			
			if ($v != $this->content[$k]) {
				return false;
			}
		}
		
		return true;
	}
}
