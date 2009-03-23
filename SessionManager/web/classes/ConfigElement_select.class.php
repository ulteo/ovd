<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com>
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

class ConfigElement_select extends ConfigElement { // list of text (r) (fixed length) (only one can be selected)
	public function toHTML() {
		$html_id = $this->htmlID();
		$html = '';
		if (is_array($this->content_available)) {
			$html .= '<select id="'.$html_id.'"  name="'.$html_id.'"  onchange="configuration_switch(this,\''.$this->path['key_name'].'\',\''.$this->path['container'].'\',\''.$this->id.'\');">';
			foreach ($this->content_available as $mykey => $myval){
				if ( $mykey == $this->content)
					$html .= '<option value="'.$mykey.'" selected="selected" >'.$myval.'</option>';
				else
					$html .= '<option value="'.$mykey.'" >'.$myval.'</option>';
			}
			$html .= '</select>';
		}
		return $html;
	}
}
