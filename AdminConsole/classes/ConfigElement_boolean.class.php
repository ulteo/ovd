<?php
/**
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
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

class ConfigElement_boolean extends ConfigElement {
	public function toHTML($readonly=false) {
		$html_id = $this->htmlID();
		$input_name = $this->get_input_name();
		
		$html = '<input type="checkbox" id="'.$html_id.'_checkbox" '.($readonly?'disabled="disabled"':'').' '.(($this->content==true)?'checked="checked"':'').' value="true" />';
		if (! $readonly) {
			$html .= '
				<input type="hidden" id="'.$html_id.'" name="'.$input_name.'" value="'.$this->value2html($this->content).'" />
				<script type="text/javascript">
					Event.observe(window, \'load\', function() {
						$(\''.$html_id.'\').observe(\'change\', function(e) {
							if ($(\''.$html_id.'\').value == "true" && $(\''.$html_id.'_checkbox\').checked != true) {
								$(\''.$html_id.'_checkbox\').checked = true;
							}
							else if ($(\''.$html_id.'\').value == "false" && $(\''.$html_id.'_checkbox\').checked != false) {
								$(\''.$html_id.'_checkbox\').checked = false;
							}
						});
						
						$(\''.$html_id.'_checkbox\').observe(\'change\', function(e) {
							if ($(\''.$html_id.'_checkbox\').checked == true && $(\''.$html_id.'\').value != "true") {
								$(\''.$html_id.'\').value = "true";
								fire_event_change($(\''.$html_id.'\'));
							}
							if ($(\''.$html_id.'_checkbox\').checked == false && $(\''.$html_id.'\').value != "false") {
								$(\''.$html_id.'\').value = "false";
								fire_event_change($(\''.$html_id.'\'));
							}
						});
					});
				</script>
			';
		}
		
		return $html;
	}
	
	public function value2html($value_) {
		return (($value_==true)?'true':'false');
	}

	public function html2value($value_) {
		if (! in_array($value_, array('true', 'false'))) {
			return null;
		}
		
		return ($value_ == 'true');
	}
}
