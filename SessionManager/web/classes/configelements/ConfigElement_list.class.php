<?php
/**
 * Copyright (C) 2009-2010 Ulteo SAS
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

class ConfigElement_list extends ConfigElement {
	public function toHTML($readonly=false) {
		$html = '';
		$html_id = $this->htmlID();
		$disabled = '';
		if ($readonly) {
			$disabled = 'disabled="disabled"';
		}

		$html .= '<div id="'.$html_id.'">';
		$html .= '<table border="0" cellspacing="1" cellpadding="3">';
		$i = 0;
		foreach ($this->content as $key1 => $value1){
			$label3 = $html_id.$this->formSeparator.$i.$this->formSeparator;
			$html .= '<tr>';
				$html .= '<td>';
				$html .= '<input type="hidden" id="'.$label3.'key" '.$disabled.' name="'.$label3.'key" value="'.$i.'" size="40" />';
				$html .= '<div id="'.$html_id.$this->formSeparator.$key1.'_divb">';
					$html .= '<input type="text" id="'.$label3.'value" '.$disabled.' name="'.$label3.'value" value="'.$value1.'" size="25" />';
					if ($readonly == false) {
						$html .= '<a href="javascript:;" onclick="configuration4_mod(this); return false"><img src="media/image/less.png"/></a>';
					}
				$html .= '</div>';
				$html .= '</td>';
			$html .= '</tr>';
			$i += 1;

		}
		$label3 = $html_id.$this->formSeparator.$i.$this->formSeparator;
		$html .= '<tr>';
		$html .= '<td>';
			$html .= '<input type="hidden" id="'.$label3.'key" name="'.$label3.'key" value="'.$i.'"  />';
			$html .= '<div id="'.$html_id.$this->formSeparator.$i.'_divaddb">';
				$html .= '<input type="text" id="'.$label3.'value" '.$disabled.' name="'.$label3.'value" value="" size="25" />';
				if ($readonly == false) {
					$html .= '<a href="javascript:;" onclick="configuration4_mod(this); return false"><img src="media/image/more.png"/></a>';
				}
				$html .= '</div>';
			$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '</div>';
	return $html;
	}
}