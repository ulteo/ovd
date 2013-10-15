<?php
/**
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2009
 * Author Julien LANGLOIS <julien@ulteo.com> 2012
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

class ConfigElement_multiselect extends ConfigElement { // list of text (r) (fixed length) (more than one can be selected)
	public function toHTML($readonly=false) {
		$html_id = $this->htmlID();
		$html = '';
		
		foreach ($this->content_available as $mykey => $myval){
			if ( in_array($mykey,$this->content))
				$html .= '<input class="input_checkbox" type="checkbox" '.($readonly?'disabled="disabled"':'name="'.$html_id.'[]"').' checked="checked" value="'.$mykey.'" onchange="configuration_switch(this,\''.$this->path['key_name'].'\',\''.$this->path['container'].'\',\''.$this->id.'\');"/>';
			else
				$html .= '<input class="input_checkbox" type="checkbox" '.($readonly?'disabled="disabled"':'name="'.$html_id.'[]"').' value="'.$mykey.'" onchange="configuration_switch(this,\''.$this->path['key_name'].'\',\''.$this->path['container'].'\',\''.$this->id.'\');"/>';
			// TODO targetid
			$html .= $myval;
			$html .= '<br />';
		}
		$html .= '<input class="input_checkbox" type="hidden" '.($readonly?'':'name="'.$html_id.'[]"').' value="thisIsADirtyHack" />'; // dirty hack for []
		return $html;
	}
}
