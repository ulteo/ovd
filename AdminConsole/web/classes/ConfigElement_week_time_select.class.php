<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com>
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

class ConfigElement_week_time_select extends ConfigElement {
	public function toHTML($readonly=false) {
		$html_id = $this->htmlID();
		$disabled = '';
		if ($readonly) {
			$disabled = 'disabled="disabled"';
		}

		$str = '<table class="time_grid'.($readonly?' disabled':'').'" id="table_'.$html_id.'"></table>';
		$str.= '<input type="hidden" '.$disabled.' id="'.$html_id.'" name="'.$html_id.'" value="'.$this->content.'" />'."\n";
		$str.= '<script type="text/javascript" charset="utf-8">'."\n";
		$str.= 'Event.observe(window, "load", function() {'."\n";
		$str.= '	setTimeout(function() {';
		$str.= '		var grid = TimeGrid.getInstance("table_'.$html_id.'");';
		$str.= '		grid.set_days_name(["'._('Sunday').'", "'._('Monday').'", "'._('Tuesday').'", "'._('Wednesday').'", "'._('Thursday').'", "'._('Friday').'", "'._('Saturday').'"]);';
		$str.= '		grid.set_value("'.$this->content.'");';
		$str.= '		grid.add_change_callback(function(value_) {'."\n";
		$str.= '			$("'.$html_id.'").value = value_;'."\n";
		$str.= '		});'."\n";
		$str.= '	}, 100);'."\n";
		$str.= '});'."\n";
		$str.= '</script>'."\n";
		
		return $str;
	}
}
