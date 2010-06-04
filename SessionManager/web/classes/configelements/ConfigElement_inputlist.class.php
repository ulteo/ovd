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
require_once(dirname(__FILE__).'/../../includes/core.inc.php');

class ConfigElement_inputlist extends ConfigElement { // list of input text (fixed length)
	public function toHTML($readonly=false) {
		$html_id = $this->htmlID();
		$disabled = '';
		if ($readonly) {
			$disabled = 'disabled="disabled"';
		}
		$html = '';

		$html .= '<table border="0" cellspacing="1" cellpadding="3">';
		$html .= "<!-- debut input list -->\n";
		$i = 0;
		foreach ($this->content as $key1 => $value1){
			$label3 = $html_id.$this->formSeparator.$i.$this->formSeparator;
			$html .= '<tr>';
				$html .= '<td>';
					$html .=  $key1;
					$html .=  '<input type="hidden" id="'.$label3.'key" name="'.$label3.'key" value="'.$key1.'" size="25" />';
				$html .= '</td>';
				$html .= '<td>';
				$html .= '<div id="'.$html_id.$this->formSeparator.$key1.'_divb">';
					$html .=  '<input type="text" '.$disabled.' id="'.$label3.'value" name="'.$label3.'value" value="'.$value1.'" size="25" />';
				$html .= '</div>';
				$html .= '</td>';
			$html .=  '</tr>';
			$i += 1;
		}
		$label3 = $html_id.$this->formSeparator.$i.$this->formSeparator;
		$html .= '<input type="hidden" name="'.$label3.'key" value="" />'; // dirty hack 
		$html .= '<input type="hidden" name="'.$label3.'value" value="" />'; // dirty hack 
		$html .= '</table>';
		return $html;
	}
}
